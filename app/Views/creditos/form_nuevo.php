<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= $appUrl ?>/creditos" class="text-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Créditos
        </a>
        <h2 class="h3 text-white fw-bold mt-1 mb-0">Nuevo Crédito</h2>
    </div>
</div>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<form method="POST" action="<?= $appUrl ?>/creditos/store"
      x-data="creditoForm()"
      x-init="init()"
      @submit="submitting = true">

    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Csrf::getToken() ?>">
    <input type="hidden" name="id_cliente" x-model="idCliente">

    <div class="row g-4">

        <!-- ── COLUMNA IZQUIERDA: Datos del crédito ──────────────────────── -->
        <div class="col-12 col-xl-7">

            <!-- 1. Selección de cliente -->
            <div class="card bg-slate-800 border-secondary mb-4">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h6 class="mb-0 text-light"><i class="bi bi-person-fill me-2 text-info"></i>Cliente</h6>
                </div>
                <div class="card-body">
                    <?php if ($clientePreseleccionado): ?>
                        <!-- Cliente ya seleccionado (venimos desde ficha de cliente) -->
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-slate-700 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                <i class="bi bi-person fs-4 text-secondary"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-light"><?= htmlspecialchars($clientePreseleccionado->nombre) ?></div>
                                <div class="text-secondary small">DNI: <?= htmlspecialchars($clientePreseleccionado->dni) ?></div>
                            </div>
                            <a href="<?= $appUrl ?>/creditos/nuevo" class="btn btn-sm btn-outline-secondary ms-auto">
                                Cambiar
                            </a>
                        </div>
                        <!-- Aviso si tiene créditos activos -->
                        <?php if (!empty($creditosActivos)): ?>
                            <div class="alert alert-warning mt-3 mb-0 small">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Atención:</strong> este cliente tiene
                                <?= count($creditosActivos) ?> crédito(s) activo(s) con saldo total
                                $<?= number_format(array_sum(array_column(array_map(fn($c) => ['s' => $c->saldo_pendiente], $creditosActivos), 's')), 2, ',', '.') ?>.
                                <ul class="mt-2 mb-0">
                                    <?php foreach ($creditosActivos as $ca): ?>
                                        <li>
                                            <a href="<?= $appUrl ?>/creditos/ficha?id=<?= $ca->id_credito ?>"
                                               class="text-warning"><?= htmlspecialchars($ca->codigo) ?></a>
                                            — Saldo: $<?= number_format($ca->saldo_pendiente, 2, ',', '.') ?>
                                            (<?= ucfirst($ca->frecuencia) ?>)
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Búsqueda de cliente por DNI/nombre -->
                        <div x-show="!clienteSeleccionado">
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text"
                                       class="form-control bg-slate-700 border-secondary text-light"
                                       placeholder="Buscar por nombre o DNI..."
                                       x-model="clienteQuery"
                                       @input.debounce.400ms="buscarCliente()"
                                       @focus="mostrarResultados = true"
                                       autocomplete="off">
                            </div>
                            <!-- Resultados del autocomplete -->
                            <div x-show="mostrarResultados && resultadosCliente.length > 0"
                                 class="border border-secondary rounded mt-1 bg-slate-700"
                                 style="max-height: 220px; overflow-y: auto;"
                                 @click.outside="mostrarResultados = false">
                                <template x-for="r in resultadosCliente" :key="r.id_cliente">
                                    <div class="px-3 py-2 hover-bg-slate-600 cursor-pointer border-bottom border-secondary"
                                         @click="seleccionarCliente(r)">
                                        <div class="fw-semibold text-light" x-text="r.nombre"></div>
                                        <div class="small text-secondary" x-text="'DNI: ' + r.dni + (r.direccion ? ' — ' + r.direccion : '')"></div>
                                    </div>
                                </template>
                            </div>
                            <div x-show="clienteQuery.length >= 2 && resultadosCliente.length === 0 && !buscandoCliente"
                                 class="text-secondary small mt-2">
                                Sin resultados. <a href="<?= $appUrl ?>/clientes/nuevo" class="text-info">Crear nuevo cliente</a>
                            </div>
                        </div>

                        <!-- Cliente seleccionado -->
                        <div x-show="clienteSeleccionado">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-slate-700 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                    <i class="bi bi-person fs-4 text-secondary"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-light" x-text="clienteSeleccionado?.nombre"></div>
                                    <div class="text-secondary small" x-text="'DNI: ' + clienteSeleccionado?.dni"></div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto"
                                        @click="limpiarCliente()">Cambiar</button>
                            </div>
                            <!-- Aviso créditos activos (cargados por AJAX) -->
                            <template x-if="creditosActivos.length > 0">
                                <div class="alert alert-warning mt-3 mb-0 small">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Atención:</strong> este cliente tiene
                                    <span x-text="creditosActivos.length"></span> crédito(s) activo(s).
                                    <ul class="mt-1 mb-0">
                                        <template x-for="ca in creditosActivos" :key="ca.id_credito">
                                            <li>
                                                <a :href="'<?= $appUrl ?>/creditos/ficha?id=' + ca.id_credito"
                                                   class="text-warning" x-text="ca.codigo"></a>
                                                — Saldo: $<span x-text="formatMonto(ca.saldo_pendiente)"></span>
                                                (<span x-text="ca.frecuencia"></span>)
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. Datos financieros -->
            <div class="card bg-slate-800 border-secondary mb-4">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h6 class="mb-0 text-light"><i class="bi bi-calculator me-2 text-success"></i>Datos del Crédito</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-sm-4">
                            <label class="form-label text-light">Capital prestado <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary">$</span>
                                <input type="number" name="capital" step="0.01" min="1" required
                                       class="form-control bg-slate-700 border-secondary text-light"
                                       x-model="capital"
                                       @input="calcular()"
                                       placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <label class="form-label text-light">Cantidad de cuotas <span class="text-danger">*</span></label>
                            <input type="number" name="cantidad_cuotas" min="1" required
                                   class="form-control bg-slate-700 border-secondary text-light"
                                   x-model="cantidadCuotas"
                                   @input="calcular()"
                                   placeholder="0">
                        </div>
                        <div class="col-6 col-sm-4">
                            <label class="form-label text-light">Valor de cuota <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary">$</span>
                                <input type="number" name="valor_cuota" step="0.01" min="0.01" required
                                       class="form-control bg-slate-700 border-secondary text-light"
                                       :class="parseFloat(valorCuota) > 0 && parseFloat(capital) > 0 && parseFloat(valorCuota) >= parseFloat(capital) ? 'border-warning' : ''"
                                       x-model="valorCuota"
                                       @input="calcular()"
                                       placeholder="0.00">
                            </div>
                            <div x-show="parseFloat(valorCuota) > 0 && parseFloat(capital) > 0 && parseFloat(valorCuota) >= parseFloat(capital)"
                                 class="alert alert-warning py-2 px-3 small mt-2 mb-0" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                <strong>¡Atención!</strong> El valor de cuota supera el capital prestado. Verificá que el monto sea correcto antes de continuar.
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label text-light">Gastos administrativos</label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-700 border-secondary text-secondary">$</span>
                                <input type="number" name="gastos_admin" step="0.01" min="0"
                                       class="form-control bg-slate-700 border-secondary text-light"
                                       x-model="gastosAdmin"
                                       @input="calcular()"
                                       placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <label class="form-label text-light">Frecuencia <span class="text-danger">*</span></label>
                            <select name="frecuencia" required
                                    class="form-select bg-slate-700 border-secondary text-light"
                                    x-model="frecuencia"
                                    @change="generarCalendario()">
                                <option value="diaria">Diaria</option>
                                <option value="semanal" selected>Semanal</option>
                                <option value="quincenal">Quincenal</option>
                                <option value="mensual">Mensual</option>
                            </select>
                        </div>
                        <div class="col-6 col-sm-4">
                            <label class="form-label text-light">Fecha primera cuota <span class="text-danger">*</span></label>
                            <input type="date" name="fecha_inicio" required
                                   class="form-control bg-slate-700 border-secondary text-light"
                                   x-model="fechaInicio"
                                   @change="generarCalendario()">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Personal y extras -->
            <div class="card bg-slate-800 border-secondary">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h6 class="mb-0 text-light"><i class="bi bi-person-badge me-2 text-secondary"></i>Personal y Observaciones</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-light">Vendedor</label>
                            <select name="id_vendedor" class="form-select bg-slate-700 border-secondary text-light">
                                <option value="">— Sin asignar —</option>
                                <?php foreach ($personal as $p): ?>
                                    <?php if (str_contains($p->rol_operativo, 'vendedor') || str_contains($p->rol_operativo, 'ambos')): ?>
                                        <option value="<?= $p->id_personal ?>"><?= htmlspecialchars($p->nombre) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-light">Cobrador</label>
                            <select name="id_cobrador" class="form-select bg-slate-700 border-secondary text-light">
                                <option value="">— Sin asignar —</option>
                                <?php foreach ($personal as $p): ?>
                                    <?php if (str_contains($p->rol_operativo, 'cobrador') || str_contains($p->rol_operativo, 'ambos')): ?>
                                        <option value="<?= $p->id_personal ?>"><?= htmlspecialchars($p->nombre) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-light">Destino del préstamo</label>
                            <input type="text" name="destino_opcional"
                                   class="form-control bg-slate-700 border-secondary text-light"
                                   placeholder="Ej: electrodoméstico, negocio...">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-light">Observaciones</label>
                            <textarea name="observaciones" rows="2"
                                      class="form-control bg-slate-700 border-secondary text-light"
                                      placeholder="Notas internas..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── COLUMNA DERECHA: Preview en vivo ─────────────────────────── -->
        <div class="col-12 col-xl-5">
            <!-- Resumen de cálculo en vivo -->
            <div class="card bg-slate-800 border-secondary mb-4 sticky-top" style="top: 16px;">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h6 class="mb-0 text-light"><i class="bi bi-bar-chart-line me-2 text-warning"></i>Resumen en Vivo</h6>
                </div>
                <div class="card-body">
                    <div class="bg-slate-700 rounded p-3 font-monospace small text-light mb-3">
                        <div class="d-flex justify-content-between border-bottom border-secondary pb-2 mb-2">
                            <span class="text-secondary">Monto prestado:</span>
                            <span>$<span x-text="formatMonto(capital || 0)">0,00</span></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-secondary">× Cuotas:</span>
                            <span x-text="cantidadCuotas || 0">0</span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom border-secondary pb-2 mb-2">
                            <span class="text-secondary">× Valor cuota:</span>
                            <span>$<span x-text="formatMonto(valorCuota || 0)">0,00</span></span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold border-bottom border-secondary pb-2 mb-2">
                            <span class="text-light">Total a devolver:</span>
                            <span class="text-warning">$<span x-text="formatMonto(montoTotal)">0,00</span></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-secondary">Interés implícito:</span>
                            <span class="text-info">
                                $<span x-text="formatMonto(interesImplicito)">0,00</span>
                                (<span x-text="interesImplicitoPct">0,00</span>%)
                            </span>
                        </div>
                        <template x-if="gastosAdmin > 0">
                            <div class="d-flex justify-content-between mt-1">
                                <span class="text-secondary">Gastos admin:</span>
                                <span>$<span x-text="formatMonto(gastosAdmin || 0)">0,00</span></span>
                            </div>
                        </template>
                    </div>

                    <!-- Botón confirmar -->
                    <button type="submit" class="btn btn-success w-100 btn-lg"
                            :disabled="!formularioValido() || submitting">
                        <span x-show="!submitting">
                            <i class="bi bi-check-circle me-2"></i> Confirmar y Crear Crédito
                        </span>
                        <span x-show="submitting">
                            <span class="spinner-border spinner-border-sm me-2"></span> Guardando...
                        </span>
                    </button>
                    <a href="<?= $appUrl ?>/creditos" class="btn btn-outline-secondary w-100 mt-2">Cancelar</a>
                </div>

                <!-- Preview del calendario de cuotas -->
                <div class="card-footer border-secondary bg-transparent">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 text-light small"><i class="bi bi-calendar3 me-2 text-info"></i>Calendario de cuotas</h6>
                        <button type="button" class="btn btn-sm btn-outline-info"
                                @click="generarCalendario()"
                                :disabled="!puedeGenerarCalendario()">
                            <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                        </button>
                    </div>

                    <div x-show="cargandoCalendario" class="text-center py-3 text-secondary small">
                        <span class="spinner-border spinner-border-sm me-2"></span> Generando...
                    </div>

                    <div x-show="!cargandoCalendario && cuotasPreview.length === 0" class="text-secondary small text-center py-2">
                        Complete los datos para ver el calendario.
                    </div>

                    <div x-show="!cargandoCalendario && cuotasPreview.length > 0"
                         class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                        <table class="table table-dark table-sm mb-0 small">
                            <thead class="sticky-top">
                                <tr class="text-secondary">
                                    <th>#</th>
                                    <th>Vencimiento</th>
                                    <th class="text-end">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(q, i) in cuotasPreview" :key="i">
                                    <tr>
                                        <td class="text-secondary" x-text="q.numero_cuota"></td>
                                        <td class="text-light" x-text="formatFecha(q.fecha_vencimiento)"></td>
                                        <td class="text-end text-light">$<span x-text="formatMonto(q.monto_esperado)"></span></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.row -->
