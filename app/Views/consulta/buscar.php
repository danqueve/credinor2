<?php
$appUrl = $_ENV['APP_URL'] ?? '';
ob_start();
?>

<div x-data="buscarApp('<?= $appUrl ?>', '<?= htmlspecialchars($q, ENT_QUOTES) ?>')">

    <!-- Buscador live -->
    <div class="input-group input-group-lg mb-4">
        <span class="input-group-text bg-slate-800 border-secondary text-secondary">
            <i class="bi bi-search" x-show="!loading"></i>
            <span class="spinner-border spinner-border-sm text-info" x-show="loading" style="width:1rem;height:1rem;"></span>
        </span>
        <input type="search"
               class="form-control bg-slate-800 border-secondary text-light"
               placeholder="Nombre, DNI, dirección, teléfono..."
               x-model="q"
               @input.debounce.300ms="buscar()"
               @search="if(q==='') reset()"
               autocomplete="off" autofocus
               style="font-size: 1rem;">
        <button class="btn btn-outline-secondary" type="button" x-show="q !== ''" @click="reset()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Resultados live -->
    <template x-if="q.length >= 2">
        <div>
            <template x-if="resultados.length === 0 && !loading">
                <div class="text-center py-5 text-secondary">
                    <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                    <div>Sin resultados para "<strong x-text="q"></strong>"</div>
                </div>
            </template>
            <template x-if="resultados.length > 0">
                <div>
                    <div class="small text-secondary mb-2" x-text="resultados.length + ' resultado(s)'"></div>
                    <div class="d-flex flex-column gap-2">
                        <template x-for="cl in resultados" :key="cl.id_cliente">
                            <a :href="appUrl + '/consulta/cliente?id=' + cl.id_cliente"
                               class="card bg-slate-800 border-0 text-decoration-none list-item-touch px-3 py-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                         :class="cl.cuotas_vencidas > 0 ? 'bg-danger' : 'bg-info'"
                                         style="width:42px;height:42px;">
                                        <span class="text-white fw-bold" x-text="cl.nombre.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="text-light fw-semibold text-truncate" x-text="cl.nombre"></div>
                                        <div class="small text-secondary" x-text="'DNI ' + cl.dni"></div>
                                        <div class="small text-secondary text-truncate" x-show="cl.direccion" x-text="cl.direccion"></div>
                                    </div>
                                    <div class="text-end flex-shrink-0 ms-2">
                                        <div class="fw-bold text-warning small" x-show="cl.saldo_total > 0"
                                             x-text="'$' + Number(cl.saldo_total).toLocaleString('es-AR', {maximumFractionDigits:0})"></div>
                                        <span class="badge bg-danger" x-show="cl.cuotas_vencidas > 0"
                                              x-text="cl.cuotas_vencidas + ' venc.'" style="font-size:0.65rem;"></span>
                                        <div class="text-secondary mt-1"><i class="bi bi-chevron-right" style="font-size:0.8rem;"></i></div>
                                    </div>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <!-- Cartera / filtros (cuando no hay búsqueda activa) -->
    <template x-if="q.length < 2">
        <div>
            <?php if (!empty($cartera) || $filtro !== ''): ?>
            <?php
            $filtroBase = '?filtro=';
            $chips = [
                ''        => 'Todos',
                'vencidos'=> 'Vencidos',
                'hoy'     => 'Hoy',
                'al-dia'  => 'Al día',
            ];
            ?>
            <div class="d-flex gap-2 mb-3 overflow-auto pb-1" style="scrollbar-width:none;">
            <?php foreach ($chips as $val => $label): ?>
                <a href="<?= $appUrl ?>/consulta/buscar<?= $filtroBase . urlencode($val) ?>"
                   class="btn btn-sm flex-shrink-0 <?= $filtro === $val ? 'btn-info' : 'btn-outline-secondary' ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
            </div>

            <div class="small text-secondary mb-2 d-flex align-items-center gap-1">
                <i class="bi bi-people-fill"></i>
                <?= count($cartera) ?> cliente(s)
            </div>
            <?php $lista = $cartera; ?>
            <?php include __DIR__ . '/_lista_clientes.php'; ?>

            <?php else: ?>
            <?php $adminWa = $_ENV['ADMIN_WHATSAPP'] ?? ''; ?>
            <div class="text-center py-5 text-secondary">
                <i class="bi bi-person-lines-fill fs-1 d-block mb-2"></i>
                <div class="mb-3 small">No tenés clientes asignados aún.</div>
                <?php if ($adminWa): ?>
                <a href="https://wa.me/<?= htmlspecialchars($adminWa) ?>?text=<?= urlencode('Hola, soy ' . (\App\Helpers\Auth::user()['nombre'] ?? 'cobrador') . '. No tengo clientes asignados. ¿Podés verificar?') ?>"
                   class="btn btn-success btn-sm" target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp me-1"></i> Avisar al admin
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </template>

</div>

<script>
function buscarApp(appUrl, qInicial) {
    return {
        appUrl,
        q: qInicial,
        resultados: [],
        loading: false,
        async buscar() {
            if (this.q.length < 2) { this.resultados = []; return; }
            this.loading = true;
            try {
                const r = await fetch(appUrl + '/api/consulta/buscar_clientes?q=' + encodeURIComponent(this.q));
                const json = await r.json();
                this.resultados = json.data ?? [];
            } catch(e) {
                this.resultados = [];
            } finally {
                this.loading = false;
            }
        },
        reset() {
            this.q = '';
            this.resultados = [];
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require APP_PATH . '/Views/layout/base_mobile.php';
?>
