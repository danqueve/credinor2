<?php

declare(strict_types=1);

return [

    // ─── Mora (desactivada por default, preparada para activar) ───────────
    'mora' => [
        'habilitada'       => false,   // ← cambiar a true para activar
        'tolerancia_dias'  => 3,
        'tramo_1_dias'     => [4, 15],
        'tramo_1_pct'      => 5.0,
        'tramo_2_dias'     => [16, 999],
        'tramo_2_pct'      => 10.0,
        'tramo_2_diario_pct' => 0.5,
    ],

    // ─── Días de cobranza ─────────────────────────────────────────────────
    // Decisión: lunes a sábado (domingos excluidos). Ajustar si el dueño decide incluir domingos.
    'dias_cobranza' => [
        'lunes'     => true,
        'martes'    => true,
        'miercoles' => true,
        'jueves'    => true,
        'viernes'   => true,
        'sabado'    => true,
        'domingo'   => false,  // ← excluido por default
    ],

    // ─── Feriados ─────────────────────────────────────────────────────────
    'feriados_excluidos' => false,  // a futuro: tabla de feriados

    // ─── Rate limit de login ──────────────────────────────────────────────
    'login_max_intentos'   => 5,
    'login_bloqueo_minutos' => 15,

    // ─── Numeración de comprobantes ───────────────────────────────────────
    'prefijo_credito' => 'CR',
    'prefijo_recibo'  => 'R',

    // ─── Score interno clientes ───────────────────────────────────────────
    'score_default' => 3,   // 1=muy malo … 5=excelente

];
