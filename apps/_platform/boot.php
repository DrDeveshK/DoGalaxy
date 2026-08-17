<?php
declare(strict_types=1);
if (!defined('DG_APP')) {
    define('DG_APP', __DIR__);
}
if (PHP_SAPI !== 'cli') {
    session_start();
}

function product(): array
{
    static $p;
    if ($p === null) {
        $p = require DG_APP . '/product.php';
    }
    return $p;
}

function is_local(): bool
{
    if (getenv('DG_LOCAL') === '1') {
        return true;
    }
    if (is_file(DG_APP . '/config.local.php')) {
        return false;
    }
    return is_file(DG_APP . '/local.sqlite');
}

function db(): PDO
{
    static $pdo;
    if ($pdo) {
        return $pdo;
    }
    $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false];
    if (is_local()) {
        $pdo = new PDO('sqlite:' . DG_APP . '/local.sqlite', null, null, $opts);
        $pdo->exec('PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON;');
        migrate_sqlite($pdo);
        return $pdo;
    }
    $f = DG_APP . '/config.local.php';
    if (!is_file($f)) {
        http_response_code(503);
        exit('Open install.php?key=dogalaxy once, or run: php bin/serve-local.php ' . product()['slug']);
    }
    $c = require $f;
    $pdo = new PDO($c['dsn'], $c['user'], $c['pass'], $opts);
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_settings (k VARCHAR(80) NOT NULL PRIMARY KEY, v TEXT NOT NULL)');
    seed_platform($pdo);
    return $pdo;
}

function migrate_sqlite(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, name TEXT NOT NULL, phone TEXT, role TEXT NOT NULL DEFAULT \'member\', status TEXT NOT NULL DEFAULT \'active\', email_ok INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, last_login_at TEXT)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_audit (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, entity TEXT NOT NULL, entity_id INTEGER, action TEXT NOT NULL, meta TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_enquiries (id INTEGER PRIMARY KEY AUTOINCREMENT, product TEXT NOT NULL, target_id INTEGER, user_id INTEGER, name TEXT NOT NULL, email TEXT NOT NULL, phone TEXT, intent TEXT NOT NULL DEFAULT \'general\', message TEXT NOT NULL, status TEXT NOT NULL DEFAULT \'new\', created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_settings (k TEXT PRIMARY KEY, v TEXT NOT NULL)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_mail (id INTEGER PRIMARY KEY AUTOINCREMENT, to_email TEXT NOT NULL, subject TEXT NOT NULL, body TEXT NOT NULL, token TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_notices (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, title TEXT NOT NULL, body TEXT NOT NULL, link TEXT, seen INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_files (id INTEGER PRIMARY KEY AUTOINCREMENT, listing_id INTEGER NOT NULL, code TEXT NOT NULL, path TEXT NOT NULL, orig TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_replies (id INTEGER PRIMARY KEY AUTOINCREMENT, enquiry_id INTEGER NOT NULL, user_id INTEGER, author TEXT NOT NULL, body TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_orders (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, listing_id INTEGER, kind TEXT NOT NULL, item TEXT NOT NULL, amount TEXT, status TEXT NOT NULL DEFAULT \'new\', note TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_tokens (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, purpose TEXT NOT NULL, token TEXT NOT NULL UNIQUE, expires_at TEXT NOT NULL)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_journeys (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, path TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $P = product();
    if (($P['mode'] ?? '') !== 'hub' && !empty($P['listing_table'])) {
        $cols = 'id INTEGER PRIMARY KEY AUTOINCREMENT, ' . $P['owner_col'] . ' INTEGER NOT NULL';
        foreach ($P['fields'] as $f) {
            $cols .= ', ' . $f[0] . ' TEXT';
        }
        $cols .= ', ' . $P['status_col'] . ' TEXT NOT NULL DEFAULT \'pending\', featured INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . $P['listing_table'] . ' (' . $cols . ')');
        $rcols = 'id INTEGER PRIMARY KEY AUTOINCREMENT, ' . $P['request_fk'] . ' INTEGER NOT NULL, name TEXT NOT NULL, email TEXT NOT NULL, phone TEXT, status TEXT NOT NULL DEFAULT \'new\', created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP';
        foreach ($P['request_fields'] as $f) {
            $rcols .= ', ' . $f[0] . ' TEXT';
        }
        $pdo->exec('CREATE TABLE IF NOT EXISTS ' . $P['request_table'] . ' (' . $rcols . ')');
        ensure_columns($pdo, $P['listing_table'], array_column($P['fields'], 0));
        ensure_columns($pdo, $P['request_table'], array_column($P['request_fields'], 0));
    }
    if (function_exists('product_migrate')) {
        product_migrate($pdo);
    }
    seed_platform($pdo);
}

