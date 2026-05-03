<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
$canManage = \App\Helpers\Auth::canManage();
ob_start();
?>
<!-- ── Header ─────────────────────────────────────────────────────────── -->
<div class="dash-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="h3 mb-1 fw-bold">
                <span class="text-gradient-blue">Panel de Control</span>
            </h1>
            <p class="text-secondary mb-0 small">
                <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y') ?>
                &nbsp;&bull;&nbsp;
                <i class="bi bi-clock me-1"></i><span id="live-clock">--:--</span>
            </p>
        </div>
        <?php if ($canManage): ?>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= $appUrl ?>/clientes/nuevo" class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus me-1"></i><span class="d-none d-sm-inline">Nuevo Cliente</span>
            </a>
            <a href="<?= $appUrl ?>/creditos/nuevo" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle me-1"></i><span class="d-none d-sm-inline">Nuevo Crédito</span>
            </a>
            <a href="<?= $appUrl ?>/pagos/nuevo" class="btn btn-outline-info btn-sm">
                <i class="bi bi-cash-coin me-1"></i><span class="d-none d-sm-inline">Registrar Pago</span>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── KPI Cards ──────────────────────────────────────────────────────── -->
<p class="sec-label"><i class="bi bi-lightning-fill text-warning"></i>Resumen del día</p>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="kpi-card-v2 kpi-blue p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon-v2 icon-blue"><i class="bi bi-people-fill"></i></div>
                <span class="badge bg-primary-subtle text-primary rounded-pill" style="font-size:.68rem;">Activos</span>
            </div>
            <div class="kpi-num-v2" data-counter="<?= $stats['clientes_activos'] ?? 0 ?>">0</div>
            <div class="kpi-lbl-v2">Clientes Activos</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi-card-v2 kpi-green p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon-v2 icon-green"><i class="bi bi-cash-stack"></i></div>
                <span class="badge bg-success-subtle text-success rounded-pill" style="font-size:.68rem;">Vigentes</span>
            </div>
            <div class="kpi-num-v2" data-counter="<?= $stats['creditos_activos'] ?? 0 ?>">0</div>
            <div class="kpi-lbl-v2">Créditos Activos</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi-card-v2 kpi-yellow p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon-v2 icon-yellow"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <span class="badge bg-warning-subtle text-warning rounded-pill" style="font-size:.68rem;">Hoy</span>
            </div>
            <div class="kpi-num-v2" data-counter="<?= $stats['cuotas_vencer_hoy'] ?? 0 ?>">0</div>
            <div class="kpi-lbl-v2">Cuotas a Vencer Hoy</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="kpi-card-v2 kpi-cyan p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="kpi-icon-v2 icon-cyan"><i class="bi bi-graph-up-arrow"></i></div>
                <span class="badge bg-info-subtle text-info rounded-pill" style="font-size:.68rem;">Hoy</span>
            </div>
            <div class="kpi-num-v2 kpi-money" data-counter="<?= $stats['cobranza_dia'] ?? 0 ?>">$0</div>
            <div class="kpi-lbl-v2">Cobranza del Día</div>
        </div>
    </div>
</div>

<!-- ── Gráficos principales ───────────────────────────────────────────── -->
<p class="sec-label"><i class="bi bi-bar-chart-fill text-info"></i>Análisis de cartera</p>
<div class="row g-4 mb-4">
    <div class="col-12 col-xl-8">
        <div class="card bg-slate-800 border-secondary h-100">
            <div class="card-header card-header-info py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-light fs-6">
                    <i class="bi bi-bar-chart-line text-info me-2"></i>Cobranza — últimos 7 días
                </h5>
                <span class="badge bg-info-subtle text-info" style="font-size:.7rem;">7 días</span>
            </div>
            <div class="card-body">
                <canvas id="cobranzaChart" style="max-height:300px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card bg-slate-800 border-secondary h-100">
            <div class="card-header card-header-warning py-3">
                <h5 class="mb-0 fw-bold text-light fs-6">
                    <i class="bi bi-pie-chart text-warning me-2"></i>Estado de Cartera
                </h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="carteraChart" style="max-height:280px;max-width:280px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ── Actividad ──────────────────────────────────────────────────────── -->
