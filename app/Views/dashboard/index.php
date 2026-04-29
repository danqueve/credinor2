<?php 
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start(); 
?><div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h3 mb-0 text-white fw-bold">Dashboard</h2>
    <div>
        <span class="text-secondary"><i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y') ?></span>
    </div>
</div>

<!-- Widgets de resumen -->
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card bg-slate-800 border-secondary h-100">
            <div class="card-body d-flex align-items-center">
                <div class="bg-primary bg-opacity-10 rounded p-3 me-3 text-primary">
                    <i class="bi bi-people-fill fs-3"></i>
                </div>
                <div>
                    <h6 class="card-title text-secondary mb-1">Clientes Activos</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($stats['clientes_activos'] ?? 0, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card bg-slate-800 border-secondary h-100">
            <div class="card-body d-flex align-items-center">
                <div class="bg-success bg-opacity-10 rounded p-3 me-3 text-success">
                    <i class="bi bi-cash-stack fs-3"></i>
                </div>
                <div>
                    <h6 class="card-title text-secondary mb-1">Créditos Activos</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($stats['creditos_activos'] ?? 0, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card bg-slate-800 border-secondary h-100">
            <div class="card-body d-flex align-items-center">
                <div class="bg-warning bg-opacity-10 rounded p-3 me-3 text-warning">
                    <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                </div>
                <div>
                    <h6 class="card-title text-secondary mb-1">Cuotas a vencer hoy</h6>
                    <h3 class="mb-0 fw-bold"><?= number_format($stats['cuotas_vencer_hoy'] ?? 0, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card bg-slate-800 border-secondary h-100">
            <div class="card-body d-flex align-items-center">
                <div class="bg-info bg-opacity-10 rounded p-3 me-3 text-info">
                    <i class="bi bi-graph-up-arrow fs-3"></i>
                </div>
                <div>
                    <h6 class="card-title text-secondary mb-1">Cobranza del día</h6>
                    <h3 class="mb-0 fw-bold">$<?= number_format($stats['cobranza_dia'] ?? 0, 2, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Gráfico cobranza semanal -->
    <div class="col-12 col-xl-8">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3">
                <h5 class="mb-0 fw-bold text-light"><i class="bi bi-bar-chart-line text-info me-2"></i>Cobranza — últimos 7 días</h5>
            </div>
            <div class="card-body">
                <canvas id="cobranzaChart" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfico estado cartera -->
    <div class="col-12 col-xl-4">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3">
                <h5 class="mb-0 fw-bold text-light"><i class="bi bi-pie-chart text-warning me-2"></i>Estado de Cartera</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="carteraChart" style="max-height: 260px; max-width: 260px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Fila: aging + saldo -->
<div class="row g-4 mb-4">
    <div class="col-12 col-xl-6">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3">
                <h5 class="mb-0 fw-bold text-light"><i class="bi bi-hourglass-split text-danger me-2"></i>Aging Cartera Vencida</h5>
            </div>
            <div class="card-body">
                <canvas id="agingChart" style="max-height: 220px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3">
                <h5 class="mb-0 fw-bold text-light"><i class="bi bi-currency-dollar text-success me-2"></i>Saldo vs Capital</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="saldoChart" style="max-height: 220px; max-width: 220px;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Próximos Vencimientos -->
    <div class="col-12 col-xl-8">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-light"><i class="bi bi-calendar-event text-warning me-2"></i> Próximos Vencimientos</h5>
                <a href="<?= $appUrl ?>/reportes/vencimientos" class="btn btn-sm btn-outline-secondary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($proximosVencimientos)): ?>
                    <div class="text-center py-4 text-secondary">
                        <i class="bi bi-inbox fs-1 d-block mb-2 text-slate-600"></i>
                        No hay vencimientos próximos
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                                <tr class="text-secondary small">
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Crédito</th>
                                    <th class="text-end">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proximosVencimientos as $v): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($v['fecha_vencimiento'])) ?></td>
                                        <td class="text-light"><?= htmlspecialchars($v['cliente_nombre'] . ' ' . $v['cliente_apellido']) ?></td>
                                        <td class="font-monospace text-info small"><?= $v['credito_codigo'] ?></td>
                                        <td class="text-end text-light fw-bold">$<?= number_format((float)$v['monto_esperado'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <div class="col-12 col-xl-4">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary py-3">
                <h5 class="mb-0 fw-bold text-light"><i class="bi bi-clock-history text-info me-2"></i> Actividad Reciente</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($actividadReciente)): ?>
                    <div class="text-center py-4 text-secondary">
                        <i class="bi bi-activity fs-1 d-block mb-2 text-slate-600"></i>
                        Sin actividad reciente
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush bg-transparent">
                        <?php foreach ($actividadReciente as $p): ?>
                            <div class="list-group-item bg-transparent border-secondary py-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-info small font-monospace"><?= $p['credito_codigo'] ?></span>
                                    <span class="badge bg-success-subtle text-success">+$<?= number_format((float)$p['monto_pagado'], 2, ',', '.') ?></span>
                                </div>
                                <div class="text-light small"><?= htmlspecialchars($p['cliente_nombre'] . ' ' . $p['cliente_apellido']) ?></div>
                                <div class="text-secondary" style="font-size: 0.75rem;"><?= date('H:i', strtotime($p['fecha_registro'])) ?> hs</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js para los gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('cobranzaChart').getContext('2d');
    const data = <?= json_encode($cobranzaSemanal) ?>;
    
    // Preparar etiquetas y datos
    const labels = data.map(item => {
        const [y, m, d] = item.fecha.split('-');
        return `${d}/${m}`;
    });
    const totals = data.map(item => item.total);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Cobranza Total ($)',
                data: totals,
                borderColor: '#0dcaf0',
                backgroundColor: 'rgba(13, 202, 240, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: '#0dcaf0'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#64748b' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b' }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    // ── Cartera por Estado (donut) ──────────────────────────────────────
    const carteraData = <?= json_encode($carteraPorEstado ?? []) ?>;
    const estadoColors = {
        activo:       '#22c55e',
        vencido:      '#ef4444',
        cancelado:    '#64748b',
        refinanciado: '#a855f7',
        incobrable:   '#f97316',
    };
    if (carteraData.length > 0) {
        new Chart(document.getElementById('carteraChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: carteraData.map(r => r.estado.charAt(0).toUpperCase() + r.estado.slice(1)),
                datasets: [{
                    data: carteraData.map(r => parseInt(r.cantidad)),
                    backgroundColor: carteraData.map(r => estadoColors[r.estado] ?? '#94a3b8'),
                    borderWidth: 2,
                    borderColor: '#1e293b'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#94a3b8', boxWidth: 12, padding: 12 }
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.label}: ${ctx.parsed} créditos`
                        }
                    }
                }
            }
        });
    }

    // ── Aging Cartera Vencida (barras horizontales) ────────────────────
    const agingData = <?= json_encode($agingResumen ?? []) ?>;
    const agingOrder = ['1-15d', '16-30d', '31-60d', '60+d'];
    const agingMap   = Object.fromEntries(agingData.map(r => [r.tramo, parseFloat(r.saldo_vencido)]));
    new Chart(document.getElementById('agingChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: agingOrder,
            datasets: [{
                label: 'Saldo vencido ($)',
                data: agingOrder.map(t => agingMap[t] ?? 0),
                backgroundColor: ['#fbbf24', '#f97316', '#ef4444', '#b91c1c'],
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.05)' },
                    ticks: {
                        color: '#64748b',
                        callback: v => '$' + v.toLocaleString('es-AR')
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#64748b' }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` $${ctx.parsed.y.toLocaleString('es-AR', {minimumFractionDigits: 2})}`
                    }
                }
            }
        }
    });

    // ── Capital vs Recuperado (donut) ──────────────────────────────────
    const capitalResumen = <?= json_encode($capitalResumen ?? ['capital_total'=>0,'saldo_pendiente'=>0,'recuperado'=>0]) ?>;
    if (capitalResumen.capital_total > 0) {
        new Chart(document.getElementById('saldoChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Recuperado', 'Saldo Pendiente'],
                datasets: [{
                    data: [capitalResumen.recuperado, capitalResumen.saldo_pendiente],
                    backgroundColor: ['#22c55e', '#3b82f6'],
                    borderWidth: 2,
                    borderColor: '#1e293b'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#94a3b8', boxWidth: 12, padding: 12 }
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.label}: $${ctx.parsed.toLocaleString('es-AR', {minimumFractionDigits: 2})}`
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php 
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php'; 
?>
