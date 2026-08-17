<?php
declare(strict_types=1);

function handle_loop(PDO $db, array $codes, string &$err): void
{
    $act = (string) ($_POST['act'] ?? '');
    if ($act === '' || in_array($act, ['register', 'login', 'logout', 'forgot', 'reset', 'changepass', 'save', 'compliance', 'site', 'catalog', 'verify', 'user', 'enq', 'order_status', 'pages', 'feature'], true)) {
        return;
    }
    if (!csrf_ok()) {
        $err = 'Session expired. Try again.';
        return;
    }
    if ($act === 'enquire') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $msg = trim((string) ($_POST['message'] ?? ''));
        if (!$name || !$email || $msg === '') {
            $err = 'Name, email and message are required.';
            return;
        }
        $tid = (int) ($_POST['target_id'] ?? 0) ?: null;
        $db->prepare('INSERT INTO dg_enquiries (product, target_id, user_id, name, email, phone, intent, message) VALUES (?,?,?,?,?,?,?,?)')
            ->execute(['udyog', $tid, user()['id'] ?? null, $name, (string) $email, trim((string) ($_POST['phone'] ?? '')), (string) ($_POST['intent'] ?? 'general'), $msg]);
        $eid = (int) $db->lastInsertId();
        if ($tid) {
            $st = $db->prepare('SELECT owner_id, legal_name FROM dg_businesses WHERE id=?');
            $st->execute([$tid]);
            $b = $st->fetch();
            if ($b) {
                notify($db, (int) $b['owner_id'], 'New enquiry', $name . ' wrote to ' . $b['legal_name'], '?p=inbox&id=' . $eid);
                $st = $db->prepare('SELECT email FROM dg_users WHERE id=?');
                $st->execute([$b['owner_id']]);
                $to = (string) $st->fetchColumn();
                if ($to) {
                    queue_mail($db, $to, 'New DoUdyog enquiry', $name . " ($email): $msg");
                }
            }
        } else {
            queue_mail($db, setting('contact_email', 'hello@doudyog.com'), 'DoUdyog contact', "$name <$email>\n$msg");
        }
        $key = thread_key(['id' => $eid, 'email' => (string) $email]);
        flash('Enquiry received. Keep this thread link to follow replies.');
        go($tid ? 'view&id=' . $tid . '&sent=1&eid=' . $eid . '&k=' . $key : 'contact&sent=1&eid=' . $eid . '&k=' . $key);
    }
    if ($act === 'reply') {
        $eid = (int) ($_POST['enquiry_id'] ?? 0);
        $body = trim((string) ($_POST['body'] ?? ''));
        $st = $db->prepare('SELECT * FROM dg_enquiries WHERE id=?');
        $st->execute([$eid]);
        $e = $st->fetch();
        if (!$e || $body === '') {
            $err = 'Reply is empty or enquiry missing.';
            return;
        }
        $biz = owner_biz($db);
        $mine = $biz && (int) $e['target_id'] === (int) $biz['id'];
        $guest = isset($_POST['k']) && hash_equals(thread_key($e), (string) $_POST['k']);
        if (!$mine && !$guest && !is_admin()) {
            $err = 'Not allowed to reply.';
            return;
        }
        $author = $mine || is_admin() ? (user()['name'] ?? 'Owner') : $e['name'];
        $db->prepare('INSERT INTO dg_replies (enquiry_id, user_id, author, body) VALUES (?,?,?,?)')
            ->execute([$eid, user()['id'] ?? null, $author, $body]);
        $db->prepare('UPDATE dg_enquiries SET status=? WHERE id=?')->execute(['open', $eid]);
        if ($mine || is_admin()) {
            queue_mail($db, $e['email'], 'Reply from ' . ($biz['legal_name'] ?? 'DoUdyog'), $body);
            flash('Reply sent to ' . $e['email'] . '.');
            go('inbox&id=' . $eid);
        }
        if ($biz) {
            notify($db, (int) $biz['owner_id'], 'Enquiry reply', $e['name'] . ' replied', '?p=inbox&id=' . $eid);
        }
        flash('Reply added.');
        go('thread&id=' . $eid . '&k=' . thread_key($e));
    }
    if ($act === 'upload' && user()) {
        $biz = owner_biz($db);
        if (!$biz) {
            $err = 'No business on this account.';
            return;
        }
        $code = (string) ($_POST['code'] ?? '');
        if (!isset($codes[$code])) {
            $err = 'Unknown document type.';
            return;
        }
        $path = save_upload('doc', (int) $biz['id'], $code);
        if ($path === null) {
            $err = 'Choose a PDF or image under 4 MB.';
            return;
        }
        if ($path === '') {
            $err = 'Upload failed. PDF, JPG or PNG under 4 MB.';
            return;
        }
        $db->prepare('INSERT INTO dg_files (business_id, code, path, orig) VALUES (?,?,?,?)')
            ->execute([$biz['id'], $code, $path, (string) ($_FILES['doc']['name'] ?? 'file')]);
        $db->prepare('UPDATE dg_compliance SET done=1, updated_at=? WHERE business_id=? AND code=?')
            ->execute([date('c'), $biz['id'], $code]);
        $sc = $db->prepare('SELECT AVG(done)*100 FROM dg_compliance WHERE business_id=?');
        $sc->execute([$biz['id']]);
        $db->prepare('UPDATE dg_businesses SET completeness=? WHERE id=?')->execute([(int) round((float) $sc->fetchColumn()), $biz['id']]);
        audit($db, user()['id'], 'file', (int) $biz['id'], 'upload:' . $code);
        flash('Document stored against ' . $codes[$code] . '.');
        go('docs');
    }
    if ($act === 'order') {
        $kind = (string) ($_POST['kind'] ?? '');
        $item = trim((string) ($_POST['item'] ?? ''));
        if (!in_array($kind, ['service', 'program', 'verify'], true) || $item === '') {
            $err = 'Choose a package.';
            return;
        }
        $biz = owner_biz($db);
        $amount = $kind === 'verify' ? setting('price_verified', '₹999') : trim((string) ($_POST['amount'] ?? ''));
        $db->prepare('INSERT INTO dg_orders (user_id, business_id, kind, item, amount, status, note) VALUES (?,?,?,?,?,?,?)')
            ->execute([user()['id'] ?? null, $biz['id'] ?? null, $kind, $item, $amount, 'new', trim((string) ($_POST['note'] ?? '')) ?: null]);
        $oid = (int) $db->lastInsertId();
        queue_mail($db, setting('contact_email', 'hello@doudyog.com'), 'New ' . $kind . ' request', $item . ' · ' . $amount);
        if (user()) {
            notify($db, (int) user()['id'], 'Request received', $item . ' is with staff.', '?p=orders');
        }
        audit($db, user()['id'] ?? null, 'order', $oid, $kind);
        flash('Request #' . $oid . ' is with the DoUdyog desk. Staff will mark it accepted.');
        go(user() ? 'orders' : 'contact&sent=1');
    }
    if ($act === 'logo' && user()) {
        $biz = owner_biz($db);
        if (!$biz) {
            $err = 'No business on this account.';
            return;
        }
        $path = save_upload('logo', (int) $biz['id'], 'logo');
        if (!$path) {
            $err = 'Upload a JPG or PNG logo under 4 MB.';
            return;
        }
        $db->prepare('UPDATE dg_businesses SET logo=?, updated_at=? WHERE id=?')->execute([$path, date('c'), $biz['id']]);
        flash('Logo updated.');
        go('dash');
    }
    if ($act === 'seen' && user()) {
        $db->prepare('UPDATE dg_notices SET seen=1 WHERE user_id=?')->execute([user()['id']]);
        go('notices');
    }
}

