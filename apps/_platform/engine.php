<?php
declare(strict_types=1);

function field_input(array $f, $val = ''): string
{
    $name = $f[0];
    $ph = $f[1];
    $type = $f[2];
    $req = !empty($f[3]) ? ' required' : '';
    if ($type === 'textarea') {
        return '<textarea name="' . h($name) . '" placeholder="' . h($ph) . '"' . $req . '>' . h((string) $val) . '</textarea>';
    }
    if ($type === 'select') {
        $html = '<select class="input" name="' . h($name) . '"' . $req . '>';
        foreach ($f[4] ?? [] as $opt) {
            $html .= '<option' . ((string) $val === (string) $opt ? ' selected' : '') . '>' . h($opt) . '</option>';
        }
        return $html . '</select>';
    }
    $t = $type === 'date' ? 'date' : ($type === 'number' ? 'number' : ($type === 'email' ? 'email' : 'text'));
    return '<input class="input" type="' . $t . '" name="' . h($name) . '" placeholder="' . h($ph) . '" value="' . h((string) $val) . '"' . $req . '>';
}

function owner_listings(PDO $db): array
{
    $P = product();
    if (($P['mode'] ?? '') === 'hub' || empty($P['listing_table']) || !user()) {
        return [];
    }
    $st = $db->prepare('SELECT * FROM ' . $P['listing_table'] . ' WHERE ' . $P['owner_col'] . '=? ORDER BY id DESC');
    $st->execute([user()['id']]);
    return $st->fetchAll();
}

