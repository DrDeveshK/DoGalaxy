#!/usr/bin/env python3
"""Copy lean kit into each product app and write a small E2E index.php."""
from __future__ import annotations

import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
KIT = ROOT / "apps" / "_kit"
SCHEMA = ROOT / "schema" / "dogalaxy.sql"

# brand, mark, home headline, listing verb
# Each index.php is hand-written below as a string keyed by slug.

INDEX: dict[str, str] = {}

INDEX["mydoapp"] = r'''<?php
declare(strict_types=1);
require __DIR__ . "/boot.php";
$err = "";
try { $db = db(); } catch (Throwable $e) { exit("Open install.php?key=dogalaxy"); }
if ($_SERVER["REQUEST_METHOD"] === "POST") { $err = handle_auth($db); }
$p = $_GET["p"] ?? "home";
$me = user();
$paths = [
  "udyog" => ["DoUdyog", "https://doudyog.com/app/", "I run a business"],
  "vishram" => ["DoVishram", "https://dovishram.com/app/", "I need or host a stay"],
  "rojgar" => ["DoRojgar", "https://dorojgar.com/app/", "I want work or to hire"],
  "swagat" => ["DoSwagat", "https://doswagat.com/app/", "I am planning an event"],
  "rishta" => ["DoRishta", "https://dorishta.com/app/", "I am looking for a life partner"],
  "bajar" => ["DoBajar", "https://dobajar.com/app/", "I want to buy or sell"],
];
if (($_POST["act"] ?? "") === "path" && $me) {
    $path = preg_replace("/[^a-z]/", "", (string) ($_POST["path"] ?? ""));
    if (isset($paths[$path])) {
        $db->prepare("INSERT INTO dg_journeys (user_id, path) VALUES (?,?)")->execute([$me["id"], $path]);
        header("Location: " . $paths[$path][1]);
        exit;
    }
}
shell_start("MyDoApp", "M", ["?p=home" => "Home", "?p=products" => "Products"], "?p=join", "Join");
if ($p === "join" || $p === "login") { auth_forms($err, $p === "join" ? "join" : "login"); }
elseif ($p === "dash" && $me) {
    echo '<section class="section"><div class="container"><h1>Hello, '.h($me["name"]).'</h1><p>Choose a path. Your account works across Do Galaxy.</p><form method="post" class="grid-3">';
    echo '<input type="hidden" name="act" value="path">';
    foreach ($paths as $k=>$r) echo '<label class="feature"><input type="radio" name="path" value="'.h($k).'" required> <h3>'.h($r[0]).'</h3><p>'.h($r[2]).'</p></label>';
    echo '</div><br><button class="btn" type="submit">Continue</button></form></div></section>';
} elseif ($p === "products") {
    echo '<section class="section"><div class="container"><div class="grid-3">';
    foreach ($paths as $r) echo '<div class="feature"><h3>'.h($r[0]).'</h3><p><a class="btn" href="'.h($r[1]).'">Open</a></p></div>';
    echo '</div></div></section>';
} else {
    echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">Do Galaxy</span><h1>One account. Six working products.</h1><p>MyDoApp is the door. Register once, then run a business, find a stay, hire, host an event, match, or sell.</p><div class="hero-actions"><a class="btn" href="?p=join">Create account</a><a class="btn light" href="?p=products">See products</a></div></div></div></section>';
}
if ($p === "dash" && !$me) { header("Location: ?p=login"); exit; }
shell_end("MyDoApp");
'''

INDEX["doudyog"] = r'''<?php
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
'''

def listing_app(brand: str, mark: str, eyebrow: str, h1: str, lede: str, table: str, owner_col: str, fields: list, req_table: str, req_fk: str, req_extra: list, product: str, dir_cols: str, title_col: str) -> str:
    """Generic list + own + request app."""
    field_names = [f[0] for f in fields]
    placeholders = ",".join(["?"] * (1 + len(field_names)))
    inserts = ", ".join([owner_col] + field_names)
    inputs = ""
    for name, ph, _ in fields:
        if name == "about" or name == "description" or name == "message":
            inputs += f'<textarea name="{name}" placeholder="{ph}"><?=h($row["{name}"]??"")?></textarea><br><br>'
        else:
            inputs += f'<input class="input" name="{name}" placeholder="{ph}" value="<?=h($row["{name}"]??"")?>" required><br><br>'
    save_vals = "".join(
        f'trim((string)($_POST["{n}"]??""))' + (", " if i < len(field_names)-1 else "")
        for i, n in enumerate(field_names)
    )
    # fix save_vals - use python
    save_list = ", ".join([f'trim((string)($_POST["{n}"] ?? ""))' for n in field_names])
    req_fields = "".join(f'<input class="input" name="{n}" placeholder="{ph}" {req}><br><br>' for n, ph, req in req_extra)
    req_cols = ", ".join([req_fk] + [n for n,_,_ in req_extra if n != "message"] + ["message"])
    # simpler: handle in PHP block generated below
    return f'''<?php
declare(strict_types=1);
require __DIR__ . "/boot.php";
$err = "";
try {{ $db = db(); }} catch (Throwable $e) {{ exit("Open install.php?key=dogalaxy"); }}
if ($_SERVER["REQUEST_METHOD"] === "POST") {{ $err = handle_auth($db); }}
$p = $_GET["p"] ?? "home";
$me = user();
if (($_POST["act"] ?? "") === "save" && $me) {{
    $st = $db->prepare("SELECT id FROM {table} WHERE {owner_col}=?"); $st->execute([$me["id"]]); $id = (int)$st->fetchColumn();
    $vals = [{save_list}];
    if (!$id) {{
        $db->prepare("INSERT INTO {table} ({inserts}) VALUES ({placeholders})")->execute(array_merge([$me["id"]], $vals));
        $id = (int)$db->lastInsertId();
        audit($db, $me["id"], "{product}", $id, "create");
    }} else {{
        $db->prepare("UPDATE {table} SET {", ".join(n+"=?" for n in field_names)} WHERE id=?")->execute(array_merge($vals, [$id]));
        audit($db, $me["id"], "{product}", $id, "update");
    }}
    header("Location: ?p=dash&ok=1"); exit;
}}
if (($_POST["act"] ?? "") === "request") {{
    $db->prepare("INSERT INTO {req_table} ({req_fk}, name, email, {", ".join(n for n,_,_ in req_extra if n not in ("name","email","message"))}{"," if any(n not in ("name","email","message") for n,_,_ in req_extra) else ""} message) VALUES ({",".join(["?"]*(3+len([n for n,_,_ in req_extra if n not in ("name","email","message")])) )})");
    // built below in concrete apps
}}
'''


# Concrete remaining apps — written fully for correctness (light, no generator bugs)

