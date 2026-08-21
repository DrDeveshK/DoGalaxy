<?php
declare(strict_types=1);

function product_migrate(PDO $pdo): void
{
    swipe_migrate($pdo);
    $oid = swipe_staff_id($pdo);
    $chk = $pdo->prepare('SELECT id FROM dg_services WHERE title=? AND city=?');
    $ins = $pdo->prepare('INSERT INTO dg_services (provider_id, title, category, city, area, rate, experience, about, status, featured) VALUES (?,?,?,?,?,?,?,?,?,?)');
    foreach ([
        ['Leak and tap repair', 'Plumbing', 'Pune', 'Kothrud', '₹499 visit', '8 yrs verified', 'Taps, flush, geyser inlet and visible leak fix.'],
        ['Night plumber desk', 'Plumbing', 'Delhi', 'Dwarka', '₹799 visit', 'Police verified', 'Same-evening leak and blockage visits.'],
        ['Fan and switchboard', 'Electrical', 'Delhi', 'Rohini', '₹399 visit', 'Licence on file', 'Fans, lights, MCBs and a short safety note.'],
        ['Inverter and wiring check', 'Electrical', 'Jaipur', 'Mansarovar', '₹599 visit', '10 yrs', 'Home wiring check before summer load.'],
        ['Door and hinge carpenter', 'Carpentry', 'Pune', 'Baner', '₹449 visit', 'ID verified', 'Doors, hinges, shelves and a small wood fix.'],
        ['Kitchen cabinet repair', 'Carpentry', 'Bengaluru', 'Indiranagar', '₹699 visit', 'Verified', 'Soft-close, handles and a warped shutter.'],
        ['Bathroom deep clean', 'Cleaning', 'Jaipur', 'C-Scheme', '₹1,299', 'Trained pair', 'Two-person bathroom and kitchen clean.'],
        ['Sofa shampoo visit', 'Cleaning', 'Mumbai', 'Andheri', '₹999', 'Kit included', 'Sofa and mattress shampoo, same-day dry.'],
        ['Cockroach and pest visit', 'Pest control', 'Hyderabad', 'Banjara Hills', '₹1,499', 'Licensed spray', 'Kitchen + drain gel, with a 30-day note.'],
        ['Home salon — women', 'Beauty & wellness', 'Delhi', 'Saket', '₹799', 'Kit + ID', 'Cleanup, threading, blow-dry at home.'],
        ['Elder companion half-day', 'Home care', 'Indore', 'Vijay Nagar', '₹700 / half', 'ID verified', 'Meals, walk, medicine reminder. Not a clinic.'],
        ['Physio at home (non-emergency)', 'Home care', 'Pune', 'Aundh', '₹1,100 visit', 'Certified', 'Mobility session at home. Not an ambulance.'],
        ['AC and appliance check', 'Appliance repair', 'Bengaluru', 'Whitefield', '₹499 visit', 'Brand-agnostic', 'Window/split AC service visit.'],
        ['Fridge gas and seal', 'Appliance repair', 'Kolkata', 'Salt Lake', '₹599 visit', 'Verified', 'Cooling complaint, gasket and a quote.'],
    ] as $r) {
        $chk->execute([$r[0], $r[2]]);
        if ($chk->fetch()) {
            continue;
        }
        $ins->execute([$oid, $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], 'live', 1]);
    }
}

