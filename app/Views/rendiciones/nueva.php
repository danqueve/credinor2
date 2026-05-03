<?php
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost/credinor2/public';
ob_start();

// Restaurar datos del formulario si venían de un error
$formData = [];
if (!empty($_SESSION['rendicion_form_data'])) {
    $formData = json_decode($_SESSION['rendicion_form_data'], true) ?? [];
    unset($_SESSION['rendicion_form_data']);
}
$fHeader = $formData['header'] ?? [];
?>

<!-- Encabezado -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= $appUrl ?>/rendiciones" class="text-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Rendiciones
        </a>
        <h2 class="h3 text-white fw-bold mt-1 mb-0">
            <i class="bi bi-journal-plus me-2 text-warning"></i>Nueva Rendición
        </h2>
    </div>
</div>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<form id="form-rendicion" method="POST" action="<?= $appUrl ?>/rendiciones/store">
    <?= \App\Helpers\Csrf::getFormField() ?>
    <!-- Campo oculto con las filas en JSON -->
    <input type="hidden" name="filas_json" id="filas_json" value="">

    <div class="row g-4">

        <!-- ── Cabecera ── -->
        <div class="col-12 col-lg-4">
            <div class="card bg-slate-800 border-secondary h-100">
                <div class="card-header bg-transparent border-secondary py-3">
                    <h6 class="mb-0 fw-bold text-light">
                        <i class="bi bi-person-badge me-2 text-info"></i>Datos de la rendición
                    </h6>
                </div>
                <div class="card-body">

                    <!-- Cobrador -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small mb-1">Cobrador <span class="text-danger">*</span></label>
                        <select name="id_cobrador" id="id_cobrador" class="form-select bg-dark text-light border-secondary" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($cobradores as $c): ?>
                                <option value="<?= $c->id_personal ?>"
                                    <?= (string)($fHeader['id_cobrador'] ?? '') === (string)$c->id_personal ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c->nombre) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Fecha -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small mb-1">Fecha de rendición <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_rendicion" id="fecha_rendicion" required
                               value="<?= htmlspecialchars($fHeader['fecha_rendicion'] ?? date('Y-m-d')) ?>"
                               class="form-control bg-dark text-light border-secondary">
                    </div>

                    <hr class="border-secondary">

                    <!-- Totales declarados -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small mb-1">
                            <i class="bi bi-cash me-1 text-success"></i>Efectivo declarado
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-slate-700 border-secondary text-secondary">$</span>
                            <input type="number" step="0.01" min="0" name="total_efectivo_declarado"
                                   id="total_efectivo_declarado"
                                   value="<?= htmlspecialchars($fHeader['total_efectivo_declarado'] ?? '0') ?>"
                                   class="form-control bg-dark text-light border-secondary"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary small mb-1">
                            <i class="bi bi-phone me-1 text-info"></i>Transferencias declaradas
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-slate-700 border-secondary text-secondary">$</span>
                            <input type="number" step="0.01" min="0" name="total_transferencias_declarado"
                                   id="total_transferencias_declarado"
                                   value="<?= htmlspecialchars($fHeader['total_transferencias_declarado'] ?? '0') ?>"
                                   class="form-control bg-dark text-light border-secondary"
                                   placeholder="0.00">
                        </div>
                    </div>

                    <!-- Total declarado calculado -->
                    <div class="rounded p-3 mb-3" style="background:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.2);">
                        <div class="text-secondary small mb-1">Total declarado</div>
                        <div class="h4 text-warning fw-bold mb-0" id="total-declarado-display">$0,00</div>
                    </div>

                    <!-- Observaciones -->
                    <div class="mb-3">
                        <label class="form-label text-secondary small mb-1">Observaciones</label>
                        <textarea name="observaciones" rows="2"
                                  class="form-control bg-dark text-light border-secondary"
                                  placeholder="Opcional..."><?= htmlspecialchars($fHeader['observaciones'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Grilla de pagos ── -->
        <div class="col-12 col-lg-8">
            <div class="card bg-slate-800 border-secondary">
                <div class="card-header bg-transparent border-secondary py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-light">
                        <i class="bi bi-list-check me-2 text-success"></i>Pagos a registrar
                        <span class="badge bg-secondary ms-2" id="fila-count">0</span>
                    </h6>
                    <button type="button" id="btn-add-fila" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-plus-lg me-1"></i>Agregar fila
                    </button>
                </div>

                <!-- Búsqueda rápida de crédito -->
                <div class="card-body border-bottom border-secondary pb-3">
                    <div class="d-flex gap-2 align-items-end">
                        <div class="flex-grow-1">
                            <label class="form-label text-secondary small mb-1">Buscar crédito por código o DNI cliente</label>
                            <input type="text" id="buscar-credito" placeholder="Ej: CR-0042 o 12345678"
                                   class="form-control bg-dark text-light border-secondary"
                                   autocomplete="off">
                        </div>
                        <button type="button" id="btn-buscar" class="btn btn-outline-info">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <div id="buscar-resultados" class="mt-2"></div>
                </div>

                <!-- Tabla de filas -->
                <div class="table-responsive">
                    <table class="table table-dark align-middle mb-0 small" id="tabla-filas">
                        <thead>
                            <tr class="text-secondary text-uppercase" style="font-size:0.7rem;">
                                <th>Crédito</th>
                                <th>Cliente</th>
                                <th class="text-end" style="min-width:120px;">Monto</th>
                                <th style="min-width:120px;">Forma pago</th>
                                <th style="min-width:130px;">Fecha pago</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="filas-body">
                            <tr id="fila-vacia">
                                <td colspan="6" class="text-center text-secondary py-4">
                                    <i class="bi bi-inbox d-block fs-3 mb-2"></i>
                                    Usá el buscador o "Agregar fila" para registrar pagos.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot class="border-top border-secondary">
                            <tr>
                                <td colspan="2" class="text-end text-secondary fw-bold">Total registrado:</td>
                                <td class="text-end fw-bold text-success" id="total-registrado-display">$0,00</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="card-footer border-secondary bg-transparent d-flex justify-content-end gap-2 py-3">
                    <a href="<?= $appUrl ?>/rendiciones" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-warning text-dark fw-bold" id="btn-submit">
                        <i class="bi bi-check-lg me-1"></i>Guardar Rendición
                    </button>
                </div>
            </div>
        </div>

    </div><!-- /row -->
</form>

<script>
const APP_URL = '<?= $appUrl ?>';
const CSRF    = '<?= \App\Helpers\Csrf::getToken() ?>';

let filas = []; // array de objetos {id_credito, codigo, cliente, monto, forma_pago, fecha_pago_real}
let filaId = 0;

// ── Formatear pesos ─────────────────────────────────────────────────────────
function fmt(n) {
    return '$' + Number(n || 0).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// ── Actualizar totales ──────────────────────────────────────────────────────
function recalcTotales() {
    const ef  = parseFloat(document.getElementById('total_efectivo_declarado').value) || 0;
    const tr  = parseFloat(document.getElementById('total_transferencias_declarado').value) || 0;
    document.getElementById('total-declarado-display').textContent = fmt(ef + tr);

    const reg = filas.reduce((s, f) => s + (parseFloat(f.monto) || 0), 0);
    document.getElementById('total-registrado-display').textContent = fmt(reg);
    document.getElementById('fila-count').textContent = filas.length;
}

// ── Renderizar tabla ────────────────────────────────────────────────────────
function renderTabla() {
    const tbody = document.getElementById('filas-body');
    if (filas.length === 0) {
        tbody.innerHTML = `<tr id="fila-vacia"><td colspan="6" class="text-center text-secondary py-4">
            <i class="bi bi-inbox d-block fs-3 mb-2"></i>Usá el buscador o "Agregar fila" para registrar pagos.</td></tr>`;
        recalcTotales();
        return;
    }
    tbody.innerHTML = filas.map(f => `
        <tr data-id="${f._id}">
            <td class="font-monospace text-info" style="font-size:.8rem;">${f.codigo || '—'}</td>
            <td class="text-light">${f.cliente || '—'}</td>
            <td>
                <input type="number" step="0.01" min="0" placeholder="0.00"
                       class="form-control form-control-sm bg-dark text-light border-secondary monto-input"
                       data-id="${f._id}" value="${f.monto || ''}">
            </td>
            <td>
                <select class="form-select form-select-sm bg-dark text-light border-secondary forma-input" data-id="${f._id}">
                    <option value="efectivo"      ${f.forma_pago === 'efectivo'      ? 'selected':''}>Efectivo</option>
                    <option value="transferencia" ${f.forma_pago === 'transferencia' ? 'selected':''}>Transferencia</option>
                    <option value="mp"            ${f.forma_pago === 'mp'            ? 'selected':''}>MercadoPago</option>
                </select>
            </td>
            <td>
                <input type="date" class="form-control form-control-sm bg-dark text-light border-secondary fecha-input"
                       data-id="${f._id}" value="${f.fecha_pago_real || ''}">
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove" data-id="${f._id}">
                    <i class="bi bi-trash3"></i>
                </button>
            </td>
        </tr>
    `).join('');

    // Eventos en las inputs
    tbody.querySelectorAll('.monto-input').forEach(el => {
        el.addEventListener('input', e => {
            const f = filas.find(f => f._id == e.target.dataset.id);
            if (f) { f.monto = e.target.value; recalcTotales(); }
        });
    });
    tbody.querySelectorAll('.forma-input').forEach(el => {
        el.addEventListener('change', e => {
            const f = filas.find(f => f._id == e.target.dataset.id);
            if (f) f.forma_pago = e.target.value;
        });
    });
    tbody.querySelectorAll('.fecha-input').forEach(el => {
        el.addEventListener('change', e => {
            const f = filas.find(f => f._id == e.target.dataset.id);
            if (f) f.fecha_pago_real = e.target.value;
        });
    });
    tbody.querySelectorAll('.btn-remove').forEach(el => {
        el.addEventListener('click', e => {
            const id = e.currentTarget.dataset.id;
            filas = filas.filter(f => f._id != id);
            renderTabla();
        });
    });

    recalcTotales();
}

// ── Agregar fila vacía ──────────────────────────────────────────────────────
document.getElementById('btn-add-fila').addEventListener('click', () => {
    filas.push({
        _id: ++filaId,
        id_credito: 0,
        codigo: '',
        cliente: '',
        monto: '',
        forma_pago: 'efectivo',
        fecha_pago_real: document.getElementById('fecha_rendicion').value || '',
    });
    renderTabla();
    // focus al último monto
    const inputs = document.querySelectorAll('.monto-input');
    if (inputs.length) inputs[inputs.length - 1].focus();
});

// ── Buscador de crédito ─────────────────────────────────────────────────────
document.getElementById('btn-buscar').addEventListener('click', buscarCredito);
document.getElementById('buscar-credito').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); buscarCredito(); }
});