INDEX["dovishram"] = r'''<?php
declare(strict_types=1);
require __DIR__ . "/boot.php";
$err=""; try { $db=db(); } catch (Throwable $e) { exit("Open install.php?key=dogalaxy"); }
if ($_SERVER["REQUEST_METHOD"]==="POST") $err=handle_auth($db);
$p=$_GET["p"]??"home"; $me=user();
if (($_POST["act"]??"")==="save" && $me) {
    $st=$db->prepare("SELECT id FROM dg_stays WHERE host_id=?"); $st->execute([$me["id"]]); $id=(int)$st->fetchColumn();
    $vals=[trim($_POST["title"]), $_POST["stay_type"]?:"Homestay", trim($_POST["city"]), $_POST["price_night"]!==""?(float)$_POST["price_night"]:null, $_POST["max_guests"]!==""?(int)$_POST["max_guests"]:null, trim($_POST["about"]??"")];
    if (!$id) { $db->prepare("INSERT INTO dg_stays (host_id,title,stay_type,city,price_night,max_guests,about) VALUES (?,?,?,?,?,?,?)")->execute(array_merge([$me["id"]],$vals)); $id=(int)$db->lastInsertId(); }
    else { $db->prepare("UPDATE dg_stays SET title=?,stay_type=?,city=?,price_night=?,max_guests=?,about=? WHERE id=?")->execute(array_merge($vals,[$id])); }
    audit($db,$me["id"],"stay",$id,"save"); header("Location: ?p=dash&ok=1"); exit;
}
if (($_POST["act"]??"")==="request") {
    $db->prepare("INSERT INTO dg_stay_requests (stay_id,guest_id,name,email,checkin,checkout,guests,message) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([(int)$_POST["stay_id"], $me["id"]??null, trim($_POST["name"]), $_POST["email"], $_POST["checkin"], $_POST["checkout"], (int)($_POST["guests"]?:1), trim($_POST["message"]??"")]);
    header("Location: ?p=view&id=".(int)$_POST["stay_id"]."&sent=1"); exit;
}
if (($_POST["act"]??"")==="decide" && $me) {
    $db->prepare("UPDATE dg_stay_requests r JOIN dg_stays s ON s.id=r.stay_id SET r.status=? WHERE r.id=? AND s.host_id=?")->execute([$_POST["status"]==="accepted"?"accepted":"declined", (int)$_POST["rid"], $me["id"]]);
    header("Location: ?p=dash"); exit;
}
$row=null; $inbox=[];
if ($me) { $st=$db->prepare("SELECT * FROM dg_stays WHERE host_id=?"); $st->execute([$me["id"]]); $row=$st->fetch()?:null;
  if ($row) { $st=$db->prepare("SELECT * FROM dg_stay_requests WHERE stay_id=? ORDER BY id DESC LIMIT 20"); $st->execute([$row["id"]]); $inbox=$st->fetchAll(); } }
shell_start("DoVishram","V",["?p=home"=>"Home","?p=dir"=>"Stays"],"?p=join","List a stay");
if ($p==="join"||$p==="login") auth_forms($err,$p==="join"?"join":"login");
elseif ($p==="dash" && $me) {
    echo '<section class="section soft"><div class="container"><h2>Your stay</h2>';
    if (!empty($_GET["ok"])) echo '<div class="notice">Saved.</div>';
    echo '<form method="post" class="card"><input type="hidden" name="act" value="save">';
    echo '<input class="input" name="title" placeholder="Stay title" value="'.h($row["title"]??"").'" required><br><br>';
    echo '<div class="form-row"><select class="input" name="stay_type">';
    foreach (["Homestay","Hotel","Resort","Room"] as $t) echo '<option '.(($row["stay_type"]??"")===$t?"selected":"").'>'.$t.'</option>';
    echo '</select><input class="input" name="city" placeholder="City" value="'.h($row["city"]??"").'" required></div><br>';
    echo '<div class="form-row"><input class="input" name="price_night" placeholder="Price / night" value="'.h((string)($row["price_night"]??"")).'"><input class="input" name="max_guests" placeholder="Max guests" value="'.h((string)($row["max_guests"]??"")).'"></div><br>';
    echo '<textarea name="about" placeholder="About the stay">'.h($row["about"]??"").'</textarea><br><br><button class="btn" type="submit">Save stay</button></form>';
    echo '<h3>Requests</h3><table class="table"><tr><th>When</th><th>Guest</th><th>Dates</th><th></th></tr>';
    foreach ($inbox as $r) echo '<tr><td>'.h($r["created_at"]).'</td><td>'.h($r["name"]).'</td><td>'.h($r["checkin"]).' → '.h($r["checkout"]).'</td><td><form method="post"><input type="hidden" name="act" value="decide"><input type="hidden" name="rid" value="'.(int)$r["id"].'"><button class="btn" name="status" value="accepted">Accept</button> <button class="btn light" name="status" value="declined">Decline</button></form></td></tr>';
    if (!$inbox) echo '<tr><td colspan="4">None yet.</td></tr>';
    echo '</table></div></section>';
} elseif ($p==="dir") {
    $q=trim((string)($_GET["q"]??"")); $sql="SELECT id,title,stay_type,city,price_night FROM dg_stays WHERE verify_status IN ('pending','verified')"; $a=[];
    if ($q!=="") { $sql.=" AND (title LIKE ? OR city LIKE ?)"; $a=["%$q%","%$q%"]; }
    $st=$db->prepare($sql." ORDER BY id DESC LIMIT 40"); $st->execute($a);
    echo '<section class="section"><div class="container"><h2>Stays</h2><form method="get"><input type="hidden" name="p" value="dir"><input class="input" name="q" value="'.h($q).'" placeholder="City or name"> <button class="btn">Search</button></form><div class="list-grid">';
    foreach ($st as $r) echo '<div class="biz-card"><h3><a href="?p=view&id='.(int)$r["id"].'">'.h($r["title"]).'</a></h3><p>'.h($r["stay_type"]).' · '.h($r["city"]).'</p></div>';
    echo '</div></div></section>';
} elseif ($p==="view") {
    $st=$db->prepare("SELECT * FROM dg_stays WHERE id=?"); $st->execute([(int)($_GET["id"]??0)]); $v=$st->fetch();
    if (!$v) echo '<section class="section"><div class="container">Not found.</div></section>';
    else {
        echo '<section class="section"><div class="container"><div class="biz-card"><h2>'.h($v["title"]).'</h2><p>'.h($v["stay_type"]).' · '.h($v["city"]).' · ₹'.h((string)($v["price_night"]?:'—')).'</p><p>'.nl2br(h($v["about"])).'</p></div><br><div class="card"><h3>Request dates</h3>';
        if (!empty($_GET["sent"])) echo '<div class="notice">Request sent.</div>';
        echo '<form method="post"><input type="hidden" name="act" value="request"><input type="hidden" name="stay_id" value="'.(int)$v["id"].'">';
        echo '<div class="form-row"><input class="input" name="name" placeholder="Name" required><input class="input" type="email" name="email" required></div><br>';
        echo '<div class="form-row"><input class="input" type="date" name="checkin" required><input class="input" type="date" name="checkout" required></div><br>';
        echo '<input class="input" name="guests" placeholder="Guests" value="2"><br><br><textarea name="message"></textarea><br><br><button class="btn">Request stay</button></form></div></div></section>';
    }
} else echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">विश्राम मिले, मन सुहे</span><h1>Find a trusted stay. List your rooms.</h1><p>Hotels, homestays and short stays with a clear request flow.</p><div class="hero-actions"><a class="btn" href="?p=join">List a stay</a><a class="btn light" href="?p=dir">Explore stays</a></div></div></div></section>';
if ($p==="dash" && !$me) { header("Location: ?p=login"); exit; }
shell_end("DoVishram");
'''