</form>

<script>
function creditoForm() {
    return {
        // Cliente
        idCliente: <?= $clientePreseleccionado ? $clientePreseleccionado->id_cliente : 'null' ?>,
        clienteQuery: '',
        clienteSeleccionado: <?= $clientePreseleccionado ? json_encode(['id_cliente' => $clientePreseleccionado->id_cliente, 'nombre' => $clientePreseleccionado->nombre, 'dni' => $clientePreseleccionado->dni]) : 'null' ?>,
        resultadosCliente: [],
        mostrarResultados: false,
        buscandoCliente: false,
        creditosActivos: <?= !empty($creditosActivos) ? json_encode(array_map(fn($c) => ['id_credito' => $c->id_credito, 'codigo' => $c->codigo, 'saldo_pendiente' => $c->saldo_pendiente, 'frecuencia' => $c->frecuencia], $creditosActivos)) : '[]' ?>,

        // Datos financieros
        capital: '',
        cantidadCuotas: '',
        valorCuota: '',
        gastosAdmin: '',
        frecuencia: 'semanal',
        fechaInicio: '',

        // Calculados
        montoTotal: 0,
        interesImplicito: 0,
        interesImplicitoPct: 0,

        // Calendario
        cuotasPreview: [],
        cargandoCalendario: false,

        // UI
        submitting: false,

        init() {
            // Setear fecha de inicio en hoy por defecto
            const hoy = new Date();
            this.fechaInicio = hoy.toISOString().split('T')[0];
        },

        calcular() {
            const cap = parseFloat(this.capital)  || 0;
            const n   = parseInt(this.cantidadCuotas)  || 0;
            const val = parseFloat(this.valorCuota) || 0;
            const gas = parseFloat(this.gastosAdmin) || 0;

            this.montoTotal       = +(n * val).toFixed(2);
            this.interesImplicito = +(this.montoTotal - cap - gas).toFixed(2);
            this.interesImplicitoPct = cap > 0
                ? +((this.interesImplicito / cap) * 100).toFixed(2)
                : 0;
        },

        async buscarCliente() {
            if (this.clienteQuery.length < 2) {
                this.resultadosCliente = [];
                return;
            }
            this.buscandoCliente = true;
            try {
                const res = await fetch(`${APP_URL}/api/clientes/buscar?q=${encodeURIComponent(this.clienteQuery)}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                this.resultadosCliente = json.ok ? json.data : [];
                this.mostrarResultados = true;
            } catch (e) {
                this.resultadosCliente = [];
            } finally {
                this.buscandoCliente = false;
            }
        },

        async seleccionarCliente(r) {
            this.clienteSeleccionado = r;
            this.idCliente = r.id_cliente;
            this.mostrarResultados = false;
            this.clienteQuery = r.nombre;

            // Cargar créditos activos del cliente
            try {
                const res = await fetch(`${APP_URL}/api/creditos/activos_cliente?id_cliente=${r.id_cliente}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                this.creditosActivos = json.ok ? json.data.creditos : [];
            } catch (e) {
                this.creditosActivos = [];
            }
        },

        limpiarCliente() {
            this.clienteSeleccionado = null;
            this.idCliente = null;
            this.clienteQuery = '';
            this.resultadosCliente = [];
            this.creditosActivos = [];
        },

        puedeGenerarCalendario() {
            return this.fechaInicio && parseInt(this.cantidadCuotas) >= 1 && parseFloat(this.valorCuota) > 0;
        },

        async generarCalendario() {
            if (!this.puedeGenerarCalendario()) return;
            this.cargandoCalendario = true;
            try {
                const params = new URLSearchParams({
                    fecha_inicio:    this.fechaInicio,
                    cantidad_cuotas: this.cantidadCuotas,
                    frecuencia:      this.frecuencia,
                    valor_cuota:     this.valorCuota || '0',
                });
                const res  = await fetch(`${APP_URL}/api/creditos/calendario_preview?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                this.cuotasPreview = json.ok ? json.data.cuotas : [];
            } catch (e) {
                this.cuotasPreview = [];
            } finally {
                this.cargandoCalendario = false;
            }
        },

        formularioValido() {
            const tieneCliente = this.idCliente || <?= $clientePreseleccionado ? 'true' : 'false' ?>;
            return tieneCliente
                && parseFloat(this.capital) > 0
                && parseInt(this.cantidadCuotas) >= 1
                && parseFloat(this.valorCuota) > 0
                && this.fechaInicio !== '';
        },

        formatMonto(n) {
            return parseFloat(n || 0).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        formatFecha(f) {
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
