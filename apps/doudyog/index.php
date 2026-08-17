<?php
declare(strict_types=1);
require __DIR__ . '/boot.php';

$codes = [
    'udyam' => 'Udyam / MSME number',
    'gstin' => 'GSTIN',
    'pan' => 'Business PAN',
    'bank' => 'Bank account for settlements',
    'address' => 'Registered address proof',
    'licence' => 'Shop / trade licence',
    'invoice' => 'Invoice / billing process',
    'contact' => 'Public phone and email',
];
$industries = ['Manufacturing', 'Retail', 'Services', 'Construction', 'Hospitality', 'Trading', 'Agriculture', 'IT', 'Technology'];
$packages = catalog_lines(setting('packages'));
$programs = catalog_lines(setting('programs'));

require __DIR__ . '/loop.php';
$db = db();
$err = '';
$p = (string) ($_GET['p'] ?? 'home');
if ($p === 'guide') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        guide_json_response($db, 'doudyog');
    }
    shell_start('Ask Do — DoUdyog');
    guide_render_page($db, 'doudyog');
    shell_end();
    exit;
}
if ($p === 'file') {
    serve_file($db);
}
if ($p === 'verify') {
    consume_verify_token($db);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $err = handle_auth($db);
    $act = (string) ($_POST['act'] ?? '');
    if ($act !== '' && !in_array($act, ['register', 'login', 'logout', 'forgot', 'reset', 'changepass'], true) && !csrf_ok()) {
        $err = 'Session expired. Try again.';
    } elseif ($act === 'save' && user()) {
        $st = $db->prepare('SELECT id FROM dg_businesses WHERE owner_id=?');
        $st->execute([user()['id']]);
        $bid = (int) $st->fetchColumn();
        if ($bid) {
            try {
                $db->prepare('UPDATE dg_businesses SET legal_name=?, industry=?, city=?, state=?, phone=?, gstin=?, udyam_no=?, pan=?, employees=?, year_started=?, website=?, about=?, updated_at=? WHERE id=?')
                    ->execute([
                        trim((string) $_POST['legal_name']),
                        (string) $_POST['industry'],
                        trim((string) $_POST['city']),
                        trim((string) ($_POST['state'] ?? '')) ?: null,
                        trim((string) ($_POST['phone'] ?? '')) ?: null,
                        strtoupper(trim((string) ($_POST['gstin'] ?? ''))) ?: null,
                        trim((string) ($_POST['udyam_no'] ?? '')) ?: null,
                        strtoupper(trim((string) ($_POST['pan'] ?? ''))) ?: null,
                        trim((string) ($_POST['employees'] ?? '')) ?: null,
                        ($_POST['year_started'] ?? '') !== '' ? (int) $_POST['year_started'] : null,
                        trim((string) ($_POST['website'] ?? '')) ?: null,
                        trim((string) ($_POST['about'] ?? '')),
                        date('c'),
                        $bid,
                    ]);
                audit($db, user()['id'], 'business', $bid, 'update');
                flash('Profile saved.');
            } catch (PDOException $e) {
                $err = str_contains($e->getMessage(), 'UNIQUE') ? 'That GSTIN is already on another record.' : 'Could not save the profile.';
            }
        }
        if ($err === '') {
            go('dash');
        }
    } elseif ($act === 'compliance' && user()) {
        $st = $db->prepare('SELECT id FROM dg_businesses WHERE owner_id=?');
        $st->execute([user()['id']]);
        $bid = (int) $st->fetchColumn();
        if ($bid) {
            $up = $db->prepare('UPDATE dg_compliance SET done=?, note=?, updated_at=? WHERE business_id=? AND code=?');
            foreach (array_keys($codes) as $c) {
                $up->execute([isset($_POST['done'][$c]) ? 1 : 0, trim((string) ($_POST['note'][$c] ?? '')) ?: null, date('c'), $bid, $c]);
            }
            $sc = $db->prepare('SELECT AVG(done)*100 FROM dg_compliance WHERE business_id=?');
            $sc->execute([$bid]);
            $db->prepare('UPDATE dg_businesses SET completeness=? WHERE id=?')->execute([(int) round((float) $sc->fetchColumn()), $bid]);
            audit($db, user()['id'], 'compliance', $bid, 'update');
            flash('Compliance ledger updated.');
        }
        go('compliance');
    }
    handle_loop($db, $codes, $err);
}