INDEX["dorojgar"] = r'''<?php
declare(strict_types=1);
require __DIR__ . "/boot.php";
$err=""; try { $db=db(); } catch (Throwable $e) { exit("Open install.php?key=dogalaxy"); }
if ($_SERVER["REQUEST_METHOD"]==="POST") $err=handle_auth($db);
$p=$_GET["p"]??"home"; $me=user();
if (($_POST["act"]??"")==="post" && $me) {
    $db->prepare("INSERT INTO dg_jobs (employer_id,title,job_type,city,pay,description) VALUES (?,?,?,?,?,?)")
        ->execute([$me["id"], trim($_POST["title"]), $_POST["job_type"]?:"Full-time", trim($_POST["city"]), trim($_POST["pay"]??""), trim($_POST["description"]??"")]);
    audit($db,$me["id"],"job",(int)$db->lastInsertId(),"create"); header("Location: ?p=dash&ok=1"); exit;
}
if (($_POST["act"]??"")==="apply") {
    try {
        $db->prepare("INSERT INTO dg_applications (job_id,seeker_id,name,email,phone,experience,message) VALUES (?,?,?,?,?,?,?)")
            ->execute([(int)$_POST["job_id"], $me["id"]??null, trim($_POST["name"]), $_POST["email"], trim($_POST["phone"]??""), trim($_POST["experience"]??""), trim($_POST["message"])]);
    } catch (PDOException $e) { /* duplicate apply */ }
    header("Location: ?p=view&id=".(int)$_POST["job_id"]."&sent=1"); exit;
}
if (($_POST["act"]??"")==="decide" && $me) {
    $db->prepare("UPDATE dg_applications a JOIN dg_jobs j ON j.id=a.job_id SET a.status=? WHERE a.id=? AND j.employer_id=?")
        ->execute([in_array($_POST["status"],["shortlisted","rejected","hired"],true)?$_POST["status"]:"new", (int)$_POST["aid"], $me["id"]]);
    header("Location: ?p=dash"); exit;
}
$jobs=[]; $apps=[];
if ($me) { $st=$db->prepare("SELECT * FROM dg_jobs WHERE employer_id=? ORDER BY id DESC"); $st->execute([$me["id"]]); $jobs=$st->fetchAll();
  if ($jobs) { $ids=implode(",", array_map(fn($j)=>(int)$j["id"], $jobs)); $apps=$db->query("SELECT * FROM dg_applications WHERE job_id IN ($ids) ORDER BY id DESC LIMIT 30")->fetchAll(); } }
shell_start("DoRojgar","R",["?p=home"=>"Home","?p=dir"=>"Jobs"],"?p=join","Post a job");
if ($p==="join"||$p==="login") auth_forms($err,$p==="join"?"join":"login");
elseif ($p==="dash" && $me) {
    echo '<section class="section soft"><div class="container"><h2>Employer console</h2><form method="post" class="card"><input type="hidden" name="act" value="post">';
    echo '<input class="input" name="title" placeholder="Role title" required><br><br><div class="form-row"><select class="input" name="job_type"><option>Full-time</option><option>Part-time</option><option>Gig</option></select><input class="input" name="city" placeholder="City" required></div><br>';
    echo '<input class="input" name="pay" placeholder="Pay"><br><br><textarea name="description" placeholder="Role details"></textarea><br><br><button class="btn">Post job</button></form>';
    echo '<h3>Applications</h3><table class="table"><tr><th>Job</th><th>Applicant</th><th>Status</th><th></th></tr>';
    foreach ($apps as $a) echo '<tr><td>#'.(int)$a["job_id"].'</td><td>'.h($a["name"]).'<br><small>'.h($a["email"]).'</small></td><td>'.h($a["status"]).'</td><td><form method="post"><input type="hidden" name="act" value="decide"><input type="hidden" name="aid" value="'.(int)$a["id"].'"><button class="btn" name="status" value="shortlisted">Shortlist</button> <button class="btn light" name="status" value="hired">Hire</button></form></td></tr>';
    if (!$apps) echo '<tr><td colspan="4">None yet.</td></tr>';
    echo '</table></div></section>';
} elseif ($p==="dir") {
    $q=trim((string)($_GET["q"]??"")); $sql="SELECT id,title,job_type,city,pay FROM dg_jobs WHERE status='open'"; $a=[];
    if ($q!=="") { $sql.=" AND (title LIKE ? OR city LIKE ?)"; $a=["%$q%","%$q%"]; }
    $st=$db->prepare($sql." ORDER BY id DESC LIMIT 40"); $st->execute($a);
    echo '<section class="section"><div class="container"><h2>Open jobs</h2><form method="get"><input type="hidden" name="p" value="dir"><input class="input" name="q" value="'.h($q).'"> <button class="btn">Search</button></form><div class="list-grid">';
    foreach ($st as $r) echo '<div class="biz-card"><h3><a href="?p=view&id='.(int)$r["id"].'">'.h($r["title"]).'</a></h3><p>'.h($r["job_type"]).' · '.h($r["city"]).' · '.h($r["pay"]).'</p></div>';
    echo '</div></div></section>';
} elseif ($p==="view") {
    $st=$db->prepare("SELECT * FROM dg_jobs WHERE id=?"); $st->execute([(int)($_GET["id"]??0)]); $v=$st->fetch();
    if (!$v) echo '<section class="section"><div class="container">Not found.</div></section>';
    else {
        echo '<section class="section"><div class="container"><div class="biz-card"><h2>'.h($v["title"]).'</h2><p>'.h($v["job_type"]).' · '.h($v["city"]).'</p><p>'.nl2br(h($v["description"])).'</p></div><br><div class="card"><h3>Apply</h3>';
        if (!empty($_GET["sent"])) echo '<div class="notice">Application stored.</div>';
        echo '<form method="post"><input type="hidden" name="act" value="apply"><input type="hidden" name="job_id" value="'.(int)$v["id"].'">';
        echo '<div class="form-row"><input class="input" name="name" required placeholder="Name"><input class="input" type="email" name="email" required></div><br>';
        echo '<div class="form-row"><input class="input" name="phone" placeholder="Phone"><input class="input" name="experience" placeholder="Experience"></div><br>';
        echo '<textarea name="message" required placeholder="Why you"></textarea><br><br><button class="btn">Apply</button></form></div></div></section>';
    }
} else echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">रोज़गार मिले, सम्मान रहे</span><h1>Post a job. Apply locally. Hire with trust.</h1><p>Hyperlocal work for seekers. A simple hiring channel for MSMEs.</p><div class="hero-actions"><a class="btn" href="?p=join">Post a job</a><a class="btn light" href="?p=dir">Browse jobs</a></div></div></div></section>';
if ($p==="dash" && !$me) { header("Location: ?p=login"); exit; }
shell_end("DoRojgar");
'''


def write_simple(slug, brand, mark, eyebrow, h1, lede, cta, cta2, table, owner, cols, req_table, req_fk, extra_req_sql, extra_req_exec, extra_req_html, dir_select, view_body, hero_cta2_p="dir"):
    pass


