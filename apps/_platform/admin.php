<?php
declare(strict_types=1);

function admin_boot(PDO $db): void
{
    $P = product();
    $act = (string) ($_POST['act'] ?? '');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $act === '' || $act === 'logout' || !csrf_ok()) {
        return;
    }
    if ($act === 'site') {
        foreach (['brand', 'topbar', 'eyebrow', 'hero_h1', 'hero_p', 'services_intro', 'footer_blurb'] as $k) {
            setting_set($k, trim((string) ($_POST[$k] ?? '')));
        }
        flash('Site copy saved.');
        go('admin&tab=site');
    }
    if ($act === 'catalog') {
        setting_set('packages', trim((string) ($_POST['packages'] ?? '')));
        setting_set('price_starter', trim((string) ($_POST['price_starter'] ?? 'Free')));
        setting_set('price_verified', trim((string) ($_POST['price_verified'] ?? '₹999')));
        setting_set('price_growth', trim((string) ($_POST['price_growth'] ?? '₹2,999')));
        flash('Catalogue saved.');
        go('admin&tab=catalog');
    }
    if ($act === 'pages') {
        foreach (['page_about', 'page_privacy', 'page_terms', 'contact_email', 'contact_phone'] as $k) {
            setting_set($k, trim((string) ($_POST[$k] ?? '')));
        }
        flash('Pages saved.');
        go('admin&tab=pages');
    }
    if ($act === 'verify' && !empty($P['listing_table'])) {
        $id = (int) ($_POST['id'] ?? 0);
        $st = (string) ($_POST['status'] ?? 'pending');
        if ($id) {
            $db->prepare('UPDATE ' . $P['listing_table'] . ' SET ' . $P['status_col'] . '=? WHERE id=?')->execute([$st, $id]);
            flash($P['listing_label'] . ' #' . $id . ' marked ' . $st . '.');
        }
        go('admin&tab=list');
    }
    if ($act === 'feature' && !empty($P['listing_table'])) {
        $id = (int) ($_POST['id'] ?? 0);
        $db->prepare('UPDATE ' . $P['listing_table'] . ' SET featured=? WHERE id=?')->execute([(int) ($_POST['featured'] ?? 0) ? 1 : 0, $id]);
        flash('Featured updated.');
        go('admin&tab=list');
    }
    if ($act === 'user') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id && $id !== (int) user()['id']) {
            $db->prepare('UPDATE dg_users SET status=? WHERE id=?')->execute([(string) ($_POST['status'] ?? 'active'), $id]);
            flash('User updated.');
        }
        go('admin&tab=users');
    }
    if ($act === 'order_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $st = (string) ($_POST['status'] ?? 'new');
        $db->prepare('UPDATE dg_orders SET status=? WHERE id=?')->execute([$st, $id]);
        $row = $db->prepare('SELECT user_id, kind, item, listing_id FROM dg_orders WHERE id=?');
        $row->execute([$id]);
        $o = $row->fetch();
        if ($o && $o['user_id']) {
            notify($db, (int) $o['user_id'], 'Request ' . $st, (string) $o['item'], '?p=orders');
        }
        if ($o && $o['kind'] === 'verify' && $st === 'accepted' && !empty($P['listing_table']) && $o['listing_id']) {
            $db->prepare('UPDATE ' . $P['listing_table'] . ' SET ' . $P['status_col'] . '=? WHERE id=?')->execute(['verified', $o['listing_id']]);
        }
        flash('Request #' . $id . ' marked ' . $st . '.');
        go('admin&tab=orders');
    }
}

