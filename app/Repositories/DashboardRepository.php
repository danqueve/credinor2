<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use PDO;

class DashboardRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Retorna estadísticas globales básicas.
     */
    public function getGlobalStats(): array
    {
        // Clientes activos (con al menos un crédito activo)
        $clientesSql = "SELECT COUNT(DISTINCT id_cliente) FROM creditos WHERE estado = 'activo' AND deleted_at IS NULL";
        
        // Créditos activos
        $creditosSql = "SELECT COUNT(*) FROM creditos WHERE estado = 'activo' AND deleted_at IS NULL";
        
        // Cuotas que vencen hoy
        $cuotasHoySql = "SELECT COUNT(*) FROM cuotas WHERE fecha_vencimiento = CURRENT_DATE AND estado IN ('pendiente', 'parcial')";
        
        // Cobranza del día (suma de montos pagados hoy)
        $cobranzaSql = "SELECT SUM(monto_pagado) FROM pagos WHERE DATE(fecha_registro) = CURRENT_DATE AND anulado = 0";

        return [
            'clientes_activos'  => (int)$this->db->query($clientesSql)->fetchColumn(),
            'creditos_activos'  => (int)$this->db->query($creditosSql)->fetchColumn(),
            'cuotas_vencer_hoy' => (int)$this->db->query($cuotasHoySql)->fetchColumn(),
            'cobranza_dia'      => (float)$this->db->query($cobranzaSql)->fetchColumn() ?: 0.0,
        ];
    }

    /**
     * Retorna los próximos vencimientos (cuotas pendientes más cercanas).
     */
    public function getProximosVencimientos(int $limit = 5): array
    {
        $sql = "
            SELECT 
                cu.fecha_vencimiento, 
                cu.monto_esperado, 
                cu.monto_pagado,
                cr.codigo AS credito_codigo,
                cl.nombre AS cliente_nombre,
                cl.apellido AS cliente_apellido
            FROM cuotas cu
            JOIN creditos cr ON cu.id_credito = cr.id_credito
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            WHERE cu.fecha_vencimiento >= CURRENT_DATE 
              AND cu.estado IN ('pendiente', 'parcial')
              AND cr.estado = 'activo'
              AND cr.deleted_at IS NULL
            ORDER BY cu.fecha_vencimiento ASC, cu.id_cuota ASC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna actividad reciente (últimos pagos registrados).
     */
    public function getActividadReciente(int $limit = 5): array
    {
        $sql = "
            SELECT 
                p.fecha_registro,
                p.monto_pagado,
                cr.codigo AS credito_codigo,
                cl.nombre AS cliente_nombre,
                cl.apellido AS cliente_apellido
            FROM pagos p
            JOIN creditos cr ON p.id_credito = cr.id_credito
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            WHERE p.anulado = 0
            ORDER BY p.id_pago DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Distribución de créditos por estado (para donut).
     */
    public function getCarteraPorEstado(): array
    {
        $sql = "
            SELECT estado, COUNT(*) AS cantidad, SUM(saldo_pendiente) AS saldo
            FROM creditos WHERE deleted_at IS NULL
            GROUP BY estado
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Aging resumido para gráfico de barras.
     */
    public function getAgingResumen(): array
    {
        $sql = "
            SELECT
                CASE
                    WHEN DATEDIFF(CURRENT_DATE, fecha_vencimiento) BETWEEN 1 AND 15 THEN '1-15d'
                    WHEN DATEDIFF(CURRENT_DATE, fecha_vencimiento) BETWEEN 16 AND 30 THEN '16-30d'
                    WHEN DATEDIFF(CURRENT_DATE, fecha_vencimiento) BETWEEN 31 AND 60 THEN '31-60d'
                    ELSE '60+d'
                END AS tramo,
                SUM(monto_esperado - monto_pagado) AS saldo_vencido
            FROM cuotas cu
            JOIN creditos cr ON cu.id_credito = cr.id_credito
            WHERE cu.fecha_vencimiento < CURRENT_DATE
              AND cu.estado IN ('pendiente','parcial','vencida')
              AND cr.estado = 'activo' AND cr.deleted_at IS NULL
            GROUP BY tramo ORDER BY tramo ASC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna capital total prestado vs saldo pendiente (créditos activos).
     */
    public function getCapitalResumen(): array
    {
        $sql = "SELECT SUM(capital) AS capital_total, SUM(saldo_pendiente) AS saldo_pendiente
                FROM creditos WHERE estado = 'activo' AND deleted_at IS NULL";
        $row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        $capitalTotal   = (float)($row['capital_total']   ?? 0);
        $saldoPendiente = (float)($row['saldo_pendiente'] ?? 0);
        return [
            'capital_total'   => $capitalTotal,
            'saldo_pendiente' => $saldoPendiente,
            'recuperado'      => max(0.0, $capitalTotal - $saldoPendiente),
        ];
    }

    /**
     * Retorna datos para gráfico de cobranza semanal.
     */
    public function getCobranzaSemanal(): array
    {
        $sql = "
            SELECT 
                DATE(fecha_registro) as fecha,
                SUM(monto_pagado) as total
            FROM pagos
            WHERE fecha_registro >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
              AND anulado = 0
            GROUP BY DATE(fecha_registro)
            ORDER BY fecha ASC
        ";
        return $this->db->query($sql)->fetchAll();
    }
}
