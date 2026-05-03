<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Models\Pago;
use App\Models\Cuota;
use PDO;

class PagoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ── Pagos ─────────────────────────────────────────────────────────────────

    public function insert(Pago $p): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO pagos (
                id_credito, id_cobrador, monto_pagado, forma_pago,
                referencia_externa, fecha_pago_real, id_rendicion,
                observaciones, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $p->id_credito, $p->id_cobrador, $p->monto_pagado, $p->forma_pago,
            $p->referencia_externa, $p->fecha_pago_real, $p->id_rendicion,
            $p->observaciones, $p->created_by,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?Pago
    {
        $stmt = $this->db->prepare("
            SELECT p.*,
                pc_pers.nombre AS cobrador_nombre,
                cl.nombre AS cliente_nombre, cl.dni AS cliente_dni,
                cr.codigo AS credito_codigo
            FROM pagos p
            JOIN creditos cr ON p.id_credito = cr.id_credito
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            LEFT JOIN personal pc_pers ON p.id_cobrador = pc_pers.id_personal
            WHERE p.id_pago = ? AND p.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $pago = $this->hydrate($row);
        $pago->cuotasAplicadas = $this->getCuotasAplicadas($id);
        return $pago;
    }

    /** @return Pago[] */
    public function findByCredito(int $idCredito): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*, pc_pers.nombre AS cobrador_nombre
            FROM pagos p
            LEFT JOIN personal pc_pers ON p.id_cobrador = pc_pers.id_personal
            WHERE p.id_credito = ? AND p.deleted_at IS NULL
            ORDER BY p.fecha_pago_real DESC, p.id_pago DESC
        ");
        $stmt->execute([$idCredito]);
        $list = [];
        while ($row = $stmt->fetch()) {
            $pago = $this->hydrate($row);
            $pago->cuotasAplicadas = $this->getCuotasAplicadas($pago->id_pago);
            $list[] = $pago;
        }
        return $list;
    }

    /** Total cobrado en una fecha por un cobrador (pagos no anulados). */
    public function getTotalCobradoPorCobrador(int $idCobrador, string $fecha): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(monto_pagado), 0)
            FROM pagos
            WHERE id_cobrador = ?
              AND fecha_pago_real = ?
              AND anulado = 0
              AND deleted_at IS NULL
        ");
        $stmt->execute([$idCobrador, $fecha]);
        return (float)$stmt->fetchColumn();
    }

    /** Últimos pagos de todos los créditos de un cliente (cross-credits). */
    public function findRecentesPorCliente(int $idCliente, int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT p.*,
                cr.codigo AS credito_codigo,
                pc_pers.nombre AS cobrador_nombre
            FROM pagos p
            JOIN creditos cr ON p.id_credito = cr.id_credito
            LEFT JOIN personal pc_pers ON p.id_cobrador = pc_pers.id_personal
            WHERE cr.id_cliente = ?
              AND p.anulado = 0
              AND p.deleted_at IS NULL
            ORDER BY p.fecha_pago_real DESC, p.id_pago DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $idCliente, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Verifica si un crédito pertenece a un cobrador dado. */
    public function belongsToCobrador(int $idCredito, int $idCobrador): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM creditos
            WHERE id_credito = ? AND id_cobrador = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$idCredito, $idCobrador]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** @return Pago[] */
    public function findAll(int $limit = 30, int $offset = 0, string $search = '', string $desde = '', string $hasta = ''): array
    {
        $sql = "
            SELECT p.*,
                pc_pers.nombre AS cobrador_nombre,
                cl.nombre AS cliente_nombre, cl.dni AS cliente_dni,
                cr.codigo AS credito_codigo
            FROM pagos p
            JOIN creditos cr ON p.id_credito = cr.id_credito
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            LEFT JOIN personal pc_pers ON p.id_cobrador = pc_pers.id_personal
            WHERE p.deleted_at IS NULL
        ";
        $params = [];
        if ($search !== '') {
            $sql .= " AND (cl.nombre LIKE ? OR cl.dni LIKE ? OR cr.codigo LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($desde !== '') {
            $sql .= " AND p.fecha_pago_real >= ?";
            $params[] = $desde;
        }
        if ($hasta !== '') {
            $sql .= " AND p.fecha_pago_real <= ?";
            $params[] = $hasta;
        }
        $sql .= " ORDER BY p.fecha_pago_real DESC, p.id_pago DESC";

        $stmt = $this->db->prepare($sql . " LIMIT ? OFFSET ?");
        $i = 1;
        foreach ($params as $v) {
            $stmt->bindValue($i++, $v, PDO::PARAM_STR);
        }
        $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($i,   $offset, PDO::PARAM_INT);
        $stmt->execute();

        $list = [];
        while ($row = $stmt->fetch()) {
            $list[] = $this->hydrate($row);
        }
        return $list;
    }

    public function countAll(string $search = '', string $desde = '', string $hasta = ''): int
    {
        $sql = "
            SELECT COUNT(*) FROM pagos p
            JOIN creditos cr ON p.id_credito = cr.id_credito
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            WHERE p.deleted_at IS NULL
        ";
        $params = [];
        if ($search !== '') {
            $sql .= " AND (cl.nombre LIKE ? OR cl.dni LIKE ? OR cr.codigo LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($desde !== '') {
            $sql .= " AND p.fecha_pago_real >= ?";
            $params[] = $desde;
        }
        if ($hasta !== '') {
            $sql .= " AND p.fecha_pago_real <= ?";
            $params[] = $hasta;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function anular(int $idPago, string $motivo, int $anuladoPor): void
    {
        $stmt = $this->db->prepare("
            UPDATE pagos
            SET anulado = 1, motivo_anulacion = ?, anulado_por = ?, anulado_at = NOW()
            WHERE id_pago = ?
        ");
        $stmt->execute([$motivo, $anuladoPor, $idPago]);
    }

    // ── Pago_cuotas ───────────────────────────────────────────────────────────

    public function insertPagoCuota(int $idPago, int $idCuota, float $montoAplicado): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO pago_cuotas (id_pago, id_cuota, monto_aplicado) VALUES (?, ?, ?)
        ");
        $stmt->execute([$idPago, $idCuota, $montoAplicado]);
    }

    /** @return array<int, array{id_cuota: int, numero_cuota: int, monto_aplicado: float, fecha_vencimiento: string}> */
    public function getCuotasAplicadas(int $idPago): array
    {
        $stmt = $this->db->prepare("
            SELECT pc.id_cuota, pc.monto_aplicado,
                cu.numero_cuota, cu.fecha_vencimiento
            FROM pago_cuotas pc
            JOIN cuotas cu ON pc.id_cuota = cu.id_cuota
            WHERE pc.id_pago = ?
            ORDER BY cu.numero_cuota ASC
        ");
        $stmt->execute([$idPago]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Cuotas ────────────────────────────────────────────────────────────────

    /** @return array<int, array{id_cuota:int, numero_cuota:int, fecha_vencimiento:string, monto_esperado:float, monto_pagado:float, monto_recargo:float, estado:string}> */
    public function getCuotasPendientes(int $idCredito): array
    {
        $stmt = $this->db->prepare("
            SELECT id_cuota, numero_cuota, fecha_vencimiento,
                   monto_esperado, monto_pagado, monto_recargo, estado
            FROM cuotas
            WHERE id_credito = ? AND estado IN ('pendiente','parcial','vencida')
            ORDER BY numero_cuota ASC
        ");
        $stmt->execute([$idCredito]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateCuota(int $idCuota, float $nuevoMontoPagado, string $estado, ?string $fechaPagada): void
    {
        $stmt = $this->db->prepare("
            UPDATE cuotas
            SET monto_pagado = ?, estado = ?, fecha_pagada = ?
            WHERE id_cuota = ?
        ");
        $stmt->execute([$nuevoMontoPagado, $estado, $fechaPagada, $idCuota]);
    }

    public function recalcularSaldoCredito(int $idCredito): float
    {
        $stmt = $this->db->prepare("
            SELECT SUM(GREATEST(monto_esperado + monto_recargo - monto_pagado, 0))
            FROM cuotas
            WHERE id_credito = ? AND estado != 'condonada'
        ");
        $stmt->execute([$idCredito]);
        return (float)($stmt->fetchColumn() ?? 0);
    }

    public function updateCreditoSaldoYEstado(int $idCredito, float $saldo, string $estado): void
    {
        $stmt = $this->db->prepare("
            UPDATE creditos SET saldo_pendiente = ?, estado = ? WHERE id_credito = ?
        ");
        $stmt->execute([$saldo, $estado, $idCredito]);
    }

    // ── Recibos ───────────────────────────────────────────────────────────────

    public function generateNumeroRecibo(): string
    {
        $year   = date('Y');
        $prefix = "R-{$year}-";
        $stmt   = $this->db->prepare(
            "SELECT MAX(CAST(SUBSTRING(numero, ?) AS UNSIGNED)) FROM recibos WHERE numero LIKE ?"
        );
        $stmt->execute([strlen($prefix) + 1, "{$prefix}%"]);
        $last = (int)$stmt->fetchColumn();
        return $prefix . str_pad((string)($last + 1), 5, '0', STR_PAD_LEFT);
    }

    public function insertRecibo(int $idPago, string $numero, ?string $pdfPath): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO recibos (id_pago, numero, pdf_path) VALUES (?, ?, ?)
        ");
        $stmt->execute([$idPago, $numero, $pdfPath]);
        return (int)$this->db->lastInsertId();
    }

    public function findReciboPorPago(int $idPago): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM recibos WHERE id_pago = ?");
        $stmt->execute([$idPago]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateReciboPdfPath(int $idRecibo, string $pdfPath): void
    {
        $this->db->prepare("UPDATE recibos SET pdf_path = ? WHERE id_recibo = ?")
                 ->execute([$pdfPath, $idRecibo]);
    }

    // ── Hidratación ───────────────────────────────────────────────────────────

    private function hydrate(array $row): Pago
    {
        $p = new Pago();
        $p->id_pago            = (int)$row['id_pago'];
        $p->id_credito         = (int)$row['id_credito'];
        $p->id_cobrador        = isset($row['id_cobrador']) && $row['id_cobrador'] !== null ? (int)$row['id_cobrador'] : null;
        $p->monto_pagado       = (float)$row['monto_pagado'];
        $p->forma_pago         = $row['forma_pago'];
        $p->referencia_externa = $row['referencia_externa'] ?? null;
        $p->fecha_pago_real    = $row['fecha_pago_real'];
        $p->fecha_registro     = $row['fecha_registro'];
        $p->id_rendicion       = isset($row['id_rendicion']) && $row['id_rendicion'] !== null ? (int)$row['id_rendicion'] : null;
        $p->observaciones      = $row['observaciones'] ?? null;
        $p->anulado            = (bool)$row['anulado'];
        $p->motivo_anulacion   = $row['motivo_anulacion'] ?? null;
        $p->anulado_por        = isset($row['anulado_por']) && $row['anulado_por'] !== null ? (int)$row['anulado_por'] : null;
        $p->anulado_at         = $row['anulado_at'] ?? null;
        $p->created_by         = (int)$row['created_by'];
        $p->created_at         = $row['created_at'] ?? '';
        $p->cobrador_nombre    = $row['cobrador_nombre'] ?? null;
        $p->cliente_nombre     = $row['cliente_nombre'] ?? null;
        $p->cliente_dni        = $row['cliente_dni'] ?? null;
        $p->credito_codigo     = $row['credito_codigo'] ?? null;
        return $p;
    }
}
