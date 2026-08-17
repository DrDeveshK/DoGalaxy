<?php
declare(strict_types=1);

function product_migrate(PDO $pdo): void
{
}

function product_seed(PDO $db): void
{
}

function product_handle_post(PDO $db, string $act, string &$err): bool
{
    return false;
}

function dr_age(?string $bd): int
{
    if (!$bd) {
        return 0;
    }
    $t = strtotime($bd);
    return $t ? (int) floor((time() - $t) / 31557600) : 0;
}

function product_render_page(PDO $db, string $page, array $P, ?array $me): bool
{
    if ($page === 'matches') {
        $q = trim((string) ($_GET['q'] ?? ''));
        $city = trim((string) ($_GET['city'] ?? ''));
        $education = trim((string) ($_GET['education'] ?? ''));
        $occupation = trim((string) ($_GET['occupation'] ?? ''));
        $community = trim((string) ($_GET['community'] ?? ''));
        $sql = "SELECT * FROM dg_profiles WHERE verify_status IN ('pending','verified')";
        $args = [];
        if ($me) {
            $sql .= ' AND user_id<>?';
            $args[] = $me['id'];
        }
        if ($q !== '') {
            $sql .= ' AND (display_name LIKE ? OR city LIKE ? OR occupation LIKE ?)';
            $args[] = "%$q%";
            $args[] = "%$q%";
            $args[] = "%$q%";
        }
        foreach (['city' => $city, 'education' => $education, 'occupation' => $occupation, 'community' => $community] as $col => $val) {
            if ($val !== '') {
                $sql .= " AND $col LIKE ?";
                $args[] = "%$val%";
            }
        }
        $st = $db->prepare($sql . ' ORDER BY featured DESC, id DESC LIMIT 40');
        $st->execute($args);
        echo '<section class="section"><div class="container"><div class="notice">DoRishta is 21+ and family-first. It is not dating.</div><div class="section-title"><div><h2>Find matches</h2><p>Shaadi-style filters with safety first: city, education, occupation and community.</p></div></div>';
        echo '<form method="get" class="form-row" style="max-width:960px;margin-bottom:24px"><input type="hidden" name="p" value="matches"><input class="input" name="q" value="' . h($q) . '" placeholder="Name / keyword"><input class="input" name="city" value="' . h($city) . '" placeholder="City"><input class="input" name="education" value="' . h($education) . '" placeholder="Education"><input class="input" name="occupation" value="' . h($occupation) . '" placeholder="Occupation"><input class="input" name="community" value="' . h($community) . '" placeholder="Community"><button class="btn" type="submit">Search</button></form><div class="list-grid">';
        foreach ($st as $r) {
            $age = dr_age($r['birth_date'] ?? null);
            if ($age && $age < 21) {
                continue;
            }
            echo '<div class="biz-card"><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['display_name']) . '</a></h3><p>' . h(($age ? $age . ' · ' : '') . ($r['city'] ?? '') . ' · ' . ($r['occupation'] ?? '')) . '</p>';
            echo '<div class="meta"><span>' . h($r['community'] ?? '') . '</span><span class="verified">' . h($r['verify_status']) . '</span></div>';
            echo '<p>' . h((string) ($r['about'] ?? '')) . '</p><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">Expression of interest</a></div>';
        }
        echo '</div></div></section>';
        return true;
    }
    if ($page === 'complete' && $me) {
        echo '<section class="section soft"><div class="container"><div class="dashboard">' . dash_nav('complete') . '<div class="dash-panel"><h2>Complete profile</h2><p>Education, occupation and a family note — the live Complete Profile fields.</p><p><a class="btn" href="?p=dash">Edit full profile</a> <a class="btn light" href="?p=docs">Upload ID (21+)</a></p></div></div></div></section>';
        return true;
    }
    if ($page === 'membership') {
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Membership plans</h2><p>Staff review every ID. No dating-style boosts.</p></div></div><div class="grid-3">';
        foreach (catalog_lines(setting('packages')) as $s) {
            echo '<div class="price-card"><h3>' . h($s[0]) . '</h3><div class="price">' . h($s[1]) . '</div><p>' . h($s[2]) . '</p><form method="post">' . csrf_fields('order') . '<input type="hidden" name="kind" value="program"><input type="hidden" name="item" value="' . h($s[0]) . '"><input type="hidden" name="amount" value="' . h($s[1]) . '"><button class="btn light" type="submit">Request</button></form></div>';
        }
        echo '</div></div></section>';
        return true;
    }
    if ($page === 'safety') {
        echo '<section class="section"><div class="container"><div class="card"><h2>Safety promise</h2><p>DoRishta is for adults 21+ with family knowledge. It is not dating.</p><ul class="checklist"><li>Under-21 accounts are removed.</li><li>Interest notes go to the family inbox, not a chat feed.</li><li>Staff can verify ID and hide a profile.</li><li>Write to hello@dorishta.com to report misuse.</li></ul></div></div></section>';
        return true;
    }
    return false;
}
