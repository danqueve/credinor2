<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
$hoy    = new \DateTime('today');
ob_start();
?>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h3 mb-0 fw-bold text-white"><?= htmlspecialchars($titulo) ?></h2>
        <p class="text-secondary small mb-0 mt-1">
            <i class="bi bi-people me-1"></i>
            <?= number_format($total ?? 0, 0, ',', '.') ?> clientes registrados
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap justify-content-end">
        <a href="<?= $appUrl ?>/reportes/exportar/clientes?format=pdf&q=<?= urlencode($search ?? '') ?>"
           class="btn btn-outline-danger" target="_blank" rel="noopener">
            <i class="bi bi-file-pdf me-1"></i> Exportar PDF
        </a>
    <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
        <a href="<?= $appUrl ?>/clientes/nuevo" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i> Nuevo Cliente
        </a>
    <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Buscador -->
<div class="card bg-slate-800 border-secondary mb-4">
    <div class="card-body py-3">
        <form action="<?= $appUrl ?>/clientes" method="GET">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-7 col-lg-5 position-relative">
                    <div class="input-group">
                        <span class="input-group-text bg-slate-700 border-secondary text-secondary">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" class="form-control bg-slate-900 border-secondary text-light"
                               id="buscador-clientes" name="q"
                               value="<?= htmlspecialchars($search) ?>"
                               placeholder="Buscar por nombre o DNI…"
                               autocomplete="off">
                        <?php if (!empty($search)): ?>
                            <a href="<?= $appUrl ?>/clientes" class="btn btn-outline-secondary border-secondary">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-primary" type="submit">Buscar</button>
                    </div>
                    <ul id="autocomplete-results"
                        class="list-group position-absolute w-100 mt-1 shadow d-none z-3"
                        style="max-height:250px;overflow-y:auto;"></ul>
                </div>
                <?php if (!empty($search)): ?>
                    <div class="col-auto">
                        <span class="badge badge-zona">Filtro: "<?= htmlspecialchars($search) ?>"</span>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card bg-slate-800 border-secondary">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Celular</th>
                        <th>Cuotas</th>
                        <th class="text-end">Valor cuota</th>
                        <th class="text-end">Saldo</th>
                        <th>Próx. vencimiento</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($clientes)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-secondary">
                            <i class="bi bi-person-x d-block fs-2 mb-2"></i>
                            No se encontraron clientes.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clientes as $c):

                        // ── Indicador de vencimiento ───────────────────────
                        $vencBadge = '';
                        $vencTxt   = '—';
                        if ($c->proxima_cuota) {
                            $vencDt = new \DateTime($c->proxima_cuota);
                            $dObj   = $hoy->diff($vencDt);
                            $isPast = ($dObj->invert === 1);
                            $dias   = (int)$dObj->days;

                            if ($isPast) {
                                $vencBadge = 'badge-vencido';
                                $vencTxt   = 'Venció hace ' . $dias . 'd';
                            } elseif ($dias === 0) {
                                $vencBadge = 'badge-vencido';
                                $vencTxt   = 'Vence hoy';
                            } elseif ($dias <= 7) {
                                $vencBadge = 'badge-refinanciado'; // naranja/violeta
                                $vencTxt   = date('d/m/Y', strtotime($c->proxima_cuota)) . ' (' . $dias . 'd)';
                            } else {
                                $vencBadge = 'badge-activo';
                                $vencTxt   = date('d/m/Y', strtotime($c->proxima_cuota));
                            }
                        }

                        // ── Progreso de cuotas ─────────────────────────────
                        $pct = ($c->cuotas_total > 0)
                            ? round($c->cuotas_pagadas / $c->cuotas_total * 100)
                            : 0;

                        // ── Datos para el modal (JSON) ─────────────────────
                        $modalData = json_encode([
                            'id_credito'    => $c->id_credito,
                            'codigo'        => $c->credito_codigo,
                            'nombre'        => $c->nombre,
                            'saldo'         => $c->credito_saldo,
                            'monto_cuota'   => $c->monto_cuota,
                            'cuotas_info'   => $c->cuotas_pagadas . ' de ' . $c->cuotas_total,
                        ], JSON_HEX_APOS | JSON_HEX_QUOT);
                    ?>
                    <tr>
                        <!-- Cliente -->
                        <td>
                            <div class="fw-bold text-light"><?= htmlspecialchars($c->nombre) ?></div>
                            <div class="small text-info font-monospace">DNI <?= htmlspecialchars($c->dni) ?></div>
                            <?php if ($c->zona_nombre): ?>
                                <span class="badge badge-zona mt-1"><?= htmlspecialchars($c->zona_nombre) ?></span>
                            <?php endif; ?>
                        </td>

                        <!-- Celular -->
                        <td>
                            <?php if ($c->telefono): ?>
                                <a href="https://wa.me/<?= preg_replace('/\D/', '', $c->telefono) ?>"
                                   target="_blank"
                                   class="text-light text-decoration-none small">
                                    <i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($c->telefono) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-secondary small fst-italic">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Cuotas con barra de progreso -->
                        <td style="min-width:130px;">
                            <?php if ($c->cuotas_total > 0): ?>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="small text-light fw-semibold">
                                        <?= $c->cuotas_pagadas ?> <span class="text-secondary fw-normal">de</span> <?= $c->cuotas_total ?>
                                    </span>
                                    <span class="small text-secondary"><?= $pct ?>%</span>
                                </div>
                                <div class="progress" style="height:5px;background:rgba(51,65,85,0.8);">
                                    <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : 'bg-primary' ?>"
                                         style="width:<?= $pct ?>%;border-radius:3px;"></div>
                                </div>
                            <?php else: ?>
                                <span class="text-secondary small fst-italic">Sin crédito</span>
                            <?php endif; ?>
                        </td>

                        <!-- Valor cuota -->
                        <td class="text-end">
                            <?php if ($c->monto_cuota !== null): ?>
                                <span class="text-light fw-semibold">
                                    $<?= number_format($c->monto_cuota, 0, ',', '.') ?>
                                </span>
                            <?php else: ?>
                                <span class="text-secondary">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Saldo pendiente -->
                        <td class="text-end">
                            <?php if ($c->credito_saldo > 0): ?>
                                <span class="text-warning fw-bold">
                                    $<?= number_format($c->credito_saldo, 0, ',', '.') ?>
                                </span>
                            <?php else: ?>
                                <span class="text-secondary small">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Próximo vencimiento -->
                        <td>
                            <?php if ($vencBadge): ?>
                                <span class="badge <?= $vencBadge ?>"><?= $vencTxt ?></span>
                            <?php else: ?>
                                <span class="text-secondary small">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Acciones -->
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <?php if ($c->id_credito): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-success btn-pagar"
                                            data-credito='<?= $modalData ?>'
                                            data-bs-toggle="tooltip"
                                            title="Registrar pago">
                                        <i class="bi bi-cash-coin me-1"></i>Pagar
                                    </button>
                                <?php endif; ?>
                                <a href="<?= $appUrl ?>/clientes/ficha?id=<?= $c->id_cliente ?>"
                                   class="btn btn-sm btn-outline-secondary"
                                   data-bs-toggle="tooltip" title="Ver Ficha">
                                    <i class="bi bi-person-vcard"></i>
                                </a>
                                <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
                                    <a href="<?= $appUrl ?>/clientes/editar?id=<?= $c->id_cliente ?>"
                                       class="btn btn-sm btn-outline-secondary"
                                       data-bs-toggle="tooltip" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($totalPages > 1): ?>
    <div class="card-footer border-secondary bg-transparent py-3">
        <nav>
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?q=<?= urlencode($search) ?>&page=<?= $page - 1 ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                <?php endif; ?>
                <?php
                $start = max(1, $page - 2);
                $end   = min($totalPages, $page + 2);
                if ($start > 1): ?>
                    <li class="page-item"><a class="page-link" href="?q=<?= urlencode($search) ?>&page=1">1</a></li>
                    <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <?php endif; ?>
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?q=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="?q=<?= urlencode($search) ?>&page=<?= $totalPages ?>"><?= $totalPages ?></a></li>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?q=<?= urlencode($search) ?>&page=<?= $page + 1 ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════════════════════
     MODAL PAGO RÁPIDO