function ensure_columns(PDO $pdo, string $table, array $cols): void
{
    $have = [];
    try {
        foreach ($pdo->query('PRAGMA table_info(' . $table . ')') as $r) {
            $have[$r['name']] = true;
        }
    } catch (Throwable $e) {
        return;
    }
    foreach ($cols as $c) {
        $c = preg_replace('/[^a-z0-9_]/', '', (string) $c);
        if ($c !== '' && empty($have[$c])) {
            $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $c . ' TEXT');
        }
    }
}

function seed_platform(PDO $pdo): void
{
    $P = product();
    $ins = $pdo->prepare('INSERT INTO dg_settings (k, v) VALUES (?,?)');
    $chk = $pdo->prepare('SELECT k FROM dg_settings WHERE k=?');
    foreach (default_settings() as $k => $v) {
        $chk->execute([$k]);
        if (!$chk->fetch()) {
            $ins->execute([$k, $v]);
        }
    }
    $admin = 'admin@' . $P['slug'] . '.local';
    $st = $pdo->prepare('SELECT id FROM dg_users WHERE email=?');
    $st->execute([$admin]);
    if (!$st->fetch()) {
        $pdo->prepare('INSERT INTO dg_users (email, password_hash, name, role) VALUES (?,?,?,?)')
            ->execute([$admin, password_hash('AdminPass9', PASSWORD_DEFAULT), $P['brand'] . ' Admin', 'admin']);
    }
}

function default_settings(): array
{
    $P = product();
    return [
        'brand' => $P['brand'],
        'topbar' => $P['topbar'],
        'eyebrow' => $P['eyebrow'],
        'hero_h1' => $P['hero_h1'],
        'hero_p' => $P['hero_p'],
        'services_intro' => $P['services_intro'] ?? 'Packaged help.',
        'packages' => $P['packages'],
        'price_starter' => $P['price_starter'] ?? 'Free',
        'price_verified' => $P['price_verified'] ?? '₹999',
        'price_growth' => $P['price_growth'] ?? '₹2,999',
        'footer_blurb' => $P['footer_blurb'],
        'contact_email' => $P['contact_email'] ?? ('hello@' . $P['slug'] . '.com'),
        'contact_phone' => $P['contact_phone'] ?? '+91 00000 00000',
        'page_about' => $P['page_about'],
        'page_privacy' => $P['page_privacy'],
        'page_terms' => $P['page_terms'],
    ];
}

