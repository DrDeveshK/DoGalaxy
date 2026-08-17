<?php
declare(strict_types=1);

function dn_inclusions(): array
{
    return [
        'Foundation' => ['Foundation & Soil Work', 0.12, '⛏'],
        'Brickwork' => ['Brickwork & Masonry', 0.13, '🧱'],
        'RCC Roof' => ['RCC Structure', 0.22, '🏗'],
        'Plastering' => ['Plastering & Finishing', 0.08, '🪟'],
        'Flooring' => ['Flooring & Tiling', 0.10, '⬜'],
        'Electrical' => ['Electrical Work', 0.07, '⚡'],
        'Plumbing' => ['Plumbing & Sanitation', 0.06, '🚿'],
        'Doors & Windows' => ['Doors, Windows & Grills', 0.10, '🚪'],
        'Painting' => ['Painting & Waterproofing', 0.06, '🎨'],
        'Waterproofing' => ['Waterproofing extra', 0.00, '💧'],
    ];
}

function dn_rates(): array
{
    return ['economy' => 1400, 'standard' => 1800, 'premium' => 2400];
}

function dn_compute(array $in): array
{
    $area = max(0, (float) ($in['area'] ?? 0));
    $floors = max(1, (int) ($in['floors'] ?? 1));
    $type = (string) ($in['type'] ?? 'standard');
    $basement = (float) ($in['basement'] ?? 0);
    $rates = dn_rates();
    $rate = $rates[$type] ?? 1800;
    $totalArea = $area * $floors + ($area * $basement * 0.7);
    $picked = $in['inc'] ?? array_keys(dn_inclusions());
    if (!is_array($picked)) {
        $picked = array_keys(dn_inclusions());
    }
    $pct = 0.06;
    $breakdown = [];
    foreach (dn_inclusions() as $k => $row) {
        if (!in_array($k, $picked, true) || $row[1] <= 0) {
            continue;
        }
        $breakdown[$row[0]] = ['pct' => $row[1], 'icon' => $row[2]];
        $pct += $row[1];
    }
    $breakdown['Labour Charges'] = ['pct' => 0.06, 'icon' => '👷'];
    $scale = $pct > 0 ? 1 / $pct : 1;
    $total = $totalArea * $rate * $pct;
    foreach ($breakdown as $k => $v) {
        $breakdown[$k]['amt'] = $total * $v['pct'] * $scale;
        $breakdown[$k]['show'] = (int) round($v['pct'] * $scale * 100);
    }
    return [
        'area' => $area,
        'floors' => $floors,
        'type' => $type,
        'city' => trim((string) ($in['city'] ?? '')),
        'basement' => $basement,
        'rate' => $rate,
        'total_area' => $totalArea,
        'total' => $total,
        'breakdown' => $breakdown,
        'materials' => [
            'Cement (bags)' => (int) round($totalArea * 0.5),
            'Steel / TMT (kg)' => (int) round($totalArea * 4),
            'Bricks' => (int) round($totalArea * 8),
            'Sand (cu ft)' => (int) round($totalArea * 1.2),
            'M-Sand (cu ft)' => (int) round($totalArea * 0.8),
            'Tiles (sq ft)' => (int) round($area * 1.1),
        ],
        'inc' => $picked,
    ];
}

function dn_materials(): array
{
    return [
        ['🏗', 'Cement', 'per bag (50kg)', '₹420', 'up', '+₹10'],
        ['🔩', 'TMT Steel', 'per kg', '₹68', 'down', '-₹2'],
        ['🧱', 'Red Bricks', 'per 1000 units', '₹7,500', 'same', 'Stable'],
        ['🪨', 'River Sand', 'per cu ft', '₹55', 'up', '+₹5'],
        ['🟫', 'M-Sand', 'per cu ft', '₹40', 'same', 'Stable'],
        ['🪟', 'AAC Blocks', 'per block', '₹48', 'down', '-₹2'],
        ['🎨', 'Cement Paint', 'per litre', '₹85', 'up', '+₹3'],
        ['⬜', 'Vitrified Tiles', 'per sq ft', '₹65', 'same', 'Stable'],
    ];
}

