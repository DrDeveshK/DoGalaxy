<?php
declare(strict_types=1);

function product_migrate(PDO $pdo): void
{
    swipe_migrate($pdo);
    $hash = password_hash('SeedPass9', PASSWORD_DEFAULT);
    foreach ([
        ['swipe1@dorishta.local', 'Kavya R.', '1997-04-18', 'Bengaluru', 'Open', 'M.Sc', 'Research', 'Parents in Mysore', 'Researcher. Wants a partner who reads and travels slowly.'],
        ['swipe2@dorishta.local', 'Arjun P.', '1994-09-09', 'Pune', 'Open', 'MBA', 'Operations', 'Joint family', 'Operations manager. Family meeting before a long call.'],
        ['swipe3@dorishta.local', 'Sara M.', '1998-12-01', 'Hyderabad', 'Open', 'B.Arch', 'Architect', 'Families may write', 'Architect. Quiet evenings, no dating-app tone.'],
        ['swipe4@dorishta.local', 'Dev K.', '1993-06-22', 'Delhi', 'Open', 'CA', 'Finance', 'Parents involved', 'CA in practice. Prefers a family introduction.'],
        ['swipe5@dorishta.local', 'Meera T.', '1999-02-14', 'Jaipur', 'Open', 'B.A.', 'Teacher', 'Joint family', 'Teaches. Values honesty and Sunday lunch at home.'],
        ['swipe6@dorishta.local', 'Farhan S.', '1995-08-03', 'Mumbai', 'Open', 'B.Tech', 'Product', 'Family first', 'Product role. Looking for a kind, working partner.'],
        ['swipe7@dorishta.local', 'Ananya L.', '1996-11-27', 'Kolkata', 'Open', 'M.A.', 'Editor', 'Parents in Howrah', 'Editor. Books, trams, and a slow introduction.'],
        ['swipe8@dorishta.local', 'Rohit G.', '1992-01-19', 'Indore', 'Open', 'B.Com', 'Family firm', 'Family meeting first', 'Runs a shop with his father. Direct, not flashy.'],
        ['swipe9@dorishta.local', 'Isha N.', '1997-07-07', 'Chandigarh', 'Open', 'MBBS intern', 'Medicine', 'Families may write first', 'Medicine intern. 21+. Family-first, not casual.'],
        ['swipe10@dorishta.local', 'Vikram D.', '1994-03-30', 'Ahmedabad', 'Open', 'B.E.', 'Manufacturing', 'Joint family', 'Plant engineer. Wants a partner who can meet the family.'],
        ['swipe11@dorishta.local', 'Zoya H.', '1998-05-21', 'Lucknow', 'Open', 'LLB', 'Law', 'Parents involved', 'Junior counsel. Prefers a written interest note.'],
        ['swipe12@dorishta.local', 'Nikhil B.', '1995-10-11', 'Nagpur', 'Open', 'B.Sc', 'Agri-business', 'Family farm', 'Agri-business. Evenings at home, not a chat wall.'],
    ] as $r) {
        $st = $pdo->prepare('SELECT id FROM dg_users WHERE email=?');
        $st->execute([$r[0]]);
        $uid = (int) $st->fetchColumn();
        if (!$uid) {
            $pdo->prepare('INSERT INTO dg_users (email, password_hash, name, role, status) VALUES (?,?,?,?,?)')->execute([$r[0], $hash, $r[1], 'member', 'active']);
            $uid = (int) $pdo->lastInsertId();
        }
        $st = $pdo->prepare('SELECT id FROM dg_profiles WHERE user_id=?');
        $st->execute([$uid]);
        if ($st->fetch()) {
            continue;
        }
        $pdo->prepare('INSERT INTO dg_profiles (user_id, display_name, birth_date, city, community, education, occupation, family_note, about, verify_status, featured) VALUES (?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([$uid, $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7], $r[8], 'verified', 1]);
    }
}

function product_seed(PDO $db): void
{
}

function product_handle_post(PDO $db, string $act, string &$err): bool
{
    if ($act === 'swipe') {
        return swipe_handle($db, 'profile', $err, 'view&id=');
    }
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
    if ($page === 'swipe') {
        $kind = (string) ($_GET['kind'] ?? '');
        $sql = "SELECT * FROM dg_profiles WHERE verify_status IN ('pending','verified')";
        $args = [];
        if ($me) {
            $sql .= ' AND user_id<>?';
            $args[] = $me['id'];
        }
        if ($kind !== '') {
            $sql .= ' AND city=?';
            $args[] = $kind;
        }
        $sql .= ' ORDER BY featured DESC, id ASC';
        $raw = swipe_deck($db, $sql, $args, 'profile', $me);
        $deck = [];
        foreach ($raw as $r) {
            $age = dr_age($r['birth_date'] ?? null);
            if ($age && $age < 21) {
                continue;
            }
            $r['_age'] = $age;
            $deck[] = $r;
        }
        swipe_render([
            'h2' => 'Review profiles',
            'lede' => 'One card at a time. Skip, or open the profile to send a family interest note. Not dating. 21+ only.',
            'notice' => 'DoRishta is family-first. Interest is private — not a public like count.',
            'kind' => $kind,
            'filters' => ['Pune' => 'Pune', 'Jaipur' => 'Jaipur', 'Delhi' => 'Delhi', 'Bengaluru' => 'Bengaluru', 'Mumbai' => 'Mumbai'],
            'side' => ['?p=matches', 'Filter list'],
            'done' => '?p=matches',
            'login_msg' => 'Log in to skip or send interest. You must be 21+.',
            'yes_mark' => '✓',
            'yes_stamp' => 'Interest',
            'no_stamp' => 'Skip',
            'card' => function (array $r) {
                $age = (int) ($r['_age'] ?? 0);
                return swipe_card_html($r, [
                    'title_col' => 'display_name',
                    'kicker' => '21+ · ' . ($r['verify_status'] ?? ''),
                    'where' => trim(($age ? $age . ' · ' : '') . ($r['city'] ?? '') . ' · ' . ($r['occupation'] ?? ''), ' ·'),
                    'tag' => trim(($r['education'] ?? '') . ' · ' . ($r['family_note'] ?? ''), ' ·'),
                    'avatar' => true,
                    'show_likes' => false,
                ]);
            },
        ], $deck, $me);
        return true;
    }
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
