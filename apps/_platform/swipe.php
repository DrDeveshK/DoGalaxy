<?php
declare(strict_types=1);

function swipe_migrate(PDO $pdo): void
{
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->exec('CREATE TABLE IF NOT EXISTS dg_swipes (id INTEGER PRIMARY KEY AUTOINCREMENT, product TEXT NOT NULL, entity TEXT NOT NULL, user_id INTEGER NOT NULL, target_id INTEGER NOT NULL, verdict TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(product, entity, user_id, target_id))');
        return;
    }
    $pdo->exec('CREATE TABLE IF NOT EXISTS `dg_swipes` (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, product VARCHAR(40) NOT NULL, entity VARCHAR(40) NOT NULL, user_id BIGINT UNSIGNED NOT NULL, target_id BIGINT UNSIGNED NOT NULL, verdict VARCHAR(12) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_swipe (product, entity, user_id, target_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

function swipe_staff_id(PDO $pdo): int
{
    return (int) $pdo->query("SELECT id FROM dg_users WHERE role='admin' LIMIT 1")->fetchColumn() ?: 1;
}

function swipe_save(PDO $pdo, string $entity, int $uid, int $tid, string $verdict): void
{
    $prod = product()['slug'];
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->prepare('INSERT INTO dg_swipes (product, entity, user_id, target_id, verdict) VALUES (?,?,?,?,?) ON CONFLICT(product, entity, user_id, target_id) DO UPDATE SET verdict=excluded.verdict, created_at=CURRENT_TIMESTAMP')
            ->execute([$prod, $entity, $uid, $tid, $verdict]);
        return;
    }
    $pdo->prepare('INSERT INTO dg_swipes (product, entity, user_id, target_id, verdict) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE verdict=VALUES(verdict), created_at=CURRENT_TIMESTAMP')
        ->execute([$prod, $entity, $uid, $tid, $verdict]);
}

function swipe_like_n(PDO $pdo, string $entity, int $tid): int
{
    $st = $pdo->prepare("SELECT COUNT(*) FROM dg_swipes WHERE product=? AND entity=? AND target_id=? AND verdict='like'");
    $st->execute([product()['slug'], $entity, $tid]);
    return (int) $st->fetchColumn();
}

function swipe_photo_file(string $city, string $fallback = 'jaipur.jpg'): string
{
    $map = [
        'Jaipur' => 'jaipur.jpg', 'Udaipur' => 'udaipur.jpg', 'Delhi' => 'hauzkhas.jpg', 'Pune' => 'pune.jpg',
        'Indore' => 'pune.jpg', 'Mumbai' => 'marinedrive.jpg', 'Goa' => 'goa.jpg', 'Bengaluru' => 'cubbon.jpg',
        'Hyderabad' => 'charminar.jpg', 'Kolkata' => 'victoria.jpg', 'Amritsar' => 'goldentemple.jpg',
        'Jodhpur' => 'mehrangarh.jpg', 'Manali' => 'manali.jpg', 'Nainital' => 'nainital.jpg',
        'Rishikesh' => 'rishikesh.jpg', 'Agra' => 'tajmahal.jpg', 'Varanasi' => 'varanasi.jpg',
        'Madikeri' => 'munnar.jpg', 'Munnar' => 'munnar.jpg', 'Leh' => 'leh.jpg',
    ];
    $f = $map[$city] ?? $fallback;
    $dir = __DIR__ . '/assets/swipe/';
    return is_file($dir . $f) ? $f : (is_file($dir . $fallback) ? $fallback : $f);
}

function swipe_photo_src(string $city, string $fallback = 'jaipur.jpg'): string
{
    return '?p=swipeimg&f=' . rawurlencode(swipe_photo_file($city, $fallback));
}

function swipe_send_photo(): void
{
    $f = basename((string) ($_GET['f'] ?? ''));
    $path = __DIR__ . '/assets/swipe/' . $f;
    if (!preg_match('/^[a-z0-9._-]+\.jpe?g$/i', $f) || !is_file($path)) {
        http_response_code(404);
        exit('missing');
    }
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=86400');
    readfile($path);
    exit;
}

function swipe_handle(PDO $db, string $entity, string &$err, ?string $likeGo = null): bool
{
    if (!user()) {
        flash('Log in to save your shortlist.');
        go('login');
    }
    $tid = (int) ($_POST['target_id'] ?? 0);
    $verdict = (string) ($_POST['verdict'] ?? '');
    $kind = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_POST['kind'] ?? ''));
    if ($tid < 1 || !in_array($verdict, ['like', 'pass'], true)) {
        $err = 'Pick skip or shortlist.';
        return true;
    }
    swipe_save($db, $entity, (int) user()['id'], $tid, $verdict);
    audit($db, (int) user()['id'], $entity, $tid, $verdict);
    if ($verdict === 'like' && $likeGo !== null) {
        go($likeGo . $tid);
    }
    go('swipe' . ($kind !== '' ? '&kind=' . $kind : ''));
}