INDEX["doswagat"] = r'''<?php
declare(strict_types=1);
require __DIR__ . "/boot.php";
$err=""; try { $db=db(); } catch (Throwable $e) { exit("Open install.php?key=dogalaxy"); }
if ($_SERVER["REQUEST_METHOD"]==="POST") $err=handle_auth($db);
$p=$_GET["p"]??"home"; $me=user();
if (($_POST["act"]??"")==="save" && $me) {
    $st=$db->prepare("SELECT id FROM dg_venues WHERE partner_id=?"); $st->execute([$me["id"]]); $id=(int)$st->fetchColumn();
    $vals=[trim($_POST["title"]), $_POST["kind"]?:"Hall", trim($_POST["city"]), $_POST["capacity"]!==""?(int)$_POST["capacity"]:null, trim($_POST["about"]??"")];
    if (!$id) { $db->prepare("INSERT INTO dg_venues (partner_id,title,kind,city,capacity,about) VALUES (?,?,?,?,?,?)")->execute(array_merge([$me["id"]],$vals)); $id=(int)$db->lastInsertId(); }
    else $db->prepare("UPDATE dg_venues SET title=?,kind=?,city=?,capacity=?,about=? WHERE id=?")->execute(array_merge($vals,[$id]));
    header("Location: ?p=dash&ok=1"); exit;
}
if (($_POST["act"]??"")==="request") {
    $db->prepare("INSERT INTO dg_event_requests (venue_id,name,email,event_date,guests,event_type,message) VALUES (?,?,?,?,?,?,?)")
        ->execute([(int)$_POST["venue_id"], trim($_POST["name"]), $_POST["email"], $_POST["event_date"], (int)($_POST["guests"]?:0)?:null, trim($_POST["event_type"]??""), trim($_POST["message"])]);
    header("Location: ?p=view&id=".(int)$_POST["venue_id"]."&sent=1"); exit;
}
$row=null; $inbox=[];
if ($me) { $st=$db->prepare("SELECT * FROM dg_venues WHERE partner_id=?"); $st->execute([$me["id"]]); $row=$st->fetch()?:null;
  if ($row) { $st=$db->prepare("SELECT * FROM dg_event_requests WHERE venue_id=? ORDER BY id DESC LIMIT 20"); $st->execute([$row["id"]]); $inbox=$st->fetchAll(); } }
shell_start("DoSwagat","S",["?p=home"=>"Home","?p=dir"=>"Venues"],"?p=join","List a venue");
if ($p==="join"||$p==="login") auth_forms($err,$p==="join"?"join":"login");
elseif ($p==="dash" && $me) {
    echo '<section class="section soft"><div class="container"><h2>Your venue</h2><form method="post" class="card"><input type="hidden" name="act" value="save">';
    echo '<input class="input" name="title" placeholder="Venue name" value="'.h($row["title"]??"").'" required><br><br>';
    echo '<div class="form-row"><select class="input" name="kind"><option>Hall</option><option>Lawn</option><option>Hotel</option><option>Studio</option></select><input class="input" name="city" placeholder="City" value="'.h($row["city"]??"").'" required></div><br>';
    echo '<input class="input" name="capacity" placeholder="Capacity" value="'.h((string)($row["capacity"]??"")).'"><br><br><textarea name="about">'.h($row["about"]??"").'</textarea><br><br><button class="btn">Save venue</button></form>';
    echo '<h3>Event requests</h3><table class="table"><tr><th>Date</th><th>From</th><th>Type</th><th>Guests</th></tr>';
    foreach ($inbox as $r) echo '<tr><td>'.h($r["event_date"]).'</td><td>'.h($r["name"]).'</td><td>'.h($r["event_type"]).'</td><td>'.h((string)$r["guests"]).'</td></tr>';
    if (!$inbox) echo '<tr><td colspan="4">None yet.</td></tr>';
    echo '</table></div></section>';
} elseif ($p==="dir") {
    $q=trim((string)($_GET["q"]??"")); $sql="SELECT id,title,kind,city,capacity FROM dg_venues WHERE verify_status IN ('pending','verified')"; $a=[];
    if ($q!=="") { $sql.=" AND (title LIKE ? OR city LIKE ?)"; $a=["%$q%","%$q%"]; }
    $st=$db->prepare($sql." ORDER BY id DESC LIMIT 40"); $st->execute($a);
    echo '<section class="section"><div class="container"><h2>Venues</h2><form method="get"><input type="hidden" name="p" value="dir"><input class="input" name="q" value="'.h($q).'"> <button class="btn">Search</button></form><div class="list-grid">';
    foreach ($st as $r) echo '<div class="biz-card"><h3><a href="?p=view&id='.(int)$r["id"].'">'.h($r["title"]).'</a></h3><p>'.h($r["kind"]).' · '.h($r["city"]).' · '.h((string)$r["capacity"]).' guests</p></div>';
    echo '</div></div></section>';
} elseif ($p==="view") {
    $st=$db->prepare("SELECT * FROM dg_venues WHERE id=?"); $st->execute([(int)($_GET["id"]??0)]); $v=$st->fetch();
    if (!$v) echo '<section class="section"><div class="container">Not found.</div></section>';
    else {
        echo '<section class="section"><div class="container"><div class="biz-card"><h2>'.h($v["title"]).'</h2><p>'.nl2br(h($v["about"])).'</p></div><br><div class="card"><h3>Request this venue</h3>';
        if (!empty($_GET["sent"])) echo '<div class="notice">Request sent.</div>';
        echo '<form method="post"><input type="hidden" name="act" value="request"><input type="hidden" name="venue_id" value="'.(int)$v["id"].'">';
        echo '<div class="form-row"><input class="input" name="name" required><input class="input" type="email" name="email" required></div><br>';
        echo '<div class="form-row"><input class="input" type="date" name="event_date" required><input class="input" name="guests" placeholder="Guests"></div><br>';
        echo '<input class="input" name="event_type" placeholder="Wedding / corporate / other"><br><br><textarea name="message" required></textarea><br><br><button class="btn">Send brief</button></form></div></div></section>';
    }
} else echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">स्वागत हो, उत्सव बने</span><h1>Book a venue. Brief a partner.</h1><p>Weddings, celebrations and corporate hospitality in one flow.</p><div class="hero-actions"><a class="btn" href="?p=join">List a venue</a><a class="btn light" href="?p=dir">Find venues</a></div></div></div></section>';
if ($p==="dash" && !$me) { header("Location: ?p=login"); exit; }
shell_end("DoSwagat");
'''

