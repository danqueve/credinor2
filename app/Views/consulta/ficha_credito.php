<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();

$cuotasPagadas  = count(array_filter($credito->cuotas, fn($q) => $q->estado === 'pagada'));
$cuotasVencidas = count(array_filter($credito->cuotas, fn($q) => $q->estado === 'vencida'));
$proxCuota      = null;
foreach ($credito->cuotas as $q) {
    if (in_array($q->estado, ['pendiente', 'parcial', 'vencida'])) {
        $proxCuota = $q;
        break;
    }
}
$porcentaje = $credito->monto_total > 0
    ? round((($credito->monto_total - $credito->saldo_pendiente) / $credito->monto_total) * 100, 1)
    : 0;
$canRegistrarVisita = ($_SESSION['usuario_rol'] ?? '') !== 'supervisor';

// Armar mensaje WhatsApp
$msgWa = '';
if ($cliente && $proxCuota) {
    $visitaProx = isset($visitas[$proxCuota->id_cuota]) ? $visitas[$proxCuota->id_cuota] : null;
    $msgWa = urlencode(
        'Hola ' . $cliente->nombre . ', te recordamos que tu cuota #' . $proxCuota->numero_cuota .
        ' de $' . number_format($proxCuota->monto_esperado, 0, ',', '.') .
        ' (crédito ' . $credito->codigo . ') vence el ' .
        date('d/m/Y', strtotime($proxCuota->fecha_vencimiento)) . '. — Credinor'
    );
}
?>