function product_seed(PDO $db): void
{
    $rows = [
        'Home physiotherapy' => ['Emergency plumber visit', 'Plumbing', 'Pune', 'Kothrud', '₹499 inspection', '8 years verified', 'Leak repair, tap replacement and bathroom fittings.'],
        'Emergency plumber visit' => ['Emergency plumber visit', 'Plumbing', 'Pune', 'Kothrud', '₹499 inspection', '8 years verified', 'Leak repair, tap replacement and bathroom fittings.'],
        'Day companion for elders' => ['Electrician for fan / switch', 'Electrical', 'Delhi', 'Dwarka', '₹399 inspection', 'Police verified', 'Fan, switchboard, light and wiring diagnosis.'],
        'Electrician for fan / switch' => ['Electrician for fan / switch', 'Electrical', 'Delhi', 'Dwarka', '₹399 inspection', 'Police verified', 'Fan, switchboard, light and wiring diagnosis.'],
        'Yoga at home' => ['Sofa and bathroom deep clean', 'Cleaning', 'Jaipur', 'Mansarovar', '₹1,499', 'Trained team', 'Two-person cleaning visit with checklist.'],
        'Sofa and bathroom deep clean' => ['Sofa and bathroom deep clean', 'Cleaning', 'Jaipur', 'Mansarovar', '₹1,499', 'Trained team', 'Two-person cleaning visit with checklist.'],
        'Rest massage' => ['Elder companion visit', 'Home care', 'Indore', 'Vijay Nagar', '₹1,200 / day', 'ID verified', 'Meals, walks, medicine reminders and company.'],
        'Elder companion visit' => ['Elder companion visit', 'Home care', 'Indore', 'Vijay Nagar', '₹1,200 / day', 'ID verified', 'Meals, walks, medicine reminders and company.'],
    ];
    $st = $db->prepare('UPDATE dg_services SET title=?, category=?, city=?, area=?, rate=?, experience=?, about=? WHERE title=?');
    foreach ($rows as $old => $r) {
        $st->execute([...$r, $old]);
    }
}

function product_handle_post(PDO $db, string $act, string &$err): bool
{
    if ($act === 'swipe') {
        return swipe_handle($db, 'service', $err);
    }
    if ($act === 'book_pack') {
        $item = trim((string) ($_POST['item'] ?? ''));
        if ($item === '') {
            $err = 'Choose a pack.';
            return true;
        }
        $db->prepare('INSERT INTO dg_orders (user_id, kind, item, amount, status) VALUES (?,?,?,?,?)')
            ->execute([user()['id'] ?? null, 'package', $item, trim((string) ($_POST['amount'] ?? '')), 'new']);
        flash('Care pack requested. Not an emergency line.');
        go('track');
    }
    if ($act === 'book_service') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $category = trim((string) ($_POST['category'] ?? ''));
        $msg = trim((string) ($_POST['message'] ?? ''));
        if (!$name || !$email || $category === '' || $msg === '') {
            $err = 'Name, email, category and service notes are required.';
            return true;
        }
        $st = $db->prepare('SELECT id FROM dg_services WHERE category=? ORDER BY featured DESC, id LIMIT 1');
        $st->execute([$category]);
        $sid = (int) $st->fetchColumn();
        if (!$sid) {
            $sid = (int) $db->query('SELECT id FROM dg_services ORDER BY id LIMIT 1')->fetchColumn();
        }
        $db->prepare('INSERT INTO dg_service_requests (service_id, name, email, phone, when_date, slot, area, address, message) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([$sid, $name, (string) $email, trim((string) ($_POST['phone'] ?? '')), trim((string) ($_POST['when_date'] ?? '')), trim((string) ($_POST['slot'] ?? '')), trim((string) ($_POST['area'] ?? '')), trim((string) ($_POST['address'] ?? '')), $category . ': ' . $msg]);
        flash('Service request sent. Track it with #' . $db->lastInsertId() . '.');
        go('track&ref=' . $db->lastInsertId() . '&email=' . rawurlencode((string) $email));
    }
    if ($act === 'care_brief') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $msg = trim((string) ($_POST['message'] ?? ''));
        if (!$name || !$email || $msg === '') {
            $err = 'Name, email and brief are required.';
            return true;
        }
        $db->prepare('INSERT INTO dg_enquiries (product, user_id, name, email, phone, intent, message) VALUES (?,?,?,?,?,?,?)')
            ->execute(['doaaram', user()['id'] ?? null, $name, (string) $email, trim((string) ($_POST['phone'] ?? '')), 'care', $msg]);
        flash('Family care desk has the brief.');
        go('care&sent=1');
    }
    return false;
}

