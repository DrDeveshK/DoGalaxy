<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    session_start();
}

function is_local(): bool
{
    if (getenv('DG_LOCAL') === '1') {
        return true;
    }
    $host = $_SERVER['HTTP_HOST'] ?? '';
    return strpos($host, '127.0.0.1') === 0 || strpos($host, 'localhost') === 0;
}

function use_sqlite(): bool
{
    return getenv('DG_LOCAL') === '1' || !is_file(__DIR__ . '/config.local.php');
}

function db(): PDO
{
    static $pdo;
    if ($pdo) {
        return $pdo;
    }
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if (use_sqlite()) {
        $path = __DIR__ . '/local.sqlite';
        $pdo = new PDO('sqlite:' . $path, null, null, $opts);
        $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
        migrate_sqlite($pdo);
        return $pdo;
    }
    $f = __DIR__ . '/config.local.php';
    if (!is_file($f)) {
        http_response_code(503);
        exit('Open install.php?key=dogalaxy once, or run: php bin/serve-local.php doudyog');
    }
    $c = require $f;
    $pdo = new PDO($c['dsn'], $c['user'], $c['pass'], $opts);
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_settings (k VARCHAR(80) NOT NULL PRIMARY KEY, v TEXT NOT NULL)');
    foreach ([
        'dg_mail' => 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, to_email VARCHAR(190) NOT NULL, subject VARCHAR(190) NOT NULL, body TEXT NOT NULL, token VARCHAR(64) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'dg_notices' => 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, title VARCHAR(190) NOT NULL, body TEXT NOT NULL, link VARCHAR(190) NULL, seen TINYINT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'dg_files' => 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, code VARCHAR(32) NOT NULL, path VARCHAR(255) NOT NULL, orig VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'dg_replies' => 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, enquiry_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NULL, author VARCHAR(120) NOT NULL, body TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'dg_orders' => 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, business_id BIGINT UNSIGNED NULL, kind VARCHAR(32) NOT NULL, item VARCHAR(190) NOT NULL, amount VARCHAR(40) NULL, status VARCHAR(32) NOT NULL DEFAULT \'new\', note TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'dg_tokens' => 'id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, purpose VARCHAR(32) NOT NULL, token VARCHAR(64) NOT NULL UNIQUE, expires_at DATETIME NOT NULL',
    ] as $t => $cols) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS $t ($cols) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    ensure_mysql_col($pdo, 'dg_businesses', 'state', 'VARCHAR(120) NULL');
    ensure_mysql_col($pdo, 'dg_businesses', 'phone', 'VARCHAR(40) NULL');
    ensure_mysql_col($pdo, 'dg_businesses', 'logo', 'VARCHAR(255) NULL');
    ensure_mysql_col($pdo, 'dg_businesses', 'verify_note', 'TEXT NULL');
    ensure_mysql_col($pdo, 'dg_businesses', 'featured', 'TINYINT NOT NULL DEFAULT 0');
    ensure_mysql_col($pdo, 'dg_users', 'email_ok', 'TINYINT NOT NULL DEFAULT 0');
    seed_platform($pdo);
    return $pdo;
}

