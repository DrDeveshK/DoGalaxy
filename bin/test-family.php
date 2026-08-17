<?php
declare(strict_types=1);
$slug = $argv[1] ?? '';
$port = ['dovishram' => 8082, 'dorojgar' => 8083, 'doswagat' => 8084, 'dorishta' => 8085, 'dobajar' => 8086, 'mydoapp' => 8080, 'doaaram' => 8087, 'donirman' => 8088, 'dovyapaar' => 8089][$slug] ?? 0;
if (!$port) {
    fwrite(STDERR, "usage: test-family.php <slug>\n");
    exit(1);
}
$base = "http://127.0.0.1:$port";
$jar = sys_get_temp_dir() . "/dg-$slug.jar";
@unlink($jar);
$fail = 0;

function req(string $url, array $opt = []): array
{
    global $jar;
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 8] + $opt);
    $h = (string) curl_exec($ch);
    $c = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$c, $h];
}
function csrf(string $html): string
{
    return preg_match('/name="csrf" value="([a-f0-9]+)"/', $html, $m) ? $m[1] : '';
}
function ok(string $n, bool $p, string $d = ''): void
{
    global $fail;
    echo ($p ? 'PASS' : 'FAIL') . "  $n" . ($d ? " — $d" : '') . "\n";
    if (!$p) {
        $fail++;
    }
}

[$c, $h] = req("$base/?p=home");
ok('home', $c === 200 && strlen($h) > 400);
[$c, $h] = req("$base/?p=about");
ok('about', $c === 200 && str_contains($h, 'Do'));
[$c, $h] = req("$base/?p=login");
$t = csrf($h);
[$c, $h] = req("$base/?p=login", [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['csrf' => $t, 'act' => 'login', 'email' => "admin@$slug.local", 'password' => 'AdminPass9'])]);
ok('admin login', $c === 200 && str_contains($h, 'Customise'));
[$c, $h] = req("$base/?p=admin&tab=site");
$t = csrf($h);
$mark = $slug . ' ' . time();
[$c, $h] = req("$base/?p=admin&tab=site", [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query([
    'csrf' => $t, 'act' => 'site', 'brand' => $slug, 'topbar' => 't', 'eyebrow' => 'e', 'hero_h1' => $mark, 'hero_p' => 'p', 'services_intro' => 's', 'footer_blurb' => 'f',
])]);
ok('admin save copy', $c === 200 && str_contains($h, 'saved'));
[$c, $h] = req("$base/?p=home");
ok('home uses copy', $c === 200 && str_contains($h, $mark));

if ($slug !== 'mydoapp') {
    @unlink($jar);
    [$c, $h] = req("$base/?p=dir");
    ok('directory', $c === 200 && (str_contains($h, 'View') || str_contains($h, 'None')));
    [$c, $h] = req("$base/?p=view&id=1");
    ok('public listing', $c === 200 && str_contains($h, 'Send'));
    $t = csrf($h);
    [$c, $h] = req("$base/?p=view&id=1", [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $t, 'act' => 'request', 'target_id' => 1, 'name' => 'Guest', 'email' => 'guest@test.local', 'phone' => '9',
        'message' => 'Need this next week.', 'note' => 'Family introduction from Pune.', 'checkin' => '2026-09-01', 'checkout' => '2026-09-03', 'guests' => '2',
        'qty' => '3', 'item' => 'boxes', 'experience' => '2 years', 'event_date' => '2026-10-10', 'when_date' => '2026-09-15', 'site_city' => 'Pune',
    ])]);
    ok('inbound request', $c === 200 && (str_contains($h, 'sent') || str_contains($h, 'Sent') || str_contains($h, 'request')));
    @unlink($jar);
    [$c, $h] = req("$base/?p=login");
    $t = csrf($h);
    [$c, $h] = req("$base/?p=login", [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['csrf' => $t, 'act' => 'login', 'email' => "seed1@$slug.local", 'password' => 'SeedPass9'])]);
    ok('owner login', $c === 200 && str_contains($h, 'Dashboard'));
    [$c, $h] = req("$base/?p=inbox");
    ok('owner inbox', $c === 200 && (str_contains($h, 'Guest') || str_contains($h, 'inbox') || str_contains($h, 'Inbox') || str_contains($h, 'Application') || str_contains($h, 'Request')));
}

echo $fail === 0 ? "\n$slug ALL PASS\n" : "\n$slug $fail FAILED\n";
exit($fail === 0 ? 0 : 1);
