<?php
declare(strict_types=1);

function ds_packs(): array
{
    return [
        ['Wedding desk', '₹49,999', 'Venue + caterer + decor shortlist for ~200 guests. Same loop as live Event Packages.'],
        ['Birthday / social', '₹14,999', 'Hall + cake hour + photographer slot.'],
        ['Corporate meet', '₹24,999', 'Half-day banquet, AV and welcome desk.'],
        ['Lawn celebration', '₹79,999', 'Open lawn + stay desk via DoVishram.'],
    ];
}

function product_migrate(PDO $pdo): void
{
}

function product_seed(PDO $db): void
{
}

function product_handle_post(PDO $db, string $act, string &$err): bool
{
    if ($act === 'event_brief') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $event = trim((string) ($_POST['event_type'] ?? ''));
        if (!$name || !$email || $event === '') {
            $err = 'Name, email and event type are required.';
            return true;
        }
        $vid = (int) $db->query('SELECT id FROM dg_venues ORDER BY featured DESC, id LIMIT 1')->fetchColumn();
        $db->prepare('INSERT INTO dg_event_requests (venue_id, name, email, phone, event_date, guests, event_type, budget, message) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([$vid, $name, (string) $email, trim((string) ($_POST['phone'] ?? '')), trim((string) ($_POST['event_date'] ?? '')), trim((string) ($_POST['guests'] ?? '')), $event, trim((string) ($_POST['budget'] ?? '')), trim((string) ($_POST['message'] ?? ''))]);
        flash('Event brief #' . $db->lastInsertId() . ' is with the Swagat desk.');
        go('track&ref=' . $db->lastInsertId() . '&email=' . rawurlencode((string) $email));
    }
    if ($act === 'book_pack') {
        $item = trim((string) ($_POST['item'] ?? ''));
        if ($item === '') {
            $err = 'Choose a package.';
            return true;
        }
        $db->prepare('INSERT INTO dg_orders (user_id, kind, item, amount, status) VALUES (?,?,?,?,?)')
            ->execute([user()['id'] ?? null, 'package', $item, trim((string) ($_POST['amount'] ?? '')), 'new']);
        flash('Package request #' . $db->lastInsertId() . ' is with the desk. Track it with your email.');
        go('track');
    }
    return false;
}

function product_render_page(PDO $db, string $page, array $P, ?array $me): bool
{
    if ($page === 'brief') {
        echo '<section class="section soft"><div class="container"><div class="card" style="max-width:44rem"><h2>Event brief wizard</h2><p>WeddingWire-style intake: date, city, guest count, budget and event type in one request.</p>';
        echo '<form method="post">' . csrf_fields('event_brief');
        echo '<div class="form-row"><input class="input" name="name" required placeholder="Your name"><input class="input" type="email" name="email" required placeholder="Email"></div><br>';
        echo '<div class="form-row"><input class="input" name="phone" placeholder="Phone"><input class="input" type="date" name="event_date"></div><br>';
        echo '<div class="form-row"><select class="input" name="event_type"><option>Wedding</option><option>Birthday</option><option>Corporate meet</option><option>Reception</option><option>Community event</option></select><input class="input" type="number" name="guests" placeholder="Guests"></div><br>';
        echo '<input class="input" name="budget" placeholder="Budget range"><br><br><textarea name="message" placeholder="City, venue preference, food/decor/photo notes"></textarea><br><br><button class="btn" type="submit">Send event brief</button></form></div></div></section>';
        return true;
    }
    if ($page === 'packages') {
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Curated event packages</h2><p>Ready-made wedding, birthday and corporate desks — live DoSwagat Event Packages.</p></div></div><div class="grid-3">';
        foreach (ds_packs() as $s) {
            echo '<div class="price-card"><h3>' . h($s[0]) . '</h3><div class="price">' . h($s[1]) . '</div><p>' . h($s[2]) . '</p>';
            echo '<form method="post">' . csrf_fields('book_pack') . '<input type="hidden" name="item" value="' . h($s[0]) . '"><input type="hidden" name="amount" value="' . h($s[1]) . '"><button class="btn" type="submit">Request package</button></form></div>';
        }
        echo '</div></div></section>';
        return true;
    }
    if ($page === 'track') {
        ds_track($db, 'dg_event_requests', 'Event request');
        return true;
    }
    if ($page === 'partner') {
        echo '<section class="section"><div class="container"><div class="card"><h2>Become a partner</h2><p>Halls, caterers, decorators and photographers list on DoSwagat and take dated briefs.</p><ul class="checklist"><li>Free to list. Verification in 48 hours.</li><li>Guests, date and event type arrive as a structured request.</li><li>Fire NOC, GST and menu sit in your documents desk.</li></ul><p><a class="btn" href="?p=join">List a venue</a></p></div></div></section>';
        return true;
    }
    if ($page === 'promise') {
        echo '<section class="section"><div class="container"><div class="card"><h2>Swagat Promise</h2><p>We do not take venue escrow. We verify listings, pass the brief, and keep a written trail.</p><ul class="checklist"><li>No fake halls — staff review photos and licence.</li><li>You can track every request by email and number.</li><li>Decline or accept from the partner inbox.</li></ul></div></div></section>';
        return true;
    }
    return false;
}

function ds_track(PDO $db, string $table, string $label): void
{
    $email = filter_var($_GET['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $id = (int) ($_GET['ref'] ?? 0);
    echo '<section class="section soft"><div class="container"><div class="card" style="max-width:36rem"><h2>Track ' . h($label) . '</h2>';
    echo '<form method="get"><input type="hidden" name="p" value="track"><div class="form-row"><input class="input" type="email" name="email" required placeholder="Email used on the request" value="' . h((string) ($email ?: '')) . '"><input class="input" name="ref" placeholder="Request #" value="' . ($id ?: '') . '"></div><br><button class="btn" type="submit">Look up</button></form>';
    if ($email && $id) {
        $st = $db->prepare("SELECT * FROM $table WHERE id=? AND email=?");
        $st->execute([$id, $email]);
        $r = $st->fetch();
        echo $r ? '<div class="notice" style="margin-top:18px">#' . (int) $r['id'] . ' · ' . h($r['status']) . ' · ' . h($r['name']) . '</div><p>' . h((string) ($r['message'] ?? $r['event_type'] ?? '')) . '</p>' : '<p class="err" style="margin-top:18px">No request with that email and number.</p>';
    }
    echo '</div></div></section>';
}
