<?php
declare(strict_types=1);
session_start();
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0700, true);
}
$usersFile = $dataDir . '/users.json';

function planets(): array
{
    return [
        'business' => ['Do Udyog', 'https://doudyog.com', 'I run or am starting a business'],
        'stay' => ['Do Vishram', 'https://dovishram.com', 'I need a stay or I host rooms'],
        'job' => ['Do Rojgar', 'https://dorojgar.com', 'I want work or I want to hire'],
        'event' => ['Do Swagat', 'https://doswagat.com', 'I am planning an event'],
        'rishta' => ['Do Rishta', 'https://dorishta.com', 'I am looking for a life partner'],
        'buy' => ['Do Bajar', 'https://dobajar.com', 'I want to sell or buy locally'],
    ];
}
function load_users(string $f): array
{
    return is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
}
function save_users(string $f, array $u): void
{
    file_put_contents($f, json_encode($u, JSON_PRETTY_PRINT));
}
function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function me(): ?array
{
    return $_SESSION['user'] ?? null;
}

$p = $_GET['p'] ?? 'home';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['act'] ?? '';
    if ($act === 'register') {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $name = trim((string) ($_POST['name'] ?? ''));
        $pass = (string) ($_POST['password'] ?? '');
        $path = preg_replace('/[^a-z]/', '', (string) ($_POST['path'] ?? ''));
        $users = load_users($usersFile);
        if (!$email || strlen($pass) < 8 || $name === '') {
            $err = 'Name, email and password (8+) required.';
            $p = 'join';
        } elseif (isset($users[$email])) {
            $err = 'That email is already registered.';
            $p = 'join';
        } else {
            $users[$email] = ['name' => $name, 'hash' => password_hash($pass, PASSWORD_DEFAULT), 'path' => $path];
            save_users($usersFile, $users);
            $_SESSION['user'] = ['email' => $email, 'name' => $name, 'path' => $path];
            header('Location: ?p=dash');
            exit;
        }
    } elseif ($act === 'login') {
        $email = (string) ($_POST['email'] ?? '');
        $users = load_users($usersFile);
        if (!isset($users[$email]) || !password_verify((string) ($_POST['password'] ?? ''), $users[$email]['hash'])) {
            $err = 'Incorrect email or password.';
            $p = 'login';
        } else {
            $_SESSION['user'] = ['email' => $email, 'name' => $users[$email]['name'], 'path' => $users[$email]['path'] ?? ''];
            header('Location: ?p=dash');
            exit;
        }
    } elseif ($act === 'path' && me()) {
        $path = preg_replace('/[^a-z]/', '', (string) ($_POST['path'] ?? ''));
        $users = load_users($usersFile);
        $email = me()['email'];
        if (isset($users[$email], planets()[$path])) {
            $users[$email]['path'] = $path;
            save_users($usersFile, $users);
            $_SESSION['user']['path'] = $path;
        }
        header('Location: ?p=dash');
        exit;
    } elseif ($act === 'logout') {
        $_SESSION = [];
        header('Location: ?p=home');
        exit;
    }
}
$user = me();
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>MyDoApp · Do Galaxy</title>
<link rel="stylesheet" href="https://fonts.bunny.net/css?family=figtree:400,600,700|fraunces:560">
<style>
body{margin:0;background:#f3eee4;color:#14110e;font-family:Figtree,system-ui,sans-serif;line-height:1.6}
a{color:#1a2744} header,main,footer{width:min(1120px,calc(100% - 2rem));margin:0 auto}
header{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;padding:1rem 0;border-bottom:1px solid #d6cdb8}
.brand small{display:block;letter-spacing:.14em;text-transform:uppercase;font-size:.68rem;color:#b8954a}
.brand b{font-family:Fraunces,Georgia,serif;font-size:1.4rem}
nav{display:flex;gap:1rem;flex-wrap:wrap}
h1{font-family:Fraunces,Georgia,serif;font-size:clamp(2rem,5vw,3.2rem);line-height:1.12}
.k{letter-spacing:.16em;text-transform:uppercase;font-size:.72rem;color:#b8954a;font-weight:700}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem}
.card{background:#fffcf7;border:1px solid #d6cdb8;border-radius:16px;padding:1.2rem}
.btn{display:inline-block;background:#14110e;color:#f3eee4;border-radius:999px;padding:.8rem 1.3rem;text-decoration:none;font-weight:700;border:0;font:inherit;cursor:pointer}
form{display:grid;gap:.7rem;max-width:28rem}
input,select,textarea{padding:.7rem .8rem;border:1px solid #d6cdb8;border-radius:12px;font:inherit}
.err{background:#f6e4d6;padding:.8rem;border-radius:12px}
footer{margin:2rem auto;border-top:1px solid #d6cdb8;padding:1rem 0;color:#3d3832}
</style></head><body>
<header>
  <div class="brand"><small>Do Galaxy</small><b><a href="?p=home">MyDoApp</a></b></div>
  <nav>
    <a href="?p=products">Products</a>
    <a href="?p=start">Start</a>
    <?php if ($user): ?><a href="?p=dash">Dashboard</a>
    <form method="post" style="margin:0"><input type="hidden" name="act" value="logout"><button class="btn" type="submit">Log out</button></form>
    <?php else: ?><a href="?p=join">Join</a><a href="?p=login">Log in</a><?php endif; ?>
  </nav>
</header>
<main>
<?php if ($p === 'home'): ?>
  <p class="k">Do Galaxy</p>
  <h1>One universe. Six working products.</h1>
  <p>MyDoApp is the door. Each Do product is a full app — register, act, track.</p>
  <p><a class="btn" href="?p=start">Start a journey</a></p>
  <div class="grid"><?php foreach (planets() as $row): ?>
    <article class="card"><p class="k"><?=h($row[0])?></p><h3><?=h($row[2])?></h3><p><a href="<?=h($row[1])?>">Open</a></p></article>
  <?php endforeach; ?></div>
<?php elseif ($p === 'products'): ?>
  <h1>Six products</h1>
  <div class="grid"><?php foreach (planets() as $row): ?>
    <article class="card"><h3><?=h($row[0])?></h3><p><a class="btn" href="<?=h($row[1])?>">Use <?=h($row[0])?></a></p></article>
  <?php endforeach; ?></div>
<?php elseif ($p === 'start'): ?>
  <h1>What do you need first?</h1>
  <form method="post" action="<?= $user ? '?p=start' : '?p=join' ?>">
    <?php if ($user): ?><input type="hidden" name="act" value="path"><?php endif; ?>
    <?php foreach (planets() as $k=>$row): ?>
      <label><input type="radio" name="path" value="<?=h($k)?>" required> <strong><?=h($row[0])?></strong> — <?=h($row[2])?></label>
    <?php endforeach; ?>
    <button class="btn" type="submit"><?= $user ? 'Save path' : 'Continue to join' ?></button>
  </form>
<?php elseif ($p === 'join'): ?>
  <h1>Create your MyDoApp account</h1>
  <?php if ($err): ?><p class="err"><?=h($err)?></p><?php endif; ?>
  <form method="post"><input type="hidden" name="act" value="register">
    <input type="hidden" name="path" value="<?=h($_POST['path'] ?? '')?>">
    <input name="name" placeholder="Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" minlength="8" placeholder="Password (8+)" required>
    <select name="path"><option value="">Choose path later</option>
      <?php foreach (planets() as $k=>$row): ?><option value="<?=h($k)?>"><?=h($row[0])?></option><?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Create account</button>
  </form>
<?php elseif ($p === 'login'): ?>
  <h1>Log in</h1>
  <?php if ($err): ?><p class="err"><?=h($err)?></p><?php endif; ?>
  <form method="post"><input type="hidden" name="act" value="login">
    <input type="email" name="email" required placeholder="Email">
    <input type="password" name="password" required placeholder="Password">
    <button class="btn" type="submit">Log in</button>
  </form>
<?php elseif ($p === 'dash'):
    if (!$user) { header('Location: ?p=login'); exit; }
    $pl = planets();
    $cur = $user['path'] && isset($pl[$user['path']]) ? $pl[$user['path']] : null; ?>
  <h1>Hello, <?=h($user['name'])?></h1>
  <?php if ($cur): ?>
    <article class="card"><p class="k">Current path</p><h3><?=h($cur[0])?></h3>
      <p><a class="btn" href="<?=h($cur[1])?>">Continue on <?=h($cur[0])?></a></p></article>
  <?php else: ?><p>No path yet. <a href="?p=start">Choose one</a>.</p><?php endif; ?>
<?php endif; ?>
</main>
<footer>MyDoApp · door to Do Udyog, Vishram, Rojgar, Swagat, Rishta, Bajar.</footer>
</body></html>
