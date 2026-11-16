<?php
// One-shot post-deploy finalizer. Self-deletes.
$log = [];
$root = __DIR__;
@unlink($root.'/public/storage');
if (@symlink($root.'/storage/app/public', $root.'/public/storage')) { $log[] = 'storage:OK'; } else { $log[] = 'storage:FAIL'; }
foreach (['config:cache','event:cache','view:cache'] as $cmd) {
    $out = []; $code = 0;
    exec('/opt/alt/php84/usr/bin/php '.$root.'/artisan '.$cmd.' 2>&1', $out, $code);
    $log[] = $cmd.($code === 0 ? ':OK' : ':FAIL');
}
@unlink(__FILE__);
header('Content-Type: text/plain');
echo 'FINALIZE '.implode(' ', $log);
