<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';

$creditoJson = $credito ? json_encode([
    'id_credito'      => $credito->id_credito,
    'codigo'          => $credito->codigo,
    'cliente_nombre'  => $credito->cliente_nombre,
    'cliente_dni'     => $credito->cliente_dni,
    'saldo_pendiente' => $credito->saldo_pendiente,
    'estado'          => $credito->estado,
]) : 'null';

$cuotasJson = json_encode(array_values($cuotasPendientes));
$pasoCargado = $credito ? 3 : 1;

ob_start();
?>

<!-- Header -->
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

<!-- Wizard container -->
<div x-data="pagoForm()" x-init="init()">

    <!-- ── STEPPER ─────────────────────────────────────────────────────────── -->
    <div class="card bg-slate-800 border-secondary mb-4">
        <div class="card-body py-3">
            <div class="d-flex align-items-center justify-content-center gap-0">

                <!-- Paso 1 -->
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold"
                         style="width:32px;height:32px;font-size:0.85rem;transition:all 0.2s;"
                         :style="paso >= 1
                             ? 'background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;box-shadow:0 0 10px rgba(59,130,246,.4)'
                             : 'background:var(--slate-700);color:var(--slate-400)'">
                        <span x-show="paso > 1"><i class="bi bi-check-lg" style="font-size:0.8rem;"></i></span>
                        <span x-show="paso <= 1">1</span>
                    </div>
                    <span class="d-none d-sm-inline small fw-semibold"
                          :class="paso >= 1 ? 'text-light' : 'text-secondary'">Buscar Cliente</span>
                </div>

                <div class="flex-grow-1 mx-2" style="height:2px;max-width:80px;"
                     :style="paso >= 2 ? 'background:linear-gradient(90deg,#3b82f6,#3b82f6)' : 'background:var(--slate-700)'"></div>

                <!-- Paso 2 -->
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold"
                         style="width:32px;height:32px;font-size:0.85rem;transition:all 0.2s;"
                         :style="paso >= 2
                             ? 'background:linear-gradient(135deg,#3b82f6,#2563eb);color:#fff;box-shadow:0 0 10px rgba(59,130,246,.4)'
                             : 'background:var(--slate-700);color:var(--slate-400)'">
                        <span x-show="paso > 2"><i class="bi bi-check-lg" style="font-size:0.8rem;"></i></span>
                        <span x-show="paso <= 2">2</span>
                    </div>
                    <span class="d-none d-sm-inline small fw-semibold"
                          :class="paso >= 2 ? 'text-light' : 'text-secondary'">Seleccionar Crédito</span>
                </div>

                <div class="flex-grow-1 mx-2" style="height:2px;max-width:80px;"
                     :style="paso >= 3 ? 'background:linear-gradient(90deg,#22c55e,#22c55e)' : 'background:var(--slate-700)'"></div>

                <!-- Paso 3 -->
                <div class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold"
                         style="width:32px;height:32px;font-size:0.85rem;transition:all 0.2s;"
                         :style="paso >= 3
                             ? 'background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;box-shadow:0 0 10px rgba(34,197,94,.4)'
                             : 'background:var(--slate-700);color:var(--slate-400)'">3</div>
                    <span class="d-none d-sm-inline small fw-semibold"
                          :class="paso >= 3 ? 'text-light' : 'text-secondary'">Datos del Pago</span>
                </div>

            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- PASO 1: Buscar cliente                                                -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div x-show="paso === 1" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

        <div class="card bg-slate-800 border-secondary">
            <div class="card-header card-header-primary py-3">
                <h6 class="mb-0 text-light fw-semibold">
                    <i class="bi bi-person-search me-2 text-primary"></i>
                    Buscar Cliente
                </h6>
            </div>
            <div class="card-body">
                <!-- Input de búsqueda -->
                <div class="input-group input-group-lg mb-4">
                    <span class="input-group-text bg-slate-700 border-secondary">
                        <i class="bi bi-search text-secondary"></i>
                    </span>
                    <input type="text"
                           class="form-control bg-slate-900 border-secondary text-light"
                           placeholder="Nombre, apellido o DNI..."
                           x-model="busquedaCliente"
                           @input.debounce.350ms="buscarClientes()"
                           autocomplete="off"
                           autofocus>
                    <button class="btn btn-outline-secondary" type="button"
                            x-show="busquedaCliente.length > 0"
                            @click="busquedaCliente = ''; clientesResultados = []">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- Estado: buscando -->
                <div x-show="buscandoCliente" class="text-center py-4 text-secondary">
                    <div class="spinner-border spinner-border-sm me-2"></div> Buscando...
                </div>

                <!-- Estado: sin resultados -->
                <div x-show="!buscandoCliente && busquedaCliente.length >= 2 && clientesResultados.length === 0"
                     class="text-center py-5">
                    <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                    <p class="text-secondary mb-0">No se encontraron clientes con ese criterio.</p>
                </div>

                <!-- Estado: instrucción inicial -->
                <div x-show="busquedaCliente.length < 2 && !buscandoCliente"
                     class="text-center py-5">
                    <i class="bi bi-person-lines-fill fs-1 d-block mb-2" style="color:var(--slate-600);"></i>
                    <p class="text-secondary mb-0 small">Escribí al menos 2 caracteres para buscar</p>
                </div>

                <!-- Resultados: grid de cards -->
                <div x-show="clientesResultados.length > 0"
                     class="row g-3">
                    <template x-for="cl in clientesResultados" :key="cl.id_cliente">
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card bg-slate-700 border-secondary h-100"
                                 style="cursor:pointer;transition:all 0.18s;"
                                 @click="seleccionarCliente(cl)"
                                 @mouseenter="$el.style.borderColor='#3b82f6';$el.style.transform='translateY(-2px)'"
                                 @mouseleave="$el.style.borderColor='';$el.style.transform=''">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                             style="width:42px;height:42px;background:linear-gradient(135deg,rgba(59,130,246,.25),rgba(59,130,246,.08));color:#60a5fa;font-size:1.1rem;">
                                            <i class="bi bi-person-fill"></i>
                                        </div>
                                        <div class="flex-fill min-width-0">
                                            <div class="fw-bold text-light text-truncate" x-text="cl.nombre"></div>
                                            <div class="text-info small font-monospace">DNI: <span x-text="cl.dni"></span></div>
                                            <div class="text-secondary small text-truncate mt-1"
                                                 x-show="cl.direccion"
                                                 x-text="cl.direccion + (cl.barrio ? ' · ' + cl.barrio : '')"></div>
                                        </div>
                                        <i class="bi bi-chevron-right text-secondary flex-shrink-0 mt-1"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- PASO 2: Seleccionar crédito                                           -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div x-show="paso === 2" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

        <!-- Cliente seleccionado (breadcrumb) -->
        <div class="card bg-slate-800 border-secondary mb-4">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="d-flex align-items-center justify-content-center rounded-circle"
                     style="width:44px;height:44px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;font-size:1.1rem;flex-shrink:0;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div class="flex-fill">
                    <div class="fw-bold text-light" x-text="clienteSeleccionado?.nombre"></div>
                    <div class="text-info small">DNI: <span x-text="clienteSeleccionado?.dni"></span></div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="volverPaso(1)">
                    <i class="bi bi-arrow-left me-1"></i> Cambiar
                </button>
            </div>
        </div>

        <div class="card bg-slate-800 border-secondary">
            <div class="card-header card-header-warning py-3">
                <h6 class="mb-0 text-light fw-semibold">
                    <i class="bi bi-cash-stack me-2 text-warning"></i>
                    Créditos Activos
                </h6>
            </div>
            <div class="card-body">

                <!-- Cargando -->
                <div x-show="cargandoCreditos" class="text-center py-4 text-secondary">
                    <div class="spinner-border spinner-border-sm me-2"></div> Cargando créditos...
                </div>

                <!-- Sin créditos -->
                <div x-show="!cargandoCreditos && creditosCliente.length === 0"
                     class="text-center py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                    <p class="text-secondary mb-0">Este cliente no tiene créditos activos.</p>
                </div>

                <!-- Lista de créditos -->
                <div x-show="!cargandoCreditos && creditosCliente.length > 0"
                     class="row g-3">
                    <template x-for="cr in creditosCliente" :key="cr.id_credito">
                        <div class="col-12 col-lg-6">
                            <div class="card border h-100"
                                 style="cursor:pointer;transition:all 0.18s;background:var(--slate-700);border-color:rgba(51,65,85,0.8)!important;"
                                 @click="seleccionarCredito(cr)"
                                 @mouseenter="$el.style.borderColor='#22c55e !important';$el.style.transform='translateY(-2px)'"
                                 @mouseleave="$el.style.borderColor='';$el.style.transform=''">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="fw-bold text-info font-monospace" x-text="cr.codigo"></span>
                                        <span class="badge badge-activo">Activo</span>
                                    </div>
                                    <div class="row g-2 small">
                                        <div class="col-6">
                                            <div class="text-secondary">Capital</div>
                                            <div class="text-light fw-semibold">$<span x-text="fmt(cr.capital)"></span></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-secondary">Saldo pendiente</div>
                                            <div class="text-warning fw-bold">$<span x-text="fmt(cr.saldo_pendiente)"></span></div>
                                        </div>
                                        <div class="col-6" x-show="cr.frecuencia">
                                            <div class="text-secondary">Frecuencia</div>
                                            <div class="text-light" x-text="cr.frecuencia"></div>
                                        </div>
                                        <div class="col-6" x-show="cr.cobrador_nombre">
                                            <div class="text-secondary">Cobrador</div>
                                            <div class="text-light text-truncate" x-text="cr.cobrador_nombre"></div>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-top border-secondary d-flex justify-content-end">
                                        <span class="small text-success">
                                            Seleccionar <i class="bi bi-arrow-right ms-1"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- PASO 3: Datos del pago                                                -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div x-show="paso === 3" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

        <!-- Breadcrumb cliente + crédito -->
        <div class="card bg-slate-800 border-secondary mb-4">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                         style="width:44px;height:44px;background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;font-size:1.1rem;">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="flex-fill">
                        <div class="fw-bold text-light" x-text="creditoSeleccionado?.cliente_nombre"></div>
                        <div class="text-secondary small">
                            DNI: <span class="text-info" x-text="creditoSeleccionado?.cliente_dni"></span>
                            <span class="mx-2">·</span>
                            Crédito: <span class="text-info font-monospace fw-bold" x-text="creditoSeleccionado?.codigo"></span>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-warning fw-bold fs-5">$<span x-text="fmt(creditoSeleccionado?.saldo_pendiente || 0)"></span></div>
                        <div class="text-secondary small">saldo pendiente</div>
                    </div>
                    <?php if (!$credito): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="volverPaso(2)">
                        <i class="bi bi-arrow-left me-1"></i> Cambiar
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= $appUrl ?>/pagos/store"
              @submit.prevent="confirmarYEnviar()">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Csrf::getToken() ?>">
            <input type="hidden" name="id_credito" x-model="idCredito">

            <div class="row g-4">

                <!-- ── COLUMNA IZQUIERDA ──────────────────────────────────── -->
                <div class="col-12 col-xl-7">

                    <!-- Cuotas pendientes -->
                    <div class="card bg-slate-800 border-secondary mb-4" x-show="cuotas.length > 0">
                        <div class="card-header card-header-warning py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 text-light fw-semibold">
                                <i class="bi bi-calendar3 me-2 text-warning"></i>Cuotas Pendientes
                            </h6>
                            <span class="badge badge-vencido" x-show="cuotas.filter(q => q.estado === 'vencida').length > 0"
                                  x-text="cuotas.filter(q => q.estado === 'vencida').length + ' vencida(s)'"></span>
                            <span class="badge badge-activo ms-1"
                                  x-text="cuotas.length + ' pendiente(s)'"></span>
                        </div>
                        <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                            <table class="table table-dark table-sm mb-0 small align-middle">
                                <thead>
                                    <tr>
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
                                                      :class="{
                                                          'badge-vencido':  q.estado === 'vencida',
                                                          'badge-cancelado': q.estado === 'parcial',
                                                          'badge-activo':   q.estado === 'pendiente'
                                                      }"
                                                      x-text="q.estado"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Datos del pago -->
                    <div class="card bg-slate-800 border-secondary">
                        <div class="card-header card-header-success py-3">
                            <h6 class="mb-0 text-light fw-semibold">
                                <i class="bi bi-wallet2 me-2 text-success"></i>Datos del Pago
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                <!-- Monto -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-label text-light">Monto a pagar <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-slate-700 border-secondary text-success fw-bold">$</span>
                                        <input type="number" name="monto_pagado" step="0.01" min="0.01" required
                                               class="form-control bg-slate-900 border-secondary text-light fw-bold"
                                               x-model="monto"
                                               @input="calcularFifo()"
                                               placeholder="0.00">
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span class="form-text text-secondary small">
                                            Saldo: $<span x-text="fmt(creditoSeleccionado?.saldo_pendiente || 0)"></span>
                                        </span>
                                        <a href="#" class="form-text text-info small text-decoration-none"
                                           @click.prevent="monto = creditoSeleccionado.saldo_pendiente; calcularFifo()">
                                            Pagar todo
                                        </a>
                                    </div>
                                </div>

                                <!-- Fecha de pago -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-label text-light">Fecha del pago <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-slate-700 border-secondary">
                                            <i class="bi bi-calendar-date text-secondary"></i>
                                        </span>
                                        <input type="date" name="fecha_pago_real" required
                                               class="form-control bg-slate-900 border-secondary text-light"
                                               x-model="fechaPagoReal"
                                               :max="hoy">
                                    </div>
                                    <div class="form-text text-secondary small">
                                        Cuándo el cliente entregó el dinero.
                                    </div>
                                </div>

                                <!-- Forma de pago -->
                                <div class="col-12 col-sm-6">
                                    <label class="form-label text-light">Forma de pago <span class="text-danger">*</span></label>
                                    <select name="forma_pago" required
                                            class="form-select bg-slate-900 border-secondary text-light"
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
                                    <select name="id_cobrador" class="form-select bg-slate-900 border-secondary text-light">
                                        <option value="">— Sin asignar —</option>
                                        <?php foreach ($personal as $p): ?>
                                            <option value="<?= $p->id_personal ?>"><?= htmlspecialchars($p->nombre) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Referencia (solo si no es efectivo) -->
                                <div class="col-12" x-show="formaPago !== 'efectivo'" x-transition>
                                    <label class="form-label text-light">Referencia / N° de comprobante</label>
                                    <input type="text" name="referencia_externa"
                                           class="form-control bg-slate-900 border-secondary text-light"
                                           placeholder="N° de transferencia, CBU, etc.">
                                </div>

                                <!-- Observaciones -->
                                <div class="col-12">
                                    <label class="form-label text-light">Observaciones</label>
                                    <textarea name="observaciones" rows="2"
                                              class="form-control bg-slate-900 border-secondary text-light"
                                              placeholder="Notas internas (opcional)..."></textarea>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>

                <!-- ── COLUMNA DERECHA: Preview FIFO ──────────────────────── -->
                <div class="col-12 col-xl-5">
                    <div class="card bg-slate-800 border-secondary sticky-top" style="top:16px;">
                        <div class="card-header card-header-info py-3">
                            <h6 class="mb-0 text-light fw-semibold">
                                <i class="bi bi-lightning-fill me-2 text-info"></i>Preview FIFO
                            </h6>
                        </div>
                        <div class="card-body">

                            <!-- Sin monto -->
                            <div x-show="!monto || parseFloat(monto) <= 0"
                                 class="text-center py-4 text-secondary small">
                                <i class="bi bi-lightning fs-2 d-block mb-2" style="color:var(--slate-600)"></i>
                                Ingresá el monto para ver cómo se distribuirá entre las cuotas.
                            </div>

                            <!-- Alerta: monto excede saldo -->
                            <div x-show="parseFloat(monto) > 0 && parseFloat(monto) > (creditoSeleccionado?.saldo_pendiente || 0) + 0.005"
                                 class="alert alert-danger small mb-3">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                El monto supera el saldo pendiente del crédito.
                            </div>

                            <!-- Aplicaciones FIFO -->
                            <div x-show="parseFloat(monto) > 0 && fifoAplicaciones.length > 0">
                                <div class="table-responsive">
                                    <table class="table table-dark table-sm mb-0 small">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Venc.</th>
                                                <th class="text-end">Aplicado</th>
                                                <th class="text-end">Queda</th>
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
                                                        x-text="ap.saldo_nuevo <= 0 ? '✓' : '$' + fmt(ap.saldo_nuevo)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Resumen -->
                                <div class="rounded p-3 mt-3 small" style="background:rgba(255,255,255,0.04);border:1px solid rgba(51,65,85,0.6);">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-secondary">Monto ingresado</span>
                                        <span class="text-light fw-bold">$<span x-text="fmt(parseFloat(monto) || 0)"></span></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-secondary">Cuotas afectadas</span>
                                        <span class="text-light" x-text="fifoAplicaciones.length"></span>
                                    </div>
                                    <div x-show="montoRestante > 0.005"
                                         class="d-flex justify-content-between border-top border-secondary pt-2 mt-1">
                                        <span class="text-warning">Sin asignar</span>
                                        <span class="text-warning fw-bold">$<span x-text="fmt(montoRestante)"></span></span>
                                    </div>
                                    <div class="d-flex justify-content-between border-top border-secondary pt-2 mt-1">
                                        <span class="text-secondary">Saldo tras pago</span>
                                        <span :class="saldoTrasP <= 0.005 ? 'text-success fw-bold' : 'text-warning fw-bold'"
                                              x-text="saldoTrasP <= 0.005 ? '✓ CANCELADO' : '$' + fmt(saldoTrasP)"></span>
                                    </div>
                                </div>
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
                            <a href="<?= $appUrl ?>/pagos" class="btn btn-outline-secondary w-100 mt-2">
                                Cancelar
                            </a>
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
                                Estás por registrar un pago de
                                <strong class="text-success">$<span x-text="fmt(parseFloat(monto) || 0)"></span></strong>
                                para el crédito <strong class="text-info font-monospace" x-text="creditoSeleccionado?.codigo"></strong>
                                del cliente <strong class="text-light" x-text="creditoSeleccionado?.cliente_nombre"></strong>.
                            </p>
                            <div class="d-flex gap-3 small mb-2">
                                <span class="text-secondary">Fecha:</span>
                                <span class="text-light" x-text="fmtFecha(fechaPagoReal)"></span>
                            </div>
                            <div class="d-flex gap-3 small mb-3">
                                <span class="text-secondary">Forma de pago:</span>
                                <span class="text-light" x-text="formaPago"></span>
                            </div>
                            <div x-show="saldoTrasP <= 0.005" class="alert alert-success py-2 small mb-0">
                                <i class="bi bi-trophy me-2"></i> Este pago cancelará el crédito completamente.
                            </div>
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
    </div><!-- /paso 3 -->