INDEX["dorishta"] = r'''<?php
declare(strict_types=1);
require __DIR__ . "/boot.php";
$err=""; try { $db=db(); } catch (Throwable $e) { exit("Open install.php?key=dogalaxy"); }
if ($_SERVER["REQUEST_METHOD"]==="POST") $err=handle_auth($db);
$p=$_GET["p"]??"home"; $me=user();
if (($_POST["act"]??"")==="save" && $me) {
    $bd=$_POST["birth_date"]??"";
    if (!$bd || (new DateTime($bd)) > (new DateTime("-21 years"))) { $err="You must be 21 or older."; $p="dash"; }
    else {
        $st=$db->prepare("SELECT id FROM dg_profiles WHERE user_id=?"); $st->execute([$me["id"]]); $id=(int)$st->fetchColumn();
        $vals=[trim($_POST["display_name"]), $bd, trim($_POST["city"]), trim($_POST["community"]??""), trim($_POST["about"]??"")];
        if (!$id) $db->prepare("INSERT INTO dg_profiles (user_id,display_name,birth_date,city,community,about) VALUES (?,?,?,?,?,?)")->execute(array_merge([$me["id"]],$vals));
        else $db->prepare("UPDATE dg_profiles SET display_name=?,birth_date=?,city=?,community=?,about=? WHERE id=?")->execute(array_merge($vals,[$id]));
        header("Location: ?p=dash&ok=1"); exit;
    }
}
if (($_POST["act"]??"")==="interest" && $me) {
    try { $db->prepare("INSERT INTO dg_interests (from_user_id,to_profile_id,note) VALUES (?,?,?)")->execute([$me["id"], (int)$_POST["to_profile_id"], trim($_POST["note"])]); }
    catch (PDOException $e) {}
    header("Location: ?p=view&id=".(int)$_POST["to_profile_id"]."&sent=1"); exit;
}
$row=null; $inbox=[];
if ($me) { $st=$db->prepare("SELECT * FROM dg_profiles WHERE user_id=?"); $st->execute([$me["id"]]); $row=$st->fetch()?:null;
  if ($row) { $st=$db->prepare("SELECT i.*, u.name FROM dg_interests i JOIN dg_users u ON u.id=i.from_user_id WHERE i.to_profile_id=? ORDER BY i.id DESC LIMIT 20"); $st->execute([$row["id"]]); $inbox=$st->fetchAll(); } }
shell_start("DoRishta","R",["?p=home"=>"Home","?p=dir"=>"Profiles"],"?p=join","Create profile");
if ($p==="join"||$p==="login") auth_forms($err,$p==="join"?"join":"login");
elseif ($p==="dash" && $me) {
    echo '<section class="section soft"><div class="container"><h2>Your profile (21+)</h2>';
    if ($err) echo '<p class="err">'.h($err).'</p>';
    if (!empty($_GET["ok"])) echo '<div class="notice">Saved.</div>';
    echo '<form method="post" class="card"><input type="hidden" name="act" value="save">';
    echo '<input class="input" name="display_name" placeholder="Display name" value="'.h($row["display_name"]??$me["name"]).'" required><br><br>';
    echo '<div class="form-row"><input class="input" type="date" name="birth_date" value="'.h($row["birth_date"]??"").'" required><input class="input" name="city" placeholder="City" value="'.h($row["city"]??"").'" required></div><br>';
    echo '<input class="input" name="community" placeholder="Community (optional)" value="'.h($row["community"]??"").'"><br><br>';
    echo '<textarea name="about" placeholder="About you and what you seek">'.h($row["about"]??"").'</textarea><br><br><button class="btn">Save profile</button></form>';
    echo '<h3>Interests received</h3><table class="table"><tr><th>From</th><th>Note</th><th>Status</th></tr>';
    foreach ($inbox as $r) echo '<tr><td>'.h($r["name"]).'</td><td>'.h($r["note"]).'</td><td>'.h($r["status"]).'</td></tr>';
    if (!$inbox) echo '<tr><td colspan="3">None yet.</td></tr>';
    echo '</table></div></section>';
} elseif ($p==="dir") {
    $q=trim((string)($_GET["q"]??"")); $sql="SELECT id,display_name,city,community FROM dg_profiles WHERE verify_status IN ('pending','verified')"; $a=[];
    if ($q!=="") { $sql.=" AND (city LIKE ? OR community LIKE ?)"; $a=["%$q%","%$q%"]; }
    $st=$db->prepare($sql." ORDER BY id DESC LIMIT 40"); $st->execute($a);
    echo '<section class="section"><div class="container"><h2>Profiles</h2><p>Adults 21+ only. Family-friendly. No casual dating.</p><form method="get"><input type="hidden" name="p" value="dir"><input class="input" name="q" value="'.h($q).'" placeholder="City or community"> <button class="btn">Search</button></form><div class="list-grid">';
    foreach ($st as $r) echo '<div class="biz-card"><h3><a href="?p=view&id='.(int)$r["id"].'">'.h($r["display_name"]).'</a></h3><p>'.h($r["city"]).' · '.h($r["community"]).'</p></div>';
    echo '</div></div></section>';
} elseif ($p==="view") {
    $st=$db->prepare("SELECT id,display_name,city,community,about FROM dg_profiles WHERE id=?"); $st->execute([(int)($_GET["id"]??0)]); $v=$st->fetch();
    if (!$v) echo '<section class="section"><div class="container">Not found.</div></section>';
    else {
        echo '<section class="section"><div class="container"><div class="biz-card"><h2>'.h($v["display_name"]).'</h2><p>'.h($v["city"]).'</p><p>'.nl2br(h($v["about"])).'</p></div><br><div class="card"><h3>Express interest</h3>';
        if (!empty($_GET["sent"])) echo '<div class="notice">Interest sent.</div>';
        if ($me) {
            echo '<form method="post"><input type="hidden" name="act" value="interest"><input type="hidden" name="to_profile_id" value="'.(int)$v["id"].'">';
            echo '<textarea name="note" required placeholder="A respectful note"></textarea><br><br><button class="btn">Send interest</button></form>';
        } else echo '<p><a class="btn" href="?p=join">Create a profile first</a></p>';
        echo '</div></div></section>';
    }
} else echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">रिश्ता बने, विश्वास रहे</span><h1>A family-friendly path to a meaningful match.</h1><p>Verified profiles, 21+, respectful interest. Not a dating app.</p><div class="hero-actions"><a class="btn" href="?p=join">Create profile</a><a class="btn light" href="?p=dir">Browse</a></div></div></div></section>';
if ($p==="dash" && !$me) { header("Location: ?p=login"); exit; }
shell_end("DoRishta");
'''

