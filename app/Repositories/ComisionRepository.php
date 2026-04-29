<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use PDO;

class ComisionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Cobranza del período: suma de pagos no anulados agrupados por cobrador.
     */
    public function getCobranzaPorPeriodo(string $periodo): array
    {
        $sql = "
            SELECT
                p.id_cobrador,
                pe.nombre,
                pe.comision_pct,
                SUM(p.monto_pagado) AS monto_cobrado
            FROM pagos p
            JOIN personal pe ON p.id_cobrador = pe.id_personal
            WHERE DATE_FORMAT(p.fecha_pago_real, '%Y-%m') = ?
              AND p.anulado = 0
              AND p.id_cobrador IS NOT NULL
            GROUP BY p.id_cobrador, pe.nombre, pe.comision_pct
            ORDER BY pe.nombre ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$periodo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ventas del período: suma de capital de créditos creados, agrupados por vendedor.
     */
    public function getVentaPorPeriodo(string $periodo): array
    {
        $sql = "
            SELECT
                cr.id_vendedor,
                pe.nombre,
                pe.comision_pct,
                SUM(cr.capital) AS monto_vendido,
                COUNT(*) AS cantidad_creditos
            FROM creditos cr
            JOIN personal pe ON cr.id_vendedor = pe.id_personal
            WHERE DATE_FORMAT(cr.created_at, '%Y-%m') = ?
              AND cr.deleted_at IS NULL
              AND cr.id_vendedor IS NOT NULL
            GROUP BY cr.id_vendedor, pe.nombre, pe.comision_pct
            ORDER BY pe.nombre ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$periodo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las comisiones ya liquidadas para un período.
     */
    public function getLiquidacion(string $periodo): array
    {
        $sql = "
            SELECT c.*, pe.nombre AS personal_nombre
            FROM comisiones c
            JOIN personal pe ON c.id_personal = pe.id_personal
            WHERE c.periodo = ?
            ORDER BY c.tipo ASC, pe.nombre ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$periodo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista de períodos que ya tienen liquidación generada.
     */
    public function getPeriodosLiquidados(): array
    {
        $sql = "SELECT DISTINCT periodo FROM comisiones ORDER BY periodo DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    public function periodoYaLiquidado(string $periodo): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM comisiones WHERE periodo = ?");
        $stmt->execute([$periodo]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function deletePorPeriodo(string $periodo): void
    {
        $stmt = $this->db->prepare("DELETE FROM comisiones WHERE periodo = ? AND pagada = 0");
        $stmt->execute([$periodo]);
    }

    public function insertar(array $data): void
    {
        $sql = "
            INSERT INTO comisiones (id_personal, periodo, tipo, monto_base, pct, monto_comision, pagada)
            VALUES (:id_personal, :periodo, :tipo, :monto_base, :pct, :monto_comision, 0)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
    }

    public function marcarPagada(int $idComision): void
    {
        $stmt = $this->db->prepare("UPDATE comisiones SET pagada = 1 WHERE id_comision = ?");
        $stmt->execute([$idComision]);
    }
}