function dn_articles(): array
{
    return [
        ['cost-mp-2025', 'Cost Guide', 'How much does it cost to build a house in Madhya Pradesh?', 'City-wise economy / standard / premium rates, with material quantities. Economy ₹1,400, Standard ₹1,800, Premium ₹2,400 per sq ft. Bhopal average around ₹1,650.', '🏗'],
        ['estimate-guide', 'Cost Guide', 'How to estimate construction cost for Indian home builders', 'Use built-up area × floors, add basement at 70% of a floor, then split foundation, RCC, masonry, finishes, MEP and labour.', '📐'],
        ['contractor-questions', 'Hiring Tips', '10 questions to ask a contractor before hiring', 'Ask for GST, past sites you can visit, daily rate vs lump-sum, who buys materials, and a written milestone schedule.', '✅'],
        ['vastu-basics', 'Vastu', 'Vastu Shastra for home construction — key rules', 'Plot slope, main door, kitchen fire corner, and bedroom orientation — a family-friendly checklist, not a substitute for an architect.', '🏠'],
        ['cement-types', 'Materials', 'Cement comparison — OPC vs PPC vs PSC', 'Foundations often use OPC 43/53. PPC suits plaster and brickwork. Ask your contractor which bag is on site.', '🧱'],
        ['legal-mp', 'Legal', 'RERA, building permits and NOCs before you build', 'Layout approval, building permission, labour cess and completion certificate — confirm with your local body.', '📋'],
        ['save-15', 'Cost Guide', 'How to save 15% without cutting quality', 'Lock steel and cement early, avoid mid-build design changes, and pay against measured work, not verbal advances.', '💰'],
        ['waterproofing', 'Materials', 'Waterproofing 101 — roofs, baths and basements', 'Treat wet areas before tiles. Budget a line item; leaks cost more than the membrane.', '🚿'],
        ['paint-guide', 'Materials', 'Choosing interior vs exterior paint', 'Primer + two coats. Exterior needs weather coat. Calculate litres from wall area, not floor area.', '🎨'],
    ];
}

