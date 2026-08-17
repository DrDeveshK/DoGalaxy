<?php
declare(strict_types=1);

function admin_boot(PDO $db): void
{
    $act = (string) ($_POST['act'] ?? '');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $act === '' || $act === 'logout' || !csrf_ok()) {
        return;
    }
    if ($act === 'site') {
        foreach (['brand', 'topbar', 'eyebrow', 'hero_h1', 'hero_p', 'services_intro', 'growth_intro', 'footer_blurb'] as $k) {
            setting_set($k, trim((string) ($_POST[$k] ?? '')));
        }
        audit($db, user()['id'], 'settings', null, 'site');
        flash('Site copy saved. Open Home to see it.');
        go('admin&tab=site');
    }
    if ($act === 'reset_site') {
        reset_public_copy();
        audit($db, user()['id'], 'settings', null, 'reset_site');
        flash('Public copy reset.');
        go('admin&tab=site');
    }
    if ($act === 'catalog') {
        setting_set('packages', trim((string) ($_POST['packages'] ?? '')));
        setting_set('programs', trim((string) ($_POST['programs'] ?? '')));
        setting_set('price_starter', trim((string) ($_POST['price_starter'] ?? 'Free')));
        setting_set('price_verified', trim((string) ($_POST['price_verified'] ?? '₹999')));
        setting_set('price_growth', trim((string) ($_POST['price_growth'] ?? '₹4,999')));
        audit($db, user()['id'], 'settings', null, 'catalog');
        flash('Services, programs and prices saved.');
        go('admin&tab=catalog');
    }
    if ($act === 'verify') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = (string) ($_POST['status'] ?? 'pending');
        if ($id && in_array($st, ['pending', 'verified', 'rejected'], true)) {
            $db->prepare('UPDATE dg_businesses SET verify_status=?, updated_at=? WHERE id=?')->execute([$st, date('c'), $id]);
            audit($db, user()['id'], 'business', $id, 'verify:' . $st);
            flash('Business #' . $id . ' marked ' . $st . '.');
        }
        go('admin&tab=biz');
    }
    if ($act === 'user') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = (string) ($_POST['status'] ?? 'active');
        if ($id && $id !== (int) user()['id'] && in_array($st, ['active', 'suspended'], true)) {
            $db->prepare('UPDATE dg_users SET status=? WHERE id=?')->execute([$st, $id]);
            audit($db, user()['id'], 'user', $id, $st);
            flash('User #' . $id . ' is ' . $st . '.');
        }
        go('admin&tab=users');
    }
    if ($act === 'pages') {
        foreach (['page_about', 'page_privacy', 'page_terms', 'contact_email', 'contact_phone'] as $k) {
            setting_set($k, trim((string) ($_POST[$k] ?? '')));
        }
        flash('Legal pages and contact saved.');
        go('admin&tab=pages');
    }
    if ($act === 'order_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = (string) ($_POST['status'] ?? 'new');
        if ($id && in_array($st, ['new', 'accepted', 'done', 'rejected'], true)) {
            $db->prepare('UPDATE dg_orders SET status=?, note=? WHERE id=?')->execute([$st, trim((string) ($_POST['note'] ?? '')) ?: null, $id]);
            $row = $db->prepare('SELECT user_id, kind, item FROM dg_orders WHERE id=?');
            $row->execute([$id]);
            $o = $row->fetch();
            if ($o && $o['user_id']) {
                notify($db, (int) $o['user_id'], 'Request ' . $st, $o['item'] . ' is now ' . $st, '?p=orders');
            }
            if ($o && $o['kind'] === 'verify' && $st === 'accepted') {
                $db->prepare('UPDATE dg_businesses SET verify_status=? WHERE id=(SELECT business_id FROM dg_orders WHERE id=?)')->execute(['verified', $id]);
            }
            flash('Request #' . $id . ' marked ' . $st . '.');
        }
        go('admin&tab=orders');
    }
    if ($act === 'feature') {
        $id = (int) ($_POST['id'] ?? 0);
        $on = (int) ($_POST['featured'] ?? 0);
        if ($id) {
            $db->prepare('UPDATE dg_businesses SET featured=? WHERE id=?')->execute([$on ? 1 : 0, $id]);
            flash('Featured flag updated.');
        }
        go('admin&tab=biz');
    }
    if ($act === 'enq') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = (string) ($_POST['status'] ?? 'open');
        if ($id && in_array($st, ['new', 'open', 'closed'], true)) {
            $db->prepare('UPDATE dg_enquiries SET status=? WHERE id=?')->execute([$st, $id]);
            flash('Enquiry #' . $id . ' marked ' . $st . '.');
        }
        go('admin&tab=enq');
    }
}

