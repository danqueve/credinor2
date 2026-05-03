<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
$soloOperativo = (bool)($tienePagos ?? false);
$personalOption = static fn($p, ?int $selected): string =>
    '<option value="' . (int)$p->id_personal . '"' . ($selected === (int)$p->id_personal ? ' selected' : '') . '>' .
    htmlspecialchars($p->nombre) . '</option>';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $credito->id_credito ?>" class="text-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> <?= htmlspecialchars($credito->codigo) ?>
        </a>
        <h2 class="h3 text-white fw-bold mt-1 mb-0">Editar Credito</h2>
        <?php if ($cliente): ?>
            <div class="text-secondary small mt-1">
                <?= htmlspecialchars($cliente->nombre) ?> - DNI <?= htmlspecialchars($cliente->dni) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<?php if ($soloOperativo): ?>
    <div class="alert alert-warning">
        <i class="bi bi-lock me-2"></i>
        Este credito ya tiene pagos registrados. Para cuidar el historial contable, solo se pueden editar vendedor,
        cobrador, destino y observaciones.
    </div>
<?php endif; ?>

<form method="POST" action="<?= $appUrl ?>/creditos/update" x-data="editarCreditoForm()" x-init="calcular()">
    <?= \App\Helpers\Csrf::getFormField() ?>
    <input type="hidden" name="id_credito" value="<?= $credito->id_credito ?>">

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <div class="card bg-slate-800 border-secondary mb-4">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h6 class="mb-0 text-light"><i class="bi bi-calculator me-2 text-success"></i>Datos financieros</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label text-light">Capital prestado</label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary">$</span>
                                <input type="number" name="capital" step="0.01" min="1" required
                                       class="form-control bg-slate-700 border-secondary text-light"
                                       x-model.number="capital" @input="calcular()"
                                       value="<?= htmlspecialchars((string)$credito->capital) ?>"
                                       <?= $soloOperativo ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label text-light">Cantidad de cuotas</label>
                            <input type="number" name="cantidad_cuotas" min="1" required
                                   class="form-control bg-slate-700 border-secondary text-light"
                                   x-model.number="cantidadCuotas" @input="calcular()"
                                   value="<?= $credito->cantidad_cuotas ?>"
                                   <?= $soloOperativo ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label text-light">Valor de cuota</label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary">$</span>
                                <input type="number" name="valor_cuota" step="0.01" min="0.01" required
                                       class="form-control bg-slate-700 border-secondary text-light"
                                       x-model.number="valorCuota" @input="calcular()"
                                       value="<?= htmlspecialchars((string)$credito->valor_cuota) ?>"
                                       <?= $soloOperativo ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label text-light">Gastos administrativos</label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary">$</span>
                                <input type="number" name="gastos_admin" step="0.01" min="0"
                                       class="form-control bg-slate-700 border-secondary text-light"
                                       x-model.number="gastosAdmin" @input="calcular()"
                                       value="<?= htmlspecialchars((string)$credito->gastos_admin) ?>"
                                       <?= $soloOperativo ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label text-light">Frecuencia</label>
                            <select name="frecuencia" class="form-select bg-slate-700 border-secondary text-light"
                                    x-model="frecuencia" <?= $soloOperativo ? 'readonly disabled' : '' ?>>
                                <?php foreach (['diaria' => 'Diaria', 'semanal' => 'Semanal', 'quincenal' => 'Quincenal', 'mensual' => 'Mensual'] as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $credito->frecuencia === $value ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($soloOperativo): ?>
                                <input type="hidden" name="frecuencia" value="<?= htmlspecialchars($credito->frecuencia) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label text-light">Fecha primera cuota</label>
                            <input type="date" name="fecha_inicio" required
                                   class="form-control bg-slate-700 border-secondary text-light"
                                   value="<?= htmlspecialchars($credito->fecha_inicio) ?>"
                                   <?= $soloOperativo ? 'readonly' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-slate-800 border-secondary">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h6 class="mb-0 text-light"><i class="bi bi-person-badge me-2 text-info"></i>Personal y notas</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Vendedor</label>
                            <select name="id_vendedor" class="form-select bg-slate-700 border-secondary text-light">
                                <option value="">- Sin asignar -</option>
                                <?php foreach ($personal as $p): ?>
                                    <?= $personalOption($p, $credito->id_vendedor) ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label text-light">Cobrador</label>
                            <select name="id_cobrador" class="form-select bg-slate-700 border-secondary text-light">
                                <option value="">- Sin asignar -</option>
                                <?php foreach ($personal as $p): ?>
                                    <?= $personalOption($p, $credito->id_cobrador) ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-light">Destino del prestamo</label>
                            <input type="text" name="destino_opcional"
                                   class="form-control bg-slate-700 border-secondary text-light"
                                   value="<?= htmlspecialchars($credito->destino_opcional ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-light">Observaciones</label>
                            <textarea name="observaciones" rows="3"
                                      class="form-control bg-slate-700 border-secondary text-light"><?= htmlspecialchars($credito->observaciones ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card bg-slate-800 border-secondary sticky-top" style="top: 16px;">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h6 class="mb-0 text-light"><i class="bi bi-bar-chart-line me-2 text-warning"></i>Resumen</h6>
                </div>
                <div class="card-body">
                    <div class="bg-slate-700 rounded p-3 font-monospace small text-light mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Total a devolver:</span>
                            <span class="text-warning">$<span x-text="fmt(montoTotal)"></span></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Interes implicito:</span>
                            <span class="text-info">$<span x-text="fmt(interesImplicito)"></span></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Interes %:</span>
                            <span class="text-info"><span x-text="interesPct"></span>%</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-save me-2"></i>Guardar cambios
                    </button>
                    <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $credito->id_credito ?>" class="btn btn-outline-secondary w-100 mt-2">
                        Cancelar
                    </a>
                </div>
                <?php if (!$soloOperativo): ?>
                    <div class="card-footer border-secondary bg-transparent text-secondary small">
                        Al guardar, se recalcula el calendario de cuotas porque el credito todavia no tiene pagos.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<script>
function editarCreditoForm() {
    return {
        capital: <?= json_encode($credito->capital) ?>,
        cantidadCuotas: <?= json_encode($credito->cantidad_cuotas) ?>,
        valorCuota: <?= json_encode($credito->valor_cuota) ?>,
        gastosAdmin: <?= json_encode($credito->gastos_admin) ?>,
        frecuencia: <?= json_encode($credito->frecuencia) ?>,
        montoTotal: 0,
        interesImplicito: 0,
        interesPct: 0,
        calcular() {
            const cap = parseFloat(this.capital) || 0;
            const n = parseInt(this.cantidadCuotas) || 0;
            const cuota = parseFloat(this.valorCuota) || 0;
            const gastos = parseFloat(this.gastosAdmin) || 0;
            this.montoTotal = +(n * cuota).toFixed(2);
            this.interesImplicito = +(this.montoTotal - cap - gastos).toFixed(2);
            this.interesPct = cap > 0 ? +((this.interesImplicito / cap) * 100).toFixed(2) : 0;
        },
        fmt(n) {
            return parseFloat(n || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