function product_migrate(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_estimates (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, city TEXT, area TEXT, floors TEXT, grade TEXT, basement TEXT, total TEXT, payload TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_projects (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, title TEXT NOT NULL, city TEXT, stage TEXT NOT NULL DEFAULT \'brief\', note TEXT, estimate_id INTEGER, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_articles (id INTEGER PRIMARY KEY AUTOINCREMENT, slug TEXT NOT NULL UNIQUE, category TEXT NOT NULL, title TEXT NOT NULL, body TEXT NOT NULL, icon TEXT, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
}

function product_seed(PDO $db): void
{
    $ins = $db->prepare('INSERT OR IGNORE INTO dg_articles (slug, category, title, body, icon) VALUES (?,?,?,?,?)');
    foreach (dn_articles() as $a) {
        $ins->execute([$a[0], $a[1], $a[2], $a[3], $a[4]]);
    }
}

function product_handle_post(PDO $db, string $act, string &$err): bool
{
    if ($act === 'save_estimate') {
        $e = dn_compute($_POST);
        if ($e['area'] < 100) {
            $err = 'Enter built-up area of at least 100 sq ft.';
            return true;
        }
        $db->prepare('INSERT INTO dg_estimates (user_id, city, area, floors, grade, basement, total, payload) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([user()['id'] ?? null, $e['city'], (string) $e['area'], (string) $e['floors'], $e['type'], (string) $e['basement'], (string) (int) round($e['total']), json_encode($e)]);
        $id = (int) $db->lastInsertId();
        if (user()) {
            notify($db, (int) user()['id'], 'Estimate saved', '₹' . number_format((int) round($e['total'])), '?p=estimates');
        }
        flash('Estimate saved. Share it with a contractor.');
        go('estimate&id=' . $id);
    }
    if ($act === 'quote_material') {
        $item = trim((string) ($_POST['item'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!$item || !$name || !$email) {
            $err = 'Name, email and material are required.';
            return true;
        }
        $db->prepare('INSERT INTO dg_enquiries (product, user_id, name, email, phone, intent, message) VALUES (?,?,?,?,?,?,?)')
            ->execute(['donirman', user()['id'] ?? null, $name, (string) $email, trim((string) ($_POST['phone'] ?? '')), 'material', $item . "\n" . trim((string) ($_POST['message'] ?? ''))]);
        queue_mail($db, setting('contact_email'), 'Material quote: ' . $item, $name . ' <' . $email . '>');
        flash('Quote request sent to the materials desk.');
        go('materials&sent=1');
    }
    if ($act === 'boq_request') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $scope = trim((string) ($_POST['scope'] ?? ''));
        if (!$name || !$email || $scope === '') {
            $err = 'Name, email and BOQ scope are required.';
            return true;
        }
        $msg = 'Site: ' . trim((string) ($_POST['site_city'] ?? '')) . "\nArea: " . trim((string) ($_POST['area'] ?? '')) . "\nItems: " . trim((string) ($_POST['items'] ?? '')) . "\nScope: " . $scope;
        $db->prepare('INSERT INTO dg_enquiries (product, user_id, name, email, phone, intent, message) VALUES (?,?,?,?,?,?,?)')
            ->execute(['donirman', user()['id'] ?? null, $name, (string) $email, trim((string) ($_POST['phone'] ?? '')), 'boq', $msg]);
        flash('BOQ / material RFQ sent to the Nirman desk.');
        go('boq&sent=1');
    }
    if ($act === 'subscribe') {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $err = 'Enter a valid email.';
            return true;
        }
        $db->prepare('INSERT INTO dg_enquiries (product, name, email, intent, message) VALUES (?,?,?,?,?)')
            ->execute(['donirman', 'NirmanGyan', (string) $email, 'subscribe', 'Weekly construction tips']);
        flash('Subscribed. Tips will sit in the mail log until SMTP is connected.');
        go('gyan&sub=1');
    }
    if ($act === 'save_project' && user()) {
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            $err = 'Project title is required.';
            return true;
        }
        $db->prepare('INSERT INTO dg_projects (user_id, title, city, stage, note, estimate_id) VALUES (?,?,?,?,?,?)')
            ->execute([user()['id'], $title, trim((string) ($_POST['city'] ?? '')), (string) ($_POST['stage'] ?? 'brief'), trim((string) ($_POST['note'] ?? '')), (int) ($_POST['estimate_id'] ?? 0) ?: null]);
        flash('Project opened.');
        go('projects');
    }
    if ($act === 'project_stage' && user()) {
        $db->prepare('UPDATE dg_projects SET stage=? WHERE id=? AND user_id=?')->execute([(string) ($_POST['stage'] ?? 'brief'), (int) ($_POST['id'] ?? 0), user()['id']]);
        flash('Milestone updated.');
        go('projects');
    }
    if ($act === 'design_brief') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $msg = trim((string) ($_POST['message'] ?? ''));
        if (!$name || !$email || $msg === '') {
            $err = 'Name, email and brief are required.';
            return true;
        }
        $db->prepare('INSERT INTO dg_enquiries (product, user_id, name, email, phone, intent, message) VALUES (?,?,?,?,?,?,?)')
            ->execute(['donirman', user()['id'] ?? null, $name, (string) $email, trim((string) ($_POST['phone'] ?? '')), 'design', $msg]);
        queue_mail($db, setting('contact_email'), 'Design Connect brief', $name . "\n" . $msg);
        flash('Brief sent. Designers in Interiors / Architect trades can be contacted from the directory.');
        go('design&sent=1');
    }
    return false;
}

function product_render_page(PDO $db, string $page, array $P, ?array $me): bool
{
    if ($page === 'home') {
        dn_home($db, $P);
        return true;
    }
    if ($page === 'estimate') {
        dn_estimate($db, $me);
        return true;
    }
    if ($page === 'materials') {
        dn_materials_page();
        return true;
    }
    if ($page === 'boq') {
        dn_boq();
        return true;
    }
    if ($page === 'gyan') {
        dn_gyan($db);
        return true;
    }
    if ($page === 'article') {
        dn_article($db);
        return true;
    }
    if ($page === 'design') {
        dn_design($db);
        return true;
    }
    if ($page === 'estimates' && $me) {
        dn_my_estimates($db, $me);
        return true;
    }
    if ($page === 'projects' && $me) {
        dn_projects($db, $me);
        return true;
    }
    if ($page === 'dir') {
        dn_dir($db, $P);
        return true;
    }
    return false;
}

function dn_boq(): void
{
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>BOQ / material RFQ</h2><p>IronCement-style intake: project area, site city, item list and supplier notes for bulk material quotes.</p></div></div><div class="card" style="max-width:44rem">';
    if (!empty($_GET['sent'])) {
        echo '<div class="notice">BOQ request received.</div>';
    }
    echo '<form method="post">' . csrf_fields('boq_request');
    echo '<div class="form-row"><input class="input" name="name" required placeholder="Your name"><input class="input" type="email" name="email" required placeholder="Email"></div><br>';
    echo '<div class="form-row"><input class="input" name="phone" placeholder="Phone"><input class="input" name="site_city" placeholder="Site city"></div><br>';
    echo '<div class="form-row"><input class="input" name="area" placeholder="Built-up area / project size"><input class="input" name="items" placeholder="Cement, steel, tiles, sand..."></div><br>';
    echo '<textarea name="scope" required placeholder="Upload-free BOQ brief: quantities, grade, delivery window, GST invoice needs"></textarea><br><br><button class="btn" type="submit">Send BOQ request</button></form></div></div></section>';
}

function dn_inr(float $n): string
{
    return '₹' . number_format((int) round($n), 0, '.', ',');
}

function dn_home(PDO $db, array $P): void
{
    echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">' . h(setting('eyebrow')) . '</span>';
    echo '<h1>' . h(setting('hero_h1')) . '</h1><p>' . h(setting('hero_p')) . '</p>';
    echo '<div class="hero-actions"><a class="btn" href="?p=dir">Find contractors</a><a class="btn light" href="?p=estimate">Estimate cost</a></div>';
    echo '<div class="stats"><div class="stat"><b>5,000+</b><span>Verified contractors</span></div><div class="stat"><b>18,000+</b><span>Projects completed</span></div><div class="stat"><b>120+</b><span>Cities</span></div><div class="stat"><b>±10%</b><span>Estimate band</span></div></div></div>';
    echo '<div class="search-panel"><h3>Find a contractor now</h3><form method="get"><input type="hidden" name="p" value="dir">';
    echo '<input class="input" name="trade" placeholder="Civil, mason, electrician"><br><br><input class="input" name="q" placeholder="City"><br><br><button class="btn" type="submit">Search contractors</button></form></div></div></section>';
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Everything for the build</h2><p>Same loops as the hosted DoNirman site.</p></div></div><div class="grid-3">';
    $cards = [
        ['dir', '🔨', 'Contractor marketplace', 'Browse trades, rates, experience and GST. Send a site brief.'],
        ['materials', '🧱', 'Materials Bazaar', 'Daily cement, steel, brick and tile rates. Request a supplier quote.'],
        ['estimate', '🧮', 'Cost estimator', 'Area, floors, grade, basement and inclusions → total, breakdown, quantities.'],
        ['design', '📐', 'Design Connect', 'Send a brief to architects and interior partners.'],
        ['projects', '📊', 'Project desk', 'Open a project, attach an estimate, move milestones.'],
        ['gyan', '📚', 'NirmanGyan', 'Cost guides, Vastu, materials and legal checklists.'],
    ];
    foreach ($cards as $c) {
        echo '<a class="feature" href="?p=' . $c[0] . '"><div class="icon">' . $c[1] . '</div><h3>' . h($c[2]) . '</h3><p>' . h($c[3]) . '</p></a>';
    }
    echo '</div></div></section>';
    $feat = $db->query('SELECT * FROM dg_contractors WHERE verify_status IN (\'pending\',\'verified\') ORDER BY featured DESC, id DESC LIMIT 4')->fetchAll();
    if ($feat) {
        echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Featured contractors</h2></div><a class="btn light" href="?p=dir">Directory</a></div><div class="list-grid">';
        foreach ($feat as $r) {
            echo '<div class="biz-card"><div class="biz-head"><div class="avatar">' . h(strtoupper(substr((string) $r['legal_name'], 0, 1))) . '</div><div><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['legal_name']) . '</a></h3><p>' . h($r['trade'] ?? '') . ' · ' . h($r['city'] ?? '') . '</p></div></div>';
            echo '<div class="meta"><span class="verified">' . h($r['verify_status']) . '</span>';
            if (!empty($r['daily_rate'])) {
                echo '<span>₹' . h($r['daily_rate']) . '/day</span>';
            }
            echo '</div><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">View</a></div>';
        }
        echo '</div></div></section>';
    }
}

function dn_estimate(PDO $db, ?array $me): void
{
    $saved = null;
    $sid = (int) ($_GET['id'] ?? 0);
    if ($sid) {
        $st = $db->prepare('SELECT * FROM dg_estimates WHERE id=?');
        $st->execute([$sid]);
        $row = $st->fetch();
        if ($row) {
            $saved = json_decode((string) $row['payload'], true) ?: null;
        }
    }
    $inc = dn_inclusions();
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Construction cost estimator</h2><p>Instant materials, labour and category breakdown. ±10% vs local market.</p></div></div>';
    echo '<div class="est-grid"><div class="card"><h3>Enter project details</h3><p class="muted">Area and floors are required. Uncheck a line to drop it from the total.</p>';
    echo '<form method="post" id="est-form">' . csrf_fields('save_estimate');
    echo '<label>Built-up area (sq ft) *</label><input class="input" type="number" name="area" id="est-area" min="100" value="' . h((string) ($saved['area'] ?? '')) . '" placeholder="e.g. 1200" required><br><br>';
    echo '<div class="form-row"><div><label>No. of floors *</label><select class="input" name="floors" id="est-floors">';
    foreach ([1 => 'Ground floor (G)', 2 => 'G + 1 floor', 3 => 'G + 2 floors', 4 => 'G + 3 floors', 5 => 'G + 4 floors'] as $k => $lab) {
        echo '<option value="' . $k . '"' . ((int) ($saved['floors'] ?? 1) === $k ? ' selected' : '') . '>' . h($lab) . '</option>';
    }
    echo '</select></div><div><label>Construction type *</label><select class="input" name="type" id="est-type">';
    foreach (['economy' => 'Economy — ₹1,400/sqft', 'standard' => 'Standard — ₹1,800/sqft', 'premium' => 'Premium — ₹2,400/sqft'] as $k => $lab) {
        echo '<option value="' . $k . '"' . (($saved['type'] ?? 'standard') === $k ? ' selected' : '') . '>' . h($lab) . '</option>';
    }
    echo '</select></div></div><br><div class="form-row"><div><label>City</label><input class="input" name="city" id="est-city" value="' . h((string) ($saved['city'] ?? '')) . '" placeholder="e.g. Bhopal"></div>';
    echo '<div><label>Basement?</label><select class="input" name="basement" id="est-basement">';
    foreach (['0' => 'No basement', '1' => 'Yes — full basement', '0.5' => 'Yes — partial basement'] as $k => $lab) {
        echo '<option value="' . $k . '"' . ((string) ($saved['basement'] ?? '0') === $k ? ' selected' : '') . '>' . h($lab) . '</option>';
    }
    echo '</select></div></div><br><label>What do you want to include?</label><div class="chips">';
    $picked = $saved['inc'] ?? array_keys($inc);
    foreach ($inc as $k => $row) {
        echo '<label class="chip"><input type="checkbox" name="inc[]" value="' . h($k) . '"' . (in_array($k, $picked, true) ? ' checked' : '') . '> ' . h($k) . '</label>';
    }
    echo '</div><br><button class="btn" type="button" onclick="runEstimator()">Calculate my estimate</button> ';
    echo '<button class="btn light" type="submit">' . ($me ? 'Save to my desk' : 'Save estimate (guest ok)') . '</button></form></div>';
    echo '<div><div id="results-placeholder" class="card est-ph"' . ($saved ? ' style="display:none"' : '') . '><div style="font-size:48px">🧮</div><h3>Your estimate will appear here</h3><p>Fill the form and click Calculate.</p></div>';
    echo '<div id="results-panel" class="est-res"' . ($saved ? '' : ' style="display:none"') . '>';
    echo '<div class="est-total"><div class="muted" style="color:#fff;opacity:.7">Estimated total cost</div><div id="res-total" class="est-num">' . ($saved ? h(dn_inr((float) $saved['total'])) : '') . '</div>';
    echo '<div id="res-meta">' . ($saved ? h(ucfirst((string) $saved['type']) . ' · ₹' . number_format((int) $saved['rate']) . '/sq ft') : '') . '</div>';
    echo '<div class="est-kpis"><div><b id="res-area">' . ($saved ? number_format((int) round((float) $saved['total_area'])) : '') . '</b><span>Total sq ft</span></div><div><b id="res-rate">' . ($saved ? '₹' . number_format((int) $saved['rate']) : '') . '</b><span>Per sq ft</span></div><div><b id="res-floors">' . ($saved ? (int) $saved['floors'] : '') . '</b><span>Floors</span></div></div></div>';
    echo '<div class="card"><h3>Cost breakdown</h3><div id="res-breakdown">';
    if ($saved) {
        foreach ($saved['breakdown'] as $k => $v) {
            echo '<div class="bar-row"><span>' . h(($v['icon'] ?? '') . ' ' . $k) . '</span><b>' . h(dn_inr((float) $v['amt'])) . ' <small>(' . (int) $v['show'] . '%)</small></b></div><div class="progress-bar"><div class="progress-fill" style="width:' . (int) $v['show'] . '%"></div></div>';
        }
    }
    echo '</div></div><div class="card"><h3>Approximate material quantities</h3><div id="res-materials" class="grid-mat">';
    if ($saved) {
        foreach ($saved['materials'] as $k => $v) {
            echo '<div class="mat"><b>' . h((string) $v) . '</b><span>' . h($k) . '</span></div>';
        }
    }
    echo '</div></div><a class="btn" href="?p=dir">Find contractors for this project</a> <button class="btn light" type="button" onclick="window.print()">Print / save</button></div></div></div>';
    echo '<div class="section-title" style="margin-top:48px"><div><h2>Accurate estimates in 3 steps</h2></div></div><div class="grid-3">';
    foreach ([['📝', 'Enter project details', 'Area, floors, grade, city and inclusions.'], ['⚡', 'Get instant estimate', 'Market rates → category split and quantities.'], ['🔨', 'Hire a verified contractor', 'Take the number to the directory and send a brief.']] as $s) {
        echo '<div class="card"><div class="icon">' . $s[0] . '</div><h3>' . h($s[1]) . '</h3><p>' . h($s[2]) . '</p></div>';
    }
    echo '</div></div></section><script>
function fmt(n){return "₹"+Math.round(n).toLocaleString("en-IN")}
function runEstimator(){
  var area=parseFloat(document.getElementById("est-area").value)||0,floors=parseInt(document.getElementById("est-floors").value)||1,type=document.getElementById("est-type").value,basement=parseFloat(document.getElementById("est-basement").value)||0;
  if(!area||area<100){alert("Enter built-up area (min 100 sq ft).");return}
  var rates={economy:1400,standard:1800,premium:2400},labels={economy:"Economy",standard:"Standard",premium:"Premium"},rate=rates[type],totalArea=area*floors+(area*basement*0.7);
  var map={"Foundation":[0.12,"Foundation & Soil Work","⛏"],"Brickwork":[0.13,"Brickwork & Masonry","🧱"],"RCC Roof":[0.22,"RCC Structure","🏗"],"Plastering":[0.08,"Plastering & Finishing","🪟"],"Flooring":[0.10,"Flooring & Tiling","⬜"],"Electrical":[0.07,"Electrical Work","⚡"],"Plumbing":[0.06,"Plumbing & Sanitation","🚿"],"Doors & Windows":[0.10,"Doors, Windows & Grills","🚪"],"Painting":[0.06,"Painting & Waterproofing","🎨"]};
  var boxes=document.querySelectorAll("input[name=\'inc[]\']:checked"),pct=0.06,rows=[["Labour Charges",0.06,"👷"]];
  boxes.forEach(function(b){if(map[b.value]){rows.push([map[b.value][1],map[b.value][0],map[b.value][2]]);pct+=map[b.value][0]}});
  var total=totalArea*rate*pct,scale=pct?1/pct:1;
  document.getElementById("res-total").textContent=fmt(total);
  document.getElementById("res-meta").textContent=labels[type]+" grade · ₹"+rate.toLocaleString("en-IN")+"/sq ft";
  document.getElementById("res-area").textContent=Math.round(totalArea).toLocaleString("en-IN");
  document.getElementById("res-rate").textContent="₹"+rate.toLocaleString("en-IN");
  document.getElementById("res-floors").textContent=floors;
  var bHtml="";
  rows.forEach(function(r){var show=Math.round(r[1]*scale*100),amt=total*r[1]*scale;bHtml+="<div class=\\"bar-row\\"><span>"+r[2]+" "+r[0]+"</span><b>"+fmt(amt)+" <small>("+show+"%)</small></b></div><div class=\\"progress-bar\\"><div class=\\"progress-fill\\" style=\\"width:"+show+"%\\"></div></div>"});
  document.getElementById("res-breakdown").innerHTML=bHtml;
  var mats={"Cement (bags)":Math.round(totalArea*0.5),"Steel / TMT (kg)":Math.round(totalArea*4),"Bricks":Math.round(totalArea*8),"Sand (cu ft)":Math.round(totalArea*1.2),"M-Sand (cu ft)":Math.round(totalArea*0.8),"Tiles (sq ft)":Math.round(area*1.1)},mHtml="";
  Object.keys(mats).forEach(function(k){mHtml+="<div class=\\"mat\\"><b>"+mats[k].toLocaleString("en-IN")+"</b><span>"+k+"</span></div>"});
  document.getElementById("res-materials").innerHTML=mHtml;
  document.getElementById("results-placeholder").style.display="none";
  document.getElementById("results-panel").style.display="block";
}
' . ($saved ? 'runEstimator();' : '') . '</script>';
}

function dn_materials_page(): void
{
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Materials Bazaar</h2><p>Indicative local rates. Last updated ' . h(date('d M Y')) . ', Bhopal, MP.</p></div></div>';
    echo '<div class="notice">Prices move daily. Request a quote for a firm supplier rate.</div><div class="grid-4">';
    foreach (dn_materials() as $m) {
        echo '<div class="card"><div class="icon">' . $m[0] . '</div><h3>' . h($m[1]) . '</h3><div class="price" style="font-size:28px">' . h($m[3]) . '</div><p>' . h($m[2]) . ' · ' . h($m[5]) . '</p>';
        echo '<form method="post" style="margin-top:12px">' . csrf_fields('quote_material') . '<input type="hidden" name="item" value="' . h($m[1]) . '">';
        echo '<input class="input" name="name" placeholder="Name" required><br><br><input class="input" type="email" name="email" placeholder="Email" required><br><br>';
        echo '<input class="input" name="phone" placeholder="Phone"><br><br><textarea name="message" placeholder="Quantity / city"></textarea><br><br><button class="btn light" type="submit">Get quotes</button></form></div>';
    }
    echo '</div></div></section>';
}

function dn_gyan(PDO $db): void
{
    $cat = trim((string) ($_GET['cat'] ?? ''));
    $sql = 'SELECT * FROM dg_articles';
    $args = [];
    if ($cat !== '') {
        $sql .= ' WHERE category=?';
        $args[] = $cat;
    }
    $st = $db->prepare($sql . ' ORDER BY id');
    $st->execute($args);
    $rows = $st->fetchAll();
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>NirmanGyan — निर्माण ज्ञान</h2><p>Tips, cost guides, Vastu and legal checklists for home builders.</p></div></div>';
    echo '<div class="chips" style="margin-bottom:24px"><a class="chip" href="?p=gyan">All</a>';
    foreach (['Cost Guide', 'Hiring Tips', 'Vastu', 'Materials', 'Legal'] as $c) {
        echo '<a class="chip" href="?p=gyan&cat=' . urlencode($c) . '">' . h($c) . '</a>';
    }
    echo '</div><div class="card" style="background:var(--navy);color:#fff;margin-bottom:28px"><span class="eyebrow">Featured guide</span><h2 style="color:#fff">How much to build in Madhya Pradesh?</h2><p>Economy ₹1,400 · Standard ₹1,800 · Premium ₹2,400 · Bhopal avg ₹1,650 / sq ft</p><a class="btn" href="?p=article&slug=cost-mp-2025">Read guide</a></div>';
    echo '<div class="grid-3">';
    foreach ($rows as $r) {
        echo '<a class="feature" href="?p=article&slug=' . h($r['slug']) . '"><div class="icon">' . h($r['icon']) . '</div><p class="muted">' . h($r['category']) . '</p><h3>' . h($r['title']) . '</h3><p>' . h(substr((string) $r['body'], 0, 140)) . '…</p></a>';
    }
    echo '</div><div class="card" style="margin-top:40px;text-align:center"><h3>Get construction tips in your inbox</h3><form method="post" class="form-row" style="max-width:480px;margin:16px auto">' . csrf_fields('subscribe');
    echo '<input class="input" type="email" name="email" required placeholder="Email"><button class="btn" type="submit">Subscribe</button></form></div></div></section>';
}

function dn_article(PDO $db): void
{
    $st = $db->prepare('SELECT * FROM dg_articles WHERE slug=?');
    $st->execute([(string) ($_GET['slug'] ?? '')]);
    $r = $st->fetch();
    echo '<section class="section"><div class="container"><div class="card">';
    if (!$r) {
        echo '<p>Article not found. <a href="?p=gyan">NirmanGyan</a></p></div></div></section>';
        return;
    }
    echo '<p class="muted">' . h($r['category']) . '</p><h1>' . h($r['icon'] . ' ' . $r['title']) . '</h1><p>' . nl2br(h($r['body'])) . '</p>';
    echo '<p><a class="btn" href="?p=estimate">Run the cost estimator</a> <a class="btn light" href="?p=gyan">More articles</a></p></div></div></section>';
}

function dn_design(PDO $db): void
{
    $rows = $db->query("SELECT * FROM dg_contractors WHERE trade IN ('Interiors','Architect') AND verify_status IN ('pending','verified') ORDER BY id DESC")->fetchAll();
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Design Connect</h2><p>Share a brief. Staff and listed architects / interiors partners see it.</p></div></div>';
    echo '<div class="card" style="max-width:40rem;margin-bottom:28px"><form method="post">' . csrf_fields('design_brief');
    echo '<div class="form-row"><input class="input" name="name" required placeholder="Name"><input class="input" type="email" name="email" required placeholder="Email"></div><br>';
    echo '<input class="input" name="phone" placeholder="Phone"><br><br><textarea name="message" required placeholder="Plot size, city, style, budget"></textarea><br><br><button class="btn" type="submit">Send brief</button></form></div>';
    echo '<div class="list-grid">';
    foreach ($rows as $r) {
        echo '<div class="biz-card"><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['legal_name']) . '</a></h3><p>' . h($r['trade'] . ' · ' . $r['city']) . '</p><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">Contact</a></div>';
    }
    if (!$rows) {
        echo '<p>No design partners listed yet. <a href="?p=join">Join as Interiors / Architect</a>.</p>';
    }
    echo '</div></div></section>';
}

function dn_my_estimates(PDO $db, array $me): void
{
    $st = $db->prepare('SELECT * FROM dg_estimates WHERE user_id=? ORDER BY id DESC');
    $st->execute([$me['id']]);
    echo '<section class="section soft"><div class="container"><div class="dashboard">' . dash_nav('estimates') . '<div class="dash-panel"><h2>Saved estimates</h2><table class="table"><tr><th>#</th><th>City</th><th>Area</th><th>Total</th><th></th></tr>';
    foreach ($st as $r) {
        echo '<tr><td>' . (int) $r['id'] . '</td><td>' . h($r['city']) . '</td><td>' . h($r['area']) . ' × ' . h($r['floors']) . '</td><td>' . h(dn_inr((float) $r['total'])) . '</td><td><a href="?p=estimate&id=' . (int) $r['id'] . '">Open</a></td></tr>';
    }
    echo '</table><p><a class="btn" href="?p=estimate">New estimate</a></p></div></div></div></section>';
}

function dn_projects(PDO $db, array $me): void
{
    $st = $db->prepare('SELECT * FROM dg_projects WHERE user_id=? ORDER BY id DESC');
    $st->execute([$me['id']]);
    $ests = $db->prepare('SELECT id, total, city FROM dg_estimates WHERE user_id=? ORDER BY id DESC');
    $ests->execute([$me['id']]);
    echo '<section class="section soft"><div class="container"><div class="dashboard">' . dash_nav('projects') . '<div class="dash-panel"><h2>Projects</h2><form method="post">' . csrf_fields('save_project');
    echo '<div class="form-row"><input class="input" name="title" required placeholder="Project title"><input class="input" name="city" placeholder="City"></div><br>';
    echo '<div class="form-row"><select class="input" name="stage"><option>brief</option><option>estimate</option><option>contractor</option><option>structure</option><option>finishes</option><option>handover</option></select><select class="input" name="estimate_id"><option value="">Link estimate</option>';
    foreach ($ests as $e) {
        echo '<option value="' . (int) $e['id'] . '">#' . (int) $e['id'] . ' ' . h(dn_inr((float) $e['total'])) . '</option>';
    }
    echo '</select></div><br><textarea name="note" placeholder="Milestones, payments, deliveries"></textarea><br><br><button class="btn" type="submit">Open project</button></form><br><table class="table"><tr><th>Title</th><th>Stage</th><th></th></tr>';
    foreach ($st as $r) {
        echo '<tr><td>' . h($r['title']) . '<br><small>' . h($r['city']) . '</small></td><td>' . h($r['stage']) . '</td><td><form method="post" class="inline">' . csrf_fields('project_stage') . '<input type="hidden" name="id" value="' . (int) $r['id'] . '"><select name="stage"><option>brief</option><option>estimate</option><option>contractor</option><option>structure</option><option>finishes</option><option>handover</option></select><button class="btn light" type="submit">Set</button></form></td></tr>';
    }
    echo '</table></div></div></div></section>';
}

function dn_dir(PDO $db, array $P): void
{
    $q = trim((string) ($_GET['q'] ?? ''));
    $trade = trim((string) ($_GET['trade'] ?? ''));
    $verified = !empty($_GET['verified']);
    $avail = !empty($_GET['available']);
    $sql = "SELECT * FROM dg_contractors WHERE verify_status IN ('pending','verified')";
    $args = [];
    if ($q !== '') {
        $sql .= ' AND (legal_name LIKE ? OR city LIKE ?)';
        $args[] = "%$q%";
        $args[] = "%$q%";
    }
    if ($trade !== '') {
        $sql .= ' AND trade LIKE ?';
        $args[] = "%$trade%";
    }
    if ($verified) {
        $sql .= " AND verify_status='verified'";
    }
    if ($avail) {
        $sql .= " AND available='Yes'";
    }
    $st = $db->prepare($sql . ' ORDER BY featured DESC, id DESC LIMIT 50');
    $st->execute($args);
    $rows = $st->fetchAll();
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Find verified contractors</h2><p>Filter by trade, city, verified and available now.</p></div></div>';
    echo '<form class="card" method="get"><input type="hidden" name="p" value="dir"><div class="form-row">';
    echo '<input class="input" name="trade" value="' . h($trade) . '" placeholder="Trade"><input class="input" name="q" value="' . h($q) . '" placeholder="City or name"></div><br>';
    echo '<label class="chip"><input type="checkbox" name="verified" value="1"' . ($verified ? ' checked' : '') . '> Verified only</label> ';
    echo '<label class="chip"><input type="checkbox" name="available" value="1"' . ($avail ? ' checked' : '') . '> Available now</label> ';
    echo '<button class="btn" type="submit">Search</button></form><br><div class="list-grid">';
    foreach ($rows as $r) {
        echo '<div class="biz-card"><div class="biz-head"><div class="avatar">' . h(strtoupper(substr((string) $r['legal_name'], 0, 1))) . '</div><div><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['legal_name']) . '</a></h3><p>' . h(($r['trade'] ?? '') . ' · ' . ($r['city'] ?? '')) . '</p></div></div>';
        echo '<div class="meta"><span class="verified">' . h($r['verify_status']) . '</span>';
        if (!empty($r['daily_rate'])) {
            echo '<span>₹' . h($r['daily_rate']) . '/day</span>';
        }
        if (!empty($r['experience_years'])) {
            echo '<span>' . h($r['experience_years']) . ' yrs</span>';
        }
        if (!empty($r['rating'])) {
            echo '<span>★ ' . h($r['rating']) . '</span>';
        }
        echo '</div><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">View / send brief</a></div>';
    }
    if (!$rows) {
        echo '<p>None yet. <a href="?p=join">Be first</a>.</p>';
    }
    echo '</div></div></section>';
}