function admin_render(PDO $db): void
{
    $tab = (string) ($_GET['tab'] ?? 'home');
    $bizN = (int) $db->query('SELECT COUNT(*) FROM dg_businesses')->fetchColumn();
    $userN = (int) $db->query('SELECT COUNT(*) FROM dg_users')->fetchColumn();
    $enqN = (int) $db->query("SELECT COUNT(*) FROM dg_enquiries WHERE status!='closed'")->fetchColumn();
    $pendN = (int) $db->query("SELECT COUNT(*) FROM dg_businesses WHERE verify_status='pending'")->fetchColumn();

    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Admin</h2><p>Customise the public site, verify firms, and work the inbox.</p></div></div>';
    echo '<div class="dashboard"><aside class="dash-nav">';
    foreach (['home' => 'Overview', 'site' => 'Site copy', 'catalog' => 'Services & prices', 'pages' => 'Legal & contact', 'biz' => 'Businesses', 'docs' => 'Documents', 'orders' => 'Requests', 'users' => 'Users', 'enq' => 'Enquiries', 'mail' => 'Mail log', 'audit' => 'Audit'] as $k => $lab) {
        echo '<a href="?p=admin&tab=' . $k . '"' . ($tab === $k ? ' class="on"' : '') . '>' . h($lab) . '</a>';
    }
    echo '</aside><div class="dash-panel">';

    if ($tab === 'site') {
        echo '<h3>Public copy</h3><p class="muted">These fields drive the homepage, header and footer.</p>';
        echo '<form method="post"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><input type="hidden" name="act" value="site">';
        foreach ([['brand', 'Brand name'], ['topbar', 'Top bar'], ['eyebrow', 'Hero eyebrow'], ['hero_h1', 'Hero headline'], ['hero_p', 'Hero paragraph'], ['services_intro', 'Services intro'], ['growth_intro', 'Growth intro'], ['footer_blurb', 'Footer blurb']] as [$k, $lab]) {
            echo '<label>' . h($lab) . '</label>';
            if ($k === 'hero_p' || $k === 'footer_blurb') {
                echo '<textarea name="' . $k . '">' . h(setting($k)) . '</textarea><br>';
            } else {
                echo '<input class="input" name="' . $k . '" value="' . h(setting($k)) . '"><br><br>';
            }
        }
        echo '<button class="btn" type="submit">Save copy</button> <a class="btn light" href="?p=home" target="_blank">Preview home</a></form><br><form method="post">' . csrf_fields('reset_site') . '<button class="btn light" type="submit">Reset public copy</button></form>';
    } elseif ($tab === 'catalog') {
        echo '<h3>Services, programs, prices</h3><p class="muted">One item per line: <code>Name | price or duration | short description</code></p>';
        echo '<form method="post"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><input type="hidden" name="act" value="catalog">';
        echo '<label>Service packages</label><textarea name="packages" style="min-height:180px">' . h(setting('packages')) . '</textarea><br>';
        echo '<label>Growth programs</label><textarea name="programs" style="min-height:180px">' . h(setting('programs')) . '</textarea><br>';
        echo '<div class="form-row"><div><label>Starter price</label><input class="input" name="price_starter" value="' . h(setting('price_starter', 'Free')) . '"></div>';
        echo '<div><label>Verified price</label><input class="input" name="price_verified" value="' . h(setting('price_verified', '₹999')) . '"></div>';
        echo '<div><label>Growth price</label><input class="input" name="price_growth" value="' . h(setting('price_growth', '₹4,999')) . '"></div></div><br>';
        echo '<button class="btn" type="submit">Save catalogue</button></form>';
    } elseif ($tab === 'biz') {
        $rows = $db->query('SELECT b.*, u.email AS owner_email FROM dg_businesses b LEFT JOIN dg_users u ON u.id=b.owner_id ORDER BY b.id DESC LIMIT 80')->fetchAll();
        echo '<h3>Businesses</h3><table class="table"><tr><th>Firm</th><th>Owner</th><th>City</th><th>%</th><th>Status</th><th></th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['legal_name']) . '</a><br><small>' . h($r['industry']) . '</small></td>';
            echo '<td>' . h($r['owner_email']) . '</td><td>' . h($r['city']) . '</td><td>' . (int) $r['completeness'] . '</td><td>' . h($r['verify_status']) . '</td><td>';
            echo '<form method="post" class="inline">' . csrf_fields('verify') . '<input type="hidden" name="id" value="' . (int) $r['id'] . '">';
            echo '<select name="status"><option>pending</option><option' . ($r['verify_status'] === 'verified' ? ' selected' : '') . '>verified</option><option' . ($r['verify_status'] === 'rejected' ? ' selected' : '') . '>rejected</option></select> ';
            echo '<button class="btn light" type="submit">Set</button></form> ';
            echo '<form method="post" class="inline">' . csrf_fields('feature') . '<input type="hidden" name="id" value="' . (int) $r['id'] . '"><input type="hidden" name="featured" value="' . ((int) ($r['featured'] ?? 0) ? '0' : '1') . '"><button class="btn light" type="submit">' . ((int) ($r['featured'] ?? 0) ? 'Unfeature' : 'Feature') . '</button></form></td></tr>';
        }
        echo '</table>';
    } elseif ($tab === 'users') {
        $rows = $db->query('SELECT id, email, name, role, status, created_at FROM dg_users ORDER BY id DESC LIMIT 80')->fetchAll();
        echo '<h3>Users</h3><table class="table"><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . h($r['name']) . '</td><td>' . h($r['email']) . '</td><td>' . h($r['role']) . '</td><td>' . h($r['status']) . '</td><td>';
            if ((int) $r['id'] !== (int) user()['id'] && $r['role'] !== 'admin') {
                $next = $r['status'] === 'active' ? 'suspended' : 'active';
                echo '<form method="post" class="inline">' . csrf_fields('user') . '<input type="hidden" name="id" value="' . (int) $r['id'] . '"><input type="hidden" name="status" value="' . $next . '"><button class="btn light" type="submit">' . $next . '</button></form>';
            }
            echo '</td></tr>';
        }
        echo '</table>';
    } elseif ($tab === 'enq') {
        $rows = $db->query('SELECT * FROM dg_enquiries ORDER BY id DESC LIMIT 80')->fetchAll();
        echo '<h3>Enquiries</h3><table class="table"><tr><th>When</th><th>From</th><th>Intent</th><th>Message</th><th>Status</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . h($r['created_at']) . '</td><td>' . h($r['name']) . '<br><small>' . h($r['email']) . '</small></td><td>' . h($r['intent']) . ($r['target_id'] ? ' #' . (int) $r['target_id'] : '') . '</td><td>' . h($r['message']) . '</td><td>';
            echo '<form method="post" class="inline">' . csrf_fields('enq') . '<input type="hidden" name="id" value="' . (int) $r['id'] . '">';
            echo '<select name="status"><option' . ($r['status'] === 'new' ? ' selected' : '') . '>new</option><option' . ($r['status'] === 'open' ? ' selected' : '') . '>open</option><option' . ($r['status'] === 'closed' ? ' selected' : '') . '>closed</option></select> ';
            echo '<button class="btn light" type="submit">Set</button></form></td></tr>';
        }
        if (!$rows) {
            echo '<tr><td colspan="5">None yet.</td></tr>';
        }
        echo '</table>';
    } elseif ($tab === 'pages') {
        echo '<h3>Legal pages and desk contact</h3><form method="post">' . csrf_fields('pages');
        echo '<label>Public contact email</label><input class="input" name="contact_email" value="' . h(setting('contact_email')) . '"><br><br>';
        echo '<label>Public phone</label><input class="input" name="contact_phone" value="' . h(setting('contact_phone')) . '"><br>';
        echo '<label>About</label><textarea name="page_about" style="min-height:140px">' . h(setting('page_about')) . '</textarea><br>';
        echo '<label>Privacy</label><textarea name="page_privacy" style="min-height:140px">' . h(setting('page_privacy')) . '</textarea><br>';
        echo '<label>Terms</label><textarea name="page_terms" style="min-height:140px">' . h(setting('page_terms')) . '</textarea><br>';
        echo '<button class="btn" type="submit">Save pages</button></form>';
    } elseif ($tab === 'docs') {
        $rows = $db->query('SELECT f.*, b.legal_name FROM dg_files f JOIN dg_businesses b ON b.id=f.business_id ORDER BY f.id DESC LIMIT 80')->fetchAll();
        echo '<h3>Uploaded documents</h3><table class="table"><tr><th>Firm</th><th>Type</th><th>File</th><th>When</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . h($r['legal_name']) . '</td><td>' . h($r['code']) . '</td><td><a href="?p=file&id=' . (int) $r['id'] . '">' . h($r['orig']) . '</a></td><td>' . h($r['created_at']) . '</td></tr>';
        }
        if (!$rows) {
            echo '<tr><td colspan="4">None yet.</td></tr>';
        }
        echo '</table>';
    } elseif ($tab === 'orders') {
        $rows = $db->query('SELECT * FROM dg_orders ORDER BY id DESC LIMIT 80')->fetchAll();
        echo '<h3>Service / program / verify requests</h3><table class="table"><tr><th>#</th><th>Kind</th><th>Item</th><th>Amount</th><th>Status</th><th></th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . (int) $r['id'] . '</td><td>' . h($r['kind']) . '</td><td>' . h($r['item']) . '</td><td>' . h($r['amount']) . '</td><td>' . h($r['status']) . '</td><td>';
            echo '<form method="post" class="inline">' . csrf_fields('order_status') . '<input type="hidden" name="id" value="' . (int) $r['id'] . '">';
            echo '<select name="status"><option>new</option><option' . ($r['status'] === 'accepted' ? ' selected' : '') . '>accepted</option><option' . ($r['status'] === 'done' ? ' selected' : '') . '>done</option><option' . ($r['status'] === 'rejected' ? ' selected' : '') . '>rejected</option></select> ';
            echo '<button class="btn light" type="submit">Set</button></form></td></tr>';
        }
        if (!$rows) {
            echo '<tr><td colspan="6">None yet.</td></tr>';
        }
        echo '</table>';
    } elseif ($tab === 'mail') {
        $rows = $db->query('SELECT * FROM dg_mail ORDER BY id DESC LIMIT 50')->fetchAll();
        echo '<h3>Outbound mail log</h3><p class="muted">No SMTP on this host yet — every email the app would send is stored here. Reset and confirm links are in the body.</p><table class="table"><tr><th>When</th><th>To</th><th>Subject</th><th>Body</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . h($r['created_at']) . '</td><td>' . h($r['to_email']) . '</td><td>' . h($r['subject']) . '</td><td>' . nl2br(h($r['body'])) . '</td></tr>';
        }
        if (!$rows) {
            echo '<tr><td colspan="4">None yet.</td></tr>';
        }
        echo '</table>';
    } elseif ($tab === 'audit') {
        $rows = $db->query('SELECT * FROM dg_audit ORDER BY id DESC LIMIT 80')->fetchAll();
        echo '<h3>Audit</h3><table class="table"><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . h($r['created_at']) . '</td><td>' . h((string) $r['user_id']) . '</td><td>' . h($r['action']) . '</td><td>' . h($r['entity']) . ' #' . h((string) $r['entity_id']) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<div class="grid-3"><div class="feature"><h3>Businesses</h3><p>' . $bizN . ' records · ' . $pendN . ' pending verify</p></div>';
        echo '<div class="feature"><h3>Users</h3><p>' . $userN . ' accounts</p></div>';
        echo '<div class="feature"><h3>Open inbox</h3><p>' . $enqN . ' enquiries</p></div></div><br>';
        echo '<p>Use <b>Site copy</b>, <b>Requests</b> and <b>Mail log</b> to run the desk. Verify firms under <b>Businesses</b>.</p>';
        echo '<p>Staff login: <code>admin@doudyog.local</code> — change this password before production.</p>';
    }
    echo '</div></div></div></section>';
}
