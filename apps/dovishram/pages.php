<?php
declare(strict_types=1);

function product_migrate(PDO $pdo): void
{
}

function product_seed(PDO $db): void
{
    $rows = [
        'Lakeview homestay' => ['Bhopal Lake Road', 'WiFi, breakfast, family rooms'],
        'Forest wellness retreat' => ['Rishikesh outskirts', 'Yoga, quiet hours, veg meals'],
        'Business hotel' => ['Indore AB Road', 'Desk, parking, early breakfast'],
        'Hill guest house' => ['Shimla Mall Road', 'Heater, valley view, kitchen access'],
    ];
    $st = $db->prepare('UPDATE dg_stays SET area=?, amenities=? WHERE title=?');
    foreach ($rows as $title => $r) {
        $st->execute([$r[0], $r[1], $title]);
    }
}

function product_handle_post(PDO $db, string $act, string &$err): bool
{
    if ($act === 'book_pack') {
        $item = trim((string) ($_POST['item'] ?? ''));
        if ($item === '') {
            $err = 'Choose a package.';
            return true;
        }
        $db->prepare('INSERT INTO dg_orders (user_id, kind, item, amount, status) VALUES (?,?,?,?,?)')
            ->execute([user()['id'] ?? null, 'package', $item, trim((string) ($_POST['amount'] ?? '')), 'new']);
        flash('Rest package request received. Track with your email.');
        go('track');
    }
    return false;
}

function product_render_page(PDO $db, string $page, array $P, ?array $me): bool
{
    if ($page === 'find') {
        $type = trim((string) ($_GET['type'] ?? ''));
        $city = trim((string) ($_GET['city'] ?? ''));
        $guests = (int) ($_GET['guests'] ?? 0);
        $sql = "SELECT * FROM dg_stays WHERE verify_status IN ('pending','verified')";
        $args = [];
        if ($type !== '') {
            $sql .= ' AND stay_type=?';
            $args[] = $type;
        }
        if ($city !== '') {
            $sql .= ' AND (city LIKE ? OR area LIKE ?)';
            $args[] = "%$city%";
            $args[] = "%$city%";
        }
        if ($guests > 0) {
            $sql .= ' AND (max_guests IS NULL OR max_guests="" OR CAST(max_guests AS INTEGER)>=?)';
            $args[] = $guests;
        }
        $st = $db->prepare($sql . ' ORDER BY featured DESC, id DESC LIMIT 40');
        $st->execute($args);
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Find stays</h2><p>Airbnb-style filters for type, locality, guests, price and amenities.</p></div></div>';
        echo '<form method="get" class="form-row" style="max-width:860px;margin-bottom:24px"><input type="hidden" name="p" value="find"><select class="input" name="type"><option value="">Any type</option>';
        foreach (['Homestay', 'Hotel', 'Resort', 'Guest house', 'Wellness retreat'] as $t) {
            echo '<option' . ($type === $t ? ' selected' : '') . '>' . h($t) . '</option>';
        }
        echo '</select><input class="input" name="city" value="' . h($city) . '" placeholder="City or locality"><input class="input" type="number" name="guests" value="' . ($guests ?: '') . '" placeholder="Guests"><button class="btn" type="submit">Search</button></form><div class="list-grid">';
        foreach ($st as $r) {
            echo '<div class="biz-card"><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['title']) . '</a></h3><p>' . h(($r['stay_type'] ?? '') . ' · ' . ($r['area'] ?? $r['city'] ?? '')) . '</p><div class="meta"><span>' . h($r['price_night'] ?? '') . '</span><span>' . h(($r['max_guests'] ?? '') . ' guests') . '</span></div><p class="muted">' . h($r['amenities'] ?? '') . '</p><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">Request stay</a></div>';
        }
        echo '</div></div></section>';
        return true;
    }
    if ($page === 'packages') {
        $packs = [
            ['Weekend rest', '₹1,999', 'Curated 2-night stay help for a family.'],
            ['Host onboarding', '₹2,499', 'Photos, house rules and calendar setup.'],
            ['Wellness weekend', '₹3,499', 'Retreat-style stay with quiet hours.'],
        ];
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Rest packages</h2><p>Same host/guest desk as live DoVishram, with named weekend packs.</p></div></div><div class="grid-3">';
        foreach ($packs as $s) {
            echo '<div class="price-card"><h3>' . h($s[0]) . '</h3><div class="price">' . h($s[1]) . '</div><p>' . h($s[2]) . '</p><form method="post">' . csrf_fields('book_pack') . '<input type="hidden" name="item" value="' . h($s[0]) . '"><input type="hidden" name="amount" value="' . h($s[1]) . '"><button class="btn" type="submit">Request</button></form></div>';
        }
        echo '</div></div></section>';
        return true;
    }
    if ($page === 'track') {
        $email = filter_var($_GET['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $id = (int) ($_GET['ref'] ?? 0);
        echo '<section class="section soft"><div class="container"><div class="card" style="max-width:36rem"><h2>Track booking</h2><form method="get"><input type="hidden" name="p" value="track"><div class="form-row"><input class="input" type="email" name="email" required value="' . h((string) ($email ?: '')) . '" placeholder="Email"><input class="input" name="ref" placeholder="Request #" value="' . ($id ?: '') . '"></div><br><button class="btn" type="submit">Look up</button></form>';
        if ($email && $id) {
            $st = $db->prepare('SELECT * FROM dg_stay_requests WHERE id=? AND email=?');
            $st->execute([$id, $email]);
            $r = $st->fetch();
            echo $r ? '<div class="notice" style="margin-top:18px">#' . (int) $r['id'] . ' · ' . h($r['status']) . ' · ' . h($r['checkin'] ?? '') . ' → ' . h($r['checkout'] ?? '') . '</div>' : '<p class="err" style="margin-top:18px">No booking with that email and number.</p>';
        }
        echo '</div></div></section>';
        return true;
    }
    return false;
}
