<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use PDO;

class CajaRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function insertar(string $tipo, float $monto, string $concepto, string $fecha, ?string $obs, int $createdBy): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO caja_movimientos (tipo, monto, concepto, fecha, observaciones, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tipo, $monto, $concepto, $fecha, $obs, $createdBy]);
        return (int)$this->db->lastInsertId();
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db->prepare("UPDATE caja_movimientos SET deleted_at = NOW() WHERE id_movimiento = ?");
        $stmt->execute([$id]);
    }

    /** Últimos N movimientos manuales, sin filtro de fecha. */
    public function getRecientes(int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT cm.*, u.nombre AS usuario_nombre
            FROM caja_movimientos cm
            LEFT JOIN usuarios u ON cm.created_by = u.id_usuario
            WHERE cm.deleted_at IS NULL
            ORDER BY cm.fecha DESC, cm.id_movimiento DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Suma de ingresos manuales históricos. */
    public function getTotalIngresos(): float
    {
        return (float)$this->db->query("
            SELECT COALESCE(SUM(monto), 0) FROM caja_movimientos
            WHERE tipo = 'ingreso' AND deleted_at IS NULL
        ")->fetchColumn();
    }

    /** Suma de egresos manuales históricos. */
    public function getTotalEgresos(): float
    {
        return (float)$this->db->query("
            SELECT COALESCE(SUM(monto), 0) FROM caja_movimientos
            WHERE tipo = 'egreso' AND deleted_at IS NULL
        ")->fetchColumn();
    }

    /** Movimientos manuales en un rango de fechas. */
    public function getEnRango(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare("
            SELECT cm.*, u.nombre AS usuario_nombre
            FROM caja_movimientos cm
            LEFT JOIN usuarios u ON cm.created_by = u.id_usuario
            WHERE cm.deleted_at IS NULL AND cm.fecha BETWEEN ? AND ?
            ORDER BY cm.fecha DESC, cm.id_movimiento DESC
        ");
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Suma ingresos/egresos manuales en un rango. */
    public function getTotalesEnRango(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN tipo='ingreso' THEN monto ELSE 0 END), 0) AS ingresos,
                COALESCE(SUM(CASE WHEN tipo='egreso'  THEN monto ELSE 0 END), 0) AS egresos
            FROM caja_movimientos
            WHERE deleted_at IS NULL AND fecha BETWEEN ? AND ?
        ");
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['ingresos' => 0, 'egresos' => 0];
    }
}