function handle_product_post(PDO $db, string &$err): void
{
    $P = product();
    $act = (string) ($_POST['act'] ?? '');
    if ($act === '' || in_array($act, ['register', 'login', 'logout', 'forgot', 'reset', 'changepass', 'site', 'catalog', 'pages', 'verify', 'user', 'enq', 'order_status', 'feature'], true)) {
        return;
    }
    if (!csrf_ok()) {
        $err = 'Session expired. Try again.';
        return;
    }
    if (function_exists('product_handle_post') && product_handle_post($db, $act, $err)) {
        return;
    }
    if ($act === 'save' && user() && ($P['mode'] ?? '') !== 'hub') {
        $id = (int) ($_POST['id'] ?? 0);
        $cols = [];
        $vals = [];
        foreach ($P['fields'] as $f) {
            $cols[] = $f[0] . '=?';
            $vals[] = trim((string) ($_POST[$f[0]] ?? '')) ?: null;
        }
        if ($id) {
            $st = $db->prepare('SELECT ' . $P['owner_col'] . ' FROM ' . $P['listing_table'] . ' WHERE id=?');
            $st->execute([$id]);
            if ((int) $st->fetchColumn() !== (int) user()['id'] && !is_admin()) {
                $err = 'Not your listing.';
                return;
            }
            $vals[] = date('c');
            $vals[] = $id;
            $db->prepare('UPDATE ' . $P['listing_table'] . ' SET ' . implode(',', $cols) . ', updated_at=? WHERE id=?')->execute($vals);
            flash('Listing saved.');
        } else {
            $insCols = [$P['owner_col']];
            $insVals = [user()['id']];
            foreach ($P['fields'] as $f) {
                $insCols[] = $f[0];
                $insVals[] = trim((string) ($_POST[$f[0]] ?? '')) ?: null;
            }
            $insCols[] = $P['status_col'];
            $insVals[] = 'pending';
            $db->prepare('INSERT INTO ' . $P['listing_table'] . ' (' . implode(',', $insCols) . ') VALUES (' . implode(',', array_fill(0, count($insCols), '?')) . ')')->execute($insVals);
            flash($P['listing_label'] . ' created.');
        }
        go('dash');
    }
    if ($act === 'request') {
        $tid = (int) ($_POST['target_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!$tid || !$name || !$email) {
            $err = 'Name, email and a listing are required.';
            return;
        }
        $cols = [$P['request_fk'], 'name', 'email', 'phone'];
        $vals = [$tid, $name, (string) $email, trim((string) ($_POST['phone'] ?? '')) ?: null];
        foreach ($P['request_fields'] as $f) {
            $cols[] = $f[0];
            $vals[] = trim((string) ($_POST[$f[0]] ?? '')) ?: null;
        }
        $db->prepare('INSERT INTO ' . $P['request_table'] . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')')->execute($vals);
        $rid = (int) $db->lastInsertId();
        $st = $db->prepare('SELECT ' . $P['owner_col'] . ', ' . $P['title_col'] . ' FROM ' . $P['listing_table'] . ' WHERE id=?');
        $st->execute([$tid]);
        $row = $st->fetch();
        if ($row) {
            notify($db, (int) $row[$P['owner_col']], 'New ' . $P['request_label'], $name . ' → ' . $row[$P['title_col']], '?p=inbox&id=' . $rid);
            $st = $db->prepare('SELECT email FROM dg_users WHERE id=?');
            $st->execute([$row[$P['owner_col']]]);
            $to = (string) $st->fetchColumn();
            if ($to) {
                queue_mail($db, $to, 'New ' . $P['request_label'], $name . ' (' . $email . ')');
            }
        }
        flash($P['request_label'] . ' sent.');
        go('view&id=' . $tid . '&sent=1');
    }
    if ($act === 'req_status' && user()) {
        $id = (int) ($_POST['id'] ?? 0);
        $st = (string) ($_POST['status'] ?? 'new');
        if ($id && in_array($st, ['new', 'accepted', 'declined', 'quoted', 'confirmed', 'shortlisted', 'hired', 'closed', 'seen'], true)) {
            $db->prepare('UPDATE ' . $P['request_table'] . ' SET status=? WHERE id=?')->execute([$st, $id]);
            flash('Request marked ' . $st . '.');
        }
        go('inbox');
    }
    if ($act === 'upload' && user()) {
        $lid = (int) ($_POST['listing_id'] ?? 0);
        $code = (string) ($_POST['code'] ?? '');
        $mine = false;
        foreach (owner_listings($db) as $L) {
            if ((int) $L['id'] === $lid) {
                $mine = true;
            }
        }
        if (!$mine && !is_admin()) {
            $err = 'Not your listing.';
            return;
        }
        $path = save_upload('doc', $lid, $code);
        if (!$path) {
            $err = 'Upload a PDF or image under 4 MB.';
            return;
        }
        $db->prepare('INSERT INTO dg_files (listing_id, code, path, orig) VALUES (?,?,?,?)')->execute([$lid, $code, $path, (string) ($_FILES['doc']['name'] ?? 'file')]);
        flash('Document stored.');
        go('docs');
    }
    if ($act === 'order') {
        $kind = (string) ($_POST['kind'] ?? 'service');
        $item = trim((string) ($_POST['item'] ?? ''));
        if ($item === '') {
            $err = 'Choose a package.';
            return;
        }
        $db->prepare('INSERT INTO dg_orders (user_id, listing_id, kind, item, amount, status) VALUES (?,?,?,?,?,?)')
            ->execute([user()['id'] ?? null, (int) ($_POST['listing_id'] ?? 0) ?: null, $kind, $item, trim((string) ($_POST['amount'] ?? '')), 'new']);
        $oid = (int) $db->lastInsertId();
        queue_mail($db, setting('contact_email'), 'New ' . $kind . ' request', $item);
        if (user()) {
            notify($db, (int) user()['id'], 'Request received', $item, '?p=orders');
        }
        flash('Request #' . $oid . ' is with the desk.');
        go(user() ? 'orders' : 'contact&sent=1');
    }
    if ($act === 'seen' && user()) {
        $db->prepare('UPDATE dg_notices SET seen=1 WHERE user_id=?')->execute([user()['id']]);
        go('notices');
    }
    if ($act === 'path' && user() && ($P['mode'] ?? '') === 'hub') {
        $path = preg_replace('/[^a-z]/', '', (string) ($_POST['path'] ?? ''));
        $paths = $P['paths'];
        if (isset($paths[$path])) {
            $db->prepare('INSERT INTO dg_journeys (user_id, path) VALUES (?,?)')->execute([user()['id'], $path]);
            header('Location: ' . galaxy_url($path));
            exit;
        }
    }
    if ($act === 'enquire') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $msg = trim((string) ($_POST['message'] ?? ''));
        if (!$name || !$email || $msg === '') {
            $err = 'Name, email and message are required.';
            return;
        }
        $db->prepare('INSERT INTO dg_enquiries (product, target_id, user_id, name, email, phone, intent, message) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$P['slug'], (int) ($_POST['target_id'] ?? 0) ?: null, user()['id'] ?? null, $name, (string) $email, trim((string) ($_POST['phone'] ?? '')), (string) ($_POST['intent'] ?? 'general'), $msg]);
        queue_mail($db, setting('contact_email'), $P['brand'] . ' contact', "$name <$email>\n$msg");
        flash('Enquiry received.');
        go('contact&sent=1');
    }
}

function run_app(): void
{
    $P = product();
    $db = db();
    $err = '';
    $page = (string) ($_GET['p'] ?? 'home');
    if ($page === 'swipeimg') {
        swipe_send_photo();
    }
    if ($page === 'guide') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            guide_json_response($db, $P['slug']);
        }
        shell_start('Ask Do — ' . $P['brand']);
        guide_render_page($db, $P['slug']);
        shell_end();
        return;
    }
    if ($page === 'file') {
        $id = (int) ($_GET['id'] ?? 0);
        $st = $db->prepare('SELECT * FROM dg_files WHERE id=?');
        $st->execute([$id]);
        $f = $st->fetch();
        if (!$f || (!is_admin() && !user())) {
            http_response_code(403);
            exit('forbidden');
        }
        $abs = DG_APP . '/uploads/' . $f['path'];
        if (!is_file($abs)) {
            http_response_code(404);
            exit('missing');
        }
        header('Content-Type: ' . (mime_content_type($abs) ?: 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . basename((string) $f['orig']) . '"');
        readfile($abs);
        exit;
    }
    if ($page === 'verify') {
        $st = $db->prepare('SELECT * FROM dg_tokens WHERE token=? AND purpose=?');
        $st->execute([(string) ($_GET['token'] ?? ''), 'verify']);
        $row = $st->fetch();
        if ($row) {
            $db->prepare('UPDATE dg_users SET email_ok=1 WHERE id=?')->execute([$row['user_id']]);
            $db->prepare('DELETE FROM dg_tokens WHERE id=?')->execute([$row['id']]);
            flash('Email confirmed.');
        } else {
            flash('That confirmation link is not valid.');
        }
        go(user() ? 'account' : 'login');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $err = handle_auth($db);
        handle_product_post($db, $err);
    }
    $me = user();
    $need = array_merge(['dash', 'docs', 'inbox', 'orders', 'notices', 'account'], array_keys($P['dash_extra'] ?? []));
    if (in_array($page, $need, true) && !$me) {
        go('login');
    }
    if (in_array($page, $need, true) && is_admin()) {
        go('admin');
    }
    if ($page === 'admin') {
        if (!is_admin()) {
            go('login');
        }
        require __DIR__ . '/admin.php';
        admin_boot($db);
        shell_start('Admin — ' . $P['brand']);
        $ok = flash();
        if ($ok) {
            echo '<div class="container"><div class="notice" style="margin-top:16px">' . h($ok) . '</div></div>';
        }
        admin_render($db);
        shell_end();
        return;
    }
    shell_start($page === 'home' ? $P['brand'] : $P['brand']);
    $ok = flash();
    if ($ok) {
        echo '<div class="container"><div class="notice" style="margin-top:16px">' . h($ok) . '</div></div>';
    }
    if ($err) {
        echo '<div class="container"><p class="err">' . h($err) . '</p></div>';
    }
    render_page($db, $page, $P, $me);
    shell_end();
}

