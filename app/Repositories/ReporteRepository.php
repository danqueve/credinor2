<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use PDO;

class ReporteRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Cartera total activa: capital prestado vs saldo pendiente.
     */
    public function getCarteraActiva(): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total_creditos,
                SUM(capital) as capital_total,
                SUM(monto_total) as monto_a_recuperar,
                SUM(saldo_pendiente) as saldo_actual
            FROM creditos
            WHERE estado = 'activo' AND deleted_at IS NULL
        ";
        return $this->db->query($sql)->fetch() ?: [];
    }

    /**
     * Cuotas vencidas (Aging): desglosado por tramos de días de atraso.
     */
    public function getAgingVencidos(): array
    {
        $sql = "
            SELECT 
                CASE 
                    WHEN DATEDIFF(CURRENT_DATE, fecha_vencimiento) BETWEEN 1 AND 15 THEN '1-15 días'
                    WHEN DATEDIFF(CURRENT_DATE, fecha_vencimiento) BETWEEN 16 AND 30 THEN '16-30 días'
                    WHEN DATEDIFF(CURRENT_DATE, fecha_vencimiento) BETWEEN 31 AND 60 THEN '31-60 días'
                    ELSE '60+ días'
                END as tramo,
                COUNT(*) as cantidad_cuotas,
                SUM(monto_esperado - monto_pagado) as saldo_vencido
            FROM cuotas cu
            JOIN creditos cr ON cu.id_credito = cr.id_credito
            WHERE cu.fecha_vencimiento < CURRENT_DATE 
              AND cu.estado IN ('pendiente', 'parcial', 'vencida')
              AND cr.estado = 'activo'
              AND cr.deleted_at IS NULL
            GROUP BY tramo
            ORDER BY tramo ASC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Cobranza por Cobrador: cuánto cobró cada uno en un periodo.
     */
    public function getCobranzaPorCobrador(string $desde, string $hasta): array
    {
        $sql = "
            SELECT 
                p.nombre as cobrador,
                COUNT(pa.id_pago) as cantidad_pagos,
                SUM(pa.monto_pagado) as total_cobrado
            FROM pagos pa
            JOIN personal p ON pa.id_cobrador = p.id_personal
            WHERE pa.fecha_pago_real BETWEEN ? AND ?
              AND pa.anulado = 0
            GROUP BY p.id_personal
            ORDER BY total_cobrado DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll();
    }

    /**
     * Clientes con múltiples créditos activos.
     */
    public function getClientesMultiCredito(): array
    {
        $sql = "
            SELECT 
                cl.nombre, cl.apellido, cl.dni,
                COUNT(cr.id_credito) as creditos_activos,
                SUM(cr.saldo_pendiente) as saldo_total
            FROM clientes cl
            JOIN creditos cr ON cl.id_cliente = cr.id_cliente
            WHERE cr.estado = 'activo' AND cr.deleted_at IS NULL
            GROUP BY cl.id_cliente
            HAVING creditos_activos > 1
            ORDER BY creditos_activos DESC
        ";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Performance de Vendedores: créditos creados en un periodo.
     */
    public function getPerformanceVendedores(string $desde, string $hasta): array
    {
        $sql = "
            SELECT 
                p.nombre as vendedor,
                COUNT(cr.id_credito) as cantidad_creditos,
                SUM(cr.capital) as capital_prestado,
                SUM(cr.monto_total) as volumen_negocio
            FROM creditos cr
            JOIN personal p ON cr.id_vendedor = p.id_personal
            WHERE DATE(cr.created_at) BETWEEN ? AND ?
              AND cr.deleted_at IS NULL
              AND cr.estado != 'anulado'
            GROUP BY p.id_personal
            ORDER BY volumen_negocio DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll();
    }

    /**
     * Flujo de caja proyectado: cuotas pendientes agrupadas por día para los próximos N días.
     */
    public function getFlujoCajaProyectado(int $dias = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT
                cu.fecha_vencimiento AS fecha,
                COUNT(cu.id_cuota)                            AS cuotas,
                SUM(cu.monto_esperado - cu.monto_pagado)      AS monto_esperado,
                COUNT(DISTINCT cr.id_cobrador)                AS cobradores
            FROM cuotas cu
            JOIN creditos cr ON cu.id_credito = cr.id_credito
            WHERE cu.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
              AND cu.estado IN ('pendiente','parcial','vencida')
              AND cr.estado = 'activo'
              AND cr.deleted_at IS NULL
            GROUP BY cu.fecha_vencimiento
            ORDER BY cu.fecha_vencimiento ASC
        ");
        $stmt->execute([$dias]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuotas que vencen en una fecha (default: hoy) con datos de cobrador y zona.
     */
    public function getCuotasVencenFecha(string $fecha): array
    {
        $stmt = $this->db->prepare("
            SELECT
                cu.id_cuota, cu.numero_cuota, cu.fecha_vencimiento,
                cu.monto_esperado, cu.monto_pagado, cu.estado,
                cr.codigo AS credito_codigo,
                cl.nombre AS cliente_nombre, cl.dni AS cliente_dni,
                cl.telefono AS cliente_telefono, cl.direccion AS cliente_direccion,
                p.nombre AS cobrador_nombre,
                z.nombre AS zona_nombre
            FROM cuotas cu
            JOIN creditos cr ON cu.id_credito = cr.id_credito
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            LEFT JOIN personal p ON cr.id_cobrador = p.id_personal
            LEFT JOIN zonas z ON cl.id_zona = z.id_zona
            WHERE cu.fecha_vencimiento = ?
              AND cr.estado = 'activo'
              AND cr.deleted_at IS NULL
              AND cu.estado NOT IN ('pagada','condonada')
            ORDER BY z.nombre ASC, p.nombre ASC, cl.nombre ASC
        ");
        $stmt->execute([$fecha]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Clientes con cuotas atrasadas, con teléfono y dirección para cobranza.
     */
    public function getClientesConAtraso(): array
    {
        $stmt = $this->db->prepare("
            SELECT
                cl.id_cliente, cl.nombre AS cliente_nombre, cl.dni,
                cl.telefono, cl.direccion,
                cr.id_credito, cr.codigo AS credito_codigo, cr.saldo_pendiente,
                COUNT(cu.id_cuota)                        AS cuotas_vencidas,
                SUM(cu.monto_esperado - cu.monto_pagado)  AS deuda_vencida,
                MIN(cu.fecha_vencimiento)                 AS primera_vencida,
                DATEDIFF(CURDATE(), MIN(cu.fecha_vencimiento)) AS dias_atraso,
                p.nombre AS cobrador_nombre,
                z.nombre AS zona_nombre
            FROM cuotas cu
            JOIN creditos cr ON cu.id_credito = cr.id_credito
            JOIN clientes cl ON cr.id_cliente = cl.id_cliente
            LEFT JOIN personal p ON cr.id_cobrador = p.id_personal
            LEFT JOIN zonas z ON cl.id_zona = z.id_zona
            WHERE cu.estado IN ('vencida','parcial')
              AND cu.fecha_vencimiento < CURDATE()
              AND cr.estado = 'activo'
              AND cr.deleted_at IS NULL
            GROUP BY cl.id_cliente, cr.id_credito
            ORDER BY dias_atraso DESC, deuda_vencida DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Próximos vencimientos: una fila por cliente (su cuota más cercana pendiente).
     */
    public function getProximosVencimientos(int $dias = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT
                cl.id_cliente,
                cl.nombre  AS cliente_nombre,
                cl.dni,
                cl.telefono,
                cr.id_credito,
                cr.codigo  AS credito_codigo,
                cr.saldo_pendiente,
                cu.numero_cuota,
                cu.fecha_vencimiento,
                cu.monto_esperado,
                cu.monto_pagado,
                cu.estado  AS cuota_estado,
                DATEDIFF(cu.fecha_vencimiento, CURDATE()) AS dias_para_vencer,
                z.nombre   AS zona_nombre
            FROM cuotas cu
            JOIN creditos cr ON cu.id_credito = cr.id_credito
            JOIN clientes cl ON cr.id_cliente  = cl.id_cliente
            LEFT JOIN zonas z ON cl.id_zona = z.id_zona
            WHERE cu.id_cuota = (
                SELECT cu2.id_cuota
                FROM cuotas cu2
                JOIN creditos cr2 ON cu2.id_credito = cr2.id_credito
                WHERE cr2.id_cliente = cl.id_cliente
                  AND cr2.estado = 'activo'
                  AND cr2.deleted_at IS NULL
                  AND cu2.estado IN ('pendiente','parcial','vencida')
                ORDER BY cu2.fecha_vencimiento ASC, cu2.id_cuota ASC
                LIMIT 1
            )
              AND cr.estado = 'activo'
              AND cr.deleted_at IS NULL
              AND cu.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY cu.fecha_vencimiento ASC
        ");
        $stmt->bindValue(1, $dias, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Capital prestado vs recuperado en un periodo.
     */
    public function getCapitalVsRecuperado(string $desde, string $hasta): array
    {
        $stmtPrestado = $this->db->prepare("
            SELECT
                DATE_FORMAT(cr.fecha_inicio, '%Y-%m') AS mes,
                COUNT(cr.id_credito)   AS creditos_otorgados,
                SUM(cr.capital)        AS capital_prestado,
                SUM(cr.monto_total)    AS monto_total_pactado
            FROM creditos cr
            WHERE cr.fecha_inicio BETWEEN ? AND ?
              AND cr.deleted_at IS NULL
              AND cr.estado != 'anulado'
            GROUP BY mes
            ORDER BY mes ASC
        ");
        $stmtPrestado->execute([$desde, $hasta]);
        $prestado = $stmtPrestado->fetchAll(PDO::FETCH_ASSOC);

        $stmtRec = $this->db->prepare("
            SELECT
                DATE_FORMAT(pa.fecha_pago_real, '%Y-%m') AS mes,
                COUNT(pa.id_pago)      AS cantidad_pagos,
                SUM(pa.monto_pagado)   AS total_recuperado
            FROM pagos pa
            WHERE pa.fecha_pago_real BETWEEN ? AND ?
              AND pa.anulado = 0
            GROUP BY mes
            ORDER BY mes ASC
        ");
        $stmtRec->execute([$desde, $hasta]);
        $recuperado = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

        // Combinar por mes
        $map = [];
        foreach ($prestado as $r) {
            $map[$r['mes']] = array_merge(['recuperado' => 0, 'cantidad_pagos' => 0], $r);
        }
        foreach ($recuperado as $r) {
            if (!isset($map[$r['mes']])) {
                $map[$r['mes']] = ['mes' => $r['mes'], 'capital_prestado' => 0, 'monto_total_pactado' => 0, 'creditos_otorgados' => 0];
            }
            $map[$r['mes']]['total_recuperado'] = $r['total_recuperado'];
            $map[$r['mes']]['cantidad_pagos']   = $r['cantidad_pagos'];
        }
        ksort($map);
        return array_values($map);
    }

    /**
     * Rendiciones con diferencia (total_declarado ≠ total_registrado).
     */
    public function getRendicionesConDiferencia(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare("
            SELECT
                r.id_rendicion, r.fecha_rendicion,
                r.total_efectivo_declarado, r.total_transferencias_declarado,
                (r.total_efectivo_declarado + r.total_transferencias_declarado) AS total_declarado,
                r.total_registrado,
                ((r.total_efectivo_declarado + r.total_transferencias_declarado) - r.total_registrado) AS diferencia,
                r.estado, r.observaciones,
                p.nombre AS cobrador_nombre
            FROM rendiciones r
            JOIN personal p ON r.id_cobrador = p.id_personal
            WHERE r.fecha_rendicion BETWEEN ? AND ?
              AND r.estado = 'con_diferencia'
            ORDER BY ABS(diferencia) DESC
        ");
        $stmt->execute([$desde, $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Total cobrado (pagos no anulados) en un rango de fechas.
     */
    public function getTotalCobradoEnRango(string $desde, string $hasta): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(monto_pagado), 0)
            FROM pagos
            WHERE anulado = 0 AND fecha_pago_real BETWEEN ? AND ?
        ");
        $stmt->execute([$desde, $hasta]);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Total capital prestado (créditos no anulados/borrados) en un rango de fechas.
     */
    public function getTotalPrestadoEnRango(string $desde, string $hasta): float
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(capital), 0)
            FROM creditos
            WHERE deleted_at IS NULL AND estado != 'anulado'
              AND fecha_inicio BETWEEN ? AND ?
        ");
        $stmt->execute([$desde, $hasta]);
        return (float)$stmt->fetchColumn();
    }

    /**
     * Métricas históricas (sin filtro de fecha).
     */
    public function getMetricasHistoricas(): array
    {
        $cobradoTotal = (float)$this->db->query("
            SELECT COALESCE(SUM(monto_pagado), 0) FROM pagos WHERE anulado = 0
        ")->fetchColumn();

        $prestadoTotal = (float)$this->db->query("
            SELECT COALESCE(SUM(capital), 0) FROM creditos
            WHERE deleted_at IS NULL AND estado != 'anulado'
        ")->fetchColumn();

        $capitalActivo = (float)$this->db->query("
            SELECT COALESCE(SUM(saldo_pendiente), 0) FROM creditos
            WHERE estado = 'activo' AND deleted_at IS NULL
        ")->fetchColumn();

        $pendientesCobro = (float)$this->db->query("
            SELECT COALESCE(SUM(cu.monto_esperado - cu.monto_pagado), 0)
            FROM cuotas cu
            JOIN creditos cr ON cu.id_credito = cr.id_credito
            WHERE cu.estado IN ('pendiente','parcial','vencida')
              AND cr.estado = 'activo' AND cr.deleted_at IS NULL
        ")->fetchColumn();

        return [
            'cobrado_total'   => $cobradoTotal,
            'prestado_total'  => $prestadoTotal,
            'capital_activo'  => $capitalActivo,
            'pendientes_cobro' => $pendientesCobro,
        ];
    }

    /**
     * Historial de movimientos unificado (pagos + créditos + caja manual) en un rango.
     */
    public function getHistorialMovimientos(string $desde, string $hasta): array
    {
        $stmt = $this->db->prepare("
            SELECT fecha, tipo, monto, detalle, usuario FROM (
                SELECT
                    pa.fecha_pago_real AS fecha,
                    'cobranza' AS tipo,
                    pa.monto_pagado AS monto,
                    CONCAT('Pago ', cr.codigo, ' — ', cl.nombre, ' ', COALESCE(cl.apellido,'')) AS detalle,
                    COALESCE(u.nombre, '—') AS usuario
                FROM pagos pa
                JOIN creditos cr ON pa.id_credito = cr.id_credito
                JOIN clientes cl ON cr.id_cliente = cl.id_cliente
                LEFT JOIN usuarios u ON pa.created_by = u.id_usuario
                WHERE pa.anulado = 0 AND pa.fecha_pago_real BETWEEN ? AND ?

                UNION ALL

                SELECT
                    cr.fecha_inicio AS fecha,
                    'prestamo' AS tipo,
                    cr.capital AS monto,
                    CONCAT('Crédito ', cr.codigo, ' — ', cl.nombre, ' ', COALESCE(cl.apellido,'')) AS detalle,
                    COALESCE(u.nombre, '—') AS usuario
                FROM creditos cr
                JOIN clientes cl ON cr.id_cliente = cl.id_cliente
                LEFT JOIN usuarios u ON cr.created_by = u.id_usuario
                WHERE cr.deleted_at IS NULL AND cr.estado != 'anulado'
                  AND cr.fecha_inicio BETWEEN ? AND ?

                UNION ALL

                SELECT
                    cm.fecha,
                    cm.tipo,
                    cm.monto,
                    cm.concepto AS detalle,
                    COALESCE(u.nombre, '—') AS usuario
                FROM caja_movimientos cm
                LEFT JOIN usuarios u ON cm.created_by = u.id_usuario
                WHERE cm.deleted_at IS NULL AND cm.fecha BETWEEN ? AND ?
            ) m
            ORDER BY fecha DESC, tipo ASC
            LIMIT 200
        ");
        $stmt->execute([$desde, $hasta, $desde, $hasta, $desde, $hasta]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calcula comisiones sugeridas basadas en capital prestado (ventas)
     * y monto cobrado (cobranza) en un periodo.
     */
    public function getComisionesPeriodo(string $desde, string $hasta): array
    {
        // Comisiones por Venta (Capital prestado)
        $ventasSql = "
            SELECT 
                p.id_personal, p.nombre, p.comision_pct as pct,
                'venta' as tipo,
                SUM(cr.capital) as monto_base,
                SUM(cr.capital * (p.comision_pct / 100)) as monto_comision
            FROM creditos cr
            JOIN personal p ON cr.id_vendedor = p.id_personal
            WHERE DATE(cr.created_at) BETWEEN ? AND ?
              AND cr.deleted_at IS NULL AND cr.estado != 'anulado'
            GROUP BY p.id_personal
        ";
        
        // Comisiones por Cobranza (Monto cobrado)
        $cobranzaSql = "
            SELECT 
                p.id_personal, p.nombre, p.comision_pct as pct,
                'cobranza' as tipo,
                SUM(pa.monto_pagado) as monto_base,
                SUM(pa.monto_pagado * (p.comision_pct / 100)) as monto_comision
            FROM pagos pa
            JOIN personal p ON pa.id_cobrador = p.id_personal
            WHERE pa.fecha_pago_real BETWEEN ? AND ?
              AND pa.anulado = 0
            GROUP BY p.id_personal
        ";

        $stmtV = $this->db->prepare($ventasSql);
        $stmtV->execute([$desde, $hasta]);
        $v = $stmtV->fetchAll();

        $stmtC = $this->db->prepare($cobranzaSql);
        $stmtC->execute([$desde, $hasta]);
        $c = $stmtC->fetchAll();

        return array_merge($v, $c);
    }
}
