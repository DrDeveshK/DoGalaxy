<?php
declare(strict_types=1);
session_start();

function cfg(): array
{
    $f = __DIR__ . '/config.local.php';
    if (!is_file($f)) {
        http_response_code(503);
        exit('Open install.php?key=dogalaxy once.');
    }
    return require $f;
}

function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $c = cfg();
        $pdo = new PDO($c['dsn'], $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function user(): ?array
{
    return $_SESSION['u'] ?? null;
}

function audit(PDO $db, ?int $uid, string $entity, ?int $eid, string $action): void
{
    $db->prepare('INSERT INTO dg_audit (user_id, entity, entity_id, action) VALUES (?,?,?,?)')
        ->execute([$uid, $entity, $eid, $action]);
}

function handle_auth(PDO $db): string
{
    if (($_POST['act'] ?? '') === 'logout') {
        $_SESSION = [];
        header('Location: ?p=home');
        exit;
    }
    if (($_POST['act'] ?? '') === 'register') {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $name = trim((string) ($_POST['name'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        if (!$email || $name === '' || strlen($pass) < 8) {
            return 'Name, email and password (8+) required.';
        }
        try {
            $db->prepare('INSERT INTO dg_users (email, password_hash, name, phone) VALUES (?,?,?,?)')
                ->execute([$email, password_hash($pass, PASSWORD_DEFAULT), $name, trim((string) ($_POST['phone'] ?? ''))]);
            $uid = (int) $db->lastInsertId();
            audit($db, $uid, 'user', $uid, 'register');
            $_SESSION['u'] = ['id' => $uid, 'name' => $name, 'email' => $email];
            header('Location: ?p=dash');
            exit;
        } catch (PDOException $e) {
            return str_contains($e->getMessage(), 'Duplicate') ? 'That email is already registered.' : 'Could not create the account.';
        }
    }
    if (($_POST['act'] ?? '') === 'login') {
        $st = $db->prepare('SELECT * FROM dg_users WHERE email=? AND status="active"');
        $st->execute([(string) ($_POST['email'] ?? '')]);
        $u = $st->fetch();
        if (!$u || !password_verify((string) ($_POST['password'] ?? ''), $u['password_hash'])) {
            return 'Incorrect email or password.';
        }
        $db->prepare('UPDATE dg_users SET last_login_at=NOW() WHERE id=?')->execute([$u['id']]);
        audit($db, (int) $u['id'], 'user', (int) $u['id'], 'login');
        $_SESSION['u'] = ['id' => (int) $u['id'], 'name' => $u['name'], 'email' => $u['email']];
        header('Location: ?p=dash');
        exit;
    }
    return '';
}

function shell_start(string $brand, string $mark, array $nav, string $ctaHref, string $ctaLabel): void
{
    $me = user();
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . h($brand) . '</title><link rel="stylesheet" href="assets/app.css"></head><body>';
    echo '<div class="topbar"><div class="container"><span>' . h($brand) . ' · Do Galaxy</span><span>Kusumit Universe · MyDoApp</span></div></div>';
    echo '<header class="site-header"><div class="container header-inner">';
    echo '<a class="brand" href="?p=home"><span class="brand-mark">' . h($mark[0]) . '<span>o</span></span><span>' . h($brand) . '</span></a>';
    echo '<nav class="nav">';
    foreach ($nav as $href => $lab) {
        echo '<a href="' . h($href) . '">' . h($lab) . '</a>';
    }
    if ($me) {
        echo '<a href="?p=dash">Dashboard</a>';
        echo '<form method="post"><input type="hidden" name="act" value="logout"><button type="submit">Log out</button></form>';
    } else {
        echo '<a href="?p=login">Log in</a>';
    }
    echo '</nav><a class="btn" href="' . h($ctaHref) . '">' . h($ctaLabel) . '</a></div></header>';
}

function shell_end(string $brand): void
{
    echo '<footer class="footer"><div class="container footer-grid">';
    echo '<div><h3>' . h($brand) . '</h3><p>A Do Galaxy product. One identity across work, stay, events, trade and family.</p></div>';
    echo '<div><h4>Do Galaxy</h4>';
    foreach (['mydoapp.com' => 'MyDoApp', 'doudyog.com' => 'DoUdyog', 'dorojgar.com' => 'DoRojgar', 'dovishram.com' => 'DoVishram', 'doswagat.com' => 'DoSwagat', 'dorishta.com' => 'DoRishta', 'dobajar.com' => 'DoBajar'] as $d => $n) {
        echo '<a href="https://' . $d . '">' . $n . '</a>';
    }
    echo '</div></div>';
    echo '<div class="container" style="border-top:1px solid rgba(255,255,255,.12);margin-top:28px;padding-top:18px;color:#91a8c8">© ' . date('Y') . ' ' . h($brand) . '. A Kusumit Universe initiative.</div>';
    echo '</footer></body></html>';
}

function auth_forms(string $err, string $which): void
{
    echo '<section class="section soft"><div class="container"><div class="card" style="max-width:28rem">';
    echo '<h2>' . ($which === 'join' ? 'Create account' : 'Log in') . '</h2>';
    if ($err) {
        echo '<p class="err">' . h($err) . '</p>';
    }
    echo '<form method="post"><input type="hidden" name="act" value="' . ($which === 'join' ? 'register' : 'login') . '">';
    if ($which === 'join') {
        echo '<input class="input" name="name" placeholder="Your name" required><br><br>';
        echo '<input class="input" name="phone" placeholder="Phone / WhatsApp"><br><br>';
    }
    echo '<input class="input" type="email" name="email" placeholder="Email" required><br><br>';
    echo '<input class="input" type="password" name="password" placeholder="Password" ' . ($which === 'join' ? 'minlength="8" ' : '') . 'required><br><br>';
    echo '<button class="btn" type="submit">' . ($which === 'join' ? 'Create account' : 'Log in') . '</button></form></div></div></section>';
}
