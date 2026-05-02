<?php

declare(strict_types=1);

return [

    // ─── Rutas GET ────────────────────────────────────────────────────────
    'GET' => [
        '/'            => [\App\Controllers\DashboardController::class, 'index'],
        '/login'       => [\App\Controllers\AuthController::class, 'showLogin'],
        '/logout'      => [\App\Controllers\AuthController::class, 'logout'],
        '/auth/totp'   => [\App\Controllers\AuthController::class, 'showTotp'],
        '/perfil/2fa'  => [\App\Controllers\AuthController::class, 'showPerfil2fa'],
        '/dashboard'   => [\App\Controllers\DashboardController::class, 'index'],

        // Zonas y Personal
        '/personal'          => [\App\Controllers\PersonalController::class, 'index'],
        '/zonas/nueva'       => [\App\Controllers\PersonalController::class, 'createZona'],
        '/zonas/editar'      => [\App\Controllers\PersonalController::class, 'editZona'],
        '/personal/nuevo'    => [\App\Controllers\PersonalController::class, 'createPersonal'],
        '/personal/editar'   => [\App\Controllers\PersonalController::class, 'editPersonal'],

        // Clientes
        '/clientes'          => [\App\Controllers\ClienteController::class, 'index'],
        '/clientes/ficha'    => [\App\Controllers\ClienteController::class, 'ficha'],
        '/clientes/nuevo'    => [\App\Controllers\ClienteController::class, 'create'],
        '/clientes/editar'   => [\App\Controllers\ClienteController::class, 'edit'],

        // APIs — Clientes
        '/api/clientes/buscar' => [\App\Controllers\Api\ClienteApiController::class, 'search'],

        // APIs — Créditos
        '/api/creditos/preview'            => [\App\Controllers\Api\CreditoApiController::class, 'preview'],
        '/api/creditos/calendario_preview' => [\App\Controllers\Api\CreditoApiController::class, 'calendarioPreview'],
        '/api/creditos/activos_cliente'    => [\App\Controllers\Api\CreditoApiController::class, 'activosByCliente'],

        // Créditos
        '/creditos'              => [\App\Controllers\CreditoController::class, 'index'],
        '/creditos/ficha'        => [\App\Controllers\CreditoController::class, 'ficha'],
        '/creditos/nuevo'        => [\App\Controllers\CreditoController::class, 'create'],
        '/creditos/refinanciar'  => [\App\Controllers\CreditoController::class, 'refinanciarForm'],

        // Pagos
        '/pagos'           => [\App\Controllers\PagoController::class, 'index'],
        '/pagos/nuevo'     => [\App\Controllers\PagoController::class, 'create'],

        // Comisiones
        '/comisiones' => [\App\Controllers\ComisionController::class, 'index'],

        // Reportes
        '/reportes'                    => [\App\Controllers\ReporteController::class, 'index'],
        '/reportes/vencimientos'       => [\App\Controllers\ReporteController::class, 'vencimientos'],
        '/reportes/exportar/cobranza' => [\App\Controllers\ReporteController::class, 'exportCobranza'],
        '/reportes/exportar/atraso'   => [\App\Controllers\ReporteController::class, 'exportAtraso'],

        // Vista cliente — estado de cuenta
        '/mi-cuenta'         => [\App\Controllers\CuentaClienteController::class, 'index'],

        // Gestión de usuarios (admin)
        '/usuarios'          => [\App\Controllers\UsuarioController::class, 'index'],
        '/usuarios/nuevo'    => [\App\Controllers\UsuarioController::class, 'create'],
        '/usuarios/editar'   => [\App\Controllers\UsuarioController::class, 'edit'],

        // Vista Consulta (cobradores — mobile-first, solo lectura)
        '/consulta'          => [\App\Controllers\ConsultaController::class, 'dashboard'],
        '/consulta/buscar'   => [\App\Controllers\ConsultaController::class, 'buscar'],
        '/consulta/cliente'  => [\App\Controllers\ConsultaController::class, 'fichaCliente'],
        '/consulta/credito'  => [\App\Controllers\ConsultaController::class, 'fichaCredito'],

        // APIs — Pagos
        '/api/pagos/preview_fifo'   => [\App\Controllers\Api\PagoApiController::class, 'previewFifo'],
        '/api/pagos/cuotas_credito' => [\App\Controllers\Api\PagoApiController::class, 'cuotasCredito'],

        // Recibos
        '/recibos/descargar' => [\App\Controllers\PagoController::class, 'descargarRecibo'],
    ],

    // ─── Rutas POST ───────────────────────────────────────────────────────
    'POST' => [
        '/login'                    => [\App\Controllers\AuthController::class, 'handleLogin'],
        '/logout'                   => [\App\Controllers\AuthController::class, 'logout'],
        '/auth/totp'                => [\App\Controllers\AuthController::class, 'handleTotp'],
        '/perfil/2fa/activar'       => [\App\Controllers\AuthController::class, 'confirmarSetup2fa'],
        '/perfil/2fa/iniciar'       => [\App\Controllers\AuthController::class, 'iniciarSetup2fa'],
        '/perfil/2fa/desactivar'    => [\App\Controllers\AuthController::class, 'desactivar2fa'],

        // Zonas y Personal
        '/zonas/storeZona'          => [\App\Controllers\PersonalController::class, 'storeZona'],
        '/zonas/updateZona'         => [\App\Controllers\PersonalController::class, 'updateZona'],
        '/personal/storePersonal'   => [\App\Controllers\PersonalController::class, 'storePersonal'],
        '/personal/updatePersonal'  => [\App\Controllers\PersonalController::class, 'updatePersonal'],

        // Clientes
        '/clientes/store'           => [\App\Controllers\ClienteController::class, 'store'],
        '/clientes/update'          => [\App\Controllers\ClienteController::class, 'update'],

        // Créditos
        '/creditos/store'        => [\App\Controllers\CreditoController::class, 'store'],
        '/creditos/anular'       => [\App\Controllers\CreditoController::class, 'anular'],
        '/creditos/refinanciar'  => [\App\Controllers\CreditoController::class, 'refinanciar'],
        '/creditos/incobrable'   => [\App\Controllers\CreditoController::class, 'marcarIncobrable'],

        // Pagos
        '/pagos/store'     => [\App\Controllers\PagoController::class, 'store'],
        '/pagos/anular'    => [\App\Controllers\PagoController::class, 'anular'],

        // Comisiones
        '/comisiones/liquidar' => [\App\Controllers\ComisionController::class, 'liquidar'],
        '/comisiones/pagar'    => [\App\Controllers\ComisionController::class, 'marcarPagada'],

        // Usuarios (admin)
        '/usuarios/store'    => [\App\Controllers\UsuarioController::class, 'store'],
        '/usuarios/update'   => [\App\Controllers\UsuarioController::class, 'update'],
        '/usuarios/delete'   => [\App\Controllers\UsuarioController::class, 'delete'],
    ],

];
