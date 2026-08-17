<?php
declare(strict_types=1);
$base = $argv[1] ?? 'http://127.0.0.1:8081';
$jar = sys_get_temp_dir() . '/dg-udyog.jar';
@unlink($jar);
$fail = 0;

function req(string $url, array $opt = []): array
{
    global $jar;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 8,
    ] + $opt);
    $raw = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hs = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return [$code, substr((string) $raw, $hs), (string) $raw];
}

function csrf(string $html): string
{
    return preg_match('/name="csrf" value="([a-f0-9]+)"/', $html, $m) ? $m[1] : '';
}

function ok(string $name, bool $pass, string $detail = ''): void
{
    global $fail;
    echo ($pass ? 'PASS' : 'FAIL') . "  $name" . ($detail !== '' ? " — $detail" : '') . "\n";
    if (!$pass) {
        $fail++;
    }
}

[$c, $h] = req("$base/?p=home");
ok('home', $c === 200 && str_contains($h, 'Register business'));

[$c, $h] = req("$base/?p=dir");
ok('directory seeded', $c === 200 && str_contains($h, 'Sharma Engineering Works') && str_contains($h, 'Aarambh Retail'));

[$c, $h] = req("$base/?p=view&id=1");
ok('public profile', $c === 200 && str_contains($h, 'Enquire'));
$t = csrf($h);
[$c, $h, $raw] = req("$base/?p=view&id=1", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $t, 'act' => 'enquire', 'target_id' => 1,
        'name' => 'Buyer One', 'email' => 'buyer@test.local', 'phone' => '9999999999',
        'intent' => 'supply', 'message' => 'Need 50 units next month.',
    ]),
]);
ok('enquiry stored', $c === 200 && (str_contains($h, 'Stored') || str_contains($h, 'Enquiry received') || str_contains($raw, 'sent=1')));

foreach (['services', 'growth', 'pricing', 'contact', 'join', 'login'] as $p) {
    [$c, $h] = req("$base/?p=$p");
    ok("page $p", $c === 200 && strlen($h) > 400);
}

[$c, $h] = req("$base/?p=join");
$t = csrf($h);
$email = 'owner' . time() . '@test.local';
[$c, $h] = req("$base/?p=join", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $t, 'act' => 'register',
        'name' => 'Test Owner', 'email' => $email, 'password' => 'TestPass99',
        'phone' => '8888888888', 'legal_name' => 'Test Foundry Pvt Ltd',
        'city' => 'Nashik', 'industry' => 'Manufacturing', 'about' => 'Castings.',
    ]),
]);
ok('register → dash', $c === 200 && str_contains($h, 'Business dashboard') && str_contains($h, 'Test Foundry'));

[$c, $h] = req("$base/?p=dash");
$t = csrf($h);
[$c, $h] = req("$base/?p=dash", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $t, 'act' => 'save', 'legal_name' => 'Test Foundry Pvt Ltd',
        'industry' => 'Manufacturing', 'city' => 'Nashik',
        'gstin' => '27AAAAA' . substr((string) time(), -4) . 'Z5',
        'udyam_no' => 'UDYAM-MH-00-0000001', 'pan' => 'AAAAA0000A',
        'employees' => '12', 'year_started' => '2018', 'website' => 'https://test.local',
        'about' => 'Castings for auto.',
    ]),
]);
ok('save profile', $c === 200 && str_contains($h, 'Profile saved'));

[$c, $h] = req("$base/?p=compliance");
ok('compliance page', $c === 200 && str_contains($h, 'Udyam'));
$t = csrf($h);
[$c, $h] = req("$base/?p=compliance", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $t, 'act' => 'compliance',
        'done' => ['udyam' => '1', 'gstin' => '1', 'pan' => '1', 'contact' => '1'],
        'note' => ['udyam' => 'UDYAM-MH-00-0000001', 'gstin' => '27AAAAA0000A1Z5'],
    ]),
]);
ok('compliance score', $c === 200 && (str_contains($h, '50%') || str_contains($h, 'ledger updated')));

@unlink($jar);
[$c, $h] = req("$base/?p=login");
$t = csrf($h);
[$c, $h] = req("$base/?p=login", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $t, 'act' => 'login',
        'email' => 'seed1@doudyog.local', 'password' => 'SeedPass9',
    ]),
]);
ok('seed login + inbox', $c === 200 && str_contains($h, 'Sharma Engineering') && str_contains($h, 'Buyer One'));

[$c] = req("$base/?p=dash");
ok('auth gate', $c === 200);

@unlink($jar);
[$c, $h] = req("$base/?p=admin");
ok('admin gate', $c === 200 && (str_contains($h, 'Owner login') || str_contains($h, 'Log in')));
[$c, $h] = req("$base/?p=login");
$t = csrf($h);
[$c, $h] = req("$base/?p=login", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $t, 'act' => 'login',
        'email' => 'admin@doudyog.local', 'password' => 'AdminPass9',
    ]),
]);
ok('admin login', $c === 200 && str_contains($h, 'Customise the public site'));
[$c, $h] = req("$base/?p=admin&tab=site");
$t = csrf($h);
$mark = 'Udyog test ' . time();
[$c, $h] = req("$base/?p=admin&tab=site", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $t, 'act' => 'site',
        'brand' => 'DoUdyog', 'topbar' => 'DoUdyog admin test',
        'eyebrow' => 'उद्योग बढ़े, भारत बढ़े', 'hero_h1' => $mark,
        'hero_p' => 'Admin-customised hero.', 'services_intro' => 'Packaged help.',
        'growth_intro' => 'Guided tracks.', 'footer_blurb' => 'MSME centre.',
    ]),
]);
ok('admin save copy', $c === 200 && str_contains($h, 'Site copy saved'));
[$c, $h] = req("$base/?p=home");
ok('home uses admin copy', $c === 200 && str_contains($h, $mark));
[$c, $h] = req("$base/?p=admin&tab=biz");
$t = csrf($h);
[$c, $h] = req("$base/?p=admin&tab=biz", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $t, 'act' => 'verify', 'id' => 1, 'status' => 'verified',
    ]),
]);
ok('admin verify firm', $c === 200 && str_contains($h, 'marked verified'));