$me = user();
$biz = null;
$inbox = [];
if ($me) {
    $st = $db->prepare('SELECT * FROM dg_businesses WHERE owner_id=?');
    $st->execute([$me['id']]);
    $biz = $st->fetch() ?: null;
    if ($biz) {
        $st = $db->prepare('SELECT * FROM dg_enquiries WHERE product=? AND target_id=? ORDER BY id DESC LIMIT 20');
        $st->execute(['udyog', $biz['id']]);
        $inbox = $st->fetchAll();
    }
}

$needLogin = in_array($p, ['dash', 'compliance', 'docs', 'inbox', 'orders', 'notices', 'account'], true);
if ($needLogin && !$me) {
    go('login');
}
if ($needLogin && is_admin()) {
    go('admin');
}
if ($p === 'admin') {
    if (!is_admin()) {
        go('login');
    }
    require __DIR__ . '/admin.php';
    admin_boot($db);
    shell_start('Admin — ' . setting('brand', 'DoUdyog'));
    $ok = flash();
    if ($ok) {
        echo '<div class="container"><div class="notice" style="margin-top:16px">' . h($ok) . '</div></div>';
    }
    admin_render($db);
    shell_end();
    exit;
}

shell_start($p === 'home' ? 'DoUdyog — MSME operating centre' : 'DoUdyog');
$ok = flash();
if ($ok) {
    echo '<div class="container"><div class="notice" style="margin-top:16px">' . h($ok) . '</div></div>';
}