function swipe_deck(PDO $db, string $sql, array $args, string $entity, ?array $me): array
{
    if ($me) {
        $sql .= ' AND id NOT IN (SELECT target_id FROM dg_swipes WHERE product=? AND entity=? AND user_id=?)';
        $args[] = product()['slug'];
        $args[] = $entity;
        $args[] = $me['id'];
    }
    $st = $db->prepare($sql . ' LIMIT 80');
    $st->execute($args);
    return $st->fetchAll();
}

function swipe_styles(): void
{
    echo '<style>.swipe-page{padding-top:36px}.swipe-filters{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 28px}.swipe-filters .pill{font-weight:800}.swipe-filters .pill.on{background:var(--navy);color:#fff;border-color:var(--navy)}.swipe-wrap{max-width:420px;margin:0 auto}.swipe-stage{position:relative;height:560px}.swipe-stage .swipe-card{position:absolute;inset:0;touch-action:none}.swipe-card{background:#fff;border:1px solid var(--border);border-radius:24px;padding:0;box-shadow:0 18px 40px rgba(22,48,40,.12);overflow:hidden;user-select:none;position:relative;display:flex;flex-direction:column}.swipe-photo{width:100%;height:320px;object-fit:cover;display:block;background:#ddd}.swipe-avatar{display:grid;place-items:center;font-size:72px;font-weight:900;color:#fff;letter-spacing:.04em}.swipe-body{padding:16px 20px 18px;flex:1}.swipe-kind{font-weight:800;color:var(--navy);margin-bottom:6px;font-size:13px}.swipe-card h2{margin:0 0 4px;font-size:26px;line-height:1.12;color:var(--navy)}.swipe-where{font-weight:700;color:var(--muted);margin:0 0 6px}.swipe-tag{font-weight:700;color:var(--navy);margin:0}.swipe-count{font-weight:800;color:var(--green);margin:8px 0 0}.swipe-stamp{position:absolute;top:22px;z-index:2;padding:6px 12px;border:3px solid;border-radius:10px;font-weight:900;opacity:0;background:rgba(255,255,255,.9)}.swipe-stamp-no{left:18px;color:#c0392b;border-color:#c0392b;transform:rotate(-18deg)}.swipe-stamp-yes{right:18px;color:var(--green);border-color:var(--green);transform:rotate(18deg)}.swipe-card.hint-no .swipe-stamp-no,.swipe-card.hint-yes .swipe-stamp-yes{opacity:1}.swipe-card.gone-left{transform:translateX(-140%) rotate(-14deg);opacity:0;transition:.22s ease-in}.swipe-card.gone-right{transform:translateX(140%) rotate(14deg);opacity:0;transition:.22s ease-in}.swipe-actions{display:flex;justify-content:center;gap:28px;margin:22px 0 8px}.swipe-btn{width:64px;height:64px;border-radius:50%;display:grid;place-items:center;font-size:26px;font-weight:900;border:0;cursor:pointer;text-decoration:none}.swipe-no{background:#fff;color:#c0392b;border:2px solid #f5b5b5}.swipe-yes{background:var(--green);color:#fff}</style>';
}

function swipe_card_html(array $p, array $opt): string
{
    $likes = (int) ($opt['likes'] ?? 0);
    $html = '<article class="swipe-card" data-id="' . (int) $p['id'] . '">';
    $html .= '<span class="swipe-stamp swipe-stamp-no">' . h($opt['no_stamp'] ?? 'Skip') . '</span><span class="swipe-stamp swipe-stamp-yes">' . h($opt['yes_stamp'] ?? 'Yes') . '</span>';
    if (!empty($opt['avatar'])) {
        $ini = strtoupper(substr((string) $p[$opt['title_col']], 0, 1));
        $html .= '<div class="swipe-photo swipe-avatar" style="background:linear-gradient(135deg,var(--navy),var(--orange))">' . h($ini) . '</div>';
    } else {
        $html .= '<img class="swipe-photo" src="' . h($opt['photo']) . '" alt="' . h($p[$opt['title_col']]) . '">';
    }
    $html .= '<div class="swipe-body"><div class="swipe-kind">' . h($opt['kicker']) . '</div>';
    $html .= '<h2>' . h($p[$opt['title_col']]) . '</h2>';
    $html .= '<p class="swipe-where">' . h($opt['where']) . '</p>';
    if (!empty($opt['tag'])) {
        $html .= '<p class="swipe-tag">' . h($opt['tag']) . '</p>';
    }
    if (!empty($opt['show_likes'])) {
        $html .= '<p class="swipe-count">' . $likes . ' ' . h($opt['like_label'] ?? 'shortlisted this') . '</p>';
    }
    return $html . '</div></article>';
}

