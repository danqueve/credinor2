<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Models\Credito;
use App\Models\Cuota;
use PDO;

class CreditoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function generateCodigo(): string
    {
        $year = date('Y');
        $prefix = "CR-{$year}-";
        $stmt = $this->db->prepare(
            "SELECT MAX(CAST(SUBSTRING(codigo, ?) AS UNSIGNED)) FROM creditos WHERE codigo LIKE ?"
        );
        $stmt->execute([strlen($prefix) + 1, "{$prefix}%"]);
        $last = (int)$stmt->fetchColumn();
        return $prefix . str_pad((string)($last + 1), 5, '0', STR_PAD_LEFT);
    }

    public function insert(Credito $c): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO creditos (
                codigo, id_cliente, id_vendedor, id_cobrador,
                capital, cantidad_cuotas, valor_cuota, monto_total,
                interes_implicito, interes_implicito_pct, gastos_admin,
                frecuencia, fecha_inicio, fecha_fin_estimada,
                saldo_pendiente, destino_opcional, estado,
                id_credito_origen, observaciones, created_by
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, 'activo',
                ?, ?, ?
            )
        ");
        $stmt->execute([
            $c->codigo,       $c->id_cliente,  $c->id_vendedor,  $c->id_cobrador,
            $c->capital,      $c->cantidad_cuotas, $c->valor_cuota, $c->monto_total,
            $c->interes_implicito, $c->interes_implicito_pct, $c->gastos_admin,
            $c->frecuencia,   $c->fecha_inicio, $c->fecha_fin_estimada,
            $c->saldo_pendiente, $c->destino_opcional,
            $c->id_credito_origen, $c->observaciones, $c->created_by,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * @param array<int, array{numero_cuota: int, fecha_vencimiento: string, monto_esperado: float}> $cuotas
     */
    public function insertCuotas(int $idCredito, array $cuotas): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO cuotas (id_credito, numero_cuota, fecha_vencimiento, monto_esperado)
            VALUES (?, ?, ?, ?)
        ");
        foreach ($cuotas as $row) {
            $stmt->execute([$idCredito, $row['numero_cuota'], $row['fecha_vencimiento'], $row['monto_esperado']]);
        }
    }

    /** @return Credito[] */
    public function findAll(int $limit = 20, int $offset = 0, string $search = '', string $estado = ''): array
    {
        $sql = "
            SELECT cr.*,
                cl.nombre AS cliente_nombre, cl.dni AS cliente_dni,
                pv.nombre AS vendedor_nombre,
                pc.nombre AS cobrador_nombre
            FROM creditos cr
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            LEFT JOIN personal pv ON cr.id_vendedor = pv.id_personal
            LEFT JOIN personal pc ON cr.id_cobrador = pc.id_personal
            WHERE cr.deleted_at IS NULL
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (cl.nombre LIKE ? OR cl.dni LIKE ? OR cr.codigo LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($estado !== '') {
            $sql .= " AND cr.estado = ?";
            $params[] = $estado;
        }
        $sql .= " ORDER BY cr.id_credito DESC";

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

    public function countAll(string $search = '', string $estado = ''): int
    {
        $sql = "
            SELECT COUNT(*) FROM creditos cr
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            WHERE cr.deleted_at IS NULL
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (cl.nombre LIKE ? OR cl.dni LIKE ? OR cr.codigo LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if ($estado !== '') {
            $sql .= " AND cr.estado = ?";
            $params[] = $estado;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?Credito
    {
        $stmt = $this->db->prepare("
            SELECT cr.*,
                cl.nombre AS cliente_nombre, cl.dni AS cliente_dni,
                pv.nombre AS vendedor_nombre,
                pc.nombre AS cobrador_nombre
            FROM creditos cr
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            LEFT JOIN personal pv ON cr.id_vendedor = pv.id_personal
            LEFT JOIN personal pc ON cr.id_cobrador = pc.id_personal
            WHERE cr.id_credito = ? AND cr.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $credito = $this->hydrate($row);
        $credito->cuotas = $this->findCuotasByCredito($id);
        return $credito;
    }

    /** @return Credito[] Todos los créditos de un cliente, activos primero */
    public function findByCliente(int $idCliente): array
    {
        $stmt = $this->db->prepare("
            SELECT cr.*,
                pv.nombre AS vendedor_nombre,
                pc.nombre AS cobrador_nombre
            FROM creditos cr
            LEFT JOIN personal pv ON cr.id_vendedor = pv.id_personal
            LEFT JOIN personal pc ON cr.id_cobrador = pc.id_personal
            WHERE cr.id_cliente = ? AND cr.deleted_at IS NULL
            ORDER BY (cr.estado = 'activo') DESC, cr.id_credito DESC
        ");
        $stmt->execute([$idCliente]);

        $list = [];
        while ($row = $stmt->fetch()) {
            $list[] = $this->hydrate($row);
        }
        return $list;
    }

    /** @return Credito[] Solo créditos activos del cliente */
    public function findActivosByCliente(int $idCliente): array
    {
        $stmt = $this->db->prepare("
            SELECT cr.*, pc.nombre AS cobrador_nombre
            FROM creditos cr
            LEFT JOIN personal pc ON cr.id_cobrador = pc.id_personal
            WHERE cr.id_cliente = ? AND cr.estado = 'activo' AND cr.deleted_at IS NULL
            ORDER BY cr.id_credito DESC
        ");
        $stmt->execute([$idCliente]);

        $list = [];
        while ($row = $stmt->fetch()) {
            $list[] = $this->hydrate($row);
        }
        return $list;
    }

    public function countActivosByCliente(int $idCliente): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM creditos
            WHERE id_cliente = ? AND estado = 'activo' AND deleted_at IS NULL
        ");
        $stmt->execute([$idCliente]);
        return (int)$stmt->fetchColumn();
    }

    /** @return Cuota[] */
    public function findCuotasByCredito(int $idCredito): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM cuotas
            WHERE id_credito = ?
            ORDER BY numero_cuota ASC
        ");
        $stmt->execute([$idCredito]);

        $list = [];
        while ($row = $stmt->fetch()) {
            $list[] = $this->hydrateCuota($row);
        }
        return $list;
    }

    public function updateEstado(int $idCredito, string $estado, int $updatedBy): void
    {
        $stmt = $this->db->prepare("
            UPDATE creditos SET estado = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id_credito = ?
        ");
        $stmt->execute([$estado, $updatedBy, $idCredito]);
    }

    /**
     * Cuotas que vencen en una fecha para un cobrador específico (o todas si $idCobrador = null).
     * @return array<int, array<string, mixed>>
     */
    public function getCuotasVencenHoyPorCobrador(?int $idCobrador, string $fecha): array
    {
        $sql = "
            SELECT
                cu.id_cuota, cu.numero_cuota, cu.fecha_vencimiento,
                cu.monto_esperado, cu.monto_pagado, cu.estado,
                cr.id_credito, cr.codigo AS credito_codigo, cr.frecuencia,
                cl.nombre AS cliente_nombre, cl.dni AS cliente_dni,
                cl.telefono AS cliente_telefono, cl.direccion AS cliente_direccion,
                z.nombre AS zona_nombre
            FROM cuotas cu
            JOIN creditos cr ON cu.id_credito = cr.id_credito
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            LEFT JOIN zonas z ON cl.id_zona = z.id_zona
            WHERE cu.fecha_vencimiento = ?
              AND cr.estado = 'activo'
              AND cr.deleted_at IS NULL
              AND cu.estado NOT IN ('pagada', 'condonada')
        ";
        $params = [$fecha];

        if ($idCobrador !== null) {
            $sql .= " AND cr.id_cobrador = ?";
            $params[] = $idCobrador;
        }

        $sql .= " ORDER BY z.nombre ASC, cl.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function decrementarScoreCliente(int $idCliente): void
    {
        $this->db->prepare("
            UPDATE clientes SET score_interno = GREATEST(1, score_interno - 1) WHERE id_cliente = ?
        ")->execute([$idCliente]);
    }

    public function incrementarScoreCliente(int $idCliente): void
    {
        $this->db->prepare("
            UPDATE clientes SET score_interno = LEAST(5, score_interno + 1) WHERE id_cliente = ?
        ")->execute([$idCliente]);
    }

    /** Verifica si el cliente tiene algún crédito en estado incobrable */
    public function clienteTieneIncobrable(int $idCliente): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM creditos
            WHERE id_cliente = ? AND estado = 'incobrable' AND deleted_at IS NULL
        ");
        $stmt->execute([$idCliente]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function hydrate(array $row): Credito
    {
        $c = new Credito();
        $c->id_credito          = (int)$row['id_credito'];
        $c->codigo              = $row['codigo'];
        $c->id_cliente          = (int)$row['id_cliente'];
        $c->id_vendedor         = isset($row['id_vendedor'])  && $row['id_vendedor']  !== null ? (int)$row['id_vendedor']  : null;
        $c->id_cobrador         = isset($row['id_cobrador'])  && $row['id_cobrador']  !== null ? (int)$row['id_cobrador']  : null;
        $c->capital             = (float)$row['capital'];
        $c->cantidad_cuotas     = (int)$row['cantidad_cuotas'];
        $c->valor_cuota         = (float)$row['valor_cuota'];
        $c->monto_total         = (float)$row['monto_total'];
        $c->interes_implicito   = (float)$row['interes_implicito'];
        $c->interes_implicito_pct = (float)$row['interes_implicito_pct'];
        $c->gastos_admin        = (float)$row['gastos_admin'];
        $c->frecuencia          = $row['frecuencia'];
        $c->fecha_inicio        = $row['fecha_inicio'];
        $c->fecha_fin_estimada  = $row['fecha_fin_estimada'] ?? null;
        $c->saldo_pendiente     = (float)$row['saldo_pendiente'];
        $c->destino_opcional    = $row['destino_opcional']   ?? null;
        $c->estado              = $row['estado'];
        $c->id_credito_origen   = isset($row['id_credito_origen']) && $row['id_credito_origen'] !== null ? (int)$row['id_credito_origen'] : null;
        $c->observaciones       = $row['observaciones']      ?? null;
        $c->created_by          = (int)$row['created_by'];
        $c->updated_by          = isset($row['updated_by'])  && $row['updated_by']  !== null ? (int)$row['updated_by']  : null;
        $c->created_at          = $row['created_at']         ?? '';
        $c->cliente_nombre      = $row['cliente_nombre']     ?? null;
        $c->cliente_dni         = $row['cliente_dni']        ?? null;
        $c->vendedor_nombre     = $row['vendedor_nombre']    ?? null;
        $c->cobrador_nombre     = $row['cobrador_nombre']    ?? null;
        return $c;
    }

    private function hydrateCuota(array $row): Cuota
    {
        $q = new Cuota();
        $q->id_cuota          = (int)$row['id_cuota'];
        $q->id_credito        = (int)$row['id_credito'];
        $q->numero_cuota      = (int)$row['numero_cuota'];
        $q->fecha_vencimiento = $row['fecha_vencimiento'];
        $q->monto_esperado    = (float)$row['monto_esperado'];
        $q->monto_pagado      = (float)$row['monto_pagado'];
        $q->monto_recargo     = (float)$row['monto_recargo'];
        $q->estado            = $row['estado'];
        $q->fecha_pagada      = $row['fecha_pagada'] ?? null;
        return $q;
    }
}