<style>
.credito-sticky-header {
    position: sticky;
    top: 50px;
    z-index: 50;
    background: rgba(15,23,42,0.97);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border-radius: 0 0 12px 12px;
    margin: 0 -12px 12px;
    padding: 0 12px;
}
.credito-sticky-header .card { border-radius: 0 0 12px 12px; }
.cuota-chip {
    width: 36px; height: 36px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 0.7rem; font-weight: 700; cursor: default;
    flex-shrink: 0;
}
.cuota-chip.pagada   { background: rgba(34,197,94,.25);  color: #4ade80; }
.cuota-chip.vencida  { background: rgba(239,68,68,.25);  color: #f87171; }
.cuota-chip.parcial  { background: rgba(251,191,36,.25); color: #fbbf24; }
.cuota-chip.pendiente{ background: rgba(100,116,139,.2); color: #94a3b8; }
</style>

<!-- Header crédito -->
<div class="credito-sticky-header">
<div class="card bg-slate-800 border-0 p-3">
    <?php if ($cliente): ?>
    <a href="<?= $appUrl ?>/consulta/cliente?id=<?= $cliente->id_cliente ?>"
       class="text-secondary small text-decoration-none mb-1 d-block">
        <i class="bi bi-arrow-left me-1"></i><?= htmlspecialchars($cliente->nombre) ?>
    </a>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="font-monospace text-light fw-bold"><?= htmlspecialchars($credito->codigo) ?></span>
            <span class="badge bg-<?= $credito->estadoBadge() ?> ms-2"><?= $credito->estadoLabel() ?></span>
        </div>
        <div class="fw-bold text-warning fs-5">$<?= number_format($credito->saldo_pendiente, 0, ',', '.') ?></div>
    </div>

    <!-- Progreso -->
    <div class="mt-2">
        <div class="d-flex justify-content-between small text-secondary mb-1">
            <span><?= $cuotasPagadas ?>/<?= $credito->cantidad_cuotas ?> cuotas pagadas</span>
            <span><?= $porcentaje ?>%</span>
        </div>
        <div class="progress" style="height: 8px; border-radius: 6px;">
            <div class="progress-bar bg-success" style="width: <?= $porcentaje ?>%; border-radius: 6px;"></div>
        </div>
    </div>
</div>
</div><!-- /credito-sticky-header -->

<!-- Próxima cuota + WhatsApp -->
<?php if ($proxCuota): ?>
<div class="card border-0 p-3 mb-3 <?= $proxCuota->estado === 'vencida' ? 'bg-danger bg-opacity-25' : 'bg-info bg-opacity-10' ?>">
    <div class="small text-secondary mb-1">Próxima cuota</div>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <span class="fw-bold text-light">Cuota #<?= $proxCuota->numero_cuota ?></span>
            <div class="small text-secondary"><?= date('d/m/Y', strtotime($proxCuota->fecha_vencimiento)) ?></div>
        </div>
        <div class="text-end">
            <div class="fw-bold fs-5 <?= $proxCuota->estado === 'vencida' ? 'text-danger' : 'text-info' ?>">
                $<?= number_format($proxCuota->monto_esperado, 0, ',', '.') ?>
            </div>
            <span class="badge bg-<?= $proxCuota->estadoBadge() ?>"><?= $proxCuota->estadoLabel() ?></span>
        </div>
    </div>

    <!-- Acciones -->
    <div class="d-flex gap-2 mt-3 flex-wrap">
        <?php if ($cliente && $cliente->telefono): ?>
        <a href="tel:<?= htmlspecialchars($cliente->telefono) ?>" class="btn btn-outline-success flex-fill btn-sm">
            <i class="bi bi-telephone me-1"></i> Llamar
        </a>
        <a href="https://wa.me/549<?= preg_replace('/\D/', '', $cliente->telefono) ?>?text=<?= $msgWa ?>"
           class="btn btn-success flex-fill btn-sm" target="_blank" rel="noopener">
            <i class="bi bi-whatsapp me-1"></i> WA
        </a>
        <?php endif; ?>
        <?php if ($canRegistrarVisita): ?>
            <button type="button" class="btn btn-outline-light flex-fill btn-sm" data-bs-toggle="modal" data-bs-target="#modalVisita">
                <i class="bi bi-geo-alt"></i> Visita
            </button>
        <?php endif; ?>
    </div>

    <?php if (isset($visitaProx) && $visitaProx): ?>
    <div class="mt-3 small px-2 py-2 bg-dark bg-opacity-25 rounded text-secondary border border-secondary border-opacity-25">
        <div class="d-flex align-items-center mb-1">
            <i class="bi bi-info-circle me-1"></i> 
            <span class="text-light fw-semibold"><?= ucfirst(str_replace('_', ' ', $visitaProx['resultado'])) ?></span> 
            <span class="ms-1">— el <?= date('d/m', strtotime($visitaProx['fecha'])) ?></span>
        </div>
        <?php if ($visitaProx['observaciones']): ?>
            <div class="text-secondary opacity-75 fst-italic lh-sm" style="font-size: 0.75rem;">
                "<?= htmlspecialchars($visitaProx['observaciones']) ?>"
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Resumen -->
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="card bg-slate-800 border-0 text-center p-2">
            <div class="small text-secondary">Pagadas</div>
            <div class="fw-bold text-success"><?= $cuotasPagadas ?></div>
        </div>
    </div>
    <div class="col-4">
        <div class="card bg-slate-800 border-0 text-center p-2">
            <div class="small text-secondary">Vencidas</div>
            <div class="fw-bold <?= $cuotasVencidas > 0 ? 'text-danger' : 'text-secondary' ?>"><?= $cuotasVencidas ?></div>
        </div>
    </div>
    <div class="col-4">
        <div class="card bg-slate-800 border-0 text-center p-2">
            <div class="small text-secondary">Frecuencia</div>
            <div class="fw-bold text-info small"><?= ucfirst($credito->frecuencia) ?></div>
        </div>
    </div>
</div>

<!-- Calendario de cuotas — chips agrupados por mes -->
<h6 class="text-secondary text-uppercase mb-3" style="font-size: 0.7rem; letter-spacing: .08em;">
    Cuotas
</h6>

<?php
$cuotasPorMes = [];
foreach ($credito->cuotas as $q) {
    $mes = substr($q->fecha_vencimiento, 0, 7); // YYYY-MM
    $cuotasPorMes[$mes][] = $q;
}
$mesesEs = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun',
            '07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
?>
<div class="d-flex flex-column gap-3">
<?php foreach ($cuotasPorMes as $ym => $cuotas): ?>
    <?php [$anio, $mes] = explode('-', $ym); ?>
    <div class="card bg-slate-800 border-0 p-3">
        <div class="small text-secondary mb-2 fw-semibold">
            <?= ($mesesEs[$mes] ?? $mes) . ' ' . $anio ?>
        </div>
        <div class="d-flex flex-wrap gap-2">
        <?php foreach ($cuotas as $q): ?>
            <div class="cuota-chip <?= $q->estado ?>"
                 data-bs-toggle="tooltip"
                 data-bs-placement="top"
                 title="Cuota #<?= $q->numero_cuota ?> · <?= date('d/m/Y', strtotime($q->fecha_vencimiento)) ?> · $<?= number_format($q->monto_esperado, 0, ',', '.') ?> · <?= $q->estadoLabel() ?>">
                <?= $q->numero_cuota ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<!-- Últimos pagos -->
<?php if (!empty($pagos)): ?>
<h6 class="text-secondary text-uppercase mt-3 mb-2" style="font-size: 0.7rem; letter-spacing: .08em;">
    Últimos Pagos
</h6>
<div class="d-flex flex-column gap-1">
<?php foreach (array_slice($pagos, 0, 5) as $p): ?>
    <?php if (!$p->anulado): ?>
    <div class="d-flex justify-content-between align-items-center bg-slate-800 rounded-3 px-3 py-2">
        <div>
            <div class="text-light small"><?= date('d/m/Y', strtotime($p->fecha_pago_real)) ?></div>
            <div class="text-secondary" style="font-size: 0.68rem;"><?= $p->formasPagoLabel() ?></div>
        </div>
        <div class="fw-bold text-success small">$<?= number_format($p->monto_pagado, 0, ',', '.') ?></div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, { trigger: 'click' });
});
</script>

<?php if ($proxCuota && $canRegistrarVisita): ?>
<!-- Modal Visita -->
<div class="modal fade" id="modalVisita" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-slate-800 text-light border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fs-6">Registrar Visita</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formVisita" method="POST" action="<?= $appUrl ?>/api/consulta/visita" x-data="visitaForm()">
                    <input type="hidden" name="id_cuota" value="<?= $proxCuota->id_cuota ?>">
                    <input type="hidden" name="geo_lat" x-model="lat">
                    <input type="hidden" name="geo_lng" x-model="lng">
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Resultado de la visita</label>
                        <select name="resultado" class="form-select bg-dark text-light border-secondary" required>
                            <option value="">Seleccionar...</option>
                            <option value="intentada">Intentada (sin éxito)</option>
                            <option value="no_contesta">No contesta</option>
                            <option value="promesa">Promesa de pago</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary small">Observaciones (opcional)</label>
                        <textarea name="observaciones" class="form-control bg-dark text-light border-secondary" rows="2" placeholder="Ej: Vuelvo mañana a las 18hs"></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 text-secondary small" x-show="locating">
                        <span><i class="bi bi-geo-alt-fill"></i> Obteniendo ubicación...</span>
                        <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                    </div>
                    
                    <button type="submit" class="btn btn-info w-100" :disabled="submitting || locating">
                        <span x-show="!submitting">Guardar Visita</span>
                        <span x-show="submitting"><div class="spinner-border spinner-border-sm"></div> Guardando...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function visitaForm() {
    return {
        lat: '',
        lng: '',
        locating: false,
        submitting: false,
        init() {
            if ("geolocation" in navigator) {
                this.locating = true;
                navigator.geolocation.getCurrentPosition(pos => {
                    this.lat = pos.coords.latitude;
                    this.lng = pos.coords.longitude;
                    this.locating = false;
                }, err => {
                    this.locating = false;
                }, { timeout: 10000 });
            }
            
            const form = document.getElementById('formVisita');
            if (form) {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    this.submitting = true;
                    try {
                        let fd = new FormData(e.target);
                        let res = await fetch(e.target.action, {
                            method: 'POST',
                            body: fd
                        });
                        let data = await res.json();
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(data.errors ? data.errors.join('\\n') : 'Error al guardar');
                            this.submitting = false;
                        }
                    } catch (err) {
                        alert('Error de conexión');
                        this.submitting = false;
                    }
                });
            }
        }
    }
}
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base_mobile.php';
?>