function product_render_page(PDO $db, string $page, array $P, ?array $me): bool
{
    if ($page === 'swipe') {
        $kind = (string) ($_GET['kind'] ?? '');
        $sql = "SELECT * FROM dg_services WHERE status IN ('pending','live','verified')";
        $args = [];
        if ($kind !== '') {
            $sql .= ' AND category=?';
            $args[] = $kind;
        }
        $sql .= ' ORDER BY featured DESC, id ASC';
        $deck = swipe_deck($db, $sql, $args, 'service', $me);
        swipe_render([
            'h2' => 'Shortlist a pro',
            'lede' => 'Right = shortlist this visit. Then book a slot. Not an emergency line.',
            'kind' => $kind,
            'filters' => ['Plumbing' => 'Plumbing', 'Electrical' => 'Electrical', 'Carpentry' => 'Carpentry', 'Cleaning' => 'Cleaning', 'Home care' => 'Home care', 'Beauty & wellness' => 'Beauty'],
            'side' => ['?p=shortlist', 'My shortlist'],
            'done' => '?p=shortlist',
            'yes_stamp' => 'Shortlist',
            'card' => function (array $r) use ($db) {
                return swipe_card_html($r, [
                    'title_col' => 'title',
                    'kicker' => ($r['category'] ?? 'Service') . ' · ' . ($r['rate'] ?? ''),
                    'where' => trim(($r['area'] ?? '') . ' · ' . ($r['city'] ?? ''), ' ·'),
                    'tag' => (string) ($r['about'] ?? ''),
                    'photo' => swipe_photo_src((string) ($r['city'] ?? '')),
                    'likes' => swipe_like_n($db, 'service', (int) $r['id']),
                    'show_likes' => true,
                    'like_label' => 'shortlisted this',
                ]);
            },
        ], $deck, $me);
        return true;
    }
    if ($page === 'shortlist') {
        swipe_wants($db, 'service', 'dg_services', 'title', function (array $r) {
            return '<div class="biz-card"><div class="meta"><span>' . h($r['category'] ?? '') . '</span><span>' . h($r['city'] ?? '') . '</span></div><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['title']) . '</a></h3><p>' . h($r['rate'] ?? '') . '</p><p><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">Book visit</a></p></div>';
        });
        return true;
    }
    if ($page === 'categories') {
        $cats = [
            ['Plumbing', 'Leaks, taps, flush, bathroom fittings', 'from ₹399'],
            ['Electrical', 'Fans, lights, switchboards, wiring checks', 'from ₹399'],
            ['Carpentry', 'Door, furniture and fixture repair', 'from ₹499'],
            ['Appliance repair', 'AC, washing machine, RO, geyser diagnosis', 'from ₹499'],
            ['Cleaning', 'Bathroom, kitchen, sofa and full-home clean', 'from ₹799'],
            ['Pest control', 'Cockroach, termite and mosquito treatment', 'quote'],
            ['Beauty & wellness', 'Salon, massage and wellness at home', 'from ₹699'],
            ['Home care', 'Elder companion, nurse and physio visit', 'quote'],
        ];
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Service categories</h2><p>UrbanCompany-style discovery for home services, handyman repairs, cleaning, wellness and care. Pick a category, slot and locality.</p></div></div><div class="grid-4">';
        foreach ($cats as $c) {
            echo '<div class="feature"><h3>' . h($c[0]) . '</h3><p>' . h($c[1]) . '</p><div class="price" style="font-size:22px">' . h($c[2]) . '</div><a class="btn light" href="?p=categories&cat=' . urlencode($c[0]) . '#book">Book</a></div>';
        }
        $sel = trim((string) ($_GET['cat'] ?? 'Plumbing'));
        echo '</div><div class="card" id="book" style="margin-top:28px"><h3>Book a service</h3><form method="post">' . csrf_fields('book_service');
        echo '<div class="form-row"><input class="input" name="name" required placeholder="Your name"><input class="input" type="email" name="email" required placeholder="Email"></div><br>';
        echo '<div class="form-row"><select class="input" name="category">';
        foreach ($cats as $c) {
            echo '<option' . ($sel === $c[0] ? ' selected' : '') . '>' . h($c[0]) . '</option>';
        }
        echo '</select><input class="input" name="phone" placeholder="Phone"></div><br>';
        echo '<div class="form-row"><input class="input" type="date" name="when_date"><input class="input" name="slot" placeholder="Preferred slot e.g. 10am-1pm"></div><br>';
        echo '<div class="form-row"><input class="input" name="area" placeholder="Area / locality"><input class="input" name="address" placeholder="Address / landmark"></div><br>';
        echo '<textarea name="message" required placeholder="What needs to be fixed or done?"></textarea><br><br><button class="btn" type="submit">Send service request</button></form></div></div></section>';
        return true;
    }
    if ($page === 'packs') {
        $packs = [
            ['Handyman visit pack', '₹1,999', 'Plumbing, electrical or carpentry visit request.'],
            ['Home deep clean', '₹2,999', 'Kitchen, bathroom, sofa or full-home cleaning request.'],
            ['Verified pro listing', '₹999', 'Provider profile, ID and skill review.'],
        ];
        echo '<section class="section"><div class="container"><div class="notice">DoAaram is not a medical emergency line. Call local emergency services if someone is in danger.</div><div class="section-title"><div><h2>Service packs</h2><p>Transparent starting points for home services and provider onboarding.</p></div></div><div class="grid-3">';
        foreach ($packs as $s) {
            echo '<div class="price-card"><h3>' . h($s[0]) . '</h3><div class="price">' . h($s[1]) . '</div><p>' . h($s[2]) . '</p><form method="post">' . csrf_fields('book_pack') . '<input type="hidden" name="item" value="' . h($s[0]) . '"><input type="hidden" name="amount" value="' . h($s[1]) . '"><button class="btn" type="submit">Request</button></form></div>';
        }
        echo '</div></div></section>';
        return true;
    }
    if ($page === 'care') {
        echo '<section class="section soft"><div class="container"><div class="card" style="max-width:40rem"><h2>Family care desk</h2><p>For elder care, nurse, physio or companion needs. Staff match a listed provider.</p>';
        echo '<form method="post">' . csrf_fields('care_brief');
        echo '<div class="form-row"><input class="input" name="name" required placeholder="Your name"><input class="input" type="email" name="email" required placeholder="Email"></div><br>';
        echo '<input class="input" name="phone" placeholder="Phone"><br><br><textarea name="message" required placeholder="Who needs care, city, days, mobility notes"></textarea><br><br><button class="btn" type="submit">Send brief</button></form></div></div></section>';
        return true;
    }
    if ($page === 'track') {
        $email = filter_var($_GET['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $id = (int) ($_GET['ref'] ?? 0);
        echo '<section class="section soft"><div class="container"><div class="card" style="max-width:36rem"><h2>Track booking</h2><form method="get"><input type="hidden" name="p" value="track"><div class="form-row"><input class="input" type="email" name="email" required value="' . h((string) ($email ?: '')) . '" placeholder="Email"><input class="input" name="ref" placeholder="Request #" value="' . ($id ?: '') . '"></div><br><button class="btn" type="submit">Look up</button></form>';
        if ($email && $id) {
            $st = $db->prepare('SELECT * FROM dg_service_requests WHERE id=? AND email=?');
            $st->execute([$id, $email]);
            $r = $st->fetch();
            echo $r ? '<div class="notice" style="margin-top:18px">#' . (int) $r['id'] . ' · ' . h($r['status']) . ' · ' . h($r['slot'] ?? '') . ' · ' . h($r['area'] ?? '') . '</div>' : '<p class="err" style="margin-top:18px">No booking with that email and number.</p>';
        }
        echo '</div></div></section>';
        return true;
    }
    return false;
}
