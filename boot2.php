<?php
// One-shot production bootstrap (idempotent). Self-deletes on success.
error_reporting(E_ALL & ~E_DEPRECATED);
$log = [];
try {
    $d = 'u515481165_andongiti2';
    $w = 'pAfNbzGGO2SAsQqaswpBKdttaZ9';
    $pdo = new PDO("mysql:host=localhost;dbname={$d}", $d, $w, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $env = $pdo->query('SELECT env FROM e LIMIT 1')->fetchColumn();
    $envFile = __DIR__.'/.env';
    $needWrite = !file_exists($envFile) || strpos((string) file_get_contents($envFile), 'APP_KEY=') === false;
    if ($needWrite && is_string($env) && strpos($env, 'APP_KEY=') !== false) {
        file_put_contents($envFile, $env);
        chmod($envFile, 0600);
        $log[] = 'env:WRITTEN';
    } else {
        $log[] = $needWrite ? 'env:NO_PAYLOAD' : 'env:PRESENT';
    }
    $log[] = file_exists(__DIR__.'/vendor/autoload.php') ? 'vendor:OK' : 'vendor:MISSING';
    require __DIR__.'/vendor/autoload.php';
    $app = require __DIR__.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    @unlink(__DIR__.'/public/storage');
    $log[] = @symlink(__DIR__.'/storage/app/public', __DIR__.'/public/storage') ? 'storage:OK' : 'storage:FAIL';
    foreach (['config:cache', 'event:cache', 'view:cache'] as $cmd) {
        try { \Illuminate\Support\Facades\Artisan::call($cmd); $log[] = $cmd.':OK'; }
        catch (Throwable $ex) { $log[] = $cmd.':ERR'; }
    }
    @unlink(__FILE__);
    header('Content-Type: text/plain');
    echo 'BOOT2_DONE '.implode(' ', $log);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'BOOT2_FAIL '.substr($e->getMessage(), 0, 300);
}
