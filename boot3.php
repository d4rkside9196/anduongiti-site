<?php
// One-shot finalizer v2: full env restore if missing, caches, cleanup.
$log = [];
try {
    $d = 'u515481165_andongiti2';
    $w = 'pAfNbzGGO2SAsQqaswpBKdttaZ9';
    $envFile = __DIR__.'/.env';
    $cur = is_file($envFile) ? (string) file_get_contents($envFile) : '';
    $hasKey = strpos($cur, 'APP_KEY=base64:') !== false;
    if (! $hasKey) {
        $pdo = new PDO("mysql:host=localhost;dbname={$d}", $d, $w, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $env = (string) $pdo->query('SELECT env FROM e LIMIT 1')->fetchColumn();
        if (strpos($env, 'APP_KEY=base64:') === false) {
            throw new RuntimeException('staging env payload missing APP_KEY');
        }
        file_put_contents($envFile, $env);
        chmod($envFile, 0600);
        $log[] = 'env:RESTORED';
    } elseif (strpos($cur, 'FILESYSTEM_DISK_PUBLIC_ROOT') === false) {
        file_put_contents($envFile, rtrim($cur)."\nFILESYSTEM_DISK_PUBLIC_ROOT=/home/u515481165/domains/anduongiti.com/public_html/uploads\n");
        chmod($envFile, 0600);
        $log[] = 'env:APPENDED';
    } else {
        $log[] = 'env:PRESENT';
    }
    require __DIR__.'/vendor/autoload.php';
    $app = require __DIR__.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    foreach (['config:cache', 'event:cache', 'view:cache'] as $cmd) {
        try { \Illuminate\Support\Facades\Artisan::call($cmd); $log[] = $cmd.':OK'; }
        catch (Throwable $ex) { $log[] = $cmd.':ERR'; }
    }
    @unlink(__DIR__.'/boot2.php');
    @unlink(__DIR__.'/fetch2.php');
    @unlink(__FILE__);
    header('Content-Type: text/plain');
    echo 'BOOT3_DONE '.implode(' ', $log);
} catch (Throwable $e) {
    http_response_code(200);
    header('Content-Type: text/plain');
    echo 'BOOT3_FAIL '.substr($e->getMessage(), 0, 300);
}
