<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Models\Rendicion;
use PDO;

class RendicionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function insert(Rendicion $r): int
    {
        // total_declarado y diferencia son columnas STORED (calculadas por MySQL), no se insertan
        $stmt = $this->db->prepare("
            INSERT INTO rendiciones
                (id_cobrador, fecha_rendicion, total_efectivo_declarado,
                 total_transferencias_declarado, total_registrado, estado, observaciones, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $r->id_cobrador,
            $r->fecha_rendicion,
            $r->total_efectivo_declarado,
            $r->total_transferencias_declarado,
            $r->total_registrado,
            $r->estado,
            $r->observaciones,
            $r->created_by,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateTotales(int $idRendicion, float $totalRegistrado, string $estado): void
    {
        $stmt = $this->db->prepare("
            UPDATE rendiciones
            SET total_registrado = ?, estado = ?
            WHERE id_rendicion = ?
        ");
        $stmt->execute([$totalRegistrado, $estado, $idRendicion]);
    }

    public function findById(int $id): ?Rendicion
    {
        $stmt = $this->db->prepare("
            SELECT r.*, p.nombre AS cobrador_nombre
            FROM rendiciones r
            JOIN personal p ON r.id_cobrador = p.id_personal
            WHERE r.id_rendicion = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    /** @return Rendicion[] */
    public function findAll(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT r.*, p.nombre AS cobrador_nombre
            FROM rendiciones r
            JOIN personal p ON r.id_cobrador = p.id_personal
            ORDER BY r.fecha_rendicion DESC, r.id_rendicion DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        $list = [];
        while ($row = $stmt->fetch()) {
            $list[] = $this->hydrate($row);
        }
        return $list;
    }

    public function countAll(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM rendiciones")->fetchColumn();
    }

    /**
     * Busca créditos activos por DNI del cliente o código del crédito.
     * Retorna array listo para JSON (para el autocomplete de la grilla).
     */
    public function buscarCreditoActivo(string $term): array
    {
        $stmt = $this->db->prepare("
            SELECT cr.id_credito, cr.codigo, cr.saldo_pendiente,
                   cr.valor_cuota, cr.frecuencia,
                   cl.nombre AS cliente_nombre, cl.dni AS cliente_dni
            FROM creditos cr
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            WHERE cr.estado = 'activo' AND cr.deleted_at IS NULL
              AND (cl.dni LIKE ? OR cr.codigo LIKE ? OR cl.nombre LIKE ?)
            ORDER BY cl.nombre ASC
            LIMIT 10
        ");
        $like = "%{$term}%";
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hydrate(array $row): Rendicion
    {
        $r = new Rendicion();
        $r->id_rendicion                   = (int)$row['id_rendicion'];
        $r->id_cobrador                    = (int)$row['id_cobrador'];
        $r->fecha_rendicion                = $row['fecha_rendicion'];
        $r->total_efectivo_declarado       = (float)$row['total_efectivo_declarado'];
        $r->total_transferencias_declarado = (float)$row['total_transferencias_declarado'];
        $r->total_declarado                = (float)($row['total_declarado'] ?? 0);
        $r->total_registrado               = (float)$row['total_registrado'];
        $r->diferencia                     = (float)($row['diferencia'] ?? 0);
        $r->estado                         = $row['estado'];
        $r->observaciones                  = $row['observaciones'] ?? null;
        $r->created_by                     = (int)$row['created_by'];
        $r->created_at                     = $row['created_at'] ?? '';
        $r->cobrador_nombre                = $row['cobrador_nombre'] ?? null;
        return $r;
    }
}