═══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalPagoRapido" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-slate-800 border-secondary">

            <div class="modal-header border-secondary card-header-success">
                <h5 class="modal-title text-light fw-semibold">
                    <i class="bi bi-cash-coin me-2 text-success"></i>Registrar Pago
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="<?= $appUrl ?>/pagos/store" id="formPagoRapido">
                <?= \App\Helpers\Csrf::getFormField() ?>
                <input type="hidden" name="id_credito" id="pr_id_credito">
                <input type="hidden" name="referencia_externa" value="">
                <input type="hidden" name="observaciones" value="">
                <input type="hidden" name="id_cobrador" value="">

                <div class="modal-body">

                    <!-- Info del crédito -->
                    <div class="rounded p-3 mb-4"
                         style="background:rgba(255,255,255,0.04);border:1px solid rgba(51,65,85,0.6);">
                        <div class="fw-bold text-light mb-1" id="pr_nombre"></div>
                        <div class="d-flex gap-3 small flex-wrap">
                            <span class="text-secondary">
                                Crédito: <span class="text-info font-monospace fw-semibold" id="pr_codigo"></span>
                            </span>
                            <span class="text-secondary">
                                Cuotas: <span class="text-light" id="pr_cuotas_info"></span>
                            </span>
                            <span class="text-secondary">
                                Saldo: <span class="text-warning fw-bold" id="pr_saldo"></span>
                            </span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Monto -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-light fw-semibold">
                                Monto <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-slate-700 border-secondary text-success fw-bold">$</span>
                                <input type="number" name="monto_pagado" id="pr_monto"
                                       class="form-control bg-slate-900 border-secondary text-light fw-bold"
                                       step="0.01" min="0.01" required
                                       placeholder="0.00">
                            </div>
                            <div class="form-text text-secondary d-flex justify-content-between">
                                <span id="pr_cuota_hint"></span>
                                <a href="#" id="pr_pagar_todo" class="text-info text-decoration-none small">Pagar todo</a>
                            </div>
                        </div>

                        <!-- Fecha -->
                        <div class="col-12 col-sm-6">
                            <label class="form-label text-light fw-semibold">
                                Fecha del pago <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-slate-700 border-secondary">
                                    <i class="bi bi-calendar-date text-secondary"></i>
                                </span>
                                <input type="date" name="fecha_pago_real" id="pr_fecha"
                                       class="form-control bg-slate-900 border-secondary text-light"
                                       required>
                            </div>
                        </div>

                        <!-- Forma de pago -->
                        <div class="col-12">
                            <label class="form-label text-light fw-semibold">Forma de pago <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2 flex-wrap">
                                <input type="radio" class="btn-check" name="forma_pago" id="fp_efectivo" value="efectivo" checked>
                                <label class="btn btn-sm btn-outline-secondary" for="fp_efectivo">
                                    <i class="bi bi-cash me-1"></i>Efectivo
                                </label>
                                <input type="radio" class="btn-check" name="forma_pago" id="fp_transferencia" value="transferencia">
                                <label class="btn btn-sm btn-outline-secondary" for="fp_transferencia">
                                    <i class="bi bi-bank me-1"></i>Transferencia
                                </label>
                                <input type="radio" class="btn-check" name="forma_pago" id="fp_mp" value="mp">
                                <label class="btn btn-sm btn-outline-secondary" for="fp_mp">
                                    <i class="bi bi-phone me-1"></i>Mercado Pago
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-circle me-2"></i>Confirmar Pago
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Autocomplete ────────────────────────────────────────────────────────
    const input   = document.getElementById('buscador-clientes');
    const results = document.getElementById('autocomplete-results');
    let timer;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { results.classList.add('d-none'); return; }
        timer = setTimeout(async () => {
            const r = await apiCall(`/api/clientes/buscar?q=${encodeURIComponent(q)}`);
            if (r.ok && r.data.length > 0) {
                results.innerHTML = '';
                r.data.forEach(cl => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item list-group-item-action bg-slate-800 text-light border-secondary';
                    li.style.cursor = 'pointer';
                    li.innerHTML = `<div class="fw-bold">${cl.nombre}</div>
                                    <div class="small text-secondary">DNI: ${cl.dni}</div>`;
                    li.onclick = () => { window.location.href = `<?= $appUrl ?>/clientes/ficha?id=${cl.id_cliente}`; };
                    results.appendChild(li);
                });
                results.classList.remove('d-none');
            } else {
                results.innerHTML = '<li class="list-group-item bg-slate-800 text-secondary border-secondary">Sin resultados</li>';
                results.classList.remove('d-none');
            }
        }, 300);
    });
    document.addEventListener('click', e => {
        if (!input.contains(e.target) && !results.contains(e.target)) results.classList.add('d-none');
    });

    // ── Modal pago rápido ───────────────────────────────────────────────────
    const modal   = new bootstrap.Modal(document.getElementById('modalPagoRapido'));
    const todayStr = (() => {
        const d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
    })();

    document.querySelectorAll('.btn-pagar').forEach(btn => {
        btn.addEventListener('click', function () {
            const data = JSON.parse(this.dataset.credito);

            document.getElementById('pr_id_credito').value = data.id_credito;
            document.getElementById('pr_nombre').textContent = data.nombre;
            document.getElementById('pr_codigo').textContent = data.codigo;
            document.getElementById('pr_cuotas_info').textContent = data.cuotas_info;
            document.getElementById('pr_saldo').textContent = '$' + parseFloat(data.saldo).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('pr_fecha').value = todayStr;

            // Pre-llenar con el monto de la próxima cuota
            const montoCuota = parseFloat(data.monto_cuota) || parseFloat(data.saldo);
            document.getElementById('pr_monto').value = montoCuota.toFixed(2);
            document.getElementById('pr_monto').max   = parseFloat(data.saldo);

            if (data.monto_cuota) {
                document.getElementById('pr_cuota_hint').textContent =
                    'Cuota: $' + parseFloat(data.monto_cuota).toLocaleString('es-AR', {minimumFractionDigits: 2});
            } else {
                document.getElementById('pr_cuota_hint').textContent = '';
            }

            // "Pagar todo"
            document.getElementById('pr_pagar_todo').onclick = e => {
                e.preventDefault();
                document.getElementById('pr_monto').value = parseFloat(data.saldo).toFixed(2);
            };

            // Reset forma de pago
            document.getElementById('fp_efectivo').checked = true;

            modal.show();
        });
    });

    // ── Tooltips ────────────────────────────────────────────────────────────
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
});
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
