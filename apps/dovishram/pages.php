<?php
declare(strict_types=1);

function dv_kinds(): array
{
    return [
        'destination' => ['Destiny location', '🗺'],
        'hotspot' => ['Hotspot', '🔥'],
        'place' => ['Place', '📍'],
        'hotel' => ['Hotel', '🏨'],
    ];
}

function dv_kind_label(string $kind): string
{
    return dv_kinds()[$kind][0] ?? $kind;
}

function product_migrate(PDO $pdo): void
{
    $sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    if ($sqlite) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS dg_places (id INTEGER PRIMARY KEY AUTOINCREMENT, kind TEXT NOT NULL, title TEXT NOT NULL, city TEXT NOT NULL, area TEXT, tagline TEXT, about TEXT, photo TEXT, stay_id INTEGER, featured INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS uq_place_title_city ON dg_places(title, city)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS dg_place_likes (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, place_id INTEGER NOT NULL, verdict TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, place_id))');
        ensure_columns($pdo, 'dg_places', ['photo']);
    } else {
        $pdo->exec('CREATE TABLE IF NOT EXISTS `dg_places` (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, kind VARCHAR(32) NOT NULL, title VARCHAR(180) NOT NULL, city VARCHAR(80) NOT NULL, area VARCHAR(120) NULL, tagline VARCHAR(190) NULL, about TEXT NULL, photo VARCHAR(190) NULL, stay_id BIGINT UNSIGNED NULL, featured TINYINT NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_place_title_city (title, city), KEY ix_place_kind (kind, city)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        $st = $pdo->query("SHOW COLUMNS FROM dg_places LIKE 'photo'");
        if (!$st->fetch()) {
            $pdo->exec('ALTER TABLE dg_places ADD COLUMN photo VARCHAR(190) NULL');
        }
        $pdo->exec('CREATE TABLE IF NOT EXISTS `dg_place_likes` (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, place_id BIGINT UNSIGNED NOT NULL, verdict VARCHAR(12) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_place_like (user_id, place_id), KEY ix_place_liked (place_id, verdict)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    }
    dv_insert_catalog($pdo);
    dv_sync_hotels($pdo);
    dv_backfill_photos($pdo);
}

function dv_catalog(): array
{
    return [
        ['destination', 'Udaipur lake city', 'Udaipur', 'Pichola belt', 'Lakes, palaces and slow evenings.', 'A destiny location for couples and families who want water, heritage walks and rooftop sunsets.', 'udaipur.jpg', 1],
        ['destination', 'Rishikesh Ganga belt', 'Rishikesh', 'Tapovan', 'River, yoga and quiet hills.', 'A destiny location for rest after work — Ganga aarti, trails and early nights.', 'rishikesh.jpg', 1],
        ['destination', 'Old Goa coast', 'Goa', 'Fontainhas', 'Churches, cafes and a sea breeze.', 'A destiny location for a short coastal pause without the party strip.', 'goa.jpg', 1],
        ['destination', 'Darjeeling hills', 'Darjeeling', 'Mall Road', 'Tea gardens and a toy-train town.', 'A destiny location for mist, chai and Kanchenjunga mornings.', 'darjeeling.jpg', 1],
        ['destination', 'Munnar tea hills', 'Munnar', 'Tea estates', 'Green slopes and cool nights.', 'A destiny location for a slow Kerala hill week.', 'munnar.jpg', 1],
        ['destination', 'Leh high desert', 'Leh', 'Leh town', 'Monasteries and thin blue air.', 'A destiny location if you want mountains more than beaches.', 'leh.jpg', 1],
        ['destination', 'Andaman sea islands', 'Havelock', 'Radhanagar', 'Clear water and quiet sand.', 'A destiny location for a first island holiday.', 'andaman.jpg', 0],
        ['destination', 'Coorg coffee country', 'Madikeri', 'Coorg', 'Plantations, mist and homestays.', 'A destiny location for rain, coffee and forest walks.', 'coorg.jpg', 0],
        ['destination', 'Spiti cold desert', 'Kaza', 'Spiti', 'Raw mountains and monastery villages.', 'A destiny location for those who like the road more than the resort.', 'spiti.jpg', 0],
        ['destination', 'Pondicherry French quarter', 'Puducherry', 'White Town', 'Promenade, bicycles and cafe mornings.', 'A destiny location for a slow seaside town, not a mega-beach.', 'pondy.jpg', 0],
        ['destination', 'Shimla ridge', 'Shimla', 'The Ridge', 'Colonial hill station and pine air.', 'A destiny location for a first Himalayan family trip.', 'shimla.jpg', 0],
        ['destination', 'Alleppey backwaters', 'Alappuzha', 'Punnamada', 'Houseboats through paddy and canals.', 'A destiny location for a night on the water.', 'alleppey.jpg', 1],
        ['destination', 'Jaisalmer desert', 'Jaisalmer', 'Sam dunes', 'Fort town and a night under stars.', 'A destiny location for sand, folk music and a golden fort.', 'jaisalmer.jpg', 0],
        ['destination', 'McLeod Ganj hills', 'Dharamshala', 'McLeod Ganj', 'Monastery town above the plains.', 'A destiny location for walks, momos and mountain light.', 'mcleodganj.jpg', 0],
        ['destination', 'Kodaikanal lake town', 'Kodaikanal', 'Coaker’s Walk', 'A south-Indian hill lake and cool evenings.', 'A destiny location when you want hills without the north-India rush.', 'kodaikanal.jpg', 0],
        ['destination', 'Ooty Nilgiri hills', 'Ooty', 'Botanical Gardens', 'Toy train, tea and eucalyptus.', 'A destiny location for a classic south-hill holiday.', 'ooty.jpg', 0],
        ['destination', 'Gokarna beach belt', 'Gokarna', 'Om Beach', 'Temples, cliffs and quieter sand than Goa.', 'A destiny location for a beach that still feels like a town.', 'gokarna.jpg', 0],
        ['destination', 'Gulmarg meadows', 'Gulmarg', 'Gondola', 'High meadows and snow in season.', 'A destiny location for a Kashmir hill pause.', 'gulmarg.jpg', 0],
        ['hotspot', 'Varanasi ghats', 'Varanasi', 'Dashashwamedh', 'Dawn light on the river steps.', 'A hotspot people return to — aarti, boats and the old city lanes.', 'varanasi.jpg', 1],
        ['hotspot', 'Hauz Khas Village', 'Delhi', 'Hauz Khas', 'Lake ruins and late cafes.', 'A city hotspot for a walk, a meal and people-watching.', 'hauzkhas.jpg', 0],
        ['hotspot', 'Mall Road Manali', 'Manali', 'Mall Road', 'Hills, apple carts and cool air.', 'A hill hotspot for a first trip north.', 'manali.jpg', 0],
        ['hotspot', 'Marine Drive', 'Mumbai', 'Queen’s Necklace', 'The bay walk after work.', 'A hotspot that still feels like the city’s living room.', 'marinedrive.jpg', 1],
        ['hotspot', 'Connaught Place', 'Delhi', 'Inner Circle', 'Colonades, food and a city meeting point.', 'A hotspot if Delhi is on the list.', 'cp.jpg', 0],
        ['hotspot', 'Mussoorie Mall', 'Mussoorie', 'Mall Road', 'Hill shops and a valley view.', 'A hotspot for a short Dehradun-side break.', 'mussoorie.jpg', 0],
        ['hotspot', 'Baga beach strip', 'Goa', 'Baga', 'Shacks, sunset and a busy shore.', 'A hotspot if you want the classic Goa evening.', 'baga.jpg', 0],
        ['hotspot', 'Chandni Chowk', 'Delhi', 'Old Delhi', 'Lanes, food and a packed old city.', 'A hotspot for one loud, delicious morning.', 'chandnichowk.jpg', 0],
        ['hotspot', 'Colaba causeway', 'Mumbai', 'Colaba', 'Harbour, stalls and the Gateway nearby.', 'A hotspot for a first Mumbai walk.', 'colaba.jpg', 0],
        ['hotspot', 'Park Street', 'Kolkata', 'Park Street', 'Lights, food and a city night out.', 'A hotspot that still feels like Calcutta after dark.', 'parkstreet.jpg', 0],
        ['hotspot', 'Fort Kochi jetty', 'Kochi', 'Fort Kochi', 'Chinese nets and a sea breeze.', 'A hotspot for a Kerala evening walk.', 'kochi.jpg', 0],
        ['hotspot', 'Pushkar lake town', 'Pushkar', 'Lake side', 'Temples, ghats and a desert town.', 'A hotspot for a Rajasthan pause that is not a palace hotel.', 'pushkar.jpg', 0],
        ['hotspot', 'Lonavala ghat stop', 'Lonavala', 'Mumbai–Pune ghat', 'Mist, fudge shops and a weekend drive.', 'A hotspot for a short Western Ghat reset.', 'lonavala.jpg', 0],
        ['place', 'Taj Mahal', 'Agra', 'Taj Ganj', 'The monument people still plan around.', 'A place on almost every first-India list — go at opening for the light.', 'tajmahal.jpg', 1],
        ['place', 'Mehrangarh Fort', 'Jodhpur', 'Fort road', 'Blue city from the ramparts.', 'A place for a half-day of courtyards, views and wind.', 'mehrangarh.jpg', 0],
        ['place', 'Cubbon Park', 'Bengaluru', 'Central', 'Shade, walks and a city pause.', 'A local place to reset between meetings.', 'cubbon.jpg', 0],
        ['place', 'India Gate', 'Delhi', 'Rajpath', 'Lawn, evening light and a city landmark.', 'A place you walk even if you live in Delhi.', 'indiagate.jpg', 0],
        ['place', 'Gateway of India', 'Mumbai', 'Apollo Bunder', 'Harbour arch and boat horns.', 'A place for a first Mumbai photograph.', 'gateway.jpg', 1],
        ['place', 'Golden Temple', 'Amritsar', 'Harmandir Sahib', 'Gold, water and langar.', 'A place people keep on the list for the stillness as much as the shine.', 'goldentemple.jpg', 1],
        ['place', 'Hawa Mahal', 'Jaipur', 'Badi Choupad', 'Pink screens and a street-level palace.', 'A place for a short Jaipur stop between bazaars.', 'hawamahal.jpg', 0],
        ['place', 'Charminar', 'Hyderabad', 'Old city', 'Four minarets and a packed old market.', 'A place for pearls, biryani and a night photograph.', 'charminar.jpg', 0],
        ['place', 'Victoria Memorial', 'Kolkata', 'Maidan', 'White marble and a city garden.', 'A place for a Kolkata morning walk.', 'victoria.jpg', 0],
        ['place', 'Qutub Minar', 'Delhi', 'Mehrauli', 'A tall tower in south Delhi stone.', 'A place if you like ruins without a long drive.', 'qutub.jpg', 0],
        ['place', 'Mysore Palace', 'Mysore', 'City centre', 'Lit palace and a Sunday-night crowd.', 'A place for a south-India palace evening.', 'mysore.jpg', 0],
        ['place', 'Ajanta Caves', 'Aurangabad', 'Ajanta', 'Painted Buddhist caves in a gorge.', 'A place for a day trip if you like old stone and quiet.', 'ajanta.jpg', 0],
        ['place', 'Red Fort', 'Delhi', 'Chandni Chowk', 'Red sandstone and the old city wall.', 'A place to pair with a Chandni Chowk walk.', 'redfort.jpg', 0],
        ['place', 'Meenakshi Temple', 'Madurai', 'Temple town', 'Gopurams and a living temple city.', 'A place if south-India temples are on your list.', 'meenakshi.jpg', 0],
        ['place', 'Lotus Temple', 'Delhi', 'Kalkaji', 'A quiet Baháʼí hall in the city.', 'A place for twenty minutes of hush.', 'lotus.jpg', 0],
        ['place', 'Khajuraho temples', 'Khajuraho', 'Western group', 'Sculpted stone in a small town.', 'A place for art, not a checklist rush.', 'khajuraho.jpg', 0],
        ['place', 'Hampi ruins', 'Hampi', 'Vijayanagara', 'Boulders, temples and a river.', 'A place people stay two nights, not two hours.', 'hampi.jpg', 1],
        ['hotel', 'Lake Palace stay', 'Udaipur', 'Lake Pichola', 'A palace-hotel on the water.', 'Swipe right if a lake hotel is the trip.', 'lakepalace.jpg', 1],
        ['hotel', 'Jaipur courtyard haveli', 'Jaipur', 'Old city', 'A haveli courtyard and a pink-city roof.', 'A hotel card for a heritage stay, not a chain tower.', 'jaipur.jpg', 0],
        ['hotel', 'Nainital lake lodge', 'Nainital', 'Mallital', 'Pine, lake and a heater in the room.', 'A hotel card for a hill weekend.', 'nainital.jpg', 0],
        ['hotel', 'Alleppey houseboat night', 'Alappuzha', 'Backwaters', 'A kettuvallam with a cook on board.', 'A hotel-on-water if the backwaters are the point.', 'houseboat.jpg', 1],
        ['hotel', 'Jaisalmer desert camp', 'Jaisalmer', 'Sam', 'Tents, folk music and a dune sunrise.', 'A hotel card for one desert night.', 'desert.jpg', 0],
        ['hotel', 'Pune city pause rooms', 'Pune', 'Koregaon Park', 'Clean rooms for a work-and-rest stop.', 'A hotel card when the trip is a city pause.', 'pune.jpg', 0],
        ['hotel', 'Rishikesh river lodge', 'Rishikesh', 'Tapovan', 'Ganga below and quiet hours after nine.', 'A hotel card for rest, not a party hostel.', 'rishikesh.jpg', 0],
        ['hotel', 'Munnar tea bungalow', 'Munnar', 'Estate road', 'A bungalow in the tea.', 'A hotel card for a Kerala hill stay.', 'munnar.jpg', 0],
        ['hotel', 'Goa beach hut', 'Goa', 'South Goa', 'Sand, a hut and a late breakfast.', 'A hotel card if the sea is the stay.', 'goa.jpg', 0],
        ['hotel', 'Leh monastery-view stay', 'Leh', 'Changspa', 'A simple room facing the hills.', 'A hotel card for acclimatising, not luxury.', 'leh.jpg', 0],
        ['hotel', 'Darjeeling tea-bungalow', 'Darjeeling', 'Lebong', 'Mist, tea and a Kanchenjunga window.', 'A hotel card for a hill week.', 'darjeeling.jpg', 0],
        ['hotel', 'Kaziranga jungle lodge', 'Kaziranga', 'Kohora', 'Grasslands and an early jeep.', 'A hotel card if wildlife is the reason to go.', 'kaziranga.jpg', 0],
    ];
}

function dv_photo_by_city(): array
{
    return [
        'Udaipur' => 'udaipur.jpg', 'Rishikesh' => 'rishikesh.jpg', 'Goa' => 'goa.jpg',
        'Varanasi' => 'varanasi.jpg', 'Delhi' => 'hauzkhas.jpg', 'Manali' => 'manali.jpg',
        'Agra' => 'tajmahal.jpg', 'Jodhpur' => 'mehrangarh.jpg', 'Bengaluru' => 'cubbon.jpg',
        'Jaipur' => 'jaipur.jpg', 'Nainital' => 'nainital.jpg', 'Pune' => 'pune.jpg',
        'Darjeeling' => 'darjeeling.jpg', 'Munnar' => 'munnar.jpg', 'Leh' => 'leh.jpg',
        'Havelock' => 'andaman.jpg', 'Madikeri' => 'coorg.jpg', 'Kaza' => 'spiti.jpg',
        'Puducherry' => 'pondy.jpg', 'Shimla' => 'shimla.jpg', 'Alappuzha' => 'alleppey.jpg',
        'Jaisalmer' => 'jaisalmer.jpg', 'Dharamshala' => 'mcleodganj.jpg', 'Kodaikanal' => 'kodaikanal.jpg',
        'Ooty' => 'ooty.jpg', 'Gokarna' => 'gokarna.jpg', 'Gulmarg' => 'gulmarg.jpg',
        'Mumbai' => 'marinedrive.jpg', 'Mussoorie' => 'mussoorie.jpg', 'Kolkata' => 'victoria.jpg',
        'Kochi' => 'kochi.jpg', 'Pushkar' => 'pushkar.jpg', 'Lonavala' => 'lonavala.jpg',
        'Amritsar' => 'goldentemple.jpg', 'Hyderabad' => 'charminar.jpg', 'Mysore' => 'mysore.jpg',
        'Aurangabad' => 'ajanta.jpg', 'Madurai' => 'meenakshi.jpg', 'Khajuraho' => 'khajuraho.jpg',
        'Hampi' => 'hampi.jpg', 'Kaziranga' => 'kaziranga.jpg',
    ];
}

function dv_photo_file(array $p): string
{
    $dir = DG_APP . '/assets/places/';
    $try = [];
    $name = basename((string) ($p['photo'] ?? ''));
    if ($name !== '') {
        $try[] = $name;
    }
    foreach (dv_catalog() as $r) {
        if ($r[1] === ($p['title'] ?? '')) {
            $try[] = $r[6];
        }
    }
    $city = (string) ($p['city'] ?? '');
    if (isset(dv_photo_by_city()[$city])) {
        $try[] = dv_photo_by_city()[$city];
    }
    $try[] = ['destination' => 'udaipur.jpg', 'hotspot' => 'varanasi.jpg', 'place' => 'tajmahal.jpg', 'hotel' => 'jaipur.jpg'][$p['kind'] ?? ''] ?? 'udaipur.jpg';
    foreach (array_unique($try) as $f) {
        if ($f !== '' && is_file($dir . $f)) {
            return $f;
        }
    }
    return 'udaipur.jpg';
}

function dv_photo_url(array $p): string
{
    return 'assets/places/' . dv_photo_file($p);
}

function dv_shot(array $p): string
{
    return '<div class="place-shot"><img src="' . h(dv_photo_url($p)) . '" alt="' . h((string) ($p['title'] ?? 'Place')) . '"></div>';
}

function dv_insert_catalog(PDO $pdo): void
{
    $sql = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
        ? 'INSERT OR IGNORE INTO dg_places (kind, title, city, area, tagline, about, photo, featured) VALUES (?,?,?,?,?,?,?,?)'
        : 'INSERT IGNORE INTO dg_places (kind, title, city, area, tagline, about, photo, featured) VALUES (?,?,?,?,?,?,?,?)';
    $ins = $pdo->prepare($sql);
    foreach (dv_catalog() as $r) {
        $ins->execute($r);
    }
}

function dv_backfill_photos(PDO $pdo): void
{
    $up = $pdo->prepare('UPDATE dg_places SET photo=? WHERE id=?');
    foreach ($pdo->query('SELECT id, kind, title, city, photo FROM dg_places') as $r) {
        $want = dv_photo_file($r);
        if (($r['photo'] ?? '') !== $want) {
            $up->execute([$want, $r['id']]);
        }
    }
}

function dv_sync_hotels(PDO $pdo): void
{
    try {
        $pdo->query('SELECT id FROM dg_stays LIMIT 1');
    } catch (Throwable $e) {
        return;
    }
    $chk = $pdo->prepare('SELECT id FROM dg_places WHERE stay_id=? OR (kind=? AND title=? AND city=?)');
    $ins = $pdo->prepare('INSERT INTO dg_places (kind, title, city, area, tagline, about, photo, stay_id, featured) VALUES (?,?,?,?,?,?,?,?,?)');
    foreach ($pdo->query('SELECT id, title, city, area, about, price_night, stay_type FROM dg_stays') as $s) {
        $chk->execute([(int) $s['id'], 'hotel', $s['title'], $s['city']]);
        if ($chk->fetch()) {
            continue;
        }
        $tag = trim(($s['stay_type'] ?? 'Hotel') . ($s['price_night'] ? ' · ' . $s['price_night'] : ''));
        $photo = dv_photo_file(['kind' => 'hotel', 'title' => $s['title'], 'city' => $s['city']]);
        $ins->execute(['hotel', $s['title'], $s['city'], $s['area'] ?? '', $tag, $s['about'] ?? '', $photo, (int) $s['id'], 0]);
    }
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
    dv_insert_catalog($db);
    dv_sync_hotels($db);
}

function dv_save_verdict(PDO $db, int $uid, int $pid, string $verdict): void
{
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $db->prepare('INSERT INTO dg_place_likes (user_id, place_id, verdict) VALUES (?,?,?) ON CONFLICT(user_id, place_id) DO UPDATE SET verdict=excluded.verdict, created_at=CURRENT_TIMESTAMP')
            ->execute([$uid, $pid, $verdict]);
        return;
    }
    $db->prepare('INSERT INTO dg_place_likes (user_id, place_id, verdict) VALUES (?,?,?) ON DUPLICATE KEY UPDATE verdict=VALUES(verdict), created_at=CURRENT_TIMESTAMP')
        ->execute([$uid, $pid, $verdict]);
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
    if ($act === 'swipe') {
        if (!user()) {
            flash('Log in to save places you want to visit.');
            go('login');
        }
        $pid = (int) ($_POST['place_id'] ?? 0);
        $verdict = (string) ($_POST['verdict'] ?? '');
        $kind = preg_replace('/[^a-z]/', '', (string) ($_POST['kind'] ?? ''));
        if ($pid < 1 || !in_array($verdict, ['like', 'pass'], true)) {
            $err = 'Pick like or pass on a place.';
            return true;
        }
        $st = $db->prepare('SELECT id FROM dg_places WHERE id=?');
        $st->execute([$pid]);
        if (!$st->fetch()) {
            $err = 'That place is gone.';
            return true;
        }
        dv_save_verdict($db, (int) user()['id'], $pid, $verdict);
        audit($db, (int) user()['id'], 'place', $pid, $verdict);
        go('swipe' . ($kind !== '' ? '&kind=' . $kind : ''));
    }
    return false;
}

function dv_like_count(PDO $db, int $pid): int
{
    $st = $db->prepare("SELECT COUNT(*) FROM dg_place_likes WHERE place_id=? AND verdict='like'");
    $st->execute([$pid]);
    return (int) $st->fetchColumn();
}

function dv_deck(PDO $db, ?array $me, string $kind): array
{
    $sql = 'SELECT * FROM dg_places WHERE 1=1';
    $args = [];
    if ($kind !== '' && isset(dv_kinds()[$kind])) {
        $sql .= ' AND kind=?';
        $args[] = $kind;
    }
    if ($me) {
        $sql .= ' AND id NOT IN (SELECT place_id FROM dg_place_likes WHERE user_id=?)';
        $args[] = $me['id'];
    }
    $st = $db->prepare($sql . ' ORDER BY featured DESC, id ASC LIMIT 80');
    $st->execute($args);
    return $st->fetchAll();
}

function dv_card(array $p, int $likes): string
{
    $kinds = dv_kinds();
    $k = $kinds[$p['kind']] ?? ['Place', '📍'];
    $html = '<article class="swipe-card" data-id="' . (int) $p['id'] . '">';
    $html .= '<span class="swipe-stamp swipe-stamp-no">Pass</span><span class="swipe-stamp swipe-stamp-yes">Want</span>';
    $html .= '<img class="swipe-photo" src="' . h(dv_photo_url($p)) . '" alt="' . h($p['title'] . ', ' . ($p['city'] ?? '')) . '">';
    $html .= '<div class="swipe-body"><div class="swipe-kind">' . h($k[1] . ' ' . $k[0]) . '</div>';
    $html .= '<h2>' . h($p['title']) . '</h2>';
    $html .= '<p class="swipe-where">' . h(trim(($p['area'] ?? '') . ' · ' . ($p['city'] ?? ''), ' ·')) . '</p>';
    if (!empty($p['tagline'])) {
        $html .= '<p class="swipe-tag">' . h($p['tagline']) . '</p>';
    }
    $html .= '<p class="swipe-count">' . $likes . ' want to visit</p></div></article>';
    return $html;
}

function product_render_page(PDO $db, string $page, array $P, ?array $me): bool
{
    if ($page === 'swipe') {
        $kind = (string) ($_GET['kind'] ?? '');
        if (!isset(dv_kinds()[$kind])) {
            $kind = '';
        }
        $deck = dv_deck($db, $me, $kind);
        $top = $deck[0] ?? null;
        echo '<section class="section swipe-page"><div class="container"><div class="section-title"><div><h2>Swipe places</h2><p>Right = you want to visit. Left = pass. Same motion as profile cards, for destiny locations, hotspots, places and hotels.</p></div><a class="btn light" href="?p=popular">Who wants to visit</a></div>';
        echo '<div class="swipe-filters">';
        echo '<a class="pill' . ($kind === '' ? ' on' : '') . '" href="?p=swipe">All</a>';
        foreach (dv_kinds() as $k => $lab) {
            echo '<a class="pill' . ($kind === $k ? ' on' : '') . '" href="?p=swipe&kind=' . h($k) . '">' . h($lab[1] . ' ' . $lab[0]) . '</a>';
        }
        echo '</div>';
        if (!$me) {
            echo '<p class="notice">Log in to save likes — then we can see who wants the same places.</p>';
        }
        echo '<div class="swipe-wrap">';
        $left = count($deck);
        if ($top) {
            echo '<p class="muted" style="text-align:center;margin:0 0 12px">' . $left . ' places left in this stack</p>';
        }
        if (!$top) {
            echo '<div class="card"><h3>Stack complete</h3><p>You have gone through this set.</p><p><a class="btn" href="?p=wants">Places you want</a> <a class="btn light" href="?p=popular">See who else wants them</a></p></div>';
        } else {
            echo '<div class="swipe-stage" id="swipeStage">' . dv_card($top, dv_like_count($db, (int) $top['id'])) . '</div>';
            echo '<div class="swipe-actions">';
            if ($me) {
                echo '<form method="post">' . csrf_fields('swipe') . '<input type="hidden" name="place_id" value="' . (int) $top['id'] . '"><input type="hidden" name="kind" value="' . h($kind) . '"><input type="hidden" name="verdict" value="pass"><button class="swipe-btn swipe-no" type="submit" aria-label="Pass">✕</button></form>';
                echo '<form method="post">' . csrf_fields('swipe') . '<input type="hidden" name="place_id" value="' . (int) $top['id'] . '"><input type="hidden" name="kind" value="' . h($kind) . '"><input type="hidden" name="verdict" value="like"><button class="swipe-btn swipe-yes" type="submit" aria-label="Want to visit">♥</button></form>';
            } else {
                echo '<a class="swipe-btn swipe-no" href="?p=login" aria-label="Log in to pass">✕</a>';
                echo '<a class="swipe-btn swipe-yes" href="?p=login" aria-label="Log in to like">♥</a>';
            }
            echo '</div><p class="muted" style="text-align:center">Drag the card, or use the buttons. Keyboard: ← pass · → want.</p>';
            echo '<script>(function(){var c=document.querySelector(".swipe-card");if(!c)return;var x0=0,dx=0,drag=false;function go(v){var f=document.querySelector(\'input[name="verdict"][value="\'+v+\'"]\');c.classList.add(v==="like"?"gone-right":"gone-left");setTimeout(function(){if(f)f.form.submit();},220);}c.addEventListener("pointerdown",function(e){drag=true;x0=e.clientX;c.setPointerCapture(e.pointerId);});c.addEventListener("pointermove",function(e){if(!drag)return;dx=e.clientX-x0;c.style.transform="translateX("+dx+"px) rotate("+(dx/18)+"deg)";c.classList.toggle("hint-yes",dx>48);c.classList.toggle("hint-no",dx<-48);});function up(){if(!drag)return;drag=false;if(dx>80)go("like");else if(dx<-80)go("pass");else{c.style.transform="";c.classList.remove("hint-yes","hint-no");}dx=0;}c.addEventListener("pointerup",up);c.addEventListener("pointercancel",up);document.addEventListener("keydown",function(e){if(e.key==="ArrowRight")go("like");if(e.key==="ArrowLeft")go("pass");});})();</script>';
        }
        echo '</div></div></section>';
        return true;
    }
    if ($page === 'wants') {
        if (!$me) {
            go('login');
        }
        $st = $db->prepare("SELECT p.*, l.created_at AS liked_at FROM dg_place_likes l JOIN dg_places p ON p.id=l.place_id WHERE l.user_id=? AND l.verdict='like' ORDER BY l.id DESC");
        $st->execute([$me['id']]);
        $rows = $st->fetchAll();
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Want to visit</h2><p>Places you swiped right. Staff and other travellers can see the like count — not a chat feed.</p></div><a class="btn" href="?p=swipe">Keep swiping</a></div><div class="list-grid">';
        foreach ($rows as $r) {
            echo '<div class="biz-card">' . dv_shot($r) . '<div class="meta"><span>' . h(dv_kind_label($r['kind'])) . '</span><span>' . dv_like_count($db, (int) $r['id']) . ' want this</span></div><h3><a href="?p=place&id=' . (int) $r['id'] . '">' . h($r['title']) . '</a></h3><p>' . h($r['city']) . '</p><p class="muted">' . h($r['tagline'] ?? '') . '</p>';
            if (!empty($r['stay_id'])) {
                echo '<p><a class="btn light" href="?p=view&id=' . (int) $r['stay_id'] . '">Request stay</a></p>';
            }
            echo '</div>';
        }
        if (!$rows) {
            echo '<p>None yet. <a href="?p=swipe">Swipe a few places</a>.</p>';
        }
        echo '</div></div></section>';
        return true;
    }
    if ($page === 'popular') {
        $st = $db->query("SELECT p.*, (SELECT COUNT(*) FROM dg_place_likes l WHERE l.place_id=p.id AND l.verdict='like') AS like_n FROM dg_places p ORDER BY like_n DESC, featured DESC, id ASC LIMIT 40");
        echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Who wants to visit</h2><p>People-liking of destiny locations, hotspots, places and hotels. Open a card to see names.</p></div><a class="btn" href="?p=swipe">Swipe</a></div><div class="list-grid">';
        foreach ($st as $r) {
            echo '<div class="biz-card">' . dv_shot($r) . '<div class="meta"><span>' . h(dv_kind_label($r['kind'])) . '</span><span class="verified">' . (int) $r['like_n'] . ' want this</span></div><h3><a href="?p=place&id=' . (int) $r['id'] . '">' . h($r['title']) . '</a></h3><p>' . h(($r['area'] ? $r['area'] . ' · ' : '') . $r['city']) . '</p><p class="muted">' . h($r['tagline'] ?? '') . '</p></div>';
        }
        echo '</div></div></section>';
        return true;
    }
    if ($page === 'place') {
        $st = $db->prepare('SELECT * FROM dg_places WHERE id=?');
        $st->execute([(int) ($_GET['id'] ?? 0)]);
        $p = $st->fetch();
        echo '<section class="section"><div class="container">';
        if (!$p) {
            echo '<p>Not found.</p></div></section>';
            return true;
        }
        $likes = dv_like_count($db, (int) $p['id']);
        $people = $db->prepare("SELECT u.name FROM dg_place_likes l JOIN dg_users u ON u.id=l.user_id WHERE l.place_id=? AND l.verdict='like' ORDER BY l.id DESC LIMIT 40");
        $people->execute([(int) $p['id']]);
        echo '<div class="biz-card">' . dv_card($p, $likes);
        echo '<p class="muted" style="margin-top:16px">People who want this</p><ul class="checklist">';
        $n = 0;
        foreach ($people as $u) {
            echo '<li>' . h($u['name']) . '</li>';
            $n++;
        }
        if (!$n) {
            echo '<li>No likes yet. Be first from Swipe places.</li>';
        }
        echo '</ul>';
        if (!empty($p['stay_id'])) {
            echo '<p><a class="btn" href="?p=view&id=' . (int) $p['stay_id'] . '">Request this stay</a></p>';
        }
        echo '<p><a class="btn light" href="?p=swipe">Back to swipe</a></p></div></div></section>';
        return true;
    }
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
            echo '<div class="biz-card">' . dv_shot(['kind' => 'hotel', 'title' => $r['title'], 'city' => $r['city']]) . '<h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['title']) . '</a></h3><p>' . h(($r['stay_type'] ?? '') . ' · ' . ($r['area'] ?? $r['city'] ?? '')) . '</p><div class="meta"><span>' . h($r['price_night'] ?? '') . '</span><span>' . h(($r['max_guests'] ?? '') . ' guests') . '</span></div><p class="muted">' . h($r['amenities'] ?? '') . '</p><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">Request stay</a></div>';
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