function reset_public_copy(): void
{
    foreach (default_settings() as $k => $v) {
        if (in_array($k, ['brand', 'topbar', 'eyebrow', 'hero_h1', 'hero_p', 'services_intro', 'footer_blurb'], true)) {
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

function is_admin(): bool
{
    return (user()['role'] ?? '') === 'admin';
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

function csrf_fields(string $act): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><input type="hidden" name="act" value="' . h($act) . '">';
}

function audit(PDO $db, ?int $uid, string $entity, ?int $eid, string $action): void
{
    $db->prepare('INSERT INTO dg_audit (user_id, entity, entity_id, action) VALUES (?,?,?,?)')->execute([$uid, $entity, $eid, $action]);
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

function queue_mail(PDO $db, string $to, string $subject, string $body, ?string $token = null): void
{
    $db->prepare('INSERT INTO dg_mail (to_email, subject, body, token) VALUES (?,?,?,?)')->execute([$to, $subject, $body, $token]);
}

function notify(PDO $db, int $uid, string $title, string $body, string $link = ''): void
{
    $db->prepare('INSERT INTO dg_notices (user_id, title, body, link) VALUES (?,?,?,?)')->execute([$uid, $title, $body, $link ?: null]);
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

function save_upload(string $field, int $lid, string $code): ?string
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
    $dir = DG_APP . '/uploads/' . $lid;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return '';
    }
    $name = $code . '-' . time() . '.' . $ext;
    if (!move_uploaded_file((string) $f['tmp_name'], $dir . '/' . $name)) {
        return '';
    }
    return $lid . '/' . $name;
}

function dash_nav(string $on = 'dash'): string
{
    $html = '<aside class="dash-nav">';
    $items = ['dash' => 'Dashboard', 'docs' => 'Documents', 'inbox' => 'Inbox', 'orders' => 'Requests', 'notices' => 'Notifications', 'account' => 'Account'];
    foreach ((product()['dash_extra'] ?? []) as $k => $lab) {
        $items[$k] = $lab;
    }
    foreach ($items as $k => $lab) {
        $html .= '<a href="?p=' . $k . '"' . ($on === $k ? ' class="on"' : '') . '>' . $lab . '</a>';
    }
    return $html . '<a href="?p=dir">Directory</a></aside>';
}

function handle_auth(PDO $db): string
{
    $P = product();
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
        if (!$email || $name === '' || strlen($pass) < 8) {
            return 'Name, email and password (8+) are required.';
        }
        $ageMin = (int) ($P['age_min'] ?? 0);
        if ($ageMin) {
            $bd = (string) ($_POST['birth_date'] ?? '');
            if ($bd === '' || (int) floor((time() - strtotime($bd)) / 31557600) < $ageMin) {
                return 'You must be ' . $ageMin . ' or older. This is a family platform, not dating.';
            }
        }
        try {
            $db->beginTransaction();
            $db->prepare('INSERT INTO dg_users (email, password_hash, name, phone, role) VALUES (?,?,?,?,?)')
                ->execute([$email, password_hash($pass, PASSWORD_DEFAULT), $name, trim((string) ($_POST['phone'] ?? '')), $P['owner_role'] ?? 'owner']);
            $uid = (int) $db->lastInsertId();
            if (($P['mode'] ?? '') !== 'hub' && !empty($P['listing_table'])) {
                $title = trim((string) ($_POST[$P['fields'][0][0]] ?? $name));
                if ($title !== '') {
                    $cols = [$P['owner_col']];
                    $vals = [$uid];
                    foreach ($P['fields'] as $f) {
                        $cols[] = $f[0];
                        $vals[] = trim((string) ($_POST[$f[0]] ?? '')) ?: null;
                    }
                    $cols[] = $P['status_col'];
                    $vals[] = 'pending';
                    $ph = implode(',', array_fill(0, count($cols), '?'));
                    $db->prepare('INSERT INTO ' . $P['listing_table'] . ' (' . implode(',', $cols) . ') VALUES (' . $ph . ')')->execute($vals);
                }
            }
            $tok = bin2hex(random_bytes(16));
            $db->prepare('INSERT INTO dg_tokens (user_id, purpose, token, expires_at) VALUES (?,?,?,?)')->execute([$uid, 'verify', $tok, date('c', time() + 86400 * 7)]);
            queue_mail($db, (string) $email, 'Confirm your ' . $P['brand'] . ' email', 'Confirm: /?p=verify&token=' . $tok, $tok);
            notify($db, $uid, 'Welcome to ' . $P['brand'], 'Complete your profile, upload documents, then request verification.', '?p=docs');
            audit($db, $uid, 'user', $uid, 'register');
            $db->commit();
            $_SESSION['u'] = ['id' => $uid, 'name' => $name, 'email' => $email, 'role' => $P['owner_role'] ?? 'owner'];
            flash('Account created. Complete your listing and documents.');
            go('dash');
        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate') ? 'That email is already registered.' : 'Could not create the account.';
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
                $db->prepare('INSERT INTO dg_tokens (user_id, purpose, token, expires_at) VALUES (?,?,?,?)')->execute([$uid, 'reset', $tok, date('c', time() + 3600)]);
                queue_mail($db, $email, 'Reset your ' . $P['brand'] . ' password', 'Open /?p=reset&token=' . $tok, $tok);
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
        if (!password_verify((string) ($_POST['old'] ?? ''), (string) $st->fetchColumn())) {
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

function shell_start(string $title = ''): void
{
    $P = product();
    $me = user();
    $brand = setting('brand', $P['brand']);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . h($title ?: $brand) . '</title><link rel="icon" href="' . h(site_icon_href($P['mark'] ?? 'D')) . '"><link rel="apple-touch-icon" href="' . h(site_icon_href($P['mark'] ?? 'D')) . '"><link rel="stylesheet" href="assets/app.css"></head><body>';
    echo '<a class="skip-link" href="#main">Skip to content</a>';
    echo '<div class="topbar"><div class="container"><span>' . h(setting('topbar', $P['topbar'])) . '</span><span>Kusumit Universe · MyDoApp</span></div></div>';
    echo '<header class="site-header"><div class="container header-inner">';
    echo '<a class="brand" href="?p=home"><span class="brand-mark">' . h($P['mark']) . '<span>o</span></span><span>' . h($brand) . '</span></a>';
    echo '<nav class="nav" id="mainNav"><a href="?p=home">Home</a>';
    if (($P['mode'] ?? '') !== 'hub') {
        echo '<a href="?p=dir">' . h($P['dir_label']) . '</a>';
        foreach ($P['nav_extra'] ?? [] as $n) {
            echo '<a href="?p=' . h($n[0]) . '">' . h($n[1]) . '</a>';
        }
        echo '<a href="?p=services">Services</a><a href="?p=pricing">Pricing</a>';
    } else {
        echo '<a href="?p=products">Products</a>';
    }
    echo '<a href="?p=guide">Ask Do</a><a href="?p=contact">Contact</a>';
    if ($me) {
        echo is_admin() ? '<a href="?p=admin">Admin</a>' : '<a href="?p=dash">Dashboard</a><a href="?p=notices">Alerts' . (unseen_n(db()) ? ' (' . unseen_n(db()) . ')' : '') . '</a>';
        echo '<form method="post">' . csrf_fields('logout') . '<button type="submit">Log out</button></form>';
    } else {
        echo '<a href="?p=login">Log in</a>';
    }
    echo '</nav><button class="btn light mobile-toggle" type="button" onclick="document.getElementById(\'mainNav\').classList.toggle(\'open\')">Menu</button>';
    echo '<a class="btn" href="?p=join">' . h($P['join_cta']) . '</a></div></header><main id="main">';
}

function shell_end(): void
{
    $P = product();
    echo '</main><footer class="footer"><div class="container footer-grid">';
    echo '<div><h3>' . h(setting('brand', $P['brand'])) . '</h3><p>' . h(setting('footer_blurb', $P['footer_blurb'])) . '</p></div>';
    echo '<div><h4>Platform</h4>';
    if (($P['mode'] ?? '') === 'hub') {
        echo '<a href="?p=products">Products</a><a href="?p=guide">Ask Do</a>';
    } else {
        echo '<a href="?p=dir">' . h($P['dir_label'] ?? 'Directory') . '</a>';
        foreach ($P['nav_extra'] ?? [] as $n) {
            echo '<a href="?p=' . h($n[0]) . '">' . h($n[1]) . '</a>';
        }
        echo '<a href="?p=services">Services</a><a href="?p=pricing">Pricing</a><a href="?p=guide">Ask Do</a>';
    }
    echo '</div>';
    echo '<div><h4>Do Galaxy</h4>';
    guide_render_footer_links();
    echo '</div>';
    echo '<div><h4>Company</h4><a href="?p=about">About</a><a href="?p=privacy">Privacy</a><a href="?p=terms">Terms</a><a href="?p=contact">Contact</a></div></div>';
    echo '<div class="container" style="border-top:1px solid rgba(255,255,255,.12);margin-top:28px;padding-top:18px;color:#91a8c8">© ' . date('Y') . ' ' . h($P['brand']) . '. A Kusumit Universe initiative.</div></footer>';
    guide_widget($P['slug']);
    echo '</body></html>';
}

require_once __DIR__ . '/guide.php';

if (is_file(DG_APP . '/pages.php')) {
    require_once DG_APP . '/pages.php';
}