function render_page(PDO $db, string $page, array $P, ?array $me): void
{
    if (function_exists('product_render_page') && product_render_page($db, $page, $P, $me)) {
        return;
    }
    $hub = ($P['mode'] ?? '') === 'hub';
    if ($page === 'join') {
        echo '<section class="section soft"><div class="container"><div class="card"><h2>' . h($P['join_cta']) . '</h2>';
        if (!empty($P['age_min'])) {
            echo '<p>Family-friendly. You must be ' . (int) $P['age_min'] . '+. This is not a dating app.</p>';
        }
        echo '<form method="post">' . csrf_fields('register');
        echo '<div class="form-row"><input class="input" name="name" placeholder="Your name" required><input class="input" type="email" name="email" placeholder="Email" required></div><br>';
        echo '<div class="form-row"><input class="input" type="password" name="password" minlength="8" placeholder="Password (8+)" required><input class="input" name="phone" placeholder="Phone"></div><br>';
        if (!empty($P['age_min'])) {
            echo '<label>Date of birth</label><input class="input" type="date" name="birth_date" required><br><br>';
        }
        if (!$hub) {
            echo '<h3>First ' . h($P['listing_label']) . '</h3>';
            foreach (array_chunk($P['fields'], 2) as $pair) {
                echo '<div class="form-row">';
                foreach ($pair as $f) {
                    echo field_input($f);
                }
                if (count($pair) === 1) {
                    echo '<div></div>';
                }
                echo '</div><br>';
            }
        }
        echo '<button class="btn" type="submit">Create account</button></form></div></div></section>';
        return;
    }
    if ($page === 'login') {
        echo '<section class="section soft"><div class="container"><div class="card" style="max-width:28rem"><h2>Log in</h2><form method="post">' . csrf_fields('login');
        echo '<input class="input" type="email" name="email" required placeholder="Email"><br><br><input class="input" type="password" name="password" required placeholder="Password"><br><br>';
        echo '<button class="btn" type="submit">Log in</button></form><p><a href="?p=forgot">Forgot password</a></p></div></div></section>';
        return;
    }
    if ($page === 'forgot') {
        echo '<section class="section soft"><div class="container"><div class="card" style="max-width:28rem"><h2>Forgot password</h2><form method="post">' . csrf_fields('forgot');
        echo '<input class="input" type="email" name="email" required placeholder="Email"><br><br><button class="btn" type="submit">Send reset link</button></form></div></div></section>';
        return;
    }
    if ($page === 'reset') {
        echo '<section class="section soft"><div class="container"><div class="card" style="max-width:28rem"><h2>New password</h2><form method="post">' . csrf_fields('reset');
        echo '<input type="hidden" name="token" value="' . h((string) ($_GET['token'] ?? '')) . '">';
        echo '<input class="input" type="password" name="password" minlength="8" required><br><br><button class="btn" type="submit">Update</button></form></div></div></section>';
        return;
    }
    if ($page === 'dash' && $me && !$hub) {
        $list = owner_listings($db);
        $edit = null;
        $eid = (int) ($_GET['id'] ?? 0);
        foreach ($list as $L) {
            if ($eid && (int) $L['id'] === $eid) {
                $edit = $L;
            }
        }
        if (!$edit && $list && ($P['listing_mode'] ?? 'many') === 'one') {
            $edit = $list[0];
        }
        echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Dashboard</h2><p>Your ' . h($P['listing_label']) . ' records.</p></div></div>';
        echo '<div class="dashboard">' . dash_nav('dash') . '<div class="dash-panel">';
        if ($list) {
            echo '<table class="table"><tr><th>' . h($P['listing_label']) . '</th><th>Status</th><th></th></tr>';
            foreach ($list as $L) {
                echo '<tr><td>' . h($L[$P['title_col']]) . '</td><td>' . h($L[$P['status_col']]) . '</td><td><a href="?p=dash&id=' . (int) $L['id'] . '">Edit</a> · <a href="?p=view&id=' . (int) $L['id'] . '">Public</a></td></tr>';
            }
            echo '</table><br>';
        }
        echo '<h3>' . ($edit ? 'Edit' : 'Add') . ' ' . h($P['listing_label']) . '</h3><form method="post">' . csrf_fields('save');
        if ($edit) {
            echo '<input type="hidden" name="id" value="' . (int) $edit['id'] . '">';
        }
        foreach (array_chunk($P['fields'], 2) as $pair) {
            echo '<div class="form-row">';
            foreach ($pair as $f) {
                echo field_input($f, $edit[$f[0]] ?? '');
            }
            echo '</div><br>';
        }
        echo '<button class="btn" type="submit">Save</button></form>';
        echo '<br><form method="post">' . csrf_fields('order') . '<input type="hidden" name="kind" value="verify"><input type="hidden" name="item" value="Verified listing"><button class="btn light" type="submit">Request verification (' . h(setting('price_verified')) . ')</button></form>';
        echo '</div></div></div></section>';
        return;
    }
    if ($page === 'dash' && $me && $hub) {
        echo '<section class="section"><div class="container"><h2>Hello, ' . h($me['name']) . '</h2><p>One account across Do Galaxy. Pick a path.</p><form method="post">' . csrf_fields('path') . '<div class="grid-3">';
        foreach ($P['paths'] as $k => $r) {
            echo '<label class="feature"><input type="radio" name="path" value="' . h($k) . '" required> <h3>' . h($r[0]) . '</h3><p>' . h($r[2]) . '</p></label>';
        }
        echo '</div><br><button class="btn" type="submit">Continue</button></form></div></section>';
        return;
    }
    if ($page === 'docs' && $me) {
        $list = owner_listings($db);
        echo '<section class="section soft"><div class="container"><div class="dashboard">' . dash_nav('docs') . '<div class="dash-panel"><h2>Documents</h2>';
        if ($list) {
            echo '<form method="post" enctype="multipart/form-data">' . csrf_fields('upload') . '<div class="form-row"><select class="input" name="listing_id">';
            foreach ($list as $L) {
                echo '<option value="' . (int) $L['id'] . '">' . h($L[$P['title_col']]) . '</option>';
            }
            echo '</select><select class="input" name="code">';
            foreach ($P['docs'] as $c => $lab) {
                echo '<option value="' . h($c) . '">' . h($lab) . '</option>';
            }
            echo '</select></div><br><input type="file" name="doc" required> <button class="btn" type="submit">Upload</button></form>';
        }
        $ids = array_column($list, 'id') ?: [0];
        $in = implode(',', array_map('intval', $ids));
        $files = $db->query("SELECT * FROM dg_files WHERE listing_id IN ($in) ORDER BY id DESC")->fetchAll();
        echo '<br><table class="table"><tr><th>File</th><th>Type</th><th>When</th></tr>';
        foreach ($files as $f) {
            echo '<tr><td><a href="?p=file&id=' . (int) $f['id'] . '">' . h($f['orig']) . '</a></td><td>' . h($f['code']) . '</td><td>' . h($f['created_at']) . '</td></tr>';
        }
        echo '</table></div></div></div></section>';
        return;
    }
    if ($page === 'inbox' && $me && !$hub) {
        $list = owner_listings($db);
        $ids = array_column($list, 'id') ?: [0];
        $in = implode(',', array_map('intval', $ids));
        $rows = $db->query('SELECT * FROM ' . $P['request_table'] . ' WHERE ' . $P['request_fk'] . " IN ($in) ORDER BY id DESC")->fetchAll();
        echo '<section class="section soft"><div class="container"><div class="dashboard">' . dash_nav('inbox') . '<div class="dash-panel"><h2>' . h($P['request_label']) . 's</h2><table class="table"><tr><th>From</th><th>Detail</th><th>Status</th><th></th></tr>';
        foreach ($rows as $r) {
            $detail = [];
            foreach ($P['request_fields'] as $f) {
                if (!empty($r[$f[0]])) {
                    $detail[] = $f[1] . ': ' . $r[$f[0]];
                }
            }
            echo '<tr><td>' . h($r['name']) . '<br><small>' . h($r['email']) . '</small></td><td>' . h(implode(' · ', $detail)) . '</td><td>' . h($r['status']) . '</td><td><form method="post" class="inline">' . csrf_fields('req_status') . '<input type="hidden" name="id" value="' . (int) $r['id'] . '"><select name="status"><option>accepted</option><option>declined</option><option>closed</option></select><button class="btn light" type="submit">Set</button></form></td></tr>';
        }
        if (!$rows) {
            echo '<tr><td colspan="4">None yet.</td></tr>';
        }
        echo '</table></div></div></div></section>';
        return;
    }
    if ($page === 'orders' && $me) {
        $st = $db->prepare('SELECT * FROM dg_orders WHERE user_id=? ORDER BY id DESC');
        $st->execute([$me['id']]);
        echo '<section class="section soft"><div class="container"><div class="dashboard">' . dash_nav('orders') . '<div class="dash-panel"><h2>Requests</h2><table class="table"><tr><th>#</th><th>Item</th><th>Status</th></tr>';
        foreach ($st as $r) {
            echo '<tr><td>' . (int) $r['id'] . '</td><td>' . h($r['item']) . '</td><td>' . h($r['status']) . '</td></tr>';
        }
        echo '</table></div></div></div></section>';
        return;
    }
    if ($page === 'notices' && $me) {
        $st = $db->prepare('SELECT * FROM dg_notices WHERE user_id=? ORDER BY id DESC LIMIT 40');
        $st->execute([$me['id']]);
        echo '<section class="section soft"><div class="container"><div class="dashboard">' . dash_nav('notices') . '<div class="dash-panel"><form method="post">' . csrf_fields('seen') . '<button class="btn light" type="submit">Mark all read</button></form><br>';
        foreach ($st as $r) {
            echo '<div class="card" style="margin-bottom:12px"><a href="' . h($r['link'] ?: '?p=dash') . '">' . h($r['title']) . '</a><p>' . h($r['body']) . '</p></div>';
        }
        echo '</div></div></div></section>';
        return;
    }
    if ($page === 'account' && $me) {
        echo '<section class="section soft"><div class="container"><div class="dashboard">' . dash_nav('account') . '<div class="dash-panel"><h2>Account</h2><p>' . h($me['email']) . '</p><form method="post">' . csrf_fields('changepass');
        echo '<input class="input" type="password" name="old" required placeholder="Current password"><br><br><input class="input" type="password" name="password" minlength="8" required placeholder="New password"><br><br><button class="btn" type="submit">Change password</button></form></div></div></div></section>';
        return;
    }
    if ($page === 'dir' && !$hub) {
        $q = trim((string) ($_GET['q'] ?? ''));
        $sql = 'SELECT * FROM ' . $P['listing_table'] . ' WHERE ' . $P['status_col'] . " IN ('pending','verified','live','open')";
        $args = [];
        if ($q !== '') {
            $sql .= ' AND (' . $P['title_col'] . ' LIKE ? OR city LIKE ?)';
            $args[] = "%$q%";
            $args[] = "%$q%";
        }
        $trade = trim((string) ($_GET['trade'] ?? ''));
        if ($trade !== '' && in_array('trade', array_column($P['fields'], 0), true)) {
            $sql .= ' AND trade LIKE ?';
            $args[] = "%$trade%";
        }
        $st = $db->prepare($sql . ' ORDER BY featured DESC, id DESC LIMIT 50');
        $st->execute($args);
        $rows = $st->fetchAll();
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>' . h($P['dir_label']) . '</h2><p>Search by name or city.</p></div></div>';
        echo '<form class="form-row" method="get" style="max-width:720px;margin-bottom:24px"><input type="hidden" name="p" value="dir"><input class="input" name="q" value="' . h($q) . '" placeholder="Name or city"><button class="btn" type="submit">Search</button></form><div class="list-grid">';
        foreach ($rows as $r) {
            echo '<div class="biz-card"><div class="biz-head"><div class="avatar">' . h(strtoupper(substr((string) $r[$P['title_col']], 0, 1))) . '</div><div><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r[$P['title_col']]) . '</a></h3><p>' . h($r['city'] ?? '') . '</p></div></div>';
            echo '<div class="meta"><span class="verified">' . h($r[$P['status_col']]) . '</span></div><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">View</a></div>';
        }
        if (!$rows) {
            echo '<p>None yet. <a href="?p=join">Be first</a>.</p>';
        }
        echo '</div></div></section>';
        return;
    }
    if ($page === 'view' && !$hub) {
        $st = $db->prepare('SELECT * FROM ' . $P['listing_table'] . ' WHERE id=?');
        $st->execute([(int) ($_GET['id'] ?? 0)]);
        $v = $st->fetch();
        echo '<section class="section"><div class="container">';
        if (!$v) {
            echo '<p>Not found.</p></div></section>';
            return;
        }
        echo '<div class="biz-card"><div class="biz-head"><div class="avatar">' . h(strtoupper(substr((string) $v[$P['title_col']], 0, 1))) . '</div><div><h2>' . h($v[$P['title_col']]) . '</h2><div class="meta"><span>' . h($v['city'] ?? '') . '</span><span class="verified">' . h($v[$P['status_col']]) . '</span></div></div></div>';
        echo '<table class="table">';
        foreach ($P['fields'] as $f) {
            if ($f[0] === $P['title_col']) {
                continue;
            }
            echo '<tr><th>' . h($f[1]) . '</th><td>' . nl2br(h((string) ($v[$f[0]] ?? '—'))) . '</td></tr>';
        }
        echo '</table></div><br><div class="card"><h3>' . h($P['request_label']) . '</h3>';
        if (!empty($_GET['sent'])) {
            echo '<div class="notice">Sent to the owner inbox.</div>';
        }
        echo '<form method="post">' . csrf_fields('request') . '<input type="hidden" name="target_id" value="' . (int) $v['id'] . '">';
        echo '<div class="form-row"><input class="input" name="name" placeholder="Name" required><input class="input" type="email" name="email" required placeholder="Email"></div><br>';
        echo '<input class="input" name="phone" placeholder="Phone"><br><br>';
        foreach ($P['request_fields'] as $f) {
            echo field_input($f) . '<br>';
        }
        echo '<button class="btn" type="submit">Send</button></form></div></div></section>';
        return;
    }
    if ($page === 'services') {
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Services</h2><p>' . h(setting('services_intro')) . '</p></div></div><div class="grid-3">';
        foreach (catalog_lines(setting('packages')) as $s) {
            echo '<div class="price-card"><h3>' . h($s[0]) . '</h3><div class="price">' . h($s[1]) . '</div><p>' . h($s[2]) . '</p><form method="post">' . csrf_fields('order') . '<input type="hidden" name="kind" value="service"><input type="hidden" name="item" value="' . h($s[0]) . '"><input type="hidden" name="amount" value="' . h($s[1]) . '"><button class="btn light" type="submit">Request</button></form></div>';
        }
        echo '</div></div></section>';
        return;
    }
    if ($page === 'pricing') {
        echo '<section class="section"><div class="container"><div class="grid-3">';
        echo '<div class="price-card"><h3>Starter</h3><div class="price">' . h(setting('price_starter')) . '</div><a class="btn" href="?p=join">Join</a></div>';
        echo '<div class="price-card featured"><h3>Verified</h3><div class="price">' . h(setting('price_verified')) . '</div><form method="post">' . csrf_fields('order') . '<input type="hidden" name="kind" value="verify"><input type="hidden" name="item" value="Verified listing"><button class="btn" type="submit">Request</button></form></div>';
        echo '<div class="price-card"><h3>Growth</h3><div class="price">' . h(setting('price_growth')) . '</div><form method="post">' . csrf_fields('order') . '<input type="hidden" name="kind" value="program"><input type="hidden" name="item" value="Growth"><button class="btn" type="submit">Talk to us</button></form></div>';
        echo '</div></div></section>';
        return;
    }
    if ($page === 'contact') {
        echo '<section class="section soft"><div class="container"><div class="card" style="max-width:36rem"><h2>Contact</h2>';
        if (!empty($_GET['sent'])) {
            echo '<div class="notice">Received.</div>';
        }
        echo '<form method="post">' . csrf_fields('enquire') . '<div class="form-row"><input class="input" name="name" required placeholder="Name"><input class="input" type="email" name="email" required placeholder="Email"></div><br>';
        echo '<input class="input" name="phone" placeholder="Phone"><br><br><textarea name="message" required></textarea><br><br><button class="btn" type="submit">Send</button></form></div></div></section>';
        return;
    }
    if (in_array($page, ['about', 'privacy', 'terms'], true)) {
        echo '<section class="section"><div class="container"><div class="card"><h2>' . h(ucfirst($page)) . '</h2><p>' . nl2br(h(setting('page_' . $page))) . '</p></div></div></section>';
        return;
    }
    if ($page === 'products' && $hub) {
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Do Galaxy products</h2><p>Choose the planet that matches your current need.</p></div><a class="btn light" href="?p=guide">Ask Do</a></div><div class="grid-3">';
        foreach ($P['paths'] as $k => $r) {
            echo '<div class="feature"><h3>' . h($r[0]) . '</h3><p>' . h($r[2]) . '</p><p><a class="btn" href="' . h(galaxy_url($k)) . '">Open</a></p></div>';
        }
        echo '</div></div></section>';
        return;
    }
    $feat = [];
    if (!$hub) {
        $feat = $db->query('SELECT * FROM ' . $P['listing_table'] . ' WHERE ' . $P['status_col'] . " IN ('pending','verified','live','open') ORDER BY featured DESC, id DESC LIMIT 4")->fetchAll();
    }
    echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">' . h(setting('eyebrow')) . '</span>';
    echo '<h1>' . h(setting('hero_h1')) . '</h1><p>' . h(setting('hero_p')) . '</p>';
    echo '<div class="hero-actions"><a class="btn" href="?p=join">' . h($P['join_cta']) . '</a>';
    echo $hub ? '<a class="btn light" href="?p=products">See products</a>' : '<a class="btn light" href="?p=dir">Explore</a>';
    foreach ($P['hero_extra'] ?? [] as $n) {
        echo '<a class="btn light" href="?p=' . h($n[0]) . '">' . h($n[1]) . '</a>';
    }
    echo '</div></div><div class="search-panel"><h3>Search</h3>';
    if ($hub) {
        echo '<p>One account for every Do Galaxy planet.</p><a class="btn" href="?p=join">Create account</a>';
    } else {
        echo '<form method="get"><input type="hidden" name="p" value="dir"><input class="input" name="q" placeholder="Name or city"><br><br><button class="btn" type="submit">Search</button></form>';
    }
    echo '</div></div></section>';
    if ($feat) {
        echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Featured</h2></div><a class="btn light" href="?p=dir">Directory</a></div><div class="list-grid">';
        foreach ($feat as $r) {
            echo '<div class="biz-card"><div class="biz-head"><div class="avatar">' . h(strtoupper(substr((string) $r[$P['title_col']], 0, 1))) . '</div><div><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r[$P['title_col']]) . '</a></h3><p>' . h($r['city'] ?? '') . '</p></div></div><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">View</a></div>';
        }
        echo '</div></div></section>';
    }
}
