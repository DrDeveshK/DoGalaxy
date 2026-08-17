<?php
declare(strict_types=1);

function dr_cats(): array
{
    return ['Sales & Marketing', 'IT & Software', 'Delivery & Field Work', 'Accounts & Finance', 'Hospitality', 'Construction', 'Home Services', 'Office/Admin'];
}

function product_migrate(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS dg_candidates (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL UNIQUE, full_name TEXT, phone TEXT, email TEXT, city TEXT, preferred_role TEXT, education TEXT, experience TEXT, skills TEXT, availability TEXT, resume_url TEXT, about TEXT, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
}

function product_seed(PDO $db): void
{
}

function product_handle_post(PDO $db, string $act, string &$err): bool
{
    if ($act === 'save_resume') {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $name = trim((string) ($_POST['full_name'] ?? ''));
        if ($name === '') {
            $err = 'Name is required.';
            return true;
        }
        if (!user()) {
            $pass = (string) ($_POST['password'] ?? '');
            if (!$email || strlen($pass) < 8) {
                $err = 'Email and password (8+) are required to save a resume.';
                return true;
            }
            try {
                $db->prepare('INSERT INTO dg_users (email, password_hash, name, phone, role) VALUES (?,?,?,?,?)')
                    ->execute([$email, password_hash($pass, PASSWORD_DEFAULT), $name, trim((string) ($_POST['phone'] ?? '')), 'seeker']);
            } catch (Throwable $e) {
                $err = 'That email already has an account. Log in, then save.';
                return true;
            }
            $uid = (int) $db->lastInsertId();
            $_SESSION['u'] = ['id' => $uid, 'email' => (string) $email, 'name' => $name, 'role' => 'seeker'];
        }
        $uid = (int) user()['id'];
        $cols = ['full_name', 'phone', 'email', 'city', 'preferred_role', 'education', 'experience', 'skills', 'availability', 'resume_url', 'about'];
        $vals = [];
        foreach ($cols as $c) {
            $vals[] = trim((string) ($_POST[$c] ?? '')) ?: null;
        }
        $st = $db->prepare('SELECT id FROM dg_candidates WHERE user_id=?');
        $st->execute([$uid]);
        if ($st->fetch()) {
            $set = implode(',', array_map(static fn($c) => $c . '=?', $cols));
            $db->prepare('UPDATE dg_candidates SET ' . $set . ', updated_at=? WHERE user_id=?')->execute([...$vals, date('c'), $uid]);
        } else {
            $db->prepare('INSERT INTO dg_candidates (user_id,' . implode(',', $cols) . ') VALUES (?,' . implode(',', array_fill(0, count($cols), '?')) . ')')->execute([$uid, ...$vals]);
        }
        flash('Resume saved. Print it or attach the link when you apply.');
        go('resume');
    }
    return false;
}

function product_render_page(PDO $db, string $page, array $P, ?array $me): bool
{
    if ($page === 'resume' || $page === 'myresume') {
        dr_resume($db, $me);
        return true;
    }
    if ($page === 'search') {
        dr_search($db);
        return true;
    }
    if ($page === 'career') {
        dr_career();
        return true;
    }
    if ($page === 'home') {
        dr_home($db, $P);
        return true;
    }
    return false;
}

function dr_search(PDO $db): void
{
    $role = trim((string) ($_GET['role'] ?? ''));
    $city = trim((string) ($_GET['city'] ?? ''));
    $type = trim((string) ($_GET['type'] ?? ''));
    $sql = "SELECT * FROM dg_jobs WHERE status IN ('open','pending','verified')";
    $args = [];
    if ($role !== '') {
        $sql .= ' AND (title LIKE ? OR skills LIKE ?)';
        $args[] = "%$role%";
        $args[] = "%$role%";
    }
    if ($city !== '') {
        $sql .= ' AND (city LIKE ? OR locality LIKE ?)';
        $args[] = "%$city%";
        $args[] = "%$city%";
    }
    if ($type !== '') {
        $sql .= ' AND job_type=?';
        $args[] = $type;
    }
    $st = $db->prepare($sql . ' ORDER BY id DESC LIMIT 40');
    $st->execute($args);
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Job search</h2><p>Naukri-style filters for role, city/locality, type, salary and experience.</p></div><a class="btn light" href="?p=resume">Build resume</a></div>';
    echo '<form method="get" class="form-row" style="max-width:860px;margin-bottom:24px"><input type="hidden" name="p" value="search"><input class="input" name="role" value="' . h($role) . '" placeholder="Role or skill"><input class="input" name="city" value="' . h($city) . '" placeholder="City or locality"><select class="input" name="type"><option value="">Any type</option>';
    foreach (['Full-time', 'Part-time', 'Gig', 'Contract', 'Apprentice', 'Internship'] as $t) {
        echo '<option' . ($type === $t ? ' selected' : '') . '>' . h($t) . '</option>';
    }
    echo '</select><button class="btn" type="submit">Search</button></form><div class="list-grid">';
    foreach ($st as $r) {
        echo '<div class="biz-card"><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['title']) . '</a></h3><p>' . h(($r['company'] ?? '') . ' · ' . ($r['locality'] ?? $r['city'] ?? '')) . '</p><div class="meta"><span>' . h($r['job_type'] ?? '') . '</span><span>' . h($r['pay'] ?? '') . '</span><span>' . h($r['experience'] ?? '') . '</span></div><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">Apply with note</a></div>';
    }
    echo '</div></div></section>';
}

function dr_cand(PDO $db, ?array $me): array
{
    if (!$me) {
        return [];
    }
    $st = $db->prepare('SELECT * FROM dg_candidates WHERE user_id=?');
    $st->execute([$me['id']]);
    return $st->fetch() ?: [];
}

function dr_home(PDO $db, array $P): void
{
    echo '<section class="hero"><div class="container hero-grid"><div><span class="eyebrow">' . h(setting('eyebrow')) . '</span><h1>' . h(setting('hero_h1')) . '</h1><p>' . h(setting('hero_p')) . '</p>';
    echo '<div class="hero-actions"><a class="btn" href="?p=dir">Find jobs</a><a class="btn light" href="?p=join">Post a job</a><a class="btn light" href="?p=resume">Build resume</a></div></div>';
    echo '<div class="search-panel"><h3>Search jobs</h3><form method="get"><input type="hidden" name="p" value="dir"><input class="input" name="q" placeholder="Title, skill, city"><br><br><button class="btn" type="submit">Search</button></form></div></div></section>';
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Popular categories</h2></div></div><div class="grid-3">';
    foreach (dr_cats() as $c) {
        echo '<a class="feature" href="?p=dir&q=' . urlencode($c) . '"><h3>' . h($c) . '</h3><p>Explore openings</p></a>';
    }
    echo '</div></div></section>';
    $jobs = $db->query("SELECT * FROM dg_jobs WHERE status IN ('open','pending','verified') ORDER BY id DESC LIMIT 6")->fetchAll();
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Latest jobs</h2></div><a class="btn light" href="?p=dir">All jobs</a></div><div class="list-grid">';
    foreach ($jobs as $r) {
        echo '<div class="biz-card"><h3><a href="?p=view&id=' . (int) $r['id'] . '">' . h($r['title']) . '</a></h3><p>' . h(($r['company'] ?? '') . ' · ' . ($r['city'] ?? '')) . '</p><div class="meta"><span>' . h($r['job_type'] ?? '') . '</span><span>' . h($r['pay'] ?? '') . '</span></div><a class="btn light" href="?p=view&id=' . (int) $r['id'] . '">Apply</a></div>';
    }
    echo '</div></div></section>';
}

function dr_resume(PDO $db, ?array $me): void
{
    $c = dr_cand($db, $me);
    echo '<section class="section soft"><div class="container"><div class="section-title"><div><h2>Resume builder</h2><p>Same seeker loop as live DoRojgar: profile → printable resume → apply with cover note.</p></div></div>';
    echo '<div class="est-grid"><div class="card"><form method="post">' . csrf_fields('save_resume');
    echo '<div class="form-row"><input class="input" name="full_name" required placeholder="Full name" value="' . h($c['full_name'] ?? ($me['name'] ?? '')) . '"><input class="input" name="phone" placeholder="Phone" value="' . h($c['phone'] ?? '') . '"></div><br>';
    echo '<div class="form-row"><input class="input" type="email" name="email" required placeholder="Email" value="' . h($c['email'] ?? ($me['email'] ?? '')) . '"><input class="input" name="city" placeholder="City" value="' . h($c['city'] ?? '') . '"></div><br>';
    if (!$me) {
        echo '<input class="input" type="password" name="password" minlength="8" placeholder="Password (8+) to save" required><br><br>';
    }
    echo '<div class="form-row"><input class="input" name="preferred_role" placeholder="Preferred role" value="' . h($c['preferred_role'] ?? '') . '"><input class="input" name="availability" placeholder="Immediate / 30 days" value="' . h($c['availability'] ?? '') . '"></div><br>';
    echo '<input class="input" name="education" placeholder="Education" value="' . h($c['education'] ?? '') . '"><br><br>';
    echo '<input class="input" name="experience" placeholder="Experience (years / roles)" value="' . h($c['experience'] ?? '') . '"><br><br>';
    echo '<input class="input" name="skills" placeholder="Skills (comma separated)" value="' . h($c['skills'] ?? '') . '"><br><br>';
    echo '<input class="input" name="resume_url" placeholder="External resume URL (optional)" value="' . h($c['resume_url'] ?? '') . '"><br><br>';
    echo '<textarea name="about" placeholder="About you">' . h($c['about'] ?? '') . '</textarea><br><br>';
    echo '<button class="btn" type="submit">Save resume</button></form></div>';
    echo '<div class="card" id="cv"><h3>' . h($c['full_name'] ?? 'Your name') . '</h3>';
    if ($c) {
        echo '<p>' . h(($c['preferred_role'] ?? '') . ' · ' . ($c['city'] ?? '') . ' · ' . ($c['phone'] ?? '') . ' · ' . ($c['email'] ?? '')) . '</p>';
        echo '<p><b>Education</b><br>' . nl2br(h((string) $c['education'])) . '</p>';
        echo '<p><b>Experience</b><br>' . nl2br(h((string) $c['experience'])) . '</p>';
        echo '<p><b>Skills</b><br>' . h((string) $c['skills']) . '</p>';
        echo '<p>' . nl2br(h((string) $c['about'])) . '</p>';
        echo '<button class="btn light" type="button" onclick="window.print()">Print / save PDF</button> <a class="btn" href="?p=dir">Apply to jobs</a>';
    } else {
        echo '<p class="muted">Fill the form. Your resume preview appears here.</p>';
    }
    echo '</div></div></div></section>';
}

function dr_career(): void
{
    $tips = [
        ['Resume', 'Keep one page. Lead with the role you want, city, and a phone that is always on.'],
        ['Interview', 'Carry ID, a printed resume, and two examples of work you finished on time.'],
        ['Local jobs', 'Say your locality. Employers on DoRojgar hire people they can meet.'],
        ['Cover note', 'Three lines: who you are, why this shop/factory, when you can start.'],
    ];
    echo '<section class="section"><div class="container"><div class="section-title"><div><h2>Career center</h2><p>Resume tips, interview prep and local-job guidance.</p></div></div><div class="grid-3">';
    foreach ($tips as $t) {
        echo '<div class="feature"><h3>' . h($t[0]) . '</h3><p>' . h($t[1]) . '</p></div>';
    }
    echo '</div><p style="margin-top:24px"><a class="btn" href="?p=resume">Open resume builder</a></p></div></section>';
}