<p class="sec-label"><i class="bi bi-activity text-success"></i>Actividad reciente</p>
<div class="row g-4">
    <!-- Capital vs Recuperado -->
    <div class="col-12 col-xl-5">
        <div class="card bg-slate-800 border-secondary h-100">
            <div class="card-header card-header-success py-3">
                <h5 class="mb-0 fw-bold text-light fs-6">
                    <i class="bi bi-currency-dollar text-success me-2"></i>Capital vs Recuperado
                </h5>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <canvas id="saldoChart" style="max-height:220px;max-width:220px;"></canvas>
                <?php if (!empty($capitalResumen) && ($capitalResumen['capital_total'] ?? 0) > 0): ?>
                <div class="row g-2 w-100 mt-4 text-center">
                    <div class="col-4">
                        <div style="font-size:.7rem;" class="text-secondary mb-1">Capital Total</div>
                        <div class="fw-bold text-light">$<?= number_format((float)$capitalResumen['capital_total'], 0, ',', '.') ?></div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:.7rem;" class="text-secondary mb-1">Recuperado</div>
                        <div class="fw-bold text-success">$<?= number_format((float)$capitalResumen['recuperado'], 0, ',', '.') ?></div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:.7rem;" class="text-secondary mb-1">Pendiente</div>
                        <div class="fw-bold text-info">$<?= number_format((float)$capitalResumen['saldo_pendiente'], 0, ',', '.') ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <div class="col-12 col-xl-7">
        <div class="card bg-slate-800 border-secondary h-100">
            <div class="card-header card-header-info py-3">
                <h5 class="mb-0 fw-bold text-light fs-6">
                    <i class="bi bi-clock-history text-info me-2"></i>Actividad Reciente
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($actividadReciente)): ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="bi bi-activity fs-2 d-block mb-2 opacity-25"></i>
                        Sin actividad reciente
                    </div>
                <?php else: ?>
                    <?php foreach ($actividadReciente as $p): ?>
                        <div class="activity-row">
                            <div class="d-flex align-items-start gap-3">
                                <div class="activity-dot"></div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-info small font-monospace"><?= $p['credito_codigo'] ?></span>
                                        <span class="badge bg-success-subtle text-success" style="font-size:.78rem;">
                                            +$<?= number_format((float)$p['monto_pagado'], 2, ',', '.') ?>
                                        </span>
                                    </div>
                                    <div class="text-light small text-truncate">
                                        <?= htmlspecialchars($p['cliente_nombre'] . ' ' . $p['cliente_apellido']) ?>
                                    </div>
                                    <div class="text-secondary" style="font-size:.7rem;">
                                        <i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($p['fecha_registro'])) ?> hs
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Chart.js ───────────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
/* ── Live Clock ──────────────────────────────────── */
(function clock() {
    const el = document.getElementById('live-clock');
    function tick() {
        const n = new Date();
        const h = String(n.getHours()).padStart(2,'0');
        const m = String(n.getMinutes()).padStart(2,'0');
        const s = String(n.getSeconds()).padStart(2,'0');
        if (el) el.textContent = `${h}:${m}:${s}`;
    }
    tick();
    setInterval(tick, 1000);
})();

