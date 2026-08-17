<?php
declare(strict_types=1);

function product_migrate(PDO $pdo): void
{
}

function product_seed(PDO $db): void
{
    $rows = [
        'Cold-pressed mustard oil 1L' => ['Cold-pressed mustard oil 1L', 'Kirana', 'Jaipur', 'Mansarovar', '8am-9pm', '₹280', 'Local mill, weekly batch. Pickup today or area delivery.'],
        'Handloom stole' => ['Morning milk subscription', 'Daily essentials', 'Jaipur', 'Vaishali Nagar', '6am-10am', '₹68 / L', 'Neighbourhood dairy route with daily doorstep delivery.'],
        'Morning milk subscription' => ['Morning milk subscription', 'Daily essentials', 'Jaipur', 'Vaishali Nagar', '6am-10am', '₹68 / L', 'Neighbourhood dairy route with daily doorstep delivery.'],
        'MS angle 40x40' => ['Vegetable basket', 'Fresh produce', 'Lucknow', 'Indira Nagar', '7am-12pm', '₹299', 'Seasonal sabzi basket from a local vendor, morning delivery.'],
        'Vegetable basket' => ['Vegetable basket', 'Fresh produce', 'Lucknow', 'Indira Nagar', '7am-12pm', '₹299', 'Seasonal sabzi basket from a local vendor, morning delivery.'],
        'Namkeen gift box' => ['Namkeen gift box', 'Food', 'Indore', 'Sarafa', '10am-10pm', '₹350', 'Festival pack, 500g. Local pickup and same-city delivery.'],
    ];
    $st = $db->prepare('UPDATE dg_listings SET title=?, category=?, city=?, area=?, store_hours=?, price=?, about=? WHERE title=?');
    foreach ($rows as $old => $r) {
        $st->execute([...$r, $old]);
    }
}

function product_handle_post(PDO $db, string $act, string &$err): bool
{
    return false;
}

function product_render_page(PDO $db, string $page, array $P, ?array $me): bool
{
    if ($page === 'shop') {
        $cat = trim((string) ($_GET['cat'] ?? ''));
        $q = trim((string) ($_GET['q'] ?? ''));
        $sql = "SELECT * FROM dg_listings WHERE status IN ('live','pending','verified')";
        $args = [];
        if ($cat !== '') {
            $sql .= ' AND category=?';
            $args[] = $cat;
        }
        if ($q !== '') {
            $sql .= ' AND (title LIKE ? OR city LIKE ? OR area LIKE ?)';
            $args[] = "%$q%";
            $args[] = "%$q%";
            $args[] = "%$q%";
        }
        $st = $db->prepare($sql . ' ORDER BY featured DESC, id DESC LIMIT 40');
        $st->execute($args);
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Browse neighbourhood shops</h2><p>Find kirana, essentials, food, produce and retail sellers near your locality. Request now; the shop confirms pickup or local delivery.</p></div></div>';
        echo '<div class="grid-3" style="margin-bottom:22px"><div class="feature"><h3>Local-first</h3><p>City and area discovery before national listings.</p></div><div class="feature"><h3>Request-based</h3><p>No blind checkout. Seller confirms stock, time and delivery.</p></div><div class="feature"><h3>Retail ready</h3><p>Kirana, food, produce, pharmacy-style and daily essentials.</p></div></div>';
        echo '<form class="form-row" method="get" style="max-width:760px;margin-bottom:18px"><input type="hidden" name="p" value="shop"><input class="input" name="q" value="' . h($q) . '" placeholder="Search item, city or locality"><button class="btn" type="submit">Find nearby</button></form><p>';
        foreach (['Kirana', 'Daily essentials', 'Fresh produce', 'Food', 'Pharmacy', 'Fashion', 'Hardware', 'Other'] as $c) {
            echo '<a class="pill" href="?p=shop&cat=' . urlencode($c) . '">' . h($c) . '</a> ';
        }
        echo '</p><div class="list-grid">';
        foreach ($st as $r) {
            $where = trim((string) (($r['area'] ?? '') ? $r['area'] . ', ' . ($r['city'] ?? '') : ($r['city'] ?? '')));
            echo '<div class="biz-card"><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['title']) . '</a></h3><p>' . h(($r['category'] ?? '') . ' · ' . $where) . '</p><div class="price" style="font-size:24px">' . h($r['price'] ?? '') . '</div><p class="muted">Hours: ' . h($r['store_hours'] ?? 'Call to confirm') . ' · pickup / local delivery after seller confirmation.</p><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">Request order</a></div>';
        }
        echo '</div></div></section>';
        return true;
    }
    if ($page === 'track') {
        $email = filter_var($_GET['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $id = (int) ($_GET['ref'] ?? 0);
        echo '<section class="section soft"><div class="container"><div class="card" style="max-width:36rem"><h2>Track order request</h2><form method="get"><input type="hidden" name="p" value="track"><div class="form-row"><input class="input" type="email" name="email" required value="' . h((string) ($email ?: '')) . '" placeholder="Email"><input class="input" name="ref" placeholder="Request #" value="' . ($id ?: '') . '"></div><br><button class="btn" type="submit">Look up</button></form>';
        if ($email && $id) {
            $st = $db->prepare('SELECT * FROM dg_order_requests WHERE id=? AND email=?');
            $st->execute([$id, $email]);
            $r = $st->fetch();
            echo $r ? '<div class="notice" style="margin-top:18px">#' . (int) $r['id'] . ' · ' . h($r['status']) . ' · qty ' . h($r['qty'] ?? '') . '</div>' : '<p class="err" style="margin-top:18px">No order with that email and number.</p>';
        }
        echo '</div></div></section>';
        return true;
    }
    return false;
}
