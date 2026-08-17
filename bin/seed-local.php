<?php
declare(strict_types=1);
$slug = $argv[1] ?? 'doudyog';
$root = dirname(__DIR__);
$app = $root . '/apps/' . $slug;
if ($slug === 'doudyog') {
    putenv('DG_LOCAL=1');
    require $app . '/boot.php';
    $db = db();
    $n = (int) $db->query('SELECT COUNT(*) FROM dg_businesses')->fetchColumn();
    if ($n > 0) {
        echo "seed exists ($n businesses)\n";
        exit(0);
    }
    $rows = [
        ['seed1@doudyog.local', 'Asha Sharma', 'Sharma Engineering Works', 'Manufacturing', 'Pune', 'Machining and fabrication for industrial buyers.'],
        ['seed2@doudyog.local', 'Rahul Meena', 'Aarambh Retail Network', 'Retail', 'Jaipur', 'Multi-city retailer looking for suppliers and hiring.'],
        ['seed3@doudyog.local', 'Imran Khan', 'Nirman BuildMart', 'Construction', 'Indore', 'Construction material distributor.'],
        ['seed4@doudyog.local', 'Neha Iyer', 'Swagat Hospitality Services', 'Hospitality', 'Delhi', 'Hospitality operator for events and hotels.'],
    ];
    foreach ($rows as $r) {
        $db->prepare('INSERT INTO dg_users (email, password_hash, name, role) VALUES (?,?,?,?)')
            ->execute([$r[0], password_hash('SeedPass9', PASSWORD_DEFAULT), $r[1], 'owner']);
        $uid = (int) $db->lastInsertId();
        $db->prepare('INSERT INTO dg_businesses (owner_id, legal_name, industry, city, about, verify_status, completeness) VALUES (?,?,?,?,?,?,?)')
            ->execute([$uid, $r[2], $r[3], $r[4], $r[5], 'verified', 50]);
        $bid = (int) $db->lastInsertId();
        $ins = $db->prepare('INSERT INTO dg_compliance (business_id, code, done) VALUES (?,?,?)');
        foreach (['udyam' => 1, 'gstin' => 1, 'pan' => 1, 'bank' => 0, 'address' => 1, 'licence' => 0, 'invoice' => 0, 'contact' => 1] as $c => $d) {
            $ins->execute([$bid, $c, $d]);
        }
    }
    echo "seeded 4 businesses (password SeedPass9)\n";
    exit(0);
}
define('DG_APP', $app);
putenv('DG_LOCAL=1');
require $root . '/apps/_platform/boot.php';
$db = db();
$P = product();
if (($P['mode'] ?? '') === 'hub') {
    echo "hub ready (admin@{$slug}.local / AdminPass9)\n";
    exit(0);
}
$n = (int) $db->query('SELECT COUNT(*) FROM ' . $P['listing_table'])->fetchColumn();
if ($n > 0) {
    echo "seed exists ($n {$P['listing_label']}s)\n";
    exit(0);
}
$seedFile = $app . '/seed.php';
$rows = is_file($seedFile) ? require $seedFile : [];
$i = 1;
foreach ($rows as $row) {
    $email = 'seed' . $i . '@' . $slug . '.local';
    $db->prepare('INSERT INTO dg_users (email, password_hash, name, role) VALUES (?,?,?,?)')
        ->execute([$email, password_hash('SeedPass9', PASSWORD_DEFAULT), (string) $row[0], $P['owner_role'] ?? 'owner']);
    $uid = (int) $db->lastInsertId();
    $cols = [$P['owner_col']];
    $vals = [$uid];
    foreach ($P['fields'] as $idx => $f) {
        $cols[] = $f[0];
        $vals[] = $row[$idx] ?? null;
    }
    $cols[] = $P['status_col'];
    $status = 'verified';
    if ($P['listing_table'] === 'dg_jobs') {
        $status = 'open';
    } elseif (in_array($P['listing_table'], ['dg_listings', 'dg_services'], true)) {
        $status = 'live';
    }
    $vals[] = $status;
    $db->prepare('INSERT INTO ' . $P['listing_table'] . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')')->execute($vals);
    $i++;
}
echo 'seeded ' . count($rows) . " {$P['listing_label']}s (password SeedPass9)\n";