if ($p === 'join') {
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Join DoUdyog</h2><p>Creates your owner account, a pending business row, and eight compliance lines.</p></div></div><div class="card">';
    if ($err) {
        echo '<p class="err">' . h($err) . '</p>';
    }
    echo '<form method="post"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><input type="hidden" name="act" value="register">';
    echo '<div class="form-row"><input class="input" name="name" placeholder="Your name" required><input class="input" type="email" name="email" placeholder="Email" required></div><br>';
    echo '<div class="form-row"><input class="input" type="password" name="password" minlength="8" placeholder="Password (8+)" required><input class="input" name="phone" placeholder="Phone / WhatsApp"></div><br>';
    echo '<div class="form-row"><input class="input" name="legal_name" placeholder="Legal / trade name" required><input class="input" name="city" placeholder="City" required></div><br>';
    echo '<select class="input" name="industry">';
    foreach ($industries as $i) {
        echo '<option>' . h($i) . '</option>';
    }
    echo '</select><br><br><textarea name="about" placeholder="What you make or sell, and what you need first"></textarea><br><br>';
    echo '<button class="btn" type="submit">Submit business</button></form></div></div></section>';
} elseif ($p === 'login') {
    echo '<section class="section soft"><div class="container"><div class="card" style="max-width:28rem"><h2>Owner login</h2>';
    if ($err) {
        echo '<p class="err">' . h($err) . '</p>';
    }
    echo '<form method="post"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><input type="hidden" name="act" value="login">';
    echo '<input class="input" type="email" name="email" required placeholder="Email"><br><br>';
    echo '<input class="input" type="password" name="password" required placeholder="Password"><br><br>';
    echo '<button class="btn" type="submit">Log in</button></form><p><a href="?p=forgot">Forgot password</a></p></div></div></section>';
} elseif ($p === 'dash' && $biz) {
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Business dashboard</h2><p>' . h($biz['legal_name']) . ' · ' . h($biz['verify_status']) . ' · ' . (int) $biz['completeness'] . '% ready</p></div><a class="btn light" href="?p=compliance">Compliance</a></div>';
    echo '<div class="dashboard">' . dash_nav('dash') . '<div class="dash-panel">';
    echo '<div class="grid-3"><div class="feature"><h3>Profile</h3><p>' . (int) $biz['completeness'] . '% complete</p></div><div class="feature"><h3>Verification</h3><p>' . h($biz['verify_status']) . '</p></div><div class="feature"><h3>Enquiries</h3><p>' . count($inbox) . ' in inbox</p></div></div><br>';
    echo '<form method="post"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><input type="hidden" name="act" value="save">';
    echo '<div class="form-row"><input class="input" name="legal_name" value="' . h($biz['legal_name']) . '" required><select class="input" name="industry">';
    foreach ($industries as $i) {
        echo '<option ' . ($i === $biz['industry'] ? 'selected' : '') . '>' . h($i) . '</option>';
    }
    echo '</select></div><br><div class="form-row"><input class="input" name="city" value="' . h($biz['city']) . '" required><input class="input" name="gstin" maxlength="15" placeholder="GSTIN" value="' . h($biz['gstin']) . '"></div><br>';
    echo '<div class="form-row"><input class="input" name="udyam_no" placeholder="Udyam" value="' . h($biz['udyam_no']) . '"><input class="input" name="pan" maxlength="10" placeholder="PAN" value="' . h($biz['pan']) . '"></div><br>';
    echo '<div class="form-row"><input class="input" name="employees" placeholder="Employees" value="' . h($biz['employees']) . '"><input class="input" name="year_started" placeholder="Year started" value="' . h((string) $biz['year_started']) . '"></div><br>';
    echo '<input class="input" name="website" placeholder="Website" value="' . h($biz['website']) . '"><br><br>';
    echo '<div class="form-row"><input class="input" name="state" placeholder="State" value="' . h($biz['state'] ?? '') . '"><input class="input" name="phone" placeholder="Public phone" value="' . h($biz['phone'] ?? '') . '"></div><br>';
    echo '<textarea name="about">' . h($biz['about']) . '</textarea><br><br><button class="btn" type="submit">Save profile</button></form>';
    echo '<br><form method="post" enctype="multipart/form-data">' . csrf_fields('logo') . '<label>Logo</label><input type="file" name="logo" accept="image/*"> <button class="btn light" type="submit">Upload logo</button></form>';
    echo '<br><form method="post">' . csrf_fields('order') . '<input type="hidden" name="kind" value="verify"><input type="hidden" name="item" value="Verified listing"><button class="btn" type="submit">Request verification (' . h(setting('price_verified', '₹999')) . ')</button></form>';
    echo '<br><h3>Enquiries</h3><p><a href="?p=inbox">Open inbox</a></p><table class="table"><tr><th>When</th><th>From</th><th>Intent</th><th>Message</th></tr>';
    foreach ($inbox as $e) {
        echo '<tr><td>' . h($e['created_at']) . '</td><td>' . h($e['name']) . '<br><small>' . h($e['email']) . '</small></td><td>' . h($e['intent']) . '</td><td>' . h($e['message']) . '</td></tr>';
    }
    if (!$inbox) {
        echo '<tr><td colspan="4">None yet. Your public profile is in the directory.</td></tr>';
    }
    echo '</table></div></div></div></section>';
} elseif ($p === 'compliance' && $biz) {
    $st = $db->prepare('SELECT code, done, note FROM dg_compliance WHERE business_id=?');
    $st->execute([$biz['id']]);
    $rows = [];
    foreach ($st as $r) {
        $rows[$r['code']] = $r;
    }
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Compliance centre</h2><p>Readiness ' . (int) $biz['completeness'] . '%. Tick what you have, then upload proof under Documents.</p></div></div>';
    echo '<div class="dashboard">' . dash_nav('compliance') . '<div class="dash-panel">';
    echo '<form method="post"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><input type="hidden" name="act" value="compliance"><table class="table"><tr><th></th><th>Item</th><th>Note / number</th></tr>';
    foreach ($codes as $c => $lab) {
        $r = $rows[$c] ?? ['done' => 0, 'note' => ''];
        echo '<tr><td><input type="checkbox" name="done[' . h($c) . ']" ' . ($r['done'] ? 'checked' : '') . '></td><td>' . h($lab) . '</td><td><input class="input" name="note[' . h($c) . ']" value="' . h($r['note']) . '"></td></tr>';
    }
    echo '</table><br><button class="btn" type="submit">Save ledger</button></form><p><a href="?p=docs">Upload supporting documents</a></p></div></div></div></section>';
} elseif ($p === 'dir') {
    $q = trim((string) ($_GET['q'] ?? ''));
    $ind = trim((string) ($_GET['industry'] ?? ''));
    $sql = "SELECT id, legal_name, industry, city, completeness, verify_status FROM dg_businesses WHERE verify_status IN ('pending','verified')";
    $args = [];
    if ($q !== '') {
        $sql .= ' AND (legal_name LIKE ? OR city LIKE ?)';
        $args[] = "%$q%";
        $args[] = "%$q%";
    }
    if ($ind !== '') {
        $sql .= ' AND industry=?';
        $args[] = $ind;
    }
    $st = $db->prepare($sql . ' ORDER BY completeness DESC, id DESC LIMIT 50');
    $st->execute($args);
    $list = $st->fetchAll();
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Business directory</h2><p>Pending and verified firms. Search by name, city or industry.</p></div></div>';
    echo '<form class="form-row" method="get" style="max-width:720px;margin-bottom:24px"><input type="hidden" name="p" value="dir">';
    echo '<input class="input" name="q" value="' . h($q) . '" placeholder="Name or city"><select class="input" name="industry"><option value="">All industries</option>';
    foreach ($industries as $i) {
        echo '<option ' . ($i === $ind ? 'selected' : '') . '>' . h($i) . '</option>';
    }
    echo '</select><button class="btn" type="submit">Search</button></form><div class="list-grid">';
    foreach ($list as $r) {
        echo '<div class="biz-card"><div class="biz-head"><div class="avatar">' . h(strtoupper(substr($r['legal_name'], 0, 1))) . '</div><div><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['legal_name']) . '</a></h3><p>' . h($r['industry']) . '</p></div></div>';
        echo '<div class="meta"><span>' . h($r['city']) . '</span><span class="verified">' . h($r['verify_status']) . '</span><span>' . (int) $r['completeness'] . '%</span></div>';
        echo '<a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">View profile</a></div>';
    }
    if (!$list) {
        echo '<p>No matching businesses. <a href="?p=join">Register yours</a>.</p>';
    }
    echo '</div></div></section>';
} elseif ($p === 'view') {
    $st = $db->prepare('SELECT * FROM dg_businesses WHERE id=?');
    $st->execute([(int) ($_GET['id'] ?? 0)]);
    $v = $st->fetch();
    if (!$v) {
        echo '<section class="section"><div class="container"><p>Not found.</p></div></section>';
    } else {
        echo '<section class="section"><div class="container"><div class="biz-card"><div class="biz-head"><div class="avatar">' . h(strtoupper(substr($v['legal_name'], 0, 1))) . '</div><div><h2>' . h($v['legal_name']) . '</h2>';
        echo '<div class="meta"><span>' . h($v['industry']) . '</span><span>' . h($v['city']) . '</span><span class="verified">' . h($v['verify_status']) . '</span></div></div></div>';
        echo '<p>' . nl2br(h($v['about'])) . '</p><table class="table"><tr><th>GSTIN</th><td>' . h($v['gstin'] ?: '—') . '</td><th>Udyam</th><td>' . h($v['udyam_no'] ?: '—') . '</td></tr>';
        echo '<tr><th>PAN</th><td>' . h($v['pan'] ?: '—') . '</td><th>Since</th><td>' . h((string) ($v['year_started'] ?: '—')) . '</td></tr></table></div><br><div class="card"><h3>Enquire</h3>';
        if ($err) {
            echo '<p class="err">' . h($err) . '</p>';
        }
        if (!empty($_GET['sent'])) {
            echo '<div class="notice">Stored in the owner inbox.</div>';
        }
        echo '<form method="post"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><input type="hidden" name="act" value="enquire"><input type="hidden" name="target_id" value="' . (int) $v['id'] . '">';
        echo '<div class="form-row"><input class="input" name="name" placeholder="Name" required><input class="input" type="email" name="email" required placeholder="Email"></div><br>';
        echo '<div class="form-row"><input class="input" name="phone" placeholder="Phone"><select class="input" name="intent"><option>intro</option><option>supply</option><option>compliance</option><option>partner</option></select></div><br>';
        echo '<textarea name="message" required placeholder="How can they help you?"></textarea><br><br><button class="btn" type="submit">Send</button></form></div></div></section>';
    }
} elseif ($p === 'services') {
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Business services</h2><p>' . h(setting('services_intro', 'Packaged help.')) . '</p></div></div><div class="grid-3">';
    foreach ($packages as $s) {
        echo '<div class="price-card"><h3>' . h($s[0]) . '</h3><div class="price">' . h($s[1]) . '</div><p>' . h($s[2]) . '</p>';
        echo '<form method="post">' . csrf_fields('order') . '<input type="hidden" name="kind" value="service"><input type="hidden" name="item" value="' . h($s[0]) . '"><input type="hidden" name="amount" value="' . h($s[1]) . '"><button class="btn light" type="submit">Request</button></form></div>';
    }
    echo '</div></div></section>';
} elseif ($p === 'growth') {
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Growth programs</h2><p>' . h(setting('growth_intro', 'Guided tracks.')) . '</p></div></div><div class="grid-3">';
    foreach ($programs as $g) {
        echo '<div class="feature"><h3>' . h($g[0]) . '</h3><p>' . h($g[1]) . ' · ' . h($g[2]) . '</p>';
        echo '<form method="post">' . csrf_fields('order') . '<input type="hidden" name="kind" value="program"><input type="hidden" name="item" value="' . h($g[0]) . '"><input type="hidden" name="amount" value="' . h(setting('price_growth', '₹4,999')) . '"><button class="btn light" type="submit">Join track</button></form></div>';
    }
    echo '</div></div></section>';
} elseif ($p === 'pricing') {
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Pricing</h2><p>Start free. Pay when you want verification and programs.</p></div></div><div class="grid-3">';
    echo '<div class="price-card"><h3>Starter</h3><div class="price">' . h(setting('price_starter', 'Free')) . '</div><ul class="checklist"><li>Business record</li><li>Compliance ledger</li><li>Directory listing</li></ul><a class="btn" href="?p=join">Join</a></div>';
    echo '<div class="price-card featured"><h3>Verified</h3><div class="price">' . h(setting('price_verified', '₹999')) . '</div><ul class="checklist"><li>Everything in Starter</li><li>Manual verification</li><li>Priority directory</li></ul><form method="post">' . csrf_fields('order') . '<input type="hidden" name="kind" value="verify"><input type="hidden" name="item" value="Verified listing"><button class="btn" type="submit">Request verify</button></form></div>';
    echo '<div class="price-card"><h3>Growth</h3><div class="price">' . h(setting('price_growth', '₹4,999')) . '</div><ul class="checklist"><li>Everything in Verified</li><li>Advisor program</li><li>Do Galaxy onboarding</li></ul><form method="post">' . csrf_fields('order') . '<input type="hidden" name="kind" value="program"><input type="hidden" name="item" value="Growth programme"><input type="hidden" name="amount" value="' . h(setting('price_growth', '₹4,999')) . '"><button class="btn" type="submit">Talk to us</button></form></div>';
    echo '</div></div></section>';
} elseif ($p === 'readiness') {
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Do Galaxy readiness</h2><p>MSME checklist before moving into retail, trade, hiring and services.</p></div><a class="btn light" href="?p=services">Get help</a></div><div class="grid-3">';
    foreach ([['Identity', 'Business profile, GST/Udyam, address and public contact are ready.'], ['Retail', 'DoBajar shopfront can list local products, hours and delivery area.'], ['Trade', 'DoVyapaar needs MOQ, GST invoice terms, lead days and rate card.'], ['Hiring', 'DoRojgar roles need salary, locality, experience and shortlist owner.'], ['Documents', 'Upload GST, ID, address proof and catalog/rate card.'], ['Compliance', 'Keep the eight-line ledger above 70% before paid promotion.']] as $c) {
        echo '<div class="feature"><h3>' . h($c[0]) . '</h3><p>' . h($c[1]) . '</p></div>';
    }
    echo '</div></div></section>';
} elseif ($p === 'contact') {
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Contact</h2><p>A human reads this. Stored in dg_enquiries.</p></div></div><div class="card" style="max-width:36rem">';
    if ($err) {
        echo '<p class="err">' . h($err) . '</p>';
    }
    if (!empty($_GET['sent'])) {
        echo '<div class="notice">Received.</div>';
    }
    echo '<form method="post"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><input type="hidden" name="act" value="enquire">';
    echo '<input type="hidden" name="intent" value="' . h((string) ($_GET['intent'] ?? 'general')) . '">';
    echo '<div class="form-row"><input class="input" name="name" placeholder="Name" required><input class="input" type="email" name="email" required placeholder="Email"></div><br>';
    echo '<input class="input" name="phone" placeholder="Phone"><br><br><textarea name="message" required placeholder="How can we help?"></textarea><br><br><button class="btn" type="submit">Send</button></form></div></div></section>';
} elseif ($p === 'forgot') {
    echo '<section class="section soft"><div class="container"><div class="card" style="max-width:28rem"><h2>Forgot password</h2><p>A reset link is written to the mail log (Admin → Mail).</p>';
    if ($err) {
        echo '<p class="err">' . h($err) . '</p>';
    }
    echo '<form method="post">' . csrf_fields('forgot') . '<input class="input" type="email" name="email" required placeholder="Email"><br><br><button class="btn" type="submit">Send reset link</button></form></div></div></section>';
} elseif ($p === 'reset') {
    echo '<section class="section soft"><div class="container"><div class="card" style="max-width:28rem"><h2>Set a new password</h2>';
    if ($err) {
        echo '<p class="err">' . h($err) . '</p>';
    }
    echo '<form method="post">' . csrf_fields('reset') . '<input type="hidden" name="token" value="' . h((string) ($_GET['token'] ?? '')) . '">';
    echo '<input class="input" type="password" name="password" minlength="8" required placeholder="New password (8+)"><br><br><button class="btn" type="submit">Update password</button></form></div></div></section>';
} elseif ($p === 'docs' && $biz) {
    $st = $db->prepare('SELECT * FROM dg_files WHERE business_id=? ORDER BY id DESC');
    $st->execute([$biz['id']]);
    $files = $st->fetchAll();
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Documents</h2><p>Proof against each compliance line. Staff open these when they verify you.</p></div></div>';
    echo '<div class="dashboard">' . dash_nav('docs') . '<div class="dash-panel">';
    echo '<form method="post" enctype="multipart/form-data">' . csrf_fields('upload') . '<div class="form-row"><select class="input" name="code">';
    foreach ($codes as $c => $lab) {
        echo '<option value="' . h($c) . '">' . h($lab) . '</option>';
    }
    echo '</select><input type="file" name="doc" required></div><br><button class="btn" type="submit">Upload</button></form><br><table class="table"><tr><th>Type</th><th>File</th><th>When</th></tr>';
    foreach ($files as $f) {
        echo '<tr><td>' . h($codes[$f['code']] ?? $f['code']) . '</td><td><a href="?p=file&id=' . (int) $f['id'] . '">' . h($f['orig']) . '</a></td><td>' . h($f['created_at']) . '</td></tr>';
    }
    if (!$files) {
        echo '<tr><td colspan="3">None yet.</td></tr>';
    }
    echo '</table></div></div></div></section>';
} elseif ($p === 'inbox' && $biz) {
    $eid = (int) ($_GET['id'] ?? 0);
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Inbox</h2><p>Replies email the sender and stay on this thread.</p></div></div>';
    echo '<div class="dashboard">' . dash_nav('inbox') . '<div class="dash-panel">';
    if ($eid) {
        $st = $db->prepare('SELECT * FROM dg_enquiries WHERE id=? AND target_id=?');
        $st->execute([$eid, $biz['id']]);
        $e = $st->fetch();
        if ($e) {
            $rs = $db->prepare('SELECT * FROM dg_replies WHERE enquiry_id=? ORDER BY id');
            $rs->execute([$eid]);
            echo '<h3>' . h($e['name']) . ' · ' . h($e['intent']) . '</h3><p>' . nl2br(h($e['message'])) . '</p>';
            foreach ($rs as $r) {
                echo '<div class="card" style="margin:12px 0"><b>' . h($r['author']) . '</b><p>' . nl2br(h($r['body'])) . '</p><small>' . h($r['created_at']) . '</small></div>';
            }
            echo '<form method="post">' . csrf_fields('reply') . '<input type="hidden" name="enquiry_id" value="' . $eid . '"><textarea name="body" required placeholder="Reply"></textarea><br><br><button class="btn" type="submit">Send reply</button></form>';
        }
    } else {
        echo '<table class="table"><tr><th>When</th><th>From</th><th>Intent</th><th></th></tr>';
        foreach ($inbox as $e) {
            echo '<tr><td>' . h($e['created_at']) . '</td><td>' . h($e['name']) . '</td><td>' . h($e['intent']) . '</td><td><a href="?p=inbox&id=' . (int) $e['id'] . '">Open</a></td></tr>';
        }
        if (!$inbox) {
            echo '<tr><td colspan="4">None yet.</td></tr>';
        }
        echo '</table>';
    }
    echo '</div></div></div></section>';
} elseif ($p === 'orders' && $me) {
    $st = $db->prepare('SELECT * FROM dg_orders WHERE user_id=? ORDER BY id DESC');
    $st->execute([$me['id']]);
    $rows = $st->fetchAll();
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Requests</h2><p>Services, programs and verification. Staff change the status.</p></div></div>';
    echo '<div class="dashboard">' . dash_nav('orders') . '<div class="dash-panel"><table class="table"><tr><th>#</th><th>Kind</th><th>Item</th><th>Amount</th><th>Status</th></tr>';
    foreach ($rows as $r) {
        echo '<tr><td>' . (int) $r['id'] . '</td><td>' . h($r['kind']) . '</td><td>' . h($r['item']) . '</td><td>' . h($r['amount']) . '</td><td>' . h($r['status']) . '</td></tr>';
    }
    if (!$rows) {
        echo '<tr><td colspan="5">None yet. Request a service or verification.</td></tr>';
    }
    echo '</table></div></div></div></section>';
} elseif ($p === 'notices' && $me) {
    $st = $db->prepare('SELECT * FROM dg_notices WHERE user_id=? ORDER BY id DESC LIMIT 40');
    $st->execute([$me['id']]);
    $rows = $st->fetchAll();
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Notifications</h2></div><form method="post">' . csrf_fields('seen') . '<button class="btn light" type="submit">Mark all read</button></form></div>';
    echo '<div class="dashboard">' . dash_nav('notices') . '<div class="dash-panel">';
    foreach ($rows as $r) {
        echo '<div class="card" style="margin-bottom:12px">' . ($r['seen'] ? '' : '<b>New · </b>') . '<a href="' . h($r['link'] ?: '?p=dash') . '">' . h($r['title']) . '</a><p>' . h($r['body']) . '</p></div>';
    }
    if (!$rows) {
        echo '<p>No alerts yet.</p>';
    }
    echo '</div></div></div></section>';
} elseif ($p === 'account' && $me) {
    $st = $db->prepare('SELECT email, email_ok FROM dg_users WHERE id=?');
    $st->execute([$me['id']]);
    $u = $st->fetch();
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Account</h2><p>' . h($u['email']) . ' · email ' . ((int) $u['email_ok'] ? 'confirmed' : 'not confirmed') . '</p></div></div>';
    echo '<div class="dashboard">' . dash_nav('account') . '<div class="dash-panel">';
    if ($err) {
        echo '<p class="err">' . h($err) . '</p>';
    }
    echo '<form method="post">' . csrf_fields('changepass') . '<input class="input" type="password" name="old" required placeholder="Current password"><br><br>';
    echo '<input class="input" type="password" name="password" minlength="8" required placeholder="New password (8+)"><br><br><button class="btn" type="submit">Change password</button></form></div></div></div></section>';
} elseif ($p === 'thread') {
    $st = $db->prepare('SELECT * FROM dg_enquiries WHERE id=?');
    $st->execute([(int) ($_GET['id'] ?? 0)]);
    $e = $st->fetch();
    $ok = $e && hash_equals(thread_key($e), (string) ($_GET['k'] ?? ''));
    echo '<section class="section"><div class="container"><div class="card">';
    if (!$ok) {
        echo '<p>Thread not found.</p>';
    } else {
        echo '<h2>Thread with ' . h($e['name']) . '</h2><p>' . nl2br(h($e['message'])) . '</p>';
        $rs = $db->prepare('SELECT * FROM dg_replies WHERE enquiry_id=? ORDER BY id');
        $rs->execute([$e['id']]);
        foreach ($rs as $r) {
            echo '<div class="card" style="margin:12px 0"><b>' . h($r['author']) . '</b><p>' . nl2br(h($r['body'])) . '</p></div>';
        }
        echo '<form method="post">' . csrf_fields('reply') . '<input type="hidden" name="enquiry_id" value="' . (int) $e['id'] . '"><input type="hidden" name="k" value="' . h((string) $_GET['k']) . '">';
        echo '<textarea name="body" required placeholder="Add a message"></textarea><br><br><button class="btn" type="submit">Reply</button></form>';
    }
    echo '</div></div></section>';
} elseif (in_array($p, ['about', 'privacy', 'terms'], true)) {
    $titles = ['about' => 'About', 'privacy' => 'Privacy', 'terms' => 'Terms'];
    echo '<section class="section"><div class="container"><div class="card"><h2>' . h($titles[$p]) . '</h2><p>' . nl2br(h(setting('page_' . $p))) . '</p></div></div></section>';
} else {
    $feat = $db->query("SELECT id, legal_name, industry, city, completeness, verify_status FROM dg_businesses WHERE verify_status IN ('pending','verified') ORDER BY featured DESC, completeness DESC, id DESC LIMIT 4")->fetchAll();
    echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">' . h(setting('eyebrow')) . '</span>';
    echo '<h1>' . h(setting('hero_h1')) . '</h1>';
    echo '<p>' . h(setting('hero_p')) . '</p>';
    echo '<div class="hero-actions"><a class="btn" href="?p=join">Register business</a><a class="btn light" href="?p=dir">Explore businesses</a></div>';
    echo '<div class="stats"><div class="stat"><b>Identity</b><span>Legal name, GST, Udyam</span></div><div class="stat"><b>Ledger</b><span>Eight compliance lines</span></div><div class="stat"><b>Directory</b><span>Searchable records</span></div><div class="stat"><b>Galaxy</b><span>Jobs · trade · market</span></div></div></div>';
    echo '<div class="search-panel"><h3>Find business support</h3><form action="" method="get"><input type="hidden" name="p" value="dir"><div class="form-row"><input class="input" name="q" placeholder="Name or city"><select class="input" name="industry"><option value="">Industry</option>';
    foreach ($industries as $i) {
        echo '<option>' . h($i) . '</option>';
    }
    echo '</select></div><br><button class="btn" type="submit">Search directory</button></form><hr><p><b>Popular:</b> MSME registration, GST help, vendor discovery, storefront, hiring kit.</p></div></div></section>';
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>One business command centre</h2><p>Identity, compliance, operations, people, trade and growth.</p></div></div><div class="grid-3">';
    foreach ([['🏢', 'Business identity', 'A verified digital profile for your shop, factory or firm.'], ['📋', 'Compliance centre', 'Track Udyam, GST, invoices, licences and readiness.'], ['📈', 'Growth programs', 'Guided tracks for digital presence, trade and sales.'], ['🤝', 'Partner network', 'Suppliers on DoVyapaar, staff on DoRojgar, buyers on DoBajar.'], ['🧰', 'Business services', 'Advisors, setup packages and documentation help.'], ['🌌', 'Do Galaxy ready', 'One identity across MyDoApp connected platforms.']] as $f) {
        echo '<div class="feature"><div class="icon">' . $f[0] . '</div><h3>' . h($f[1]) . '</h3><p>' . h($f[2]) . '</p></div>';
    }
    echo '</div></div></section>';
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Featured businesses</h2><p>Live rows from the directory.</p></div><a class="btn light" href="?p=dir">View directory</a></div><div class="list-grid">';
    foreach ($feat as $r) {
        echo '<div class="biz-card"><div class="biz-head"><div class="avatar">' . h(strtoupper(substr($r['legal_name'], 0, 1))) . '</div><div><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['legal_name']) . '</a></h3><p>' . h($r['industry']) . ' · ' . h($r['city']) . '</p></div></div><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">View profile</a></div>';
    }
    if (!$feat) {
        echo '<p>No businesses yet. <a href="?p=join">Be the first</a>.</p>';
    }
    echo '</div></div></section>';
}
shell_end();