function admin_render(PDO $db): void
{
    $P = product();
    $tab = (string) ($_GET['tab'] ?? 'home');
    $hub = ($P['mode'] ?? '') === 'hub';
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Admin</h2><p>Customise the public site and run the desk.</p></div></div><div class="dashboard"><aside class="dash-nav">';
    $tabs = ['home' => 'Overview', 'site' => 'Site copy', 'catalog' => 'Services & prices', 'pages' => 'Legal', 'list' => $P['dir_label'] ?? 'Listings', 'orders' => 'Requests', 'users' => 'Users', 'mail' => 'Mail log', 'audit' => 'Audit'];
    if ($hub) {
        unset($tabs['list']);
    }
    foreach ($tabs as $k => $lab) {
        echo '<a href="?p=admin&tab=' . $k . '"' . ($tab === $k ? ' class="on"' : '') . '>' . h($lab) . '</a>';
    }
    echo '</aside><div class="dash-panel">';
    if ($tab === 'site') {
        echo '<form method="post">' . csrf_fields('site');
        foreach ([['brand', 'Brand'], ['topbar', 'Top bar'], ['eyebrow', 'Eyebrow'], ['hero_h1', 'Headline'], ['hero_p', 'Hero'], ['services_intro', 'Services intro'], ['footer_blurb', 'Footer']] as [$k, $lab]) {
            echo '<label>' . h($lab) . '</label>';
            echo in_array($k, ['hero_p', 'footer_blurb'], true) ? '<textarea name="' . $k . '">' . h(setting($k)) . '</textarea><br>' : '<input class="input" name="' . $k . '" value="' . h(setting($k)) . '"><br><br>';
        }
        echo '<button class="btn" type="submit">Save copy</button></form>';
    } elseif ($tab === 'catalog') {
        echo '<form method="post">' . csrf_fields('catalog') . '<label>Packages (Name | price | blurb)</label><textarea name="packages" style="min-height:160px">' . h(setting('packages')) . '</textarea><br>';
        echo '<div class="form-row"><input class="input" name="price_starter" value="' . h(setting('price_starter')) . '"><input class="input" name="price_verified" value="' . h(setting('price_verified')) . '"><input class="input" name="price_growth" value="' . h(setting('price_growth')) . '"></div><br><button class="btn" type="submit">Save</button></form>';
    } elseif ($tab === 'pages') {
        echo '<form method="post">' . csrf_fields('pages');
        echo '<input class="input" name="contact_email" value="' . h(setting('contact_email')) . '"><br><br><input class="input" name="contact_phone" value="' . h(setting('contact_phone')) . '"><br>';
        echo '<textarea name="page_about">' . h(setting('page_about')) . '</textarea><br><textarea name="page_privacy">' . h(setting('page_privacy')) . '</textarea><br><textarea name="page_terms">' . h(setting('page_terms')) . '</textarea><br><button class="btn" type="submit">Save</button></form>';
    } elseif ($tab === 'list' && !$hub) {
        $rows = $db->query('SELECT * FROM ' . $P['listing_table'] . ' ORDER BY id DESC LIMIT 80')->fetchAll();
        echo '<table class="table"><tr><th>' . h($P['listing_label']) . '</th><th>Status</th><th></th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r[$P['title_col']]) . '</a></td><td>' . h($r[$P['status_col']]) . '</td><td>';
            echo '<form method="post" class="inline">' . csrf_fields('verify') . '<input type="hidden" name="id" value="' . (int) $r['id'] . '"><select name="status"><option>pending</option><option' . ($r[$P['status_col']] === 'verified' ? ' selected' : '') . '>verified</option><option>rejected</option><option>live</option></select><button class="btn light" type="submit">Set</button></form> ';
            echo '<form method="post" class="inline">' . csrf_fields('feature') . '<input type="hidden" name="id" value="' . (int) $r['id'] . '"><input type="hidden" name="featured" value="' . ((int) ($r['featured'] ?? 0) ? '0' : '1') . '"><button class="btn light" type="submit">' . ((int) ($r['featured'] ?? 0) ? 'Unfeature' : 'Feature') . '</button></form></td></tr>';
        }
        echo '</table>';
    } elseif ($tab === 'orders') {
        $rows = $db->query('SELECT * FROM dg_orders ORDER BY id DESC LIMIT 80')->fetchAll();
        echo '<table class="table"><tr><th>#</th><th>Item</th><th>Status</th><th></th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . (int) $r['id'] . '</td><td>' . h($r['item']) . '</td><td>' . h($r['status']) . '</td><td><form method="post" class="inline">' . csrf_fields('order_status') . '<input type="hidden" name="id" value="' . (int) $r['id'] . '"><select name="status"><option>new</option><option>accepted</option><option>done</option><option>rejected</option></select><button class="btn light" type="submit">Set</button></form></td></tr>';
        }
        echo '</table>';
    } elseif ($tab === 'users') {
        $rows = $db->query('SELECT id,name,email,role,status FROM dg_users ORDER BY id DESC LIMIT 80')->fetchAll();
        echo '<table class="table"><tr><th>Name</th><th>Email</th><th>Role</th><th></th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . h($r['name']) . '</td><td>' . h($r['email']) . '</td><td>' . h($r['role']) . ' / ' . h($r['status']) . '</td><td>';
            if ($r['role'] !== 'admin') {
                echo '<form method="post" class="inline">' . csrf_fields('user') . '<input type="hidden" name="id" value="' . (int) $r['id'] . '"><input type="hidden" name="status" value="' . ($r['status'] === 'active' ? 'suspended' : 'active') . '"><button class="btn light" type="submit">' . ($r['status'] === 'active' ? 'suspend' : 'activate') . '</button></form>';
            }
            echo '</td></tr>';
        }
        echo '</table>';
    } elseif ($tab === 'mail') {
        $rows = $db->query('SELECT * FROM dg_mail ORDER BY id DESC LIMIT 40')->fetchAll();
        echo '<table class="table"><tr><th>To</th><th>Subject</th><th>Body</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . h($r['to_email']) . '</td><td>' . h($r['subject']) . '</td><td>' . nl2br(h($r['body'])) . '</td></tr>';
        }
        echo '</table>';
    } elseif ($tab === 'audit') {
        $rows = $db->query('SELECT * FROM dg_audit ORDER BY id DESC LIMIT 40')->fetchAll();
        echo '<table class="table"><tr><th>When</th><th>Action</th><th>Entity</th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . h($r['created_at']) . '</td><td>' . h($r['action']) . '</td><td>' . h($r['entity']) . '</td></tr>';
        }
        echo '</table>';
    } else {
        $users = (int) $db->query('SELECT COUNT(*) FROM dg_users')->fetchColumn();
        $mail = (int) $db->query('SELECT COUNT(*) FROM dg_mail')->fetchColumn();
        echo '<div class="grid-3"><div class="feature"><h3>Users</h3><p>' . $users . '</p></div><div class="feature"><h3>Mail</h3><p>' . $mail . '</p></div><div class="feature"><h3>Desk</h3><p>Copy · verify · inbox</p></div></div>';
        echo '<p>Staff: <code>admin@' . h($P['slug']) . '.local</code> / AdminPass9</p>';
    }
    echo '</div></div></div></section>';
}