</div><!-- /x-data -->

<script>
function pagoForm() {
    return {
        paso: <?= $pasoCargado ?>,

        // Paso 1
        busquedaCliente: '',
        clientesResultados: [],
        buscandoCliente: false,

        // Paso 2
        clienteSeleccionado: null,
        creditosCliente: [],
        cargandoCreditos: false,

        // Paso 3
        idCredito: <?= $credito ? $credito->id_credito : 'null' ?>,
        creditoSeleccionado: <?= $creditoJson ?>,
        cuotas: <?= $cuotasJson ?>,
        monto: '',
        fechaPagoReal: '',
        formaPago: 'efectivo',
        hoy: new Date().toISOString().split('T')[0],
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

        // ── Paso 1: búsqueda de clientes ────────────────────────────────────
        async buscarClientes() {
            if (this.busquedaCliente.length < 2) {
                this.clientesResultados = [];
                return;
            }
            this.buscandoCliente = true;
            try {
                const r = await fetch(
                    `${APP_URL}/api/clientes/buscar?q=${encodeURIComponent(this.busquedaCliente)}`,
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                );
                const j = await r.json();
                this.clientesResultados = j.ok ? j.data : [];
            } catch (e) {
                this.clientesResultados = [];
            } finally {
                this.buscandoCliente = false;
            }
        },

        // ── Paso 1 → 2: seleccionar cliente y cargar créditos ───────────────
        async seleccionarCliente(cl) {
            this.clienteSeleccionado = cl;
            this.paso = 2;
            this.cargandoCreditos = true;
            this.creditosCliente = [];
            try {
                const r = await fetch(
                    `${APP_URL}/api/creditos/activos_cliente?id_cliente=${cl.id_cliente}`,
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                );
                const j = await r.json();
                if (j.ok && j.data.creditos) {
                    this.creditosCliente = j.data.creditos.map(cr => ({
                        ...cr,
                        cliente_nombre: cl.nombre,
                        cliente_dni: cl.dni,
                    }));
                }
            } catch (e) {
                this.creditosCliente = [];
            } finally {
                this.cargandoCreditos = false;
            }
        },

        // ── Paso 2 → 3: seleccionar crédito y cargar cuotas ─────────────────
        async seleccionarCredito(cr) {
            this.cargandoCreditos = true;
            try {
                const r = await fetch(
                    `${APP_URL}/api/pagos/cuotas_credito?id_credito=${cr.id_credito}`,
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                );
                const j = await r.json();
                if (j.ok) {
                    this.creditoSeleccionado = {
                        ...j.data.credito,
                        cliente_nombre: cr.cliente_nombre,
                        cliente_dni: cr.cliente_dni,
                    };
                    this.cuotas = j.data.cuotas;
                    this.idCredito = cr.id_credito;
                    this.saldoTrasP = j.data.credito.saldo_pendiente;
                    this.monto = '';
                    this.fifoAplicaciones = [];
                    this.paso = 3;
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.cargandoCreditos = false;
            }
        },

        // ── Volver a paso anterior ───────────────────────────────────────────
        volverPaso(n) {
            this.paso = n;
            if (n === 1) {
                this.clienteSeleccionado = null;
                this.creditosCliente = [];
                this.creditoSeleccionado = null;
                this.idCredito = null;
                this.cuotas = [];
                this.monto = '';
                this.fifoAplicaciones = [];
            }
            if (n === 2) {
                this.creditoSeleccionado = null;
                this.idCredito = null;
                this.cuotas = [];
                this.monto = '';
                this.fifoAplicaciones = [];
            }
        },

        // ── FIFO ────────────────────────────────────────────────────────────
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
                    id_cuota:          q.id_cuota,
                    numero_cuota:      q.numero_cuota,
                    fecha_vencimiento: q.fecha_vencimiento,
                    monto_aplicado:    Math.round(aplicar * 100) / 100,
                    saldo_nuevo:       Math.round((saldo - aplicar) * 100) / 100,
                });
                restante = Math.round((restante - aplicar) * 100) / 100;
            }
            this.fifoAplicaciones = aplicaciones;
            this.montoRestante    = restante;
            this.saldoTrasP       = Math.max(0, Math.round(((this.creditoSeleccionado?.saldo_pendiente || 0) - m) * 100) / 100);
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
            new bootstrap.Modal(document.getElementById('modalConfirmar')).show();
        },

        enviarFormulario() {
            this.submitting = true;
            bootstrap.Modal.getInstance(document.getElementById('modalConfirmar'))?.hide();
            this.$el.querySelector('form').submit();
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