function swipe_render(array $cfg, array $deck, ?array $me): void
{
    $top = $deck[0] ?? null;
    $kind = (string) ($cfg['kind'] ?? '');
    swipe_styles();
    echo '<section class="section swipe-page"><div class="container"><div class="section-title"><div><h2>' . h($cfg['h2']) . '</h2><p>' . h($cfg['lede']) . '</p></div>';
    if (!empty($cfg['side'])) {
        echo '<a class="btn light" href="' . h($cfg['side'][0]) . '">' . h($cfg['side'][1]) . '</a>';
    }
    echo '</div>';
    if (!empty($cfg['notice'])) {
        echo '<p class="notice">' . h($cfg['notice']) . '</p>';
    }
    if (!empty($cfg['filters'])) {
        echo '<div class="swipe-filters"><a class="pill' . ($kind === '' ? ' on' : '') . '" href="?p=swipe">All</a>';
        foreach ($cfg['filters'] as $k => $lab) {
            echo '<a class="pill' . ($kind === $k ? ' on' : '') . '" href="?p=swipe&kind=' . h($k) . '">' . h($lab) . '</a>';
        }
        echo '</div>';
    }
    if (!$me) {
        echo '<p class="notice">' . h($cfg['login_msg'] ?? 'Log in to save a shortlist.') . '</p>';
    }
    echo '<div class="swipe-wrap">';
    if (!$top) {
        echo '<div class="card"><h3>Stack complete</h3><p>You have gone through this set.</p><p><a class="btn" href="' . h($cfg['done'] ?? '?p=home') . '">Continue</a></p></div></div></div></section>';
        return;
    }
    echo '<p class="muted" style="text-align:center;margin:0 0 12px">' . count($deck) . ' left in this stack</p>';
    echo '<div class="swipe-stage">' . $cfg['card']($top) . '</div><div class="swipe-actions">';
    $yes = $cfg['yes_mark'] ?? '♥';
    $no = $cfg['no_mark'] ?? '✕';
    if ($me) {
        echo '<form method="post">' . csrf_fields('swipe') . '<input type="hidden" name="target_id" value="' . (int) $top['id'] . '"><input type="hidden" name="kind" value="' . h($kind) . '"><input type="hidden" name="verdict" value="pass"><button class="swipe-btn swipe-no" type="submit">' . $no . '</button></form>';
        echo '<form method="post">' . csrf_fields('swipe') . '<input type="hidden" name="target_id" value="' . (int) $top['id'] . '"><input type="hidden" name="kind" value="' . h($kind) . '"><input type="hidden" name="verdict" value="like"><button class="swipe-btn swipe-yes" type="submit">' . $yes . '</button></form>';
    } else {
        echo '<a class="swipe-btn swipe-no" href="?p=login">' . $no . '</a><a class="swipe-btn swipe-yes" href="?p=login">' . $yes . '</a>';
    }
    echo '</div><p class="muted" style="text-align:center">Drag, or use the buttons. ← skip · → yes.</p>';
    echo '<script>(function(){var c=document.querySelector(".swipe-card");if(!c)return;var x0=0,dx=0,drag=false;function go(v){var f=document.querySelector(\'input[name="verdict"][value="\'+v+\'"]\');c.classList.add(v==="like"?"gone-right":"gone-left");setTimeout(function(){if(f)f.form.submit();},220);}c.addEventListener("pointerdown",function(e){drag=true;x0=e.clientX;c.setPointerCapture(e.pointerId);});c.addEventListener("pointermove",function(e){if(!drag)return;dx=e.clientX-x0;c.style.transform="translateX("+dx+"px) rotate("+(dx/18)+"deg)";c.classList.toggle("hint-yes",dx>48);c.classList.toggle("hint-no",dx<-48);});function up(){if(!drag)return;drag=false;if(dx>80)go("like");else if(dx<-80)go("pass");else{c.style.transform="";c.classList.remove("hint-yes","hint-no");}dx=0;}c.addEventListener("pointerup",up);c.addEventListener("pointercancel",up);document.addEventListener("keydown",function(e){if(e.key==="ArrowRight")go("like");if(e.key==="ArrowLeft")go("pass");});})();</script>';
    echo '</div></div></section>';
}

function swipe_wants(PDO $db, string $entity, string $table, string $titleCol, callable $rowHtml): void
{
    $me = user();
    if (!$me) {
        go('login');
    }
    $st = $db->prepare("SELECT t.* FROM dg_swipes s JOIN $table t ON t.id=s.target_id WHERE s.product=? AND s.entity=? AND s.user_id=? AND s.verdict='like' ORDER BY s.id DESC");
    $st->execute([product()['slug'], $entity, $me['id']]);
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Your shortlist</h2></div><a class="btn" href="?p=swipe">Keep reviewing</a></div><div class="list-grid">';
    $n = 0;
    foreach ($st as $r) {
        echo $rowHtml($r);
        $n++;
    }
    if (!$n) {
        echo '<p>None yet. <a href="?p=swipe">Review the stack</a>.</p>';
    }
    echo '</div></div></section>';
}
