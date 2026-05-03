<?php
// $lista debe estar definida antes de incluir este partial
$appUrl = $appUrl ?? ($_ENV['APP_URL'] ?? '');
?>
<div class="d-flex flex-column gap-2">
    <?php foreach ($lista as $cl): ?>
        <a href="<?= $appUrl ?>/consulta/cliente?id=<?= $cl->id_cliente ?>"
           class="card bg-slate-800 border-0 text-decoration-none list-item-touch px-3 py-2">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0
                     <?= $cl->cuotas_vencidas > 0 ? 'bg-danger' : 'bg-info' ?>"
                     style="width:42px;height:42px;">
                    <span class="text-white fw-bold"><?= mb_strtoupper(mb_substr($cl->nombre, 0, 1)) ?></span>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="text-light fw-semibold text-truncate"><?= htmlspecialchars($cl->nombre) ?></div>
                    <div class="small text-secondary">DNI <?= htmlspecialchars($cl->dni) ?></div>
                    <?php if (!empty($cl->direccion)): ?>
                    <div class="small text-secondary text-truncate">
                        <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($cl->direccion) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="text-end flex-shrink-0 ms-2">
                    <?php if ($cl->saldo_total > 0): ?>
                    <div class="fw-bold text-warning small">$<?= number_format($cl->saldo_total, 0, ',', '.') ?></div>
                    <?php endif; ?>
                    <?php if ($cl->cuotas_vencidas > 0): ?>
                    <span class="badge bg-danger" style="font-size:0.65rem;">
                        <?= $cl->cuotas_vencidas ?> venc.
                    </span>
                    <?php endif; ?>
                    <div class="text-secondary mt-1"><i class="bi bi-chevron-right" style="font-size:0.8rem;"></i></div>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
</div>