INDEX["dobajar"] = r'''<?php
declare(strict_types=1);
require __DIR__ . "/boot.php";
$err=""; try { $db=db(); } catch (Throwable $e) { exit("Open install.php?key=dogalaxy"); }
if ($_SERVER["REQUEST_METHOD"]==="POST") $err=handle_auth($db);
$p=$_GET["p"]??"home"; $me=user();
if (($_POST["act"]??"")==="save" && $me) {
    $db->prepare("INSERT INTO dg_listings (seller_id,title,category,city,price,about,status) VALUES (?,?,?,?,?,?,'pending')")
        ->execute([$me["id"], trim($_POST["title"]), $_POST["category"]?:"General", trim($_POST["city"]), $_POST["price"]!==""?(float)$_POST["price"]:null, trim($_POST["about"]??"")]);
    header("Location: ?p=dash&ok=1"); exit;
}
if (($_POST["act"]??"")==="order") {
    $db->prepare("INSERT INTO dg_order_requests (listing_id,name,email,phone,qty,message) VALUES (?,?,?,?,?,?)")
        ->execute([(int)$_POST["listing_id"], trim($_POST["name"]), $_POST["email"], trim($_POST["phone"]??""), (int)($_POST["qty"]?:1), trim($_POST["message"])]);
    header("Location: ?p=view&id=".(int)$_POST["listing_id"]."&sent=1"); exit;
}
$mine=[]; $inbox=[];
if ($me) { $st=$db->prepare("SELECT * FROM dg_listings WHERE seller_id=? ORDER BY id DESC"); $st->execute([$me["id"]]); $mine=$st->fetchAll();
  if ($mine) { $ids=implode(",",array_map(fn($x)=>(int)$x["id"],$mine)); $inbox=$db->query("SELECT * FROM dg_order_requests WHERE listing_id IN ($ids) ORDER BY id DESC LIMIT 20")->fetchAll(); } }
shell_start("DoBajar","B",["?p=home"=>"Home","?p=dir"=>"Listings"],"?p=join","Sell");
if ($p==="join"||$p==="login") auth_forms($err,$p==="join"?"join":"login");
elseif ($p==="dash" && $me) {
    echo '<section class="section soft"><div class="container"><h2>Your listings</h2><form method="post" class="card"><input type="hidden" name="act" value="save">';
    echo '<input class="input" name="title" placeholder="Product / service" required><br><br><div class="form-row"><select class="input" name="category"><option>General</option><option>Food</option><option>Craft</option><option>Wholesale</option></select><input class="input" name="city" placeholder="City" required></div><br>';
    echo '<input class="input" name="price" placeholder="Price"><br><br><textarea name="about"></textarea><br><br><button class="btn">Add listing</button></form>';
    echo '<h3>Orders</h3><table class="table"><tr><th>Listing</th><th>Buyer</th><th>Qty</th><th>Message</th></tr>';
    foreach ($inbox as $r) echo '<tr><td>#'.(int)$r["listing_id"].'</td><td>'.h($r["name"]).'</td><td>'.(int)$r["qty"].'</td><td>'.h($r["message"]).'</td></tr>';
    if (!$inbox) echo '<tr><td colspan="4">None yet.</td></tr>';
    echo '</table></div></section>';
} elseif ($p==="dir") {
    $q=trim((string)($_GET["q"]??"")); $sql="SELECT id,title,category,city,price FROM dg_listings WHERE status IN ('pending','live')"; $a=[];
    if ($q!=="") { $sql.=" AND (title LIKE ? OR city LIKE ?)"; $a=["%$q%","%$q%"]; }
    $st=$db->prepare($sql." ORDER BY id DESC LIMIT 40"); $st->execute($a);
    echo '<section class="section"><div class="container"><h2>Marketplace</h2><form method="get"><input type="hidden" name="p" value="dir"><input class="input" name="q" value="'.h($q).'"> <button class="btn">Search</button></form><div class="list-grid">';
    foreach ($st as $r) echo '<div class="biz-card"><h3><a href="?p=view&id='.(int)$r["id"].'">'.h($r["title"]).'</a></h3><p>'.h($r["category"]).' · '.h($r["city"]).' · ₹'.h((string)($r["price"]?:'—')).'</p></div>';
    echo '</div></div></section>';
} elseif ($p==="view") {
    $st=$db->prepare("SELECT * FROM dg_listings WHERE id=?"); $st->execute([(int)($_GET["id"]??0)]); $v=$st->fetch();
    if (!$v) echo '<section class="section"><div class="container">Not found.</div></section>';
    else {
        echo '<section class="section"><div class="container"><div class="biz-card"><h2>'.h($v["title"]).'</h2><p>'.nl2br(h($v["about"])).'</p></div><br><div class="card"><h3>Order request</h3>';
        if (!empty($_GET["sent"])) echo '<div class="notice">Request sent.</div>';
        echo '<form method="post"><input type="hidden" name="act" value="order"><input type="hidden" name="listing_id" value="'.(int)$v["id"].'">';
        echo '<div class="form-row"><input class="input" name="name" required><input class="input" type="email" name="email" required></div><br>';
        echo '<div class="form-row"><input class="input" name="phone"><input class="input" name="qty" value="1"></div><br>';
        echo '<textarea name="message" required></textarea><br><br><button class="btn">Send request</button></form></div></div></section>';
    }
} else echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">बाज़ार खुले, व्यापार बढ़े</span><h1>List products. Get found. Sell locally.</h1><p>Seller visibility and product discovery for everyday buyers.</p><div class="hero-actions"><a class="btn" href="?p=join">Become a seller</a><a class="btn light" href="?p=dir">Browse</a></div></div></div></section>';
if ($p==="dash" && !$me) { header("Location: ?p=login"); exit; }
shell_end("DoBajar");
'''

INDEX["doaaram"] = r'''<?php
declare(strict_types=1);
require __DIR__ . "/boot.php";
$err=""; try { $db=db(); } catch (Throwable $e) { exit("Open install.php?key=dogalaxy"); }
if ($_SERVER["REQUEST_METHOD"]==="POST") $err=handle_auth($db);
$p=$_GET["p"]??"home"; $me=user();
if (($_POST["act"]??"")==="save" && $me) {
    $db->prepare("INSERT INTO dg_services (provider_id,title,category,city,rate,about,status) VALUES (?,?,?,?,?,?,'pending')")
        ->execute([$me["id"], trim($_POST["title"]), $_POST["category"]?:"Home", trim($_POST["city"]), trim($_POST["rate"]??""), trim($_POST["about"]??"")]);
    header("Location: ?p=dash&ok=1"); exit;
}
if (($_POST["act"]??"")==="request") {
    $db->prepare("INSERT INTO dg_service_requests (service_id,name,email,phone,when_date,message) VALUES (?,?,?,?,?,?)")
        ->execute([(int)$_POST["service_id"], trim($_POST["name"]), $_POST["email"], trim($_POST["phone"]??""), $_POST["when_date"]?:null, trim($_POST["message"])]);
    header("Location: ?p=view&id=".(int)$_POST["service_id"]."&sent=1"); exit;
}
$mine=[]; $inbox=[];
if ($me) { $st=$db->prepare("SELECT * FROM dg_services WHERE provider_id=? ORDER BY id DESC"); $st->execute([$me["id"]]); $mine=$st->fetchAll();
  if ($mine) { $ids=implode(",",array_map(fn($x)=>(int)$x["id"],$mine)); $inbox=$db->query("SELECT * FROM dg_service_requests WHERE service_id IN ($ids) ORDER BY id DESC LIMIT 20")->fetchAll(); } }
shell_start("DoAaram","A",["?p=home"=>"Home","?p=dir"=>"Services"],"?p=join","Offer a service");
if ($p==="join"||$p==="login") auth_forms($err,$p==="join"?"join":"login");
elseif ($p==="dash" && $me) {
    echo '<section class="section soft"><div class="container"><h2>Your services</h2><form method="post" class="card"><input type="hidden" name="act" value="save">';
    echo '<input class="input" name="title" placeholder="Service" required><br><br><div class="form-row"><select class="input" name="category"><option>Home</option><option>Tuition</option><option>Transport</option><option>Care</option></select><input class="input" name="city" required placeholder="City"></div><br>';
    echo '<input class="input" name="rate" placeholder="Rate"><br><br><textarea name="about"></textarea><br><br><button class="btn">Add service</button></form>';
    echo '<h3>Requests</h3><table class="table"><tr><th>From</th><th>When</th><th>Message</th></tr>';
    foreach ($inbox as $r) echo '<tr><td>'.h($r["name"]).'</td><td>'.h((string)$r["when_date"]).'</td><td>'.h($r["message"]).'</td></tr>';
    if (!$inbox) echo '<tr><td colspan="3">None yet.</td></tr>';
    echo '</table></div></section>';
} elseif ($p==="dir") {
    $q=trim((string)($_GET["q"]??"")); $sql="SELECT id,title,category,city,rate FROM dg_services WHERE status IN ('pending','live')"; $a=[];
    if ($q!=="") { $sql.=" AND (title LIKE ? OR city LIKE ?)"; $a=["%$q%","%$q%"]; }
    $st=$db->prepare($sql." ORDER BY id DESC LIMIT 40"); $st->execute($a);
    echo '<section class="section"><div class="container"><h2>Local services</h2><form method="get"><input type="hidden" name="p" value="dir"><input class="input" name="q" value="'.h($q).'"> <button class="btn">Search</button></form><div class="list-grid">';
    foreach ($st as $r) echo '<div class="biz-card"><h3><a href="?p=view&id='.(int)$r["id"].'">'.h($r["title"]).'</a></h3><p>'.h($r["category"]).' · '.h($r["city"]).'</p></div>';
    echo '</div></div></section>';
} elseif ($p==="view") {
    $st=$db->prepare("SELECT * FROM dg_services WHERE id=?"); $st->execute([(int)($_GET["id"]??0)]); $v=$st->fetch();
    if (!$v) echo '<section class="section"><div class="container">Not found.</div></section>';
    else {
        echo '<section class="section"><div class="container"><div class="biz-card"><h2>'.h($v["title"]).'</h2><p>'.nl2br(h($v["about"])).'</p></div><br><div class="card"><h3>Request this service</h3>';
        if (!empty($_GET["sent"])) echo '<div class="notice">Request sent.</div>';
        echo '<form method="post"><input type="hidden" name="act" value="request"><input type="hidden" name="service_id" value="'.(int)$v["id"].'">';
        echo '<div class="form-row"><input class="input" name="name" required><input class="input" type="email" name="email" required></div><br>';
        echo '<div class="form-row"><input class="input" name="phone"><input class="input" type="date" name="when_date"></div><br>';
        echo '<textarea name="message" required></textarea><br><br><button class="btn">Send</button></form></div></div></section>';
    }
} else echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">आराम मिले, काम बने</span><h1>Everyday services, close to home.</h1><p>Plumbers, tutors, drivers, home help — request with a clear identity.</p><div class="hero-actions"><a class="btn" href="?p=join">Offer a service</a><a class="btn light" href="?p=dir">Find help</a></div></div></div></section>';
if ($p==="dash" && !$me) { header("Location: ?p=login"); exit; }
shell_end("DoAaram");
'''

