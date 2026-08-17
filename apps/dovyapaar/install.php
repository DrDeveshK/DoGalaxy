<?php
declare(strict_types=1);
if (($_GET['key'] ?? '') !== 'dogalaxy') {
    http_response_code(403);
    exit('forbidden');
}
$candidates = [
    dirname(__DIR__) . '/wp-config.php',
    '/home/koloconi/doudyog.com/wp-config.php',
    '/home/koloconi/public_html/wp-config.php',
];
$wp = '';
foreach ($candidates as $c) {
    if (is_file($c)) {
        $wp = $c;
        break;
    }
}
if ($wp === '') {
    exit('wp-config not found');
}
$src = (string) file_get_contents($wp);
preg_match("/DB_NAME',\s*'([^']+)'/", $src, $n);
preg_match("/DB_USER',\s*'([^']+)'/", $src, $u);
preg_match("/DB_PASSWORD',\s*'([^']+)'/", $src, $p);
preg_match("/DB_HOST',\s*'([^']+)'/", $src, $h);
if (!$n || !$u || !$p) {
    exit('cannot parse wp-config');
}
$pdo = new PDO(
    'mysql:host=' . $h[1] . ';dbname=' . $n[1] . ';charset=utf8mb4',
    $u[1],
    $p[1],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$sql = file_get_contents(__DIR__ . '/schema.sql') ?: '';
preg_match_all('/CREATE TABLE[\s\S]+?;/i', $sql, $m);
foreach ($m[0] as $stmt) {
    $pdo->exec($stmt);
}
file_put_contents(
    __DIR__ . '/config.local.php',
    "<?php\nreturn [\n  'dsn' => " . var_export('mysql:host=' . $h[1] . ';dbname=' . $n[1] . ';charset=utf8mb4', true)
    . ",\n  'user' => " . var_export($u[1], true) . ",\n  'pass' => " . var_export($p[1], true) . ",\n];\n"
);
echo 'ok — tables ready. delete install.php';
