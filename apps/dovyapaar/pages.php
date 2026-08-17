<?php
declare(strict_types=1);

function product_migrate(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_requirements (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, name TEXT NOT NULL, email TEXT NOT NULL, phone TEXT, item TEXT NOT NULL, qty TEXT, city TEXT, message TEXT, status TEXT NOT NULL DEFAULT \'open\', created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_quotes (id INTEGER PRIMARY KEY AUTOINCREMENT, requirement_id INTEGER NOT NULL, supplier_id INTEGER NOT NULL, amount TEXT, note TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_trade_products (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, industry TEXT, city TEXT, price TEXT, moq TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_trade_leads (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, lead_type TEXT, industry TEXT, city TEXT, qty TEXT, body TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    if (function_exists('ensure_columns')) {
        ensure_columns($pdo, 'dg_requirements', ['entry_type', 'lead_type', 'industry', 'budget']);
    }
}

function product_seed(PDO $db): void
{
    if ((int) $db->query('SELECT COUNT(*) FROM dg_requirements')->fetchColumn() === 0) {
        $db->prepare('INSERT INTO dg_requirements (name, email, phone, item, qty, city, message) VALUES (?,?,?,?,?,?,?)')
            ->execute(['Guest Buyer', 'buyer@dovyapaar.local', '9000000001', 'Corrugated boxes 12x10x8', '2000', 'Jaipur', 'Need weekly supply, GST invoice.']);
    }
    if ((int) $db->query('SELECT COUNT(*) FROM dg_trade_products')->fetchColumn() === 0) {
        $ins = $db->prepare('INSERT INTO dg_trade_products (title, industry, city, price, moq) VALUES (?,?,?,?,?)');
        foreach ([
            ['TMT Steel Bars 500D', 'Construction Materials', 'Pune', '₹54/kg', 'MOQ 5 MT'],
            ['Cotton Uniform Fabric', 'Textiles', 'Ahmedabad', '₹115/meter', 'MOQ 500 meters'],
            ['Wholesale Grocery Combo', 'FMCG', 'Delhi', '₹18,500/lot', 'MOQ 20 lots'],
        ] as $p) {
            $ins->execute($p);
        }
    }
    if ((int) $db->query('SELECT COUNT(*) FROM dg_trade_leads')->fetchColumn() === 0) {
        $ins = $db->prepare('INSERT INTO dg_trade_leads (title, lead_type, industry, city, qty, body) VALUES (?,?,?,?,?,?)');
        foreach ([
            ['Need 100 MT TMT Steel in Pune', 'Buy Lead', 'Construction Materials', 'Pune', 'Bulk', 'Verified suppliers can quote.'],
            ['Distributor required for FMCG in Rajasthan', 'Sell Lead', 'FMCG', 'Jaipur', 'Bulk', 'Verified suppliers can quote.'],
            ['Bulk hotel linen supplier needed', 'Buy Lead', 'Hospitality Supplies', 'Mumbai', 'Bulk', 'Verified suppliers can quote.'],
        ] as $l) {
            $ins->execute($l);
        }
    }
}

function product_handle_post(PDO $db, string $act, string &$err): bool
{
    if ($act === 'post_requirement') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $item = trim((string) ($_POST['item'] ?? ''));
        if (!$name || !$email || $item === '') {
            $err = 'Name, email and item are required.';
            return true;
        }
        $db->prepare('INSERT INTO dg_requirements (user_id, name, email, phone, item, qty, city, message, entry_type, lead_type, industry, budget) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([user()['id'] ?? null, $name, (string) $email, trim((string) ($_POST['phone'] ?? '')), $item, trim((string) ($_POST['qty'] ?? '')), trim((string) ($_POST['city'] ?? '')), trim((string) ($_POST['message'] ?? '')), (string) ($_POST['entry_type'] ?? 'rfq'), (string) ($_POST['lead_type'] ?? 'Buy Lead'), trim((string) ($_POST['industry'] ?? '')), trim((string) ($_POST['budget'] ?? ''))]);
        $id = (int) $db->lastInsertId();
        queue_mail($db, setting('contact_email'), 'New requirement #' . $id, $item);
        flash('Requirement posted. Suppliers can quote from the RFQ board.');
        go('board&id=' . $id);
    }
    if ($act === 'quote_req' && user()) {
        $rid = (int) ($_POST['requirement_id'] ?? 0);
        $note = trim((string) ($_POST['note'] ?? ''));
        if (!$rid || $note === '') {
            $err = 'Quote note is required.';
            return true;
        }
        $st = $db->prepare('SELECT id FROM dg_suppliers WHERE owner_id=?');
        $st->execute([user()['id']]);
        $sid = (int) $st->fetchColumn();
        if (!$sid) {
            $err = 'List as a supplier first.';
            return true;
        }
        $db->prepare('INSERT INTO dg_quotes (requirement_id, supplier_id, amount, note) VALUES (?,?,?,?)')
            ->execute([$rid, $sid, trim((string) ($_POST['amount'] ?? '')), $note]);
        flash('Quote sent to the buyer desk.');
        go('board&id=' . $rid);
    }
    return false;
}