/* ── Animated KPI Counters ───────────────────────── */
function animateCounter(el) {
    const target = parseFloat(el.dataset.counter) || 0;
    const isMoney = el.classList.contains('kpi-money');
    const dur = 1100;
    const start = performance.now();
    (function step(now) {
        const p = Math.min((now - start) / dur, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        const val = target * eased;
        if (isMoney) {
            el.textContent = '$' + val.toLocaleString('es-AR', {minimumFractionDigits:2, maximumFractionDigits:2});
        } else {
            el.textContent = Math.round(val).toLocaleString('es-AR');
        }
        if (p < 1) requestAnimationFrame(step);
    })(start);
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-counter]').forEach(animateCounter);

    const tooltipDefaults = {
        backgroundColor: '#1e293b',
        borderColor: 'rgba(51,65,85,0.9)',
        borderWidth: 1,
        titleColor: '#94a3b8',
        bodyColor: '#e2e8f0',
        padding: 12,
    };

    /* ── Cobranza (line) ─────────────────────────── */
    const cobranzaData = <?= json_encode($cobranzaSemanal) ?>;
    const cobranzaCtx = document.getElementById('cobranzaChart').getContext('2d');
    const grad = cobranzaCtx.createLinearGradient(0, 0, 0, 300);
    grad.addColorStop(0, 'rgba(6,182,212,0.35)');
    grad.addColorStop(1, 'rgba(6,182,212,0.02)');

    new Chart(cobranzaCtx, {
        type: 'line',
        data: {
            labels: cobranzaData.map(d => { const [,m,dd] = d.fecha.split('-'); return `${dd}/${m}`; }),
            datasets: [{
                label: 'Cobranza ($)',
                data: cobranzaData.map(d => d.total),
                borderColor: '#06b6d4',
                backgroundColor: grad,
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#06b6d4',
                pointBorderColor: '#1e293b',
                pointBorderWidth: 2,
                pointHoverRadius: 7,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { color: '#64748b', callback: v => '$' + v.toLocaleString('es-AR') }
                },
                x: { grid: { display: false }, ticks: { color: '#64748b' } }
            },
            plugins: {
                legend: { display: false },
                tooltip: { ...tooltipDefaults, callbacks: {
                    label: ctx => ` $${ctx.parsed.y.toLocaleString('es-AR', {minimumFractionDigits:2})}`
                }}
            }
        }
    });

    /* ── Cartera por Estado (donut) ──────────────── */
    const carteraData = <?= json_encode($carteraPorEstado ?? []) ?>;
    const estadoColors = { activo:'#22c55e', vencido:'#ef4444', cancelado:'#64748b', refinanciado:'#a855f7', incobrable:'#f97316' };
    if (carteraData.length > 0) {
        new Chart(document.getElementById('carteraChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: carteraData.map(r => r.estado.charAt(0).toUpperCase() + r.estado.slice(1)),
                datasets: [{
                    data: carteraData.map(r => parseInt(r.cantidad)),
                    backgroundColor: carteraData.map(r => estadoColors[r.estado] ?? '#94a3b8'),
                    borderWidth: 3,
                    borderColor: '#1e293b',
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position:'bottom', labels:{ color:'#94a3b8', boxWidth:10, padding:14, font:{size:12} } },
                    tooltip: { ...tooltipDefaults, callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed} créditos` } }
                }
            }
        });
    }

    /* ── Capital vs Recuperado (donut) ───────────── */
    const cap = <?= json_encode($capitalResumen ?? ['capital_total'=>0,'saldo_pendiente'=>0,'recuperado'=>0]) ?>;
    if (cap.capital_total > 0) {
        new Chart(document.getElementById('saldoChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Recuperado','Saldo Pendiente'],
                datasets: [{
                    data: [cap.recuperado, cap.saldo_pendiente],
                    backgroundColor: ['rgba(34,197,94,0.85)','rgba(59,130,246,0.85)'],
                    borderColor: ['#22c55e','#3b82f6'],
                    borderWidth: 2,
                    hoverOffset: 5,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { position:'bottom', labels:{ color:'#94a3b8', boxWidth:10, padding:12, font:{size:12} } },
                    tooltip: { ...tooltipDefaults, callbacks: {
                        label: ctx => ` ${ctx.label}: $${ctx.parsed.toLocaleString('es-AR', {minimumFractionDigits:2})}`
                    }}
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