INDEX["donirman"] = r'''<?php
declare(strict_types=1);
require __DIR__ . "/boot.php";
$err=""; try { $db=db(); } catch (Throwable $e) { exit("Open install.php?key=dogalaxy"); }
if ($_SERVER["REQUEST_METHOD"]==="POST") $err=handle_auth($db);
$p=$_GET["p"]??"home"; $me=user();
if (($_POST["act"]??"")==="save" && $me) {
    $st=$db->prepare("SELECT id FROM dg_contractors WHERE owner_id=?"); $st->execute([$me["id"]]); $id=(int)$st->fetchColumn();
    $vals=[trim($_POST["legal_name"]), $_POST["trade"]?:"Civil", trim($_POST["city"]), trim($_POST["about"]??"")];
    if (!$id) { $db->prepare("INSERT INTO dg_contractors (owner_id,legal_name,trade,city,about) VALUES (?,?,?,?,?)")->execute(array_merge([$me["id"]],$vals)); $id=(int)$db->lastInsertId(); }
    else $db->prepare("UPDATE dg_contractors SET legal_name=?,trade=?,city=?,about=? WHERE id=?")->execute(array_merge($vals,[$id]));
    header("Location: ?p=dash&ok=1"); exit;
}
if (($_POST["act"]??"")==="lead") {
    $db->prepare("INSERT INTO dg_project_leads (contractor_id,name,email,phone,site_city,message) VALUES (?,?,?,?,?,?)")
        ->execute([(int)$_POST["contractor_id"], trim($_POST["name"]), $_POST["email"], trim($_POST["phone"]??""), trim($_POST["site_city"]??""), trim($_POST["message"])]);
    header("Location: ?p=view&id=".(int)$_POST["contractor_id"]."&sent=1"); exit;
}
$row=null; $inbox=[];
if ($me) { $st=$db->prepare("SELECT * FROM dg_contractors WHERE owner_id=?"); $st->execute([$me["id"]]); $row=$st->fetch()?:null;
  if ($row) { $st=$db->prepare("SELECT * FROM dg_project_leads WHERE contractor_id=? ORDER BY id DESC LIMIT 20"); $st->execute([$row["id"]]); $inbox=$st->fetchAll(); } }
shell_start("DoNirman","N",["?p=home"=>"Home","?p=dir"=>"Contractors"],"?p=join","List your firm");
if ($p==="join"||$p==="login") auth_forms($err,$p==="join"?"join":"login");
elseif ($p==="dash" && $me) {
    echo '<section class="section soft"><div class="container"><h2>Contractor profile</h2><form method="post" class="card"><input type="hidden" name="act" value="save">';
    echo '<input class="input" name="legal_name" placeholder="Firm name" value="'.h($row["legal_name"]??"").'" required><br><br>';
    echo '<div class="form-row"><select class="input" name="trade"><option>Civil</option><option>Electrical</option><option>Plumbing</option><option>Interior</option></select><input class="input" name="city" value="'.h($row["city"]??"").'" required></div><br>';
    echo '<textarea name="about">'.h($row["about"]??"").'</textarea><br><br><button class="btn">Save</button></form>';
    echo '<h3>Project leads</h3><table class="table"><tr><th>From</th><th>City</th><th>Message</th></tr>';
    foreach ($inbox as $r) echo '<tr><td>'.h($r["name"]).'</td><td>'.h((string)$r["site_city"]).'</td><td>'.h($r["message"]).'</td></tr>';
    if (!$inbox) echo '<tr><td colspan="3">None yet.</td></tr>';
    echo '</table></div></section>';
} elseif ($p==="dir") {
    $q=trim((string)($_GET["q"]??"")); $sql="SELECT id,legal_name,trade,city FROM dg_contractors WHERE verify_status IN ('pending','verified')"; $a=[];
    if ($q!=="") { $sql.=" AND (legal_name LIKE ? OR city LIKE ?)"; $a=["%$q%","%$q%"]; }
    $st=$db->prepare($sql." ORDER BY id DESC LIMIT 40"); $st->execute($a);
    echo '<section class="section"><div class="container"><h2>Contractors</h2><form method="get"><input type="hidden" name="p" value="dir"><input class="input" name="q" value="'.h($q).'"> <button class="btn">Search</button></form><div class="list-grid">';
    foreach ($st as $r) echo '<div class="biz-card"><h3><a href="?p=view&id='.(int)$r["id"].'">'.h($r["legal_name"]).'</a></h3><p>'.h($r["trade"]).' · '.h($r["city"]).'</p></div>';
    echo '</div></div></section>';
} elseif ($p==="view") {
    $st=$db->prepare("SELECT * FROM dg_contractors WHERE id=?"); $st->execute([(int)($_GET["id"]??0)]); $v=$st->fetch();
    if (!$v) echo '<section class="section"><div class="container">Not found.</div></section>';
    else {
        echo '<section class="section"><div class="container"><div class="biz-card"><h2>'.h($v["legal_name"]).'</h2><p>'.nl2br(h($v["about"])).'</p></div><br><div class="card"><h3>Project enquiry</h3>';
        if (!empty($_GET["sent"])) echo '<div class="notice">Lead sent.</div>';
        echo '<form method="post"><input type="hidden" name="act" value="lead"><input type="hidden" name="contractor_id" value="'.(int)$v["id"].'">';
        echo '<div class="form-row"><input class="input" name="name" required><input class="input" type="email" name="email" required></div><br>';
        echo '<div class="form-row"><input class="input" name="phone"><input class="input" name="site_city" placeholder="Site city"></div><br>';
        echo '<textarea name="message" required></textarea><br><br><button class="btn">Send</button></form></div></div></section>';
    }
} else echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">निर्माण सही, भरोसा रहे</span><h1>Build smarter. Build sahi.</h1><p>Find contractors. Send a project lead. Keep the work local and verified.</p><div class="hero-actions"><a class="btn" href="?p=join">List your firm</a><a class="btn light" href="?p=dir">Find contractors</a></div></div></div></section>';
if ($p==="dash" && !$me) { header("Location: ?p=login"); exit; }
shell_end("DoNirman");
'''