async function buscarCredito() {
    const q = document.getElementById('buscar-credito').value.trim();
    if (!q) return;
    const res = document.getElementById('buscar-resultados');
    res.innerHTML = '<span class="text-secondary small"><i class="bi bi-hourglass-split me-1"></i>Buscando...</span>';
    try {
        const r = await fetch(`${APP_URL}/api/creditos/buscar?q=${encodeURIComponent(q)}`);
        const data = await r.json();
        const items = data.data ?? data;
        if (!items.length) {
            res.innerHTML = '<span class="text-secondary small">Sin créditos activos para ese término.</span>';
            return;
        }
        res.innerHTML = `<div class="list-group mt-1" style="max-height:200px;overflow-y:auto;">` +
            items.map(item => `
                <button type="button"
                    class="list-group-item list-group-item-action bg-slate-800 border-secondary text-light py-2 small btn-add-credito"
                    data-id="${item.id_credito}"
                    data-codigo="${item.codigo}"
                    data-cliente="${item.nombre}">
                    <span class="font-monospace text-info">${item.codigo}</span>
                    <span class="ms-2">${item.nombre}</span>
                    <span class="ms-2 text-secondary">DNI ${item.dni}</span>
                    <span class="ms-auto text-warning small">Saldo: $${Number(item.saldo_pendiente||0).toLocaleString('es-AR',{minimumFractionDigits:2})}</span>
                </button>
            `).join('') + '</div>';

        res.querySelectorAll('.btn-add-credito').forEach(btn => {
            btn.addEventListener('click', () => {
                filas.push({
                    _id: ++filaId,
                    id_credito: btn.dataset.id,
                    codigo: btn.dataset.codigo,
                    cliente: btn.dataset.cliente,
                    monto: '',
                    forma_pago: 'efectivo',
                    fecha_pago_real: document.getElementById('fecha_rendicion').value || '',
                });
                renderTabla();
                document.getElementById('buscar-credito').value = '';
                res.innerHTML = '';
                const inputs = document.querySelectorAll('.monto-input');
                if (inputs.length) inputs[inputs.length - 1].focus();
            });
        });
    } catch(e) {
        res.innerHTML = '<span class="text-danger small">Error al buscar.</span>';
    }
}

// ── Totales declarados ──────────────────────────────────────────────────────
['total_efectivo_declarado', 'total_transferencias_declarado'].forEach(id => {
    document.getElementById(id).addEventListener('input', recalcTotales);
});

// ── Submit: serializar filas a JSON ────────────────────────────────────────
document.getElementById('form-rendicion').addEventListener('submit', function(e) {
    // Validar que haya al menos una fila con monto
    const filasValidas = filas.filter(f => f.id_credito && parseFloat(f.monto) > 0 && f.fecha_pago_real);
    if (filasValidas.length === 0) {
        e.preventDefault();
        alert('Debés agregar al menos un pago con crédito, monto y fecha.');
        return;
    }
    document.getElementById('filas_json').value = JSON.stringify(filasValidas);
});

// ── Init ────────────────────────────────────────────────────────────────────
recalcTotales();
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base.php';
?>
