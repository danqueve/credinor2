<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();
?>

<div class="d-flex align-items-center mb-4 gap-3">
    <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $credito->id_credito ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h2 class="h3 text-white fw-bold mb-0">
            <i class="bi bi-arrow-repeat text-warning me-2"></i>Refinanciar Crédito
        </h2>
        <span class="text-secondary small">Origen: <?= htmlspecialchars($credito->codigo) ?></span>
    </div>
</div>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<div class="row g-4">
    <!-- Info del crédito original -->
    <div class="col-12 col-lg-4">
        <div class="card bg-slate-800 border-warning">
            <div class="card-header bg-transparent border-warning">
                <h6 class="mb-0 text-warning"><i class="bi bi-info-circle me-2"></i>Crédito Original</h6>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item bg-transparent border-secondary d-flex justify-content-between text-light">
                    <span class="text-secondary">Código</span>
                    <span class="font-monospace"><?= htmlspecialchars($credito->codigo) ?></span>
                </li>
                <li class="list-group-item bg-transparent border-secondary d-flex justify-content-between text-light">
                    <span class="text-secondary">Capital original</span>
                    <span>$<?= number_format($credito->capital, 2, ',', '.') ?></span>
                </li>
                <li class="list-group-item bg-transparent border-secondary d-flex justify-content-between text-light">
                    <span class="text-secondary">Saldo pendiente</span>
                    <span class="text-warning fw-bold">$<?= number_format($credito->saldo_pendiente, 2, ',', '.') ?></span>
                </li>
                <li class="list-group-item bg-transparent border-secondary d-flex justify-content-between text-light">
                    <span class="text-secondary">Frecuencia</span>
                    <span><?= ucfirst($credito->frecuencia) ?></span>
                </li>
            </ul>
            <div class="card-body pt-2">
                <div class="alert alert-warning py-2 small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    El saldo de <strong>$<?= number_format($credito->saldo_pendiente, 2, ',', '.') ?></strong>
                    se usará como capital del nuevo crédito.
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario nuevo crédito -->
    <div class="col-12 col-lg-8">
        <div class="card bg-slate-800 border-secondary">
            <div class="card-header bg-transparent border-secondary">
                <h5 class="mb-0 text-light"><i class="bi bi-plus-circle me-2 text-success"></i>Condiciones del Nuevo Crédito</h5>
            </div>
            <div class="card-body" x-data="refinanciarForm()">
                <form method="POST" action="<?= $appUrl ?>/creditos/refinanciar" @submit.prevent="validar($el)">
                    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Csrf::getToken() ?>">
                    <input type="hidden" name="id_credito" value="<?= $credito->id_credito ?>">

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label text-light">Capital (saldo a refinanciar)</label>
                            <input type="text" class="form-control bg-slate-700 border-secondary text-warning fw-bold"
                                   value="$<?= number_format($credito->saldo_pendiente, 2, ',', '.') ?>" disabled>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light">Frecuencia <span class="text-danger">*</span></label>
                            <select name="frecuencia" class="form-select bg-slate-700 border-secondary text-light" required x-model="frecuencia">
                                <option value="diaria">Diaria</option>
                                <option value="semanal" selected>Semanal</option>
                                <option value="quincenal">Quincenal</option>
                                <option value="mensual">Mensual</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light">Cantidad de cuotas <span class="text-danger">*</span></label>
                            <input type="number" name="cantidad_cuotas" class="form-control bg-slate-700 border-secondary text-light"
                                   min="1" max="360" required x-model.number="cantidadCuotas">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light">Valor de cada cuota <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary">$</span>
                                <input type="number" name="valor_cuota" class="form-control bg-slate-700 border-secondary text-light"
                                       min="0.01" step="0.01" required x-model.number="valorCuota">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light">Fecha primera cuota <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_inicio" class="form-control bg-slate-700 border-secondary text-light"
                                   value="<?= date('Y-m-d') ?>" required x-model="fechaInicio">
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light">Gastos admin</label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary">$</span>
                                <input type="number" name="gastos_admin" class="form-control bg-slate-700 border-secondary text-light"
                                       min="0" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light">Cobrador</label>
                            <select name="id_cobrador" class="form-select bg-slate-700 border-secondary text-light">
                                <option value="">— Sin asignar —</option>
                                <?php foreach ($personal as $p): ?>
                                    <option value="<?= $p->id_personal ?>"
                                        <?= $p->id_personal === $credito->id_cobrador ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p->nombre) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-light">Vendedor</label>
                            <select name="id_vendedor" class="form-select bg-slate-700 border-secondary text-light">
                                <option value="">— Sin asignar —</option>
                                <?php foreach ($personal as $p): ?>
                                    <option value="<?= $p->id_personal ?>"
                                        <?= $p->id_personal === $credito->id_vendedor ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p->nombre) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-light">Observaciones</label>
                            <input type="text" name="observaciones" class="form-control bg-slate-700 border-secondary text-light"
                                   placeholder="Motivo de la refinanciación...">
                        </div>
                    </div>

                    <!-- Preview en vivo -->
                    <div class="card bg-slate-900 border-info mt-4" x-show="cantidadCuotas > 0 && valorCuota > 0">
                        <div class="card-body py-3">
                            <div class="row text-center g-2">
                                <div class="col-3">
                                    <div class="small text-secondary">Capital</div>
                                    <div class="fw-bold text-warning">$<?= number_format($credito->saldo_pendiente, 2, ',', '.') ?></div>
                                </div>
                                <div class="col-3">
                                    <div class="small text-secondary">Total a devolver</div>
                                    <div class="fw-bold text-light" x-text="'$' + fmt(cantidadCuotas * valorCuota)"></div>
                                </div>
                                <div class="col-3">
                                    <div class="small text-secondary">Interés implícito</div>
                                    <div class="fw-bold text-warning" x-text="interesPct() + '%'"></div>
                                </div>
                                <div class="col-3">
                                    <div class="small text-secondary">Cuotas</div>
                                    <div class="fw-bold text-info" x-text="cantidadCuotas + ' × $' + fmt(valorCuota)"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-arrow-repeat me-1"></i> Confirmar Refinanciación
                        </button>
                        <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $credito->id_credito ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function refinanciarForm() {
    return {
        cantidadCuotas: 0,
        valorCuota: 0,
        frecuencia: 'semanal',
        fechaInicio: '<?= date('Y-m-d') ?>',
        capital: <?= $credito->saldo_pendiente ?>,
        fmt(n) {
            return Number(n).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        interesPct() {
            if (this.capital <= 0) return '0.00';
            const total = this.cantidadCuotas * this.valorCuota;
            const interes = total - this.capital;
            return ((interes / this.capital) * 100).toFixed(2);
        },
        validar(form) {
            form.submit();
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