function serve_file(PDO $db): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $st = $db->prepare('SELECT f.*, b.owner_id FROM dg_files f JOIN dg_businesses b ON b.id=f.business_id WHERE f.id=?');
    $st->execute([$id]);
    $f = $st->fetch();
    if (!$f) {
        http_response_code(404);
        exit('not found');
    }
    $ok = is_admin() || (user() && (int) user()['id'] === (int) $f['owner_id']);
    if (!$ok) {
        http_response_code(403);
        exit('forbidden');
    }
    $abs = __DIR__ . '/uploads/' . $f['path'];
    if (!is_file($abs)) {
        http_response_code(404);
        exit('missing');
    }
    $mime = mime_content_type($abs) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename((string) $f['orig']) . '"');
    readfile($abs);
    exit;
}

function consume_verify_token(PDO $db): void
{
    $tok = (string) ($_GET['token'] ?? '');
    $st = $db->prepare('SELECT * FROM dg_tokens WHERE token=? AND purpose=?');
    $st->execute([$tok, 'verify']);
    $row = $st->fetch();
    if (!$row) {
        flash('That confirmation link is not valid.');
        go('login');
    }
    $db->prepare('UPDATE dg_users SET email_ok=1 WHERE id=?')->execute([$row['user_id']]);
    $db->prepare('DELETE FROM dg_tokens WHERE id=?')->execute([$row['id']]);
    flash('Email confirmed.');
    go(user() ? 'account' : 'login');
}