[$c, $h] = req("$base/?p=about");
ok('about page', $c === 200 && str_contains($h, 'MSME'));
[$c, $h] = req("$base/?p=privacy");
ok('privacy page', $c === 200 && str_contains($h, 'data'));
[$c, $h] = req("$base/?p=terms");
ok('terms page', $c === 200 && str_contains($h, 'listing'));

@unlink($jar);
[$c, $h] = req("$base/?p=forgot");
$t = csrf($h);
[$c, $h] = req("$base/?p=forgot", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['csrf' => $t, 'act' => 'forgot', 'email' => 'seed2@doudyog.local']),
]);
ok('forgot password', $c === 200 && str_contains($h, 'reset link'));
$pdo = new PDO('sqlite:' . dirname(__DIR__) . '/apps/doudyog/local.sqlite');
$tok = (string) $pdo->query("SELECT token FROM dg_tokens WHERE purpose='reset' ORDER BY id DESC LIMIT 1")->fetchColumn();
ok('reset token mailed', $tok !== '');
[$c, $h] = req("$base/?p=reset&token=$tok");
$t = csrf($h);
[$c, $h] = req("$base/?p=reset&token=$tok", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['csrf' => $t, 'act' => 'reset', 'token' => $tok, 'password' => 'NewPass99']),
]);
ok('reset password', $c === 200 && str_contains($h, 'Password updated'));
[$c, $h] = req("$base/?p=login");
$t = csrf($h);
[$c, $h] = req("$base/?p=login", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['csrf' => $t, 'act' => 'login', 'email' => 'seed2@doudyog.local', 'password' => 'NewPass99']),
]);
ok('login after reset', $c === 200 && str_contains($h, 'Aarambh'));

[$c, $h] = req("$base/?p=services");
$t = csrf($h);
[$c, $h] = req("$base/?p=services", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $t, 'act' => 'order', 'kind' => 'service',
        'item' => 'GST & Compliance Starter', 'amount' => '₹1,999',
    ]),
]);
ok('service request', $c === 200 && str_contains($h, 'Request #'));

@unlink($jar);
[$c, $h] = req("$base/?p=login");
$t = csrf($h);
req("$base/?p=login", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['csrf' => $t, 'act' => 'login', 'email' => 'seed1@doudyog.local', 'password' => 'SeedPass9']),
]);
[$c, $h] = req("$base/?p=inbox");
ok('owner inbox', $c === 200 && str_contains($h, 'Inbox'));
$eid = (int) $pdo->query("SELECT id FROM dg_enquiries WHERE target_id=1 ORDER BY id DESC LIMIT 1")->fetchColumn();
[$c, $h] = req("$base/?p=inbox&id=$eid");
$t = csrf($h);
[$c, $h] = req("$base/?p=inbox&id=$eid", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['csrf' => $t, 'act' => 'reply', 'enquiry_id' => $eid, 'body' => 'We can supply next week.']),
]);
ok('owner reply', $c === 200 && str_contains($h, 'Reply sent'));

@unlink($jar);
[$c, $h] = req("$base/?p=login");
$t = csrf($h);
req("$base/?p=login", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['csrf' => $t, 'act' => 'login', 'email' => 'admin@doudyog.local', 'password' => 'AdminPass9']),
]);
[$c, $h] = req("$base/?p=admin&tab=mail");
ok('admin mail log', $c === 200 && str_contains($h, 'Reset your DoUdyog'));
[$c, $h] = req("$base/?p=admin&tab=orders");
$t = csrf($h);
$oid = (int) $pdo->query('SELECT id FROM dg_orders ORDER BY id DESC LIMIT 1')->fetchColumn();
[$c, $h] = req("$base/?p=admin&tab=orders", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['csrf' => $t, 'act' => 'order_status', 'id' => $oid, 'status' => 'accepted']),
]);
ok('admin accept request', $c === 200 && str_contains($h, 'marked accepted'));
[$c, $h] = req("$base/?p=admin&tab=site");
$t = csrf($h);
req("$base/?p=admin&tab=site", [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf' => $t, 'act' => 'site',
        'brand' => 'DoUdyog',
        'topbar' => 'DoUdyog — Business identity, compliance, growth and MSME enablement',
        'eyebrow' => 'उद्योग बढ़े, भारत बढ़े',
        'hero_h1' => 'Build, verify and grow your business with DoUdyog.',
        'hero_p' => 'DoUdyog is the MSME operating centre of Do Galaxy. Create a business profile, manage compliance, get found, then hire, trade and sell across the other planets.',
        'services_intro' => 'Packaged help. Request any item — it lands as an enquiry.',
        'growth_intro' => 'Guided tracks. Join from contact — we match an advisor.',
        'footer_blurb' => "India's business operating-system planet under Do Galaxy — identity, compliance, services and growth for MSMEs.",
    ]),
]);

echo $fail === 0 ? "\nALL PASS\n" : "\n$fail FAILED\n";
exit($fail === 0 ? 0 : 1);