function migrate_sqlite(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, name TEXT NOT NULL,
      phone TEXT, role TEXT NOT NULL DEFAULT \'member\', status TEXT NOT NULL DEFAULT \'active\',
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, last_login_at TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_audit (
      id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, entity TEXT NOT NULL,
      entity_id INTEGER, action TEXT NOT NULL, meta TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_enquiries (
      id INTEGER PRIMARY KEY AUTOINCREMENT, product TEXT NOT NULL, target_id INTEGER, user_id INTEGER,
      name TEXT NOT NULL, email TEXT NOT NULL, phone TEXT, intent TEXT NOT NULL DEFAULT \'general\',
      message TEXT NOT NULL, status TEXT NOT NULL DEFAULT \'new\', created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_businesses (
      id INTEGER PRIMARY KEY AUTOINCREMENT, owner_id INTEGER NOT NULL, legal_name TEXT NOT NULL,
      industry TEXT NOT NULL, city TEXT NOT NULL, state TEXT, gstin TEXT UNIQUE, udyam_no TEXT, pan TEXT,
      employees TEXT, year_started INTEGER, website TEXT, about TEXT,
      verify_status TEXT NOT NULL DEFAULT \'pending\', completeness INTEGER NOT NULL DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_compliance (
      id INTEGER PRIMARY KEY AUTOINCREMENT, business_id INTEGER NOT NULL, code TEXT NOT NULL,
      done INTEGER NOT NULL DEFAULT 0, note TEXT, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE(business_id, code))');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_settings (
      k TEXT PRIMARY KEY, v TEXT NOT NULL)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_mail (
      id INTEGER PRIMARY KEY AUTOINCREMENT, to_email TEXT NOT NULL, subject TEXT NOT NULL,
      body TEXT NOT NULL, token TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_notices (
      id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, title TEXT NOT NULL,
      body TEXT NOT NULL, link TEXT, seen INTEGER NOT NULL DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_files (
      id INTEGER PRIMARY KEY AUTOINCREMENT, business_id INTEGER NOT NULL, code TEXT NOT NULL,
      path TEXT NOT NULL, orig TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_replies (
      id INTEGER PRIMARY KEY AUTOINCREMENT, enquiry_id INTEGER NOT NULL, user_id INTEGER,
      author TEXT NOT NULL, body TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_orders (
      id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, business_id INTEGER, kind TEXT NOT NULL,
      item TEXT NOT NULL, amount TEXT, status TEXT NOT NULL DEFAULT \'new\', note TEXT,
      created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_tokens (
      id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, purpose TEXT NOT NULL,
      token TEXT NOT NULL UNIQUE, expires_at TEXT NOT NULL)');
    ensure_col($pdo, 'dg_businesses', 'state', 'TEXT');
    ensure_col($pdo, 'dg_businesses', 'phone', 'TEXT');
    ensure_col($pdo, 'dg_businesses', 'logo', 'TEXT');
    ensure_col($pdo, 'dg_businesses', 'verify_note', 'TEXT');
    ensure_col($pdo, 'dg_businesses', 'featured', 'INTEGER NOT NULL DEFAULT 0');
    ensure_col($pdo, 'dg_users', 'email_ok', 'INTEGER NOT NULL DEFAULT 0');
    seed_platform($pdo);
}

function ensure_col(PDO $pdo, string $table, string $col, string $def): void
{
    foreach ($pdo->query("PRAGMA table_info($table)") as $r) {
        if ($r['name'] === $col) {
            return;
        }
    }
    $pdo->exec("ALTER TABLE $table ADD COLUMN $col $def");
}

function ensure_mysql_col(PDO $pdo, string $table, string $col, string $def): void
{
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $st = $pdo->query("SHOW COLUMNS FROM `$safeTable` LIKE " . $pdo->quote($col));
    if (!$st->fetch()) {
        $pdo->exec("ALTER TABLE `$safeTable` ADD COLUMN `$col` $def");
    }
}

function seed_platform(PDO $pdo): void
{
    $ins = $pdo->prepare('INSERT INTO dg_settings (k, v) VALUES (?,?)');
    $chk = $pdo->prepare('SELECT k FROM dg_settings WHERE k=?');
    foreach (default_settings() as $k => $v) {
        $chk->execute([$k]);
        if (!$chk->fetch()) {
            $ins->execute([$k, $v]);
        }
    }
    $st = $pdo->prepare('SELECT id FROM dg_users WHERE email=?');
    $st->execute(['admin@doudyog.local']);
    if (!$st->fetch()) {
        $pdo->prepare('INSERT INTO dg_users (email, password_hash, name, role) VALUES (?,?,?,?)')
            ->execute(['admin@doudyog.local', password_hash('AdminPass9', PASSWORD_DEFAULT), 'DoUdyog Admin', 'admin']);
    }
}

function default_settings(): array
{
    return [
        'brand' => 'DoUdyog',
        'topbar' => 'DoUdyog — Business identity, compliance, growth and MSME enablement',
        'eyebrow' => 'उद्योग बढ़े, भारत बढ़े',
        'hero_h1' => 'Build, verify and grow your business with DoUdyog.',
        'hero_p' => 'DoUdyog is the MSME operating centre of Do Galaxy. Create a business profile, manage compliance, get found, then hire, trade and sell across the other planets.',
        'services_intro' => 'Packaged help. Request any item — it lands as an enquiry.',
        'growth_intro' => 'Guided tracks. Join from contact — we match an advisor.',
        'packages' => "MSME Business Profile Setup | ₹999 | Verified identity and Do Galaxy readiness.\nGST & Compliance Starter | ₹1,999 | GST basics, document checklist, filing calendar.\nDigital Storefront Launch | ₹2,999 | Get listed on DoUdyog, DoBajar and DoVyapaar.\nHiring & Workforce Kit | ₹1,499 | Connect with DoRojgar for staff and contractors.\nBusiness Growth Audit | ₹4,999 | Operations, sales and technology review.\nVendor & Supplier Network | ₹2,499 | Discover partners through DoVyapaar.",
        'programs' => "Udyog Starter Program | 4 weeks | Identity, compliance and first customers.\nDigital Vyapaar Program | 4 weeks | Trade discovery and storefront.\nLocal to National Growth Sprint | 6 weeks | From city market to multi-city.\nWomen Entrepreneurs Circle | Ongoing | Peer group plus advisor hours.\nManufacturing Excellence Track | 8 weeks | Process, quality, vendor readiness.\nRetail Growth Accelerator | 4 weeks | Billing, stock, local demand.",
        'price_starter' => 'Free',
        'price_verified' => '₹999',
        'price_growth' => '₹4,999',
        'footer_blurb' => "India's business operating-system planet under Do Galaxy — identity, compliance, services and growth for MSMEs.",
        'contact_email' => 'hello@doudyog.com',
        'contact_phone' => '+91 00000 00000',
        'page_about' => "DoUdyog is the MSME operating centre of Do Galaxy. We help shops, factories and service firms create a trusted business identity, keep a compliance ledger, get found in the directory, and move into hiring, trade and selling on the other planets.\n\nKusumit Universe · MyDoApp.",
        'page_privacy' => "We store the account, business profile, compliance notes, uploaded documents and enquiries you submit. Data stays on the DoUdyog database. Staff can see it to verify and support you. We do not sell your data. Write to the contact email to request deletion.",
        'page_terms' => "DoUdyog is a listing and operating tool, not a government portal and not legal advice. You are responsible for the accuracy of GST, Udyam and other numbers you enter. Verification is a human review of what you uploaded. Paid packages are fulfilled after staff accept the request.",
    ];
}

function reset_public_copy(): void
{
    foreach (default_settings() as $k => $v) {
        if (in_array($k, ['brand', 'topbar', 'eyebrow', 'hero_h1', 'hero_p', 'services_intro', 'growth_intro', 'footer_blurb'], true)) {
            setting_set($k, $v);
        }
    }
}

function setting(string $k, string $fallback = ''): string
{
    static $all;
    if ($k === '__bust' || $all === null) {
        $all = [];
        foreach (db()->query('SELECT k, v FROM dg_settings') as $r) {
            $all[$r['k']] = $r['v'];
        }
        if ($k === '__bust') {
            return '';
        }
    }
    return (string) ($all[$k] ?? $fallback);
}

function setting_set(string $k, string $v): void
{
    $sql = is_local()
        ? 'INSERT INTO dg_settings (k, v) VALUES (?,?) ON CONFLICT(k) DO UPDATE SET v=excluded.v'
        : 'INSERT INTO dg_settings (k, v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)';
    db()->prepare($sql)->execute([$k, $v]);
    setting('__bust');
}

function catalog_lines(string $raw): array
{
    $out = [];
    foreach (preg_split('/\r?\n/', trim($raw)) as $line) {
        if ($line === '') {
            continue;
        }
        $p = array_map('trim', explode('|', $line));
        $out[] = [$p[0] ?? '', $p[1] ?? '', $p[2] ?? ''];
    }
    return $out;
}

function is_admin(): bool
{
    return (user()['role'] ?? '') === 'admin';
}

function owner_biz(PDO $db): ?array
{
    if (!user()) {
        return null;
    }
    $st = $db->prepare('SELECT * FROM dg_businesses WHERE owner_id=?');
    $st->execute([user()['id']]);
    return $st->fetch() ?: null;
}

function queue_mail(PDO $db, string $to, string $subject, string $body, ?string $token = null): void
{
    $db->prepare('INSERT INTO dg_mail (to_email, subject, body, token) VALUES (?,?,?,?)')
        ->execute([$to, $subject, $body, $token]);
}

function notify(PDO $db, int $uid, string $title, string $body, string $link = ''): void
{
    $db->prepare('INSERT INTO dg_notices (user_id, title, body, link) VALUES (?,?,?,?)')
        ->execute([$uid, $title, $body, $link ?: null]);
}

function unseen_n(PDO $db): int
{
    if (!user()) {
        return 0;
    }
    $st = $db->prepare('SELECT COUNT(*) FROM dg_notices WHERE user_id=? AND seen=0');
    $st->execute([user()['id']]);
    return (int) $st->fetchColumn();
}

function thread_key(array $e): string
{
    return substr(hash('sha256', $e['id'] . '|' . $e['email']), 0, 16);
}

function save_upload(string $field, int $bid, string $code): ?string
{
    $f = $_FILES[$field] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($f['error'] ?? 1) !== UPLOAD_ERR_OK || ($f['size'] ?? 0) > 4_000_000) {
        return '';
    }
    $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true)) {
        return '';
    }
    $dir = __DIR__ . '/uploads/' . $bid;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return '';
    }
    $name = $code . '-' . time() . '.' . $ext;
    if (!move_uploaded_file((string) $f['tmp_name'], $dir . '/' . $name)) {
        return '';
    }
    return $bid . '/' . $name;
}

function dash_nav(string $on = 'dash'): string
{
    $html = '<aside class="dash-nav">';
    foreach (['dash' => 'Profile', 'compliance' => 'Compliance', 'docs' => 'Documents', 'inbox' => 'Inbox', 'orders' => 'Requests', 'notices' => 'Notifications', 'account' => 'Account'] as $k => $lab) {
        $html .= '<a href="?p=' . $k . '"' . ($on === $k ? ' class="on"' : '') . '>' . $lab . '</a>';
    }
    $html .= '<a href="?p=dir">Directory</a></aside>';
    return $html;
}

function csrf_fields(string $act): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><input type="hidden" name="act" value="' . h($act) . '">';
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function site_icon_href(string $mark): string
{
    $letter = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $mark) ?: 'D', 0, 1));
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96"><rect width="96" height="96" rx="22" fill="#073a76"/><text x="48" y="63" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="54" font-weight="800" fill="#fff">' . $letter . '</text></svg>';
    return 'data:image/svg+xml,' . rawurlencode($svg);
}

