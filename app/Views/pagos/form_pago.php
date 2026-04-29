<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';

// Serializar datos del crédito y cuotas para Alpine.js
$creditoJson          = $credito ? json_encode([
    'id_credito'      => $credito->id_credito,
    'codigo'          => $credito->codigo,
    'cliente_nombre'  => $credito->cliente_nombre,
    'cliente_dni'     => $credito->cliente_dni,
    'saldo_pendiente' => $credito->saldo_pendiente,
    'estado'          => $credito->estado,
    'fecha_inicio'    => $credito->fecha_inicio,
]) : 'null';

$cuotasJson = json_encode(array_values($cuotasPendientes));

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= $appUrl ?>/pagos" class="text-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Pagos
        </a>
        <h2 class="h3 text-white fw-bold mt-1 mb-0">Registrar Pago</h2>
    </div>
</div>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<form method="POST" action="<?= $appUrl ?>/pagos/store"
      x-data="pagoForm()"
      x-init="init()"
      @submit.prevent="confirmarYEnviar()">

    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Csrf::getToken() ?>">
    <input type="hidden" name="id_credito" x-model="idCredito">

    <div class="row g-4">

        <!-- ── COLUMNA IZQUIERDA ─────────────────────────────────────────── -->
        <div class="col-12 col-xl-7">

            <!-- 1. Selección de crédito -->
            <div class="card bg-slate-800 border-secondary mb-4">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h6 class="mb-0 text-light"><i class="bi bi-cash-stack me-2 text-info"></i>Crédito a Pagar</h6>
                </div>
                <div class="card-body">

                    <!-- Crédito pre-seleccionado (viene desde ficha) -->
                    <div x-show="creditoSeleccionado">
                        <div class="d-flex align-items-center gap-3 p-3 bg-slate-700 rounded">
                            <i class="bi bi-cash-stack fs-3 text-success"></i>
                            <div class="flex-fill">
                                <div class="fw-bold text-light font-monospace" x-text="creditoSeleccionado?.codigo"></div>
                                <div class="text-secondary small" x-text="creditoSeleccionado?.cliente_nombre + ' (DNI: ' + creditoSeleccionado?.cliente_dni + ')'"></div>
                            </div>
                            <div class="text-end">
                                <div class="text-warning fw-bold">
                                    $<span x-text="fmt(creditoSeleccionado?.saldo_pendiente || 0)"></span>
                                </div>
                                <div class="text-secondary small">saldo pendiente</div>
                            </div>
                            <?php if (!$credito): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    @click="limpiarCredito()">Cambiar</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Búsqueda de crédito (si no viene pre-seleccionado) -->
                    <?php if (!$credito): ?>
                    <div x-show="!creditoSeleccionado">
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-slate-700 border-secondary text-secondary">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text"
                                   class="form-control bg-slate-700 border-secondary text-light"
                                   placeholder="Buscar por nombre, DNI o código de crédito..."
                                   x-model="busqueda"
                                   @input.debounce.400ms="buscarCredito()"
                                   @focus="mostrarResultados = true"
                                   autocomplete="off">
                        </div>
                        <div x-show="mostrarResultados && resultados.length > 0"
                             class="border border-secondary rounded bg-slate-700"
                             style="max-height: 240px; overflow-y: auto;"
                             @click.outside="mostrarResultados = false">
                            <template x-for="r in resultados" :key="r.id_credito">
                                <div class="px-3 py-2 border-bottom border-secondary"
                                     style="cursor:pointer;"
                                     @click="seleccionarCredito(r)">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold text-info font-monospace" x-text="r.codigo"></span>
                                        <span class="text-warning">$<span x-text="fmt(r.saldo_pendiente)"></span></span>
                                    </div>
                                    <div class="small text-secondary" x-text="r.cliente_nombre + ' (DNI: ' + r.cliente_dni + ')'"></div>
                                </div>
                            </template>
                        </div>
                        <div x-show="busqueda.length >= 2 && resultados.length === 0 && !buscando"
                             class="text-secondary small mt-1">Sin resultados.</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Cuotas pendientes -->
            <div class="card bg-slate-800 border-secondary mb-4" x-show="creditoSeleccionado && cuotas.length > 0">
                <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between">
                    <h6 class="mb-0 text-light"><i class="bi bi-calendar3 me-2 text-secondary"></i>Cuotas Pendientes</h6>
                    <span class="badge bg-warning text-dark" x-text="cuotas.length + ' pendiente(s)'"></span>
                </div>
                <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                    <table class="table table-dark table-sm mb-0 small">
                        <thead class="sticky-top border-secondary">
                            <tr class="text-secondary">
                                <th>#</th>
                                <th>Vencimiento</th>
                                <th class="text-end">Esperado</th>
                                <th class="text-end">Pagado</th>
                                <th class="text-end">Saldo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="q in cuotas" :key="q.id_cuota">
                                <tr :class="diasAtraso(q) > 0 ? 'text-danger' : 'text-light'">
                                    <td x-text="q.numero_cuota"></td>
                                    <td x-text="fmtFecha(q.fecha_vencimiento)"></td>
                                    <td class="text-end" x-text="'$' + fmt(q.monto_esperado)"></td>
                                    <td class="text-end" x-text="'$' + fmt(q.monto_pagado)"></td>
                                    <td class="text-end fw-bold" x-text="'$' + fmt(+q.monto_esperado + +q.monto_recargo - +q.monto_pagado)"></td>
                                    <td>
                                        <span class="badge"
                                              :class="{'bg-danger': q.estado==='vencida','bg-warning text-dark': q.estado==='parcial','bg-info text-dark': q.estado==='pendiente'}"
                                              x-text="q.estado"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. Datos del pago -->
            <div class="card bg-slate-800 border-secondary" x-show="creditoSeleccionado">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h6 class="mb-0 text-light"><i class="bi bi-wallet2 me-2 text-success"></i>Datos del Pago</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Monto -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-light">Monto a pagar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary">$</span>
                                <input type="number" name="monto_pagado" step="0.01" min="0.01" required
                                       class="form-control bg-slate-700 border-secondary text-light fw-bold"
                                       x-model="monto"
                                       @input="calcularFifo()"
                                       placeholder="0.00">
                            </div>
                            <div class="form-text text-secondary small" x-show="creditoSeleccionado">
                                Saldo: $<span x-text="fmt(creditoSeleccionado?.saldo_pendiente || 0)"></span>
                                <a href="#" class="text-info ms-2"
                                   @click.prevent="monto = creditoSeleccionado.saldo_pendiente; calcularFifo()">
                                    Pagar todo
                                </a>
                            </div>
                        </div>

                        <!-- Fecha pago real -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-light">Fecha de pago real <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_pago_real" required
                                   class="form-control bg-slate-700 border-secondary text-light"
                                   x-model="fechaPagoReal"
                                   :max="hoy">
                            <div class="form-text text-secondary small">
                                Cuándo el cliente entregó el dinero (puede ser anterior a hoy).
                            </div>
                        </div>

                        <!-- Forma de pago -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-light">Forma de pago <span class="text-danger">*</span></label>
                            <select name="forma_pago" required
                                    class="form-select bg-slate-700 border-secondary text-light"
                                    x-model="formaPago">
                                <option value="efectivo">💵 Efectivo</option>
                                <option value="transferencia">🏦 Transferencia</option>
                                <option value="mp">📱 Mercado Pago</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        <!-- Cobrador -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-light">Cobrador</label>
                            <select name="id_cobrador" class="form-select bg-slate-700 border-secondary text-light">
                                <option value="">— Sin asignar —</option>
                                <?php foreach ($personal as $p): ?>
                                    <option value="<?= $p->id_personal ?>"><?= htmlspecialchars($p->nombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Referencia (sólo si no es efectivo) -->
                        <div class="col-12" x-show="formaPago !== 'efectivo'">
                            <label class="form-label text-light">Referencia / Nro de comprobante</label>
                            <input type="text" name="referencia_externa"
                                   class="form-control bg-slate-700 border-secondary text-light"
                                   placeholder="Nro de transferencia, CBU, etc.">
                        </div>

                        <!-- Observaciones -->
                        <div class="col-12">
                            <label class="form-label text-light">Observaciones</label>
                            <textarea name="observaciones" rows="2"
                                      class="form-control bg-slate-700 border-secondary text-light"
                                      placeholder="Notas internas (opcional)..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── COLUMNA DERECHA: Preview FIFO ─────────────────────────────── -->
        <div class="col-12 col-xl-5" x-show="creditoSeleccionado">
            <div class="card bg-slate-800 border-secondary sticky-top" style="top: 16px;">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h6 class="mb-0 text-light">
                        <i class="bi bi-lightning-fill me-2 text-warning"></i>Preview de Aplicación FIFO
                    </h6>
                </div>
                <div class="card-body">

                    <!-- Sin monto -->
                    <div x-show="!monto || parseFloat(monto) <= 0" class="text-secondary small text-center py-3">
                        Ingresá el monto para ver cómo se distribuirá.
                    </div>

                    <!-- Con monto y aplicaciones -->
                    <div x-show="parseFloat(monto) > 0 && fifoAplicaciones.length > 0">
                        <div class="table-responsive">
                            <table class="table table-dark table-sm mb-0 small">
                                <thead class="border-secondary">
                                    <tr class="text-secondary">
                                        <th>#</th>
                                        <th>Venc.</th>
                                        <th class="text-end">Aplicado</th>
                                        <th class="text-end">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="ap in fifoAplicaciones" :key="ap.id_cuota">
                                        <tr>
                                            <td class="text-secondary" x-text="ap.numero_cuota"></td>
                                            <td class="text-light" x-text="fmtFecha(ap.fecha_vencimiento)"></td>
                                            <td class="text-end text-success fw-bold" x-text="'$' + fmt(ap.monto_aplicado)"></td>
                                            <td class="text-end"
                                                :class="ap.saldo_nuevo <= 0 ? 'text-success' : 'text-warning'"
                                                x-text="ap.saldo_nuevo <= 0 ? '✓ Pagada' : '$' + fmt(ap.saldo_nuevo)">
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Resumen -->
                        <div class="bg-slate-700 rounded p-3 mt-3 small">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Monto ingresado:</span>
                                <span class="text-light fw-bold">$<span x-text="fmt(parseFloat(monto) || 0)"></span></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Cuotas afectadas:</span>
                                <span class="text-light" x-text="fifoAplicaciones.length"></span>
                            </div>
                            <div class="d-flex justify-content-between border-top border-secondary pt-2 mt-1"
                                 x-show="montoRestante > 0.005">
                                <span class="text-warning">Sin asignar:</span>
                                <span class="text-warning fw-bold">$<span x-text="fmt(montoRestante)"></span></span>
                            </div>
                            <div class="d-flex justify-content-between border-top border-secondary pt-2 mt-1">
                                <span class="text-secondary">Saldo tras pago:</span>
                                <span :class="saldoTrasP <= 0.005 ? 'text-success fw-bold' : 'text-warning'"
                                      x-text="saldoTrasP <= 0.005 ? '✓ CRÉDITO CANCELADO' : '$' + fmt(saldoTrasP)">
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Monto mayor al saldo -->
                    <div x-show="parseFloat(monto) > 0 && parseFloat(monto) > (creditoSeleccionado?.saldo_pendiente || 0)"
                         class="alert alert-danger mt-2 small mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        El monto supera el saldo pendiente.
                    </div>
                </div>

                <!-- Botón confirmar -->
                <div class="card-footer border-secondary bg-transparent">
                    <button type="submit" class="btn btn-success w-100 btn-lg"
                            :disabled="!formularioValido() || submitting">
                        <span x-show="!submitting">
                            <i class="bi bi-check-circle me-2"></i> Confirmar Pago
                        </span>
                        <span x-show="submitting">
                            <span class="spinner-border spinner-border-sm me-2"></span> Registrando...
                        </span>
                    </button>
                    <a href="<?= $appUrl ?>/creditos<?= $credito ? '/ficha?id=' . $credito->id_credito : '' ?>"
                       class="btn btn-outline-secondary w-100 mt-2">Cancelar</a>
                </div>
            </div>
        </div>

    </div><!-- /.row -->

    <!-- Modal de confirmación -->
    <div class="modal fade" id="modalConfirmar" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content bg-slate-800 border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-light">
                        <i class="bi bi-check-circle me-2 text-success"></i>Confirmar Pago
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="text-light">
                        Está por registrar un pago de
                        <strong class="text-success">$<span x-text="fmt(parseFloat(monto) || 0)"></span></strong>
                        para el crédito <strong class="text-info font-monospace" x-text="creditoSeleccionado?.codigo"></strong>.
                    </p>
                    <div x-show="saldoTrasP <= 0.005" class="alert alert-success py-2 small">
                        <i class="bi bi-trophy me-2"></i> Este pago cancelará el crédito completamente.
                    </div>
                    <p class="text-secondary small mb-0">Esta acción puede revertirse con anulación de pago.</p>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                    <button type="button" class="btn btn-success" @click="enviarFormulario()">
                        <i class="bi bi-check-lg me-1"></i> Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function pagoForm() {
    return {
        // Crédito
        idCredito: <?= $credito ? $credito->id_credito : 'null' ?>,
        creditoSeleccionado: <?= $creditoJson ?>,
        cuotas: <?= $cuotasJson ?>,

        // Búsqueda (sólo si no hay crédito pre-seleccionado)
        busqueda: '',
        resultados: [],
        mostrarResultados: false,
        buscando: false,

        // Pago
        monto: '',
        fechaPagoReal: '',
        formaPago: 'efectivo',
        hoy: new Date().toISOString().split('T')[0],

        // FIFO
        fifoAplicaciones: [],
        montoRestante: 0,
        saldoTrasP: 0,

        submitting: false,

        init() {
            this.fechaPagoReal = this.hoy;
            if (this.creditoSeleccionado) {
                this.saldoTrasP = this.creditoSeleccionado.saldo_pendiente;
            }
        },

        calcularFifo() {
            const m = parseFloat(this.monto) || 0;
            if (m <= 0) {
                this.fifoAplicaciones = [];
                this.montoRestante = 0;
                this.saldoTrasP = this.creditoSeleccionado?.saldo_pendiente || 0;
                return;
            }

            let restante = m;
            const aplicaciones = [];

            for (const q of this.cuotas) {
                if (restante <= 0.005) break;
                const saldo = parseFloat(q.monto_esperado) + parseFloat(q.monto_recargo) - parseFloat(q.monto_pagado);
                if (saldo <= 0.005) continue;
                const aplicar = Math.min(saldo, restante);
                aplicaciones.push({
                    id_cuota:           q.id_cuota,
                    numero_cuota:       q.numero_cuota,
                    fecha_vencimiento:  q.fecha_vencimiento,
                    monto_esperado:     parseFloat(q.monto_esperado),
                    monto_aplicado:     Math.round(aplicar * 100) / 100,
                    saldo_nuevo:        Math.round((saldo - aplicar) * 100) / 100,
                });
                restante = Math.round((restante - aplicar) * 100) / 100;
            }

            this.fifoAplicaciones = aplicaciones;
            this.montoRestante    = restante;
            this.saldoTrasP       = Math.max(0, Math.round(((this.creditoSeleccionado?.saldo_pendiente || 0) - m) * 100) / 100);
        },

        async buscarCredito() {
            if (this.busqueda.length < 2) { this.resultados = []; return; }
            this.buscando = true;
            try {
                // Buscar primero por cliente, luego unir con créditos activos
                const r1 = await fetch(`${APP_URL}/api/clientes/buscar?q=${encodeURIComponent(this.busqueda)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const j1 = await r1.json();
                if (!j1.ok || !j1.data.length) { this.resultados = []; return; }

                // Para cada cliente, buscar créditos activos
                const creditos = [];
                for (const cl of j1.data.slice(0, 5)) {
                    const r2 = await fetch(`${APP_URL}/api/creditos/activos_cliente?id_cliente=${cl.id_cliente}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const j2 = await r2.json();
                    if (j2.ok) {
                        for (const cr of j2.data.creditos) {
                            creditos.push({
                                ...cr,
                                cliente_nombre: cl.nombre,
                                cliente_dni:    cl.dni,
                            });
                        }
                    }
                }
                this.resultados = creditos;
                this.mostrarResultados = true;
            } catch (e) {
                this.resultados = [];
            } finally {
                this.buscando = false;
            }
        },

        async seleccionarCredito(r) {
            this.mostrarResultados = false;
            // Cargar cuotas pendientes via AJAX
            try {
                const res  = await fetch(`${APP_URL}/api/pagos/cuotas_credito?id_credito=${r.id_credito}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                if (json.ok) {
                    this.creditoSeleccionado = json.data.credito;
                    this.cuotas = json.data.cuotas;
                    this.idCredito = r.id_credito;
                    this.saldoTrasP = json.data.credito.saldo_pendiente;
                }
            } catch (e) {
                console.error(e);
            }
        },

        limpiarCredito() {
            this.creditoSeleccionado = null;
            this.idCredito = null;
            this.cuotas = [];
            this.busqueda = '';
            this.resultados = [];
            this.fifoAplicaciones = [];
            this.monto = '';
        },

        diasAtraso(q) {
            const hoy  = new Date(); hoy.setHours(0,0,0,0);
            const venc = new Date(q.fecha_vencimiento + 'T00:00:00');
            return hoy > venc ? Math.floor((hoy - venc) / 86400000) : 0;
        },

        formularioValido() {
            const m = parseFloat(this.monto) || 0;
            return this.idCredito
                && m > 0
                && m <= (this.creditoSeleccionado?.saldo_pendiente || 0) + 0.005
                && this.fechaPagoReal !== ''
                && this.formaPago !== '';
        },

        confirmarYEnviar() {
            if (!this.formularioValido()) return;
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmar'));
            modal.show();
        },

        enviarFormulario() {
            this.submitting = true;
            bootstrap.Modal.getInstance(document.getElementById('modalConfirmar'))?.hide();
            this.$el.submit();
        },

        fmt(n) {
            return parseFloat(n || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        fmtFecha(f) {
            if (!f) return '';
            const [y, m, d] = f.split('-');
            return `${d}/${m}/${y}`;
        },
    };
}
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
