<?php
/**
 * Local server: php .tools/php bin/serve-local.php doudyog
 */
declare(strict_types=1);
$slug = $argv[1] ?? 'doudyog';
$root = dirname(__DIR__);
$app = $root . '/apps/' . $slug;
$php = $root . '/.tools/php';
if (!is_dir($app)) {
    fwrite(STDERR, "unknown app $slug\n");
    exit(1);
}
putenv('DG_LOCAL=1');
$_ENV['DG_LOCAL'] = '1';
touch($app . '/local.sqlite');
// bootstrap schema + seed via a one-shot include
passthru(escapeshellarg($php) . ' ' . escapeshellarg($root . '/bin/seed-local.php') . ' ' . escapeshellarg($slug), $code);
if ($code !== 0) {
    exit($code);
}
$port = ['doudyog' => 8081, 'dovishram' => 8082, 'dorojgar' => 8083, 'doswagat' => 8084, 'dorishta' => 8085, 'dobajar' => 8086, 'mydoapp' => 8080, 'doaaram' => 8087, 'donirman' => 8088, 'dovyapaar' => 8089][$slug] ?? 8090;
echo "http://127.0.0.1:{$port}/  ({$slug})\n";
passthru(escapeshellarg($php) . ' -S 127.0.0.1:' . $port . ' -t ' . escapeshellarg($app));