INDEX["dovyapaar"] = r'''<?php
declare(strict_types=1);
require __DIR__ . "/boot.php";
$err=""; try { $db=db(); } catch (Throwable $e) { exit("Open install.php?key=dogalaxy"); }
if ($_SERVER["REQUEST_METHOD"]==="POST") $err=handle_auth($db);
$p=$_GET["p"]??"home"; $me=user();
if (($_POST["act"]??"")==="save" && $me) {
    $st=$db->prepare("SELECT id FROM dg_suppliers WHERE owner_id=?"); $st->execute([$me["id"]]); $id=(int)$st->fetchColumn();
    $vals=[trim($_POST["legal_name"]), $_POST["category"]?:"General", trim($_POST["city"]), trim($_POST["about"]??"")];
    if (!$id) { $db->prepare("INSERT INTO dg_suppliers (owner_id,legal_name,category,city,about) VALUES (?,?,?,?,?)")->execute(array_merge([$me["id"]],$vals)); $id=(int)$db->lastInsertId(); }
    else $db->prepare("UPDATE dg_suppliers SET legal_name=?,category=?,city=?,about=? WHERE id=?")->execute(array_merge($vals,[$id]));
    header("Location: ?p=dash&ok=1"); exit;
}
if (($_POST["act"]??"")==="rfq") {
    $db->prepare("INSERT INTO dg_rfqs (supplier_id,name,email,phone,item,qty,message) VALUES (?,?,?,?,?,?,?)")
        ->execute([(int)$_POST["supplier_id"], trim($_POST["name"]), $_POST["email"], trim($_POST["phone"]??""), trim($_POST["item"]), trim($_POST["qty"]??""), trim($_POST["message"])]);
    header("Location: ?p=view&id=".(int)$_POST["supplier_id"]."&sent=1"); exit;
}
$row=null; $inbox=[];
if ($me) { $st=$db->prepare("SELECT * FROM dg_suppliers WHERE owner_id=?"); $st->execute([$me["id"]]); $row=$st->fetch()?:null;
  if ($row) { $st=$db->prepare("SELECT * FROM dg_rfqs WHERE supplier_id=? ORDER BY id DESC LIMIT 20"); $st->execute([$row["id"]]); $inbox=$st->fetchAll(); } }
shell_start("DoVyapaar","V",["?p=home"=>"Home","?p=dir"=>"Suppliers"],"?p=join","List as supplier");
if ($p==="join"||$p==="login") auth_forms($err,$p==="join"?"join":"login");
elseif ($p==="dash" && $me) {
    echo '<section class="section soft"><div class="container"><h2>Supplier profile</h2><form method="post" class="card"><input type="hidden" name="act" value="save">';
    echo '<input class="input" name="legal_name" placeholder="Trade name" value="'.h($row["legal_name"]??"").'" required><br><br>';
    echo '<div class="form-row"><select class="input" name="category"><option>General</option><option>Raw material</option><option>Packaging</option><option>Machinery</option></select><input class="input" name="city" value="'.h($row["city"]??"").'" required></div><br>';
    echo '<textarea name="about">'.h($row["about"]??"").'</textarea><br><br><button class="btn">Save</button></form>';
    echo '<h3>RFQs</h3><table class="table"><tr><th>Item</th><th>From</th><th>Qty</th><th>Message</th></tr>';
    foreach ($inbox as $r) echo '<tr><td>'.h($r["item"]).'</td><td>'.h($r["name"]).'</td><td>'.h((string)$r["qty"]).'</td><td>'.h($r["message"]).'</td></tr>';
    if (!$inbox) echo '<tr><td colspan="4">None yet.</td></tr>';
    echo '</table></div></section>';
} elseif ($p==="dir") {
    $q=trim((string)($_GET["q"]??"")); $sql="SELECT id,legal_name,category,city FROM dg_suppliers WHERE verify_status IN ('pending','verified')"; $a=[];
    if ($q!=="") { $sql.=" AND (legal_name LIKE ? OR city LIKE ?)"; $a=["%$q%","%$q%"]; }
    $st=$db->prepare($sql." ORDER BY id DESC LIMIT 40"); $st->execute($a);
    echo '<section class="section"><div class="container"><h2>Suppliers</h2><form method="get"><input type="hidden" name="p" value="dir"><input class="input" name="q" value="'.h($q).'"> <button class="btn">Search</button></form><div class="list-grid">';
    foreach ($st as $r) echo '<div class="biz-card"><h3><a href="?p=view&id='.(int)$r["id"].'">'.h($r["legal_name"]).'</a></h3><p>'.h($r["category"]).' · '.h($r["city"]).'</p></div>';
    echo '</div></div></section>';
} elseif ($p==="view") {
    $st=$db->prepare("SELECT * FROM dg_suppliers WHERE id=?"); $st->execute([(int)($_GET["id"]??0)]); $v=$st->fetch();
    if (!$v) echo '<section class="section"><div class="container">Not found.</div></section>';
    else {
        echo '<section class="section"><div class="container"><div class="biz-card"><h2>'.h($v["legal_name"]).'</h2><p>'.nl2br(h($v["about"])).'</p></div><br><div class="card"><h3>Send RFQ</h3>';
        if (!empty($_GET["sent"])) echo '<div class="notice">RFQ sent.</div>';
        echo '<form method="post"><input type="hidden" name="act" value="rfq"><input type="hidden" name="supplier_id" value="'.(int)$v["id"].'">';
        echo '<div class="form-row"><input class="input" name="name" required><input class="input" type="email" name="email" required></div><br>';
        echo '<div class="form-row"><input class="input" name="item" placeholder="Item" required><input class="input" name="qty" placeholder="Qty"></div><br>';
        echo '<input class="input" name="phone" placeholder="Phone"><br><br><textarea name="message" required></textarea><br><br><button class="btn">Send RFQ</button></form></div></div></section>';
    }
} else echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">व्यापार बढ़े, साथ बने</span><h1>Find suppliers. Post an RFQ.</h1><p>Trade discovery for MSMEs — identity-first, city-first.</p><div class="hero-actions"><a class="btn" href="?p=join">List as supplier</a><a class="btn light" href="?p=dir">Find suppliers</a></div></div></div></section>';
if ($p==="dash" && !$me) { header("Location: ?p=login"); exit; }
shell_end("DoVyapaar");
'''


def main() -> None:
    sql = SCHEMA.read_text()
    for slug, php in INDEX.items():
        dest = ROOT / "apps" / slug
        dest.mkdir(parents=True, exist_ok=True)
        (dest / "assets").mkdir(exist_ok=True)
        shutil.copy2(KIT / "boot.php", dest / "boot.php")
        shutil.copy2(KIT / "install.php", dest / "install.php")
        shutil.copy2(KIT / ".htaccess", dest / ".htaccess")
        shutil.copy2(KIT / "assets" / "app.css", dest / "assets" / "app.css")
        (dest / "schema.sql").write_text(sql)
        (dest / "index.php").write_text(php)
        print(slug, (dest / "index.php").stat().st_size, "bytes")


if __name__ == "__main__":
    main()