function product_render_page(PDO $db, string $page, array $P, ?array $me): bool
{
    if ($page === 'requirement') {
        echo '<section class="section soft"><div class="container"><div class="card" style="max-width:40rem"><h2>Post a requirement</h2><p>Broadcast an RFQ to the supplier desk — same as live DoVyapaar post-requirement.</p>';
        echo '<form method="post">' . csrf_fields('post_requirement');
        echo '<div class="form-row"><select class="input" name="entry_type"><option value="rfq">RFQ</option><option value="lead">Trade Lead</option></select><select class="input" name="lead_type"><option>Buy Lead</option><option>Sell Lead</option><option>Distribution Required</option><option>Supplier Required</option></select></div><br>';
        echo '<div class="form-row"><input class="input" name="name" required placeholder="Buyer name" value="' . h($me['name'] ?? '') . '"><input class="input" type="email" name="email" required placeholder="Email" value="' . h($me['email'] ?? '') . '"></div><br>';
        echo '<div class="form-row"><input class="input" name="phone" placeholder="Phone"><input class="input" name="city" placeholder="City (Mumbai, Delhi, Pune…)"></div><br>';
        echo '<div class="form-row"><input class="input" name="industry" placeholder="Industry (Steel, FMCG…)"><input class="input" name="budget" placeholder="Budget"></div><br>';
        echo '<input class="input" name="item" required placeholder="Requirement title / item"><br><br>';
        echo '<input class="input" name="qty" placeholder="Quantity / MOQ"><br><br>';
        echo '<textarea name="message" placeholder="Specs, GST, delivery window"></textarea><br><br>';
        echo '<button class="btn" type="submit">Submit requirement</button></form></div></div></section>';
        return true;
    }
    if ($page === 'board') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id) {
            $st = $db->prepare('SELECT * FROM dg_requirements WHERE id=?');
            $st->execute([$id]);
            $r = $st->fetch();
            echo '<section class="section"><div class="container">';
            if (!$r) {
                echo '<p>Not found.</p></div></section>';
                return true;
            }
            echo '<div class="card"><h2>' . h($r['item']) . '</h2><p>' . h($r['qty'] . ' · ' . $r['city'] . ' · ' . $r['name']) . '</p><p>' . nl2br(h((string) $r['message'])) . '</p></div><br>';
            if ($me && !is_admin()) {
                echo '<div class="card"><h3>Send a quote</h3><form method="post">' . csrf_fields('quote_req') . '<input type="hidden" name="requirement_id" value="' . $id . '">';
                echo '<input class="input" name="amount" placeholder="Amount / rate"><br><br><textarea name="note" required placeholder="Lead time, GST, freight"></textarea><br><br><button class="btn" type="submit">Quote</button></form></div>';
            }
            $qs = $db->prepare('SELECT q.*, s.legal_name FROM dg_quotes q LEFT JOIN dg_suppliers s ON s.id=q.supplier_id WHERE q.requirement_id=? ORDER BY q.id DESC');
            $qs->execute([$id]);
            echo '<br><table class="table"><tr><th>Supplier</th><th>Amount</th><th>Note</th></tr>';
            foreach ($qs as $q) {
                echo '<tr><td>' . h($q['legal_name'] ?? 'Supplier') . '</td><td>' . h($q['amount']) . '</td><td>' . h($q['note']) . '</td></tr>';
            }
            echo '</table></div></section>';
            return true;
        }
        $rows = $db->query("SELECT * FROM dg_requirements WHERE status='open' ORDER BY id DESC LIMIT 40")->fetchAll();
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Open RFQs</h2><p>Buyers posted these. Suppliers quote from their desk.</p></div><a class="btn" href="?p=requirement">Post requirement</a></div><table class="table"><tr><th>Item</th><th>Qty</th><th>City</th><th></th></tr>';
        foreach ($rows as $r) {
            echo '<tr><td>' . h($r['item']) . '</td><td>' . h($r['qty']) . '</td><td>' . h($r['city']) . '</td><td><a href="?p=board&id=' . (int) $r['id'] . '">Open</a></td></tr>';
        }
        echo '</table></div></section>';
        return true;
    }
    if ($page === 'products') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $sql = 'SELECT * FROM dg_trade_products';
        $args = [];
        if ($q !== '') {
            $sql .= ' WHERE title LIKE ? OR city LIKE ? OR industry LIKE ?';
            $args = ["%$q%", "%$q%", "%$q%"];
        }
        $st = $db->prepare($sql . ' ORDER BY id DESC');
        $st->execute($args);
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Trade products</h2><p>Bulk SKUs from the live DoVyapaar catalog.</p></div></div>';
        echo '<form method="get" class="form-row" style="max-width:640px;margin-bottom:24px"><input type="hidden" name="p" value="products"><input class="input" name="q" value="' . h($q) . '" placeholder="Product, city, industry"><button class="btn" type="submit">Filter</button></form><div class="grid-3">';
        foreach ($st as $r) {
            echo '<div class="price-card"><h3>' . h($r['title']) . '</h3><div class="price" style="font-size:26px">' . h($r['price']) . '</div><p>' . h($r['industry'] . ' · ' . $r['city'] . ' · ' . $r['moq']) . '</p><a class="btn light" href="?p=requirement">RFQ this</a></div>';
        }
        echo '</div></div></section>';
        return true;
    }
    if ($page === 'leads') {
        $kind = trim((string) ($_GET['type'] ?? ''));
        $sql = 'SELECT * FROM dg_trade_leads';
        $args = [];
        if ($kind !== '') {
            $sql .= ' WHERE lead_type=?';
            $args[] = $kind;
        }
        $st = $db->prepare($sql . ' ORDER BY id DESC');
        $st->execute($args);
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Trade leads</h2><p>Buy and sell leads — same board as live DoVyapaar.</p></div><a class="btn" href="?p=requirement">Post lead</a></div>';
        echo '<p><a class="chip" href="?p=leads">All</a> <a class="chip" href="?p=leads&type=Buy+Lead">Buy</a> <a class="chip" href="?p=leads&type=Sell+Lead">Sell</a></p><div class="list-grid">';
        foreach ($st as $r) {
            echo '<div class="biz-card"><div class="meta"><span>' . h($r['lead_type']) . '</span><span>' . h($r['industry']) . '</span></div><h3>' . h($r['title']) . '</h3><p>' . h($r['city'] . ' · ' . $r['qty']) . '</p><p>' . h((string) $r['body']) . '</p><a class="btn light" href="?p=board">Quote from RFQ Center</a></div>';
        }
        echo '</div></div></section>';
        return true;
    }
    if ($page === 'trust') {
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Supplier trust</h2><p>IndiaMART/Udaan-style B2B guardrails: MOQ, GST invoice, lead time and verified supplier documents.</p></div><a class="btn" href="?p=requirement">Post RFQ</a></div><div class="grid-3">';
        foreach ([['GST invoice', 'Ask for GSTIN, invoice format and dispatch state before price negotiation.'], ['MOQ and lead time', 'Capture minimum order quantity, packing lot and dispatch window on every quote.'], ['Bulk quote compare', 'Use RFQ Center to compare amount, freight, GST and supplier notes in one table.']] as $c) {
            echo '<div class="feature"><h3>' . h($c[0]) . '</h3><p>' . h($c[1]) . '</p></div>';
        }
        echo '</div><div class="card" style="margin-top:24px"><h3>B2B only</h3><p>DoVyapaar is for bulk, wholesale and supplier requirements. Consumer kirana and local shop orders belong on DoBajar.</p></div></div></section>';
        return true;
    }
    return false;
}
