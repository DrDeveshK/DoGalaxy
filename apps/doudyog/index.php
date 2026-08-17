<?php
declare(strict_types=1);
require __DIR__ . "/boot.php";
$err = "";
try { $db = db(); } catch (Throwable $e) { exit("Open install.php?key=dogalaxy"); }
if ($_SERVER["REQUEST_METHOD"] === "POST") { $err = handle_auth($db); }
$p = $_GET["p"] ?? "home";
$me = user();
$codes = ["udyam"=>"Udyam","gstin"=>"GSTIN","pan"=>"PAN","bank"=>"Bank","address"=>"Address","licence"=>"Licence","invoice"=>"Invoicing","contact"=>"Public contact"];
if (($_POST["act"] ?? "") === "save" && $me) {
    $st = $db->prepare("SELECT id FROM dg_businesses WHERE owner_id=?"); $st->execute([$me["id"]]); $bid = (int)$st->fetchColumn();
    if (!$bid) {
        $db->prepare("INSERT INTO dg_businesses (owner_id, legal_name, industry, city) VALUES (?,?,?,?)")
            ->execute([$me["id"], trim($_POST["legal_name"]), $_POST["industry"] ?: "Services", trim($_POST["city"])]);
        $bid = (int)$db->lastInsertId();
        $ins = $db->prepare("INSERT INTO dg_compliance (business_id, code, done) VALUES (?,?,0)");
        foreach (array_keys($codes) as $c) $ins->execute([$bid, $c]);
    } else {
        $db->prepare("UPDATE dg_businesses SET legal_name=?, industry=?, city=?, gstin=?, udyam_no=?, about=? WHERE id=?")
            ->execute([trim($_POST["legal_name"]), $_POST["industry"], trim($_POST["city"]), strtoupper(trim($_POST["gstin"]??""))?:null, trim($_POST["udyam_no"]??"")?:null, trim($_POST["about"]??""), $bid]);
    }
    header("Location: ?p=dash&ok=1"); exit;
}
if (($_POST["act"] ?? "") === "compliance" && $me) {
    $st = $db->prepare("SELECT id FROM dg_businesses WHERE owner_id=?"); $st->execute([$me["id"]]); $bid = (int)$st->fetchColumn();
    if ($bid) {
        $up = $db->prepare("UPDATE dg_compliance SET done=?, note=? WHERE business_id=? AND code=?");
        foreach (array_keys($codes) as $c) $up->execute([isset($_POST["done"][$c])?1:0, trim($_POST["note"][$c]??"")?:null, $bid, $c]);
        $sc = (int)round((float)$db->query("SELECT AVG(done)*100 FROM dg_compliance WHERE business_id=".$bid)->fetchColumn());
        $db->prepare("UPDATE dg_businesses SET completeness=? WHERE id=?")->execute([$sc, $bid]);
    }
    header("Location: ?p=compliance&ok=1"); exit;
}
if (($_POST["act"] ?? "") === "enquire") {
    $db->prepare("INSERT INTO dg_enquiries (product, target_id, user_id, name, email, phone, message) VALUES ('udyog',?,?,?,?,?,?)")
        ->execute([(int)$_POST["target_id"]?:null, $me["id"]??null, trim($_POST["name"]), $_POST["email"], trim($_POST["phone"]??""), trim($_POST["message"])]);
    header("Location: ?p=view&id=".(int)$_POST["target_id"]."&sent=1"); exit;
}
$biz = null;
if ($me) { $st=$db->prepare("SELECT * FROM dg_businesses WHERE owner_id=?"); $st->execute([$me["id"]]); $biz=$st->fetch()?:null; }
shell_start("DoUdyog", "U", ["?p=home"=>"Home","?p=dir"=>"Directory"], "?p=join", "Join Udyog");
if ($p==="join"||$p==="login") auth_forms($err, $p==="join"?"join":"login");
elseif ($p==="dash" && $me) {
    echo '<section class="section soft"><div class="container"><h2>Business profile</h2>';
    if (!empty($_GET["ok"])) echo '<div class="notice">Saved.</div>';
    echo '<form method="post" class="card"><input type="hidden" name="act" value="save">';
    echo '<div class="form-row"><input class="input" name="legal_name" placeholder="Legal name" value="'.h($biz["legal_name"]??"").'" required>';
    echo '<input class="input" name="industry" placeholder="Industry" value="'.h($biz["industry"]??"").'"></div><br>';
    echo '<div class="form-row"><input class="input" name="city" placeholder="City" value="'.h($biz["city"]??"").'" required>';
    echo '<input class="input" name="gstin" placeholder="GSTIN" value="'.h($biz["gstin"]??"").'"></div><br>';
    echo '<input class="input" name="udyam_no" placeholder="Udyam" value="'.h($biz["udyam_no"]??"").'"><br><br>';
    echo '<textarea name="about" placeholder="About">'.h($biz["about"]??"").'</textarea><br><br><button class="btn" type="submit">Save</button></form>';
    echo '<p><a class="btn light" href="?p=compliance">Compliance ledger</a></p></div></section>';
} elseif ($p==="compliance" && $me && $biz) {
    $st=$db->prepare("SELECT code,done,note FROM dg_compliance WHERE business_id=?"); $st->execute([$biz["id"]]); $rows=[]; foreach($st as $r) $rows[$r["code"]]=$r;
    echo '<section class="section"><div class="container"><h2>Compliance · '.(int)$biz["completeness"].'%</h2><form method="post"><input type="hidden" name="act" value="compliance"><table class="table">';
    foreach ($codes as $c=>$lab) { $r=$rows[$c]??["done"=>0,"note"=>""]; echo '<tr><td><input type="checkbox" name="done['.h($c).']" '.($r["done"]?"checked":"").'></td><td>'.h($lab).'</td><td><input class="input" name="note['.h($c).']" value="'.h($r["note"]).'"></td></tr>'; }
    echo '</table><br><button class="btn" type="submit">Save ledger</button></form></div></section>';
} elseif ($p==="dir") {
    $q=trim((string)($_GET["q"]??"")); $sql="SELECT id,legal_name,industry,city,completeness,verify_status FROM dg_businesses WHERE verify_status IN ('pending','verified')"; $a=[];
    if ($q!=="") { $sql.=" AND (legal_name LIKE ? OR city LIKE ?)"; $a=["%$q%","%$q%"]; }
    $st=$db->prepare($sql." ORDER BY id DESC LIMIT 40"); $st->execute($a); $list=$st->fetchAll();
    echo '<section class="section"><div class="container"><h2>Directory</h2><form method="get"><input type="hidden" name="p" value="dir"><input class="input" name="q" value="'.h($q).'" placeholder="Name or city"> <button class="btn" type="submit">Search</button></form><div class="list-grid">';
    foreach ($list as $r) echo '<div class="biz-card"><h3><a href="?p=view&id='.(int)$r["id"].'">'.h($r["legal_name"]).'</a></h3><p>'.h($r["industry"]).' · '.h($r["city"]).'</p></div>';
    if (!$list) echo '<p>No rows yet.</p>';
    echo '</div></div></section>';
} elseif ($p==="view") {
    $st=$db->prepare("SELECT * FROM dg_businesses WHERE id=?"); $st->execute([(int)($_GET["id"]??0)]); $v=$st->fetch();
    if (!$v) echo '<section class="section"><div class="container"><p>Not found.</p></div></section>';
    else {
        echo '<section class="section"><div class="container"><div class="biz-card"><h2>'.h($v["legal_name"]).'</h2><p>'.h($v["industry"]).' · '.h($v["city"]).'</p><p>'.nl2br(h($v["about"])).'</p></div><br><div class="card"><h3>Enquire</h3>';
        if (!empty($_GET["sent"])) echo '<div class="notice">Sent.</div>';
        echo '<form method="post"><input type="hidden" name="act" value="enquire"><input type="hidden" name="target_id" value="'.(int)$v["id"].'">';
        echo '<div class="form-row"><input class="input" name="name" placeholder="Name" required><input class="input" type="email" name="email" required placeholder="Email"></div><br>';
        echo '<input class="input" name="phone" placeholder="Phone"><br><br><textarea name="message" required></textarea><br><br><button class="btn" type="submit">Send</button></form></div></div></section>';
    }
} else {
    echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">उद्योग बढ़े, भारत बढ़े</span><h1>Build, verify and grow your business.</h1><p>Identity, compliance and a public directory — in MySQL, not a brochure.</p><div class="hero-actions"><a class="btn" href="?p=join">Register business</a><a class="btn light" href="?p=dir">Directory</a></div></div></div></section>';
}
if (in_array($p, ["dash","compliance"], true) && !$me) { header("Location: ?p=login"); exit; }
shell_end("DoUdyog");
