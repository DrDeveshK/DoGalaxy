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

function guide_has(string $base, string $q, string $needle): bool
{
    [$c, $h] = req("$base/?p=guide", [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['q' => $q]),
    ]);
    return $c === 200 && str_contains($h, $needle);
}

[$c, $h] = req("$base/?p=home");
ok('home', $c === 200 && strlen($h) > 400);
[$c, $h] = req("$base/?p=guide");
ok('ask do page', $c === 200 && str_contains($h, 'Ask Do'));
ok('ask do aaram', guide_has($base, 'home care', 'DoAaram'));
ok('ask do nirman', guide_has($base, 'cost estimate', 'DoNirman'));
ok('ask do rishta', guide_has($base, '21 safety', 'DoRishta'));
if ($slug === 'mydoapp') {
    [$c, $h] = req("$base/?p=router&q=plumber");
    ok('guided router', $c === 200 && str_contains($h, 'DoAaram'));
}
if ($slug === 'dorojgar') {
    [$c, $h] = req("$base/?p=search&role=accounts&city=Delhi");
    ok('job search filters', $c === 200 && str_contains($h, 'Job search'));
    [$c, $h] = req("$base/?p=resume");
    ok('resume builder', $c === 200 && str_contains($h, 'Resume builder'));
    [$c, $h] = req("$base/?p=career");
    ok('career center', $c === 200 && str_contains($h, 'Career center'));
}
if ($slug === 'dovyapaar') {
    [$c, $h] = req("$base/?p=requirement");
    ok('post requirement', $c === 200 && str_contains($h, 'Post a requirement'));
    [$c, $h] = req("$base/?p=board");
    ok('rfq board', $c === 200 && (str_contains($h, 'Open RFQs') || str_contains($h, 'Corrugated')));
    [$c, $h] = req("$base/?p=products");
    ok('trade products', $c === 200 && str_contains($h, 'TMT'));
    [$c, $h] = req("$base/?p=leads");
    ok('trade leads', $c === 200 && str_contains($h, 'Buy Lead'));
    [$c, $h] = req("$base/?p=trust");
    ok('supplier trust', $c === 200 && str_contains($h, 'B2B only'));
}
if ($slug === 'doswagat') {
    [$c, $h] = req("$base/?p=brief");
    ok('event brief wizard', $c === 200 && str_contains($h, 'Event brief wizard'));
    [$c, $h] = req("$base/?p=packages");
    ok('event packages', $c === 200 && str_contains($h, 'Wedding desk'));
    [$c, $h] = req("$base/?p=track");
    ok('track request', $c === 200 && str_contains($h, 'Track'));
}
if ($slug === 'dorishta') {
    [$c, $h] = req("$base/?p=matches&city=Pune&education=MBA");
    ok('find matches', $c === 200 && str_contains($h, 'Find matches') && str_contains($h, '21+'));
    [$c, $h] = req("$base/?p=safety");
    ok('safety promise', $c === 200 && str_contains($h, '21+'));
}
if ($slug === 'dovishram') {
    [$c, $h] = req("$base/?p=find&type=Homestay&guests=2");
    ok('stay filters', $c === 200 && str_contains($h, 'Find stays'));
    [$c, $h] = req("$base/?p=packages");
    ok('rest packages', $c === 200 && str_contains($h, 'Weekend rest'));
}
if ($slug === 'doaaram') {
    [$c, $h] = req("$base/?p=categories&cat=Plumbing");
    ok('service categories', $c === 200 && str_contains($h, 'UrbanCompany-style'));
    $t = csrf($h);
    [$c, $h] = req("$base/?p=categories", [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $t, 'act' => 'book_service', 'name' => 'Home User', 'email' => 'home@test.local', 'category' => 'Plumbing',
        'phone' => '9', 'when_date' => '2026-09-20', 'slot' => '10am-1pm', 'area' => 'Kothrud', 'address' => 'Near park', 'message' => 'Tap leak',
    ])]);
    ok('service booking', $c === 200 && str_contains($h, 'Track booking'));
    [$c, $h] = req("$base/?p=packs");
    ok('visit packs', $c === 200 && str_contains($h, 'Handyman visit pack'));
    [$c, $h] = req("$base/?p=care");
    ok('care desk', $c === 200 && str_contains($h, 'Family care desk'));
}
if ($slug === 'dobajar') {
    [$c, $h] = req("$base/?p=shop");
    ok('browse shop', $c === 200 && (str_contains($h, 'Browse shop') || str_contains($h, 'mustard')));
}
if ($slug === 'donirman') {
    [$c, $h] = req("$base/?p=estimate");
    ok('cost estimator', $c === 200 && str_contains($h, 'Calculate my estimate') && str_contains($h, '1,800'));
    [$c, $h] = req("$base/?p=materials");
    ok('materials bazaar', $c === 200 && str_contains($h, 'Cement') && str_contains($h, 'Get quotes'));
    [$c, $h] = req("$base/?p=gyan");
    ok('nirman gyan', $c === 200 && str_contains($h, 'NirmanGyan'));
    [$c, $h] = req("$base/?p=boq");
    ok('boq request page', $c === 200 && str_contains($h, 'BOQ / material RFQ'));
}
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
[$c, $h] = req("$base/?p=admin&tab=site");
$t = csrf($h);
[$c, $h] = req("$base/?p=admin&tab=site", [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['csrf' => $t, 'act' => 'reset_site'])]);
ok('reset public copy', $c === 200 && str_contains($h, 'Public copy reset'));
[$c, $h] = req("$base/?p=home");
ok('home copy restored', $c === 200 && !str_contains($h, $mark));

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
