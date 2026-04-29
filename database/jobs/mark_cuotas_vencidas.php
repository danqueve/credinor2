<?php

/**
 * Job diario: marca cuotas como 'vencida' cuando la fecha de vencimiento ya pasó.
 * Sin recargo (mora desactivada por default).
 *
 * Ejecutar via cron:
 *   0 1 * * * php /ruta/credinor2/database/jobs/mark_cuotas_vencidas.php >> /ruta/storage/logs/cron.log 2>&1
 */

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH',  ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');

require ROOT_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->load();

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'America/Argentina/Tucuman');

$db = App\Helpers\Database::getInstance();

$sql = "
    UPDATE cuotas c
    INNER JOIN creditos cr ON c.id_credito = cr.id_credito
    SET c.estado = 'vencida'
    WHERE c.fecha_vencimiento < CURDATE()
      AND c.estado = 'pendiente'
      AND cr.estado = 'activo'
      AND cr.deleted_at IS NULL
";

$stmt   = $db->prepare($sql);
$stmt->execute();
$afect  = $stmt->rowCount();

$ts = date('Y-m-d H:i:s');
echo "[{$ts}] mark_cuotas_vencidas: {$afect} cuotas marcadas como vencidas.\n";