function user(): ?array
{
    return $_SESSION['u'] ?? null;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_ok(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string) $_POST['csrf']);
}

function audit(PDO $db, ?int $uid, string $entity, ?int $eid, string $action): void
{
    $db->prepare('INSERT INTO dg_audit (user_id, entity, entity_id, action) VALUES (?,?,?,?)')
        ->execute([$uid, $entity, $eid, $action]);
}

function flash(?string $set = null): string
{
    if ($set !== null) {
        $_SESSION['flash'] = $set;
        return '';
    }
    $m = (string) ($_SESSION['flash'] ?? '');
    unset($_SESSION['flash']);
    return $m;
}

function go(string $p): never
{
    header('Location: ?p=' . $p);
    exit;
}

function handle_auth(PDO $db): string
{
    $act = (string) ($_POST['act'] ?? '');
    if ($act === 'logout') {
        $_SESSION = [];
        go('home');
    }
    if ($act !== '' && !csrf_ok()) {
        return 'Session expired. Try again.';
    }
    if ($act === 'register') {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $name = trim((string) ($_POST['name'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $biz = trim((string) ($_POST['legal_name'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        if (!$email || $name === '' || strlen($pass) < 8 || $biz === '' || $city === '') {
            return 'Name, email, password (8+), business name and city are required.';
        }
        try {
            $db->beginTransaction();
            $db->prepare('INSERT INTO dg_users (email, password_hash, name, phone, role) VALUES (?,?,?,?,?)')
                ->execute([$email, password_hash($pass, PASSWORD_DEFAULT), $name, trim((string) ($_POST['phone'] ?? '')), 'owner']);
            $uid = (int) $db->lastInsertId();
            $db->prepare('INSERT INTO dg_businesses (owner_id, legal_name, industry, city, about, verify_status) VALUES (?,?,?,?,?,?)')
                ->execute([$uid, $biz, (string) ($_POST['industry'] ?? 'Services'), $city, trim((string) ($_POST['about'] ?? '')), 'pending']);
            $bid = (int) $db->lastInsertId();
            $ins = $db->prepare('INSERT INTO dg_compliance (business_id, code, done) VALUES (?,?,0)');
            foreach (['udyam', 'gstin', 'pan', 'bank', 'address', 'licence', 'invoice', 'contact'] as $c) {
                $ins->execute([$bid, $c]);
            }
            audit($db, $uid, 'business', $bid, 'register');
            $tok = bin2hex(random_bytes(16));
            $db->prepare('INSERT INTO dg_tokens (user_id, purpose, token, expires_at) VALUES (?,?,?,?)')
                ->execute([$uid, 'verify', $tok, date('c', time() + 86400 * 7)]);
            queue_mail($db, (string) $email, 'Confirm your DoUdyog email', "Confirm: /?p=verify&token=$tok", $tok);
            notify($db, $uid, 'Welcome to DoUdyog', 'Complete compliance, upload documents, then request verification.', '?p=docs');
            $db->commit();
            $_SESSION['u'] = ['id' => $uid, 'name' => $name, 'email' => $email, 'role' => 'owner'];
            flash('Business record created. Complete compliance, then upload documents.');
            go('dash');
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate')
                ? 'That email is already registered.'
                : 'Could not create the account.';
        }
    }
    if ($act === 'login') {
        $st = $db->prepare('SELECT * FROM dg_users WHERE email=? AND status=?');
        $st->execute([(string) ($_POST['email'] ?? ''), 'active']);
        $u = $st->fetch();
        if (!$u || !password_verify((string) ($_POST['password'] ?? ''), $u['password_hash'])) {
            return 'Incorrect email or password.';
        }
        $db->prepare('UPDATE dg_users SET last_login_at=? WHERE id=?')->execute([date('c'), $u['id']]);
        audit($db, (int) $u['id'], 'user', (int) $u['id'], 'login');
        $_SESSION['u'] = ['id' => (int) $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
        go($u['role'] === 'admin' ? 'admin' : 'dash');
    }
    if ($act === 'forgot') {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if ($email) {
            $st = $db->prepare('SELECT id FROM dg_users WHERE email=? AND status=?');
            $st->execute([$email, 'active']);
            $uid = (int) $st->fetchColumn();
            if ($uid) {
                $tok = bin2hex(random_bytes(16));
                $db->prepare('INSERT INTO dg_tokens (user_id, purpose, token, expires_at) VALUES (?,?,?,?)')
                    ->execute([$uid, 'reset', $tok, date('c', time() + 3600)]);
                $link = (is_local() ? 'http://127.0.0.1:8081' : '') . '/?p=reset&token=' . $tok;
                queue_mail($db, $email, 'Reset your DoUdyog password', "Open this link within one hour:\n$link", $tok);
            }
        }
        flash('If that email is registered, a reset link is in the mail log.');
        go('forgot');
    }
    if ($act === 'reset') {
        $tok = (string) ($_POST['token'] ?? '');
        $pass = (string) ($_POST['password'] ?? '');
        if (strlen($pass) < 8 || $tok === '') {
            return 'Password must be 8+ characters.';
        }
        $st = $db->prepare('SELECT * FROM dg_tokens WHERE token=? AND purpose=?');
        $st->execute([$tok, 'reset']);
        $row = $st->fetch();
        if (!$row || strtotime((string) $row['expires_at']) < time()) {
            return 'This reset link is invalid or expired.';
        }
        $db->prepare('UPDATE dg_users SET password_hash=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), $row['user_id']]);
        $db->prepare('DELETE FROM dg_tokens WHERE id=?')->execute([$row['id']]);
        flash('Password updated. Log in.');
        go('login');
    }
    if ($act === 'changepass' && user()) {
        $st = $db->prepare('SELECT password_hash FROM dg_users WHERE id=?');
        $st->execute([user()['id']]);
        $hash = (string) $st->fetchColumn();
        if (!password_verify((string) ($_POST['old'] ?? ''), $hash)) {
            return 'Current password is wrong.';
        }
        $pass = (string) ($_POST['password'] ?? '');
        if (strlen($pass) < 8) {
            return 'New password must be 8+ characters.';
        }
        $db->prepare('UPDATE dg_users SET password_hash=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), user()['id']]);
        flash('Password changed.');
        go('account');
    }
    return '';
}

function shell_start(string $title = 'DoUdyog'): void
{
    $me = user();
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . h($title) . '</title><link rel="icon" href="' . h(site_icon_href('D')) . '"><link rel="apple-touch-icon" href="' . h(site_icon_href('D')) . '"><link rel="stylesheet" href="assets/app.css"></head><body>';
    echo '<a class="skip-link" href="#main">Skip to content</a>';
    echo '<div class="topbar"><div class="container"><span>' . h(setting('topbar', 'DoUdyog')) . '</span><span>Kusumit Universe · MyDoApp</span></div></div>';
    echo '<header class="site-header"><div class="container header-inner">';
    echo '<a class="brand" href="?p=home"><span class="brand-mark">D<span>o</span></span><span>' . h(setting('brand', 'DoUdyog')) . '</span></a>';
    echo '<nav class="nav" id="mainNav">';
    echo '<a href="?p=home">Home</a><a href="?p=dir">Businesses</a><a href="?p=services">Services</a><a href="?p=growth">Growth</a><a href="?p=readiness">Galaxy readiness</a><a href="?p=pricing">Pricing</a><a href="?p=guide">Ask Do</a><a href="?p=contact">Contact</a>';
    if ($me) {
        if (is_admin()) {
            echo '<a href="?p=admin">Admin</a>';
        } else {
            $n = unseen_n(db());
            echo '<a href="?p=dash">Dashboard</a><a href="?p=notices">Alerts' . ($n ? ' (' . $n . ')' : '') . '</a>';
        }
        echo '<form method="post"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><input type="hidden" name="act" value="logout"><button type="submit">Log out</button></form>';
    } else {
        echo '<a href="?p=login">Log in</a>';
    }
    echo '</nav><button class="btn light mobile-toggle" type="button" onclick="document.getElementById(\'mainNav\').classList.toggle(\'open\')">Menu</button>';
    echo '<a class="btn" href="?p=join">Join Udyog</a></div></header><main id="main">';
}

function shell_end(): void
{
    echo '</main><footer class="footer"><div class="container footer-grid">';
    echo '<div><h3>' . h(setting('brand', 'DoUdyog')) . '</h3><p>' . h(setting('footer_blurb', 'MSME operating centre.')) . '</p></div>';
    echo '<div><h4>Platform</h4><a href="?p=dir">Businesses</a><a href="?p=services">Services</a><a href="?p=growth">Growth</a><a href="?p=readiness">Galaxy readiness</a><a href="?p=pricing">Pricing</a><a href="?p=guide">Ask Do</a></div>';
    echo '<div><h4>Do Galaxy</h4>';
    guide_render_footer_links();
    echo '</div>';
    echo '<div><h4>Company</h4><a href="?p=about">About</a><a href="?p=privacy">Privacy</a><a href="?p=terms">Terms</a><a href="?p=contact">Contact</a></div></div>';
    echo '<div class="container" style="border-top:1px solid rgba(255,255,255,.12);margin-top:28px;padding-top:18px;color:#91a8c8">© ' . date('Y') . ' DoUdyog. A Kusumit Universe initiative.</div></footer>';
    guide_widget('doudyog');
    echo '</body></html>';
}

require_once dirname(__DIR__) . '/_platform/guide.php';
