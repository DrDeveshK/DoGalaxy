#!/usr/bin/env python3
"""Generate E2E product plugins + classic themes for remaining Do Galaxy verticals."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

APPS = {
    "dovishram": {
        "name": "Do Vishram",
        "role": "vishram_host",
        "role_label": "Host",
        "cpt": "stay",
        "cpt_label": "Stays",
        "slug": "stay",
        "dir": "stays",
        "kicker": "विश्राम मिले, मन टिके",
        "h1": "List a stay. Find a stay. Request dates.",
        "lede": "Hosts publish rooms and retreats. Guests search and send a stay request with dates.",
        "join_cta": "List your stay",
        "fields": [
            ("stay_name", "Stay name", "text", True),
            ("city", "City", "text", True),
            ("stay_type", "Type", "select:Hotel,Homestay,Resort,Room,Retreat", True),
            ("price", "Price per night (₹)", "text", False),
            ("guests", "Max guests", "text", False),
            ("about", "About", "textarea", False),
        ],
        "request_fields": [
            ("checkin", "Check-in", "date"),
            ("checkout", "Check-out", "date"),
            ("guests", "Guests", "text"),
        ],
        "request_label": "Stay request",
        "industries": ["Hotel", "Homestay", "Resort", "Room", "Retreat"],
        "color": "#2f4a3c",
        "accent": "#6b8f71",
    },
    "dorojgar": {
        "name": "Do Rojgar",
        "role": "rojgar_employer",
        "role_label": "Employer",
        "cpt": "job",
        "cpt_label": "Jobs",
        "slug": "jobs",
        "dir": "jobs",
        "kicker": "रोज़गार आपके पास",
        "h1": "Post a job. Apply. Track the outcome.",
        "lede": "Employers post local roles. Seekers apply with a short profile. Both sides see status on a dashboard.",
        "join_cta": "I am hiring",
        "seeker_role": "rojgar_seeker",
        "fields": [
            ("job_title", "Job title", "text", True),
            ("city", "City", "text", True),
            ("job_type", "Type", "select:Full-time,Part-time,Gig,Contract", True),
            ("pay", "Pay", "text", False),
            ("about", "Description", "textarea", False),
        ],
        "request_fields": [
            ("experience", "Experience", "text"),
            ("phone", "Phone", "text"),
        ],
        "request_label": "Application",
        "industries": ["Full-time", "Part-time", "Gig", "Contract"],
        "color": "#6b2d1a",
        "accent": "#c45c26",
    },
    "doswagat": {
        "name": "Do Swagat",
        "role": "swagat_partner",
        "role_label": "Partner",
        "cpt": "venue",
        "cpt_label": "Venues",
        "slug": "venues",
        "dir": "venues",
        "kicker": "स्वागत हो",
        "h1": "Book a venue. Brief a partner. Track the request.",
        "lede": "Partners list venues and services. Families and firms send an event request with date and guest count.",
        "join_cta": "List a venue",
        "fields": [
            ("venue_name", "Venue / service name", "text", True),
            ("city", "City", "text", True),
            ("kind", "Kind", "select:Venue,Caterer,Decorator,Photographer,Package", True),
            ("capacity", "Capacity", "text", False),
            ("about", "About", "textarea", False),
        ],
        "request_fields": [
            ("event_date", "Event date", "date"),
            ("guests", "Guests", "text"),
            ("event_type", "Event type", "text"),
        ],
        "request_label": "Event request",
        "industries": ["Venue", "Caterer", "Decorator", "Photographer", "Package"],
        "color": "#5c1a2e",
        "accent": "#b8954a",
    },
    "dorishta": {
        "name": "Do Rishta",
        "role": "rishta_member",
        "role_label": "Member",
        "cpt": "profile",
        "cpt_label": "Profiles",
        "slug": "profiles",
        "dir": "profiles",
        "kicker": "अपना रिश्ता यहीं",
        "h1": "Create a verified profile. Express interest with care.",
        "lede": "Family-friendly matrimony. 21+ only. Interest is private — no public chat wall.",
        "join_cta": "Create profile",
        "fields": [
            ("display_name", "Name for profile", "text", True),
            ("city", "City", "text", True),
            ("age", "Age (21+)", "text", True),
            ("community", "Community (optional)", "text", False),
            ("about", "About", "textarea", False),
        ],
        "request_fields": [
            ("note", "A respectful note", "textarea"),
        ],
        "request_label": "Interest",
        "industries": ["Family-led", "Professional", "Community"],
        "color": "#4a2040",
        "accent": "#a85a7a",
        "min_age": 21,
    },
    "dobajar": {
        "name": "Do Bajar",
        "role": "bajar_seller",
        "role_label": "Seller",
        "cpt": "listing",
        "cpt_label": "Listings",
        "slug": "listings",
        "dir": "listings",
        "kicker": "बाज़ार आपके द्वार",
        "h1": "List a product. Discover a stall. Send an order request.",
        "lede": "Sellers from Do Udyog get a stall. Buyers browse and request an order — the commerce layer of Do Galaxy.",
        "join_cta": "Open a stall",
        "fields": [
            ("listing_name", "Product name", "text", True),
            ("city", "City", "text", True),
            ("category", "Category", "select:Food,Craft,Apparel,Home,Wholesale,Other", True),
            ("price", "Price (₹)", "text", False),
            ("about", "Details", "textarea", False),
        ],
        "request_fields": [
            ("qty", "Quantity", "text"),
            ("phone", "Phone", "text"),
        ],
        "request_label": "Order request",
        "industries": ["Food", "Craft", "Apparel", "Home", "Wholesale", "Other"],
        "color": "#3d2a12",
        "accent": "#c4a035",
    },
}


def field_input(name, typ, required):
    req = " required" if required else ""
    if typ.startswith("select:"):
        opts = "".join(f"<option>{o}</option>" for o in typ.split(":", 1)[1].split(","))
        return f'<select name="{name}"{req}>{opts}</select>'
    if typ == "textarea":
        return f'<textarea name="{name}" rows="4"{req}></textarea>'
    return f'<input type="{typ}" name="{name}"{req}>'


def write_app(slug, a):
    pdir = ROOT / "plugins" / f"{slug}-app"
    (pdir / "inc").mkdir(parents=True, exist_ok=True)
    (pdir / "views").mkdir(parents=True, exist_ok=True)
    (pdir / "assets").mkdir(parents=True, exist_ok=True)
    prefix = slug
    cpt = a["cpt"]
    role = a["role"]
    fn = f"{slug}_activate"

    (pdir / f"{slug}-app.php").write_text(
        f"""<?php
/**
 * Plugin Name: {a['name']} App
 * Description: End-to-end {a['name']} product.
 * Version: 1.0.0
 * Author: Dr. Devesh Kumar Sharma
 */
if (!defined('ABSPATH')) {{ exit; }}
define('{prefix.upper()}_DIR', plugin_dir_path(__FILE__));
define('{prefix.upper()}_URL', plugin_dir_url(__FILE__));
require_once {prefix.upper()}_DIR . 'inc/app.php';
register_activation_hook(__FILE__, '{fn}');
"""
    )

    extra_role = ""
    roles_extra = ""
    if a.get("seeker_role"):
        extra_role = f"    add_role('{a['seeker_role']}', 'Seeker', ['read' => true]);\n"
        roles_extra = f", '{a['seeker_role']}'"

    (pdir / "inc" / "app.php").write_text(
        f"""<?php
if (!defined('ABSPATH')) {{ exit; }}

function {fn}(): void {{
    add_role('{role}', '{a['role_label']}', ['read' => true, 'upload_files' => true]);
{extra_role}    foreach (['join'=>'Join','login'=>'Login','dashboard'=>'Dashboard','{a['dir']}'=>'{a['cpt_label']}','contact'=>'Contact'] as $s=>$t) {{
        if (!get_page_by_path($s)) {{
            wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$t,'post_name'=>$s]);
        }}
    }}
    {prefix}_types();
    flush_rewrite_rules();
}}

add_action('init', '{prefix}_types');
function {prefix}_types(): void {{
    register_post_type('{cpt}', [
        'labels' => ['name' => '{a['cpt_label']}', 'singular_name' => '{a['cpt_label'][:-1] if a['cpt_label'].endswith('s') else a['cpt_label']}'],
        'public' => true, 'has_archive' => false, 'rewrite' => ['slug' => '{a['slug']}'],
        'show_in_rest' => true, 'menu_icon' => 'dashicons-admin-site', 'supports' => ['title','editor','excerpt'],
    ]);
    register_post_type('{prefix}_req', [
        'labels' => ['name' => '{a['request_label']}s', 'singular_name' => '{a['request_label']}'],
        'public' => false, 'show_ui' => true, 'supports' => ['title','editor'],
    ]);
}}

add_action('wp_enqueue_scripts', function () {{
    wp_enqueue_style('{prefix}-app', {prefix.upper()}_URL . 'assets/app.css', [], '1.0.0');
}});

function {prefix}_mine($uid = 0) {{
    $uid = $uid ?: get_current_user_id();
    if (!$uid) return null;
    $q = new WP_Query(['post_type'=>'{cpt}','author'=>$uid,'posts_per_page'=>1,'post_status'=>['publish','pending','draft']]);
    return $q->have_posts() ? $q->posts[0] : null;
}}
function {prefix}_need_login() {{
    if (!is_user_logged_in()) {{ wp_safe_redirect(home_url('/login/')); exit; }}
}}

add_action('admin_post_nopriv_{prefix}_register', '{prefix}_register');
add_action('admin_post_{prefix}_register', '{prefix}_register');
function {prefix}_register() {{
    if (!wp_verify_nonce($_POST['_n'] ?? '', '{prefix}_register')) wp_die('bad nonce');
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $name = sanitize_text_field(wp_unslash($_POST['full_name'] ?? ''));
    $title = sanitize_text_field(wp_unslash($_POST['item_name'] ?? ''));
    if (!$name || !is_email($email) || strlen($pass) < 8 || !$title) {{
        wp_safe_redirect(home_url('/join/?err=missing')); exit;
    }}
    if (email_exists($email)) {{ wp_safe_redirect(home_url('/join/?err=exists')); exit; }}
    $role = sanitize_text_field(wp_unslash($_POST['as_role'] ?? '{role}'));
    $allowed = ['{role}'{roles_extra}];
    if (!in_array($role, $allowed, true)) $role = '{role}';
    $uid = wp_insert_user(['user_login'=>sanitize_user(current(explode('@',$email)),true).wp_rand(10,99),'user_email'=>$email,'user_pass'=>$pass,'display_name'=>$name,'role'=>$role]);
    if (is_wp_error($uid)) {{ wp_safe_redirect(home_url('/join/?err=user')); exit; }}
    if ($role === '{role}') {{
        $id = wp_insert_post(['post_type'=>'{cpt}','post_status'=>'pending','post_title'=>$title,'post_author'=>$uid,'post_content'=>'']);
        if (!is_wp_error($id)) update_post_meta($id, 'city', sanitize_text_field(wp_unslash($_POST['city'] ?? '')));
    }}
    wp_set_current_user($uid); wp_set_auth_cookie($uid, true);
    wp_safe_redirect(home_url('/dashboard/?joined=1')); exit;
}}

add_action('admin_post_nopriv_{prefix}_login', '{prefix}_login');
add_action('admin_post_{prefix}_login', '{prefix}_login');
function {prefix}_login() {{
    if (!wp_verify_nonce($_POST['_n'] ?? '', '{prefix}_login')) wp_die('bad nonce');
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $u = get_user_by('email', $email);
    $user = $u ? wp_signon(['user_login'=>$u->user_login,'user_password'=>(string)($_POST['password'] ?? ''),'remember'=>true], is_ssl()) : new WP_Error();
    if (is_wp_error($user)) {{ wp_safe_redirect(home_url('/login/?err=1')); exit; }}
    wp_safe_redirect(home_url('/dashboard/')); exit;
}}

add_action('admin_post_{prefix}_save', function () {{
    if (!wp_verify_nonce($_POST['_n'] ?? '', '{prefix}_save')) wp_die('bad nonce');
    {prefix}_need_login();
    $item = {prefix}_mine();
    if (!$item) {{ wp_safe_redirect(home_url('/join/')); exit; }}
    wp_update_post(['ID'=>$item->ID,'post_title'=>sanitize_text_field(wp_unslash($_POST['item_name'] ?? $item->post_title)),'post_content'=>wp_kses_post(wp_unslash($_POST['about'] ?? ''))]);
    foreach (['city','kind','stay_type','job_type','category','price','pay','guests','capacity','age','community','phone'] as $k) {{
        if (isset($_POST[$k])) update_post_meta($item->ID, $k, sanitize_text_field(wp_unslash($_POST[$k])));
    }}
    wp_safe_redirect(home_url('/dashboard/?saved=1')); exit;
}});

add_action('admin_post_nopriv_{prefix}_req', '{prefix}_req');
add_action('admin_post_{prefix}_req', '{prefix}_req');
function {prefix}_req() {{
    if (!wp_verify_nonce($_POST['_n'] ?? '', '{prefix}_req')) wp_die('bad nonce');
    $pid = (int) ($_POST['item_id'] ?? 0);
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $msg = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    if (!$name || !is_email($email) || !$msg || !$pid) {{ wp_safe_redirect(wp_get_referer() ?: home_url('/')); exit; }}
    $rid = wp_insert_post(['post_type'=>'{prefix}_req','post_status'=>'private','post_title'=>$name.' → '.get_the_title($pid),'post_content'=>$msg]);
    if (!is_wp_error($rid)) {{
        update_post_meta($rid, 'item_id', $pid);
        update_post_meta($rid, 'email', $email);
        foreach (['checkin','checkout','guests','experience','phone','event_date','event_type','qty','note'] as $k) {{
            if (!empty($_POST[$k])) update_post_meta($rid, $k, sanitize_text_field(wp_unslash($_POST[$k])));
        }}
        $owner = (int) get_post_field('post_author', $pid);
        if ($owner && ($u = get_userdata($owner))) wp_mail($u->user_email, '{a['request_label']} on {a['name']}', $name.' <'.$email.'>\\n\\n'.$msg);
    }}
    wp_safe_redirect(add_query_arg('sent','1', wp_get_referer() ?: home_url('/'))); exit;
}}

function {prefix}_inbox($item_id) {{
    return (new WP_Query(['post_type'=>'{prefix}_req','post_status'=>'private','posts_per_page'=>30,'meta_key'=>'item_id','meta_value'=>$item_id]))->posts;
}}

add_filter('template_include', function ($t) {{
    if (is_front_page()) return {prefix.upper()}_DIR . 'views/home.php';
    if (is_singular('{cpt}')) return {prefix.upper()}_DIR . 'views/single.php';
    if (!is_page()) return $t;
    $s = get_post_field('post_name', get_queried_object_id());
    $map = ['join'=>'join.php','login'=>'login.php','dashboard'=>'dash.php','{a['dir']}'=>'dir.php','contact'=>'contact.php'];
    if (isset($map[$s])) {{
        if ($s === 'dashboard') {prefix}_need_login();
        return {prefix.upper()}_DIR . 'views/' . $map[$s];
    }}
    return $t;
}});
"""
    )

    css = Path(ROOT / "plugins/doudyog-app/assets/app.css").read_text()
    (pdir / "assets" / "app.css").write_text(css.replace("#1b3d4a", a["color"]).replace("#c45c26", a["accent"]))

    seeker = ""
    if a.get("seeker_role"):
        seeker = """
    <label>I am
      <select name="as_role">
        <option value="%s">Employer — I want to hire</option>
        <option value="%s">Seeker — I want work</option>
      </select>
    </label>""" % (a["role"], a["seeker_role"])

    (pdir / "views" / "home.php").write_text(
        f"""<?php get_header(); ?>
<section class="ud-hero">
  <p class="ud-kicker">{a['kicker']}</p>
  <h1>{a['h1']}</h1>
  <p class="ud-lede">{a['lede']}</p>
  <div class="ud-actions">
    <a class="ud-btn" href="<?php echo esc_url(home_url('/join/')); ?>">{a['join_cta']}</a>
    <a class="ud-btn ud-btn-ghost" href="<?php echo esc_url(home_url('/{a['dir']}/')); ?>">Browse {a['cpt_label'].lower()}</a>
  </div>
</section>
<section class="ud-band">
  <div class="ud-grid3">
    <article class="ud-card"><h3>1. Join</h3><p>Create an account and your first listing or profile.</p></article>
    <article class="ud-card"><h3>2. Publish</h3><p>Admin verifies. The item appears in search.</p></article>
    <article class="ud-card"><h3>3. {a['request_label']}</h3><p>The other side sends a request. You see it on the dashboard.</p></article>
  </div>
</section>
<?php get_footer();
"""
    )
    (pdir / "views" / "join.php").write_text(
        f"""<?php get_header(); $err = sanitize_text_field(wp_unslash($_GET['err'] ?? '')); ?>
<section class="ud-split">
  <div><p class="ud-kicker">Join</p><h1>{a['join_cta']}</h1><p class="ud-lede">{a['lede']}</p></div>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php if ($err) : ?><p class="ud-notice ud-warn">Check name, email, password (8+) and the title.</p><?php endif; ?>
    <input type="hidden" name="action" value="{prefix}_register">
    <?php wp_nonce_field('{prefix}_register', '_n'); ?>
    <label>Your name<input name="full_name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Password<input type="password" name="password" minlength="8" required></label>
    {seeker}
    <label>Title / name<input name="item_name" required></label>
    <label>City<input name="city" required></label>
    <button type="submit">Create account</button>
    <p class="ud-fine"><a href="<?php echo esc_url(home_url('/login/')); ?>">Log in</a></p>
  </form>
</section>
<?php get_footer();
"""
    )
    (pdir / "views" / "login.php").write_text(
        f"""<?php get_header(); ?>
<section class="ud-split">
  <div><h1>Log in to {a['name']}</h1></div>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php if (!empty($_GET['err'])) : ?><p class="ud-notice ud-warn">Incorrect email or password.</p><?php endif; ?>
    <input type="hidden" name="action" value="{prefix}_login">
    <?php wp_nonce_field('{prefix}_login', '_n'); ?>
    <label>Email<input type="email" name="email" required></label>
    <label>Password<input type="password" name="password" required></label>
    <button type="submit">Log in</button>
  </form>
</section>
<?php get_footer();
"""
    )
    (pdir / "views" / "dash.php").write_text(
        f"""<?php
get_header();
$item = {prefix}_mine();
?>
<section class="ud-app">
  <aside class="ud-side">
    <p class="ud-kicker">Dashboard</p>
    <strong><?php echo esc_html($item ? $item->post_title : wp_get_current_user()->display_name); ?></strong>
    <nav>
      <a href="<?php echo esc_url(home_url('/dashboard/')); ?>">Home</a>
      <a href="<?php echo esc_url(home_url('/{a['dir']}/')); ?>">Browse</a>
      <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Log out</a>
    </nav>
  </aside>
  <div>
    <?php if (!empty($_GET['joined'])) : ?><p class="ud-notice">Account created. Complete the listing, then wait for verification to appear in search.</p><?php endif; ?>
    <?php if (!$item) : ?>
      <p>No listing on this account (seeker / guest). <a href="<?php echo esc_url(home_url('/{a['dir']}/')); ?>">Browse</a></p>
    <?php else : $inbox = {prefix}_inbox($item->ID); ?>
    <h1>Your {a['cpt_label'][:-1] if a['cpt_label'].endswith('s') else a['cpt_label']}</h1>
    <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="{prefix}_save">
      <?php wp_nonce_field('{prefix}_save', '_n'); ?>
      <label>Name<input name="item_name" value="<?php echo esc_attr($item->post_title); ?>" required></label>
      <label>City<input name="city" value="<?php echo esc_attr(get_post_meta($item->ID,'city',true)); ?>"></label>
      <label>About<textarea name="about" rows="5"><?php echo esc_textarea($item->post_content); ?></textarea></label>
      <button type="submit">Save</button>
    </form>
    <h2>{a['request_label']}s</h2>
    <?php if (!$inbox) : ?><p>None yet.</p><?php endif; ?>
    <?php foreach ($inbox as $r) : ?>
      <article class="ud-card"><h3><?php echo esc_html($r->post_title); ?></h3><p><?php echo nl2br(esc_html($r->post_content)); ?></p></article>
    <?php endforeach; endif; ?>
  </div>
</section>
<?php get_footer();
"""
    )
    (pdir / "views" / "dir.php").write_text(
        f"""<?php
get_header();
$q = new WP_Query(['post_type'=>'{cpt}','post_status'=>'publish','posts_per_page'=>12,'s'=>sanitize_text_field(wp_unslash($_GET['q'] ?? ''))]);
if (!empty($_GET['city'])) {{
    $q = new WP_Query(['post_type'=>'{cpt}','post_status'=>'publish','posts_per_page'=>12,'meta_key'=>'city','meta_value'=>sanitize_text_field(wp_unslash($_GET['city'])),'meta_compare'=>'LIKE']);
}}
?>
<section class="ud-band">
  <h1>{a['cpt_label']}</h1>
  <form class="ud-filters" method="get">
    <input name="q" placeholder="Search" value="<?php echo esc_attr(wp_unslash($_GET['q'] ?? '')); ?>">
    <input name="city" placeholder="City" value="<?php echo esc_attr(wp_unslash($_GET['city'] ?? '')); ?>">
    <button type="submit">Search</button>
  </form>
  <div class="ud-grid2">
    <?php if (!$q->have_posts()) : ?><p>Nothing public yet. <a href="<?php echo esc_url(home_url('/join/')); ?>">Be first</a>.</p><?php endif; ?>
    <?php while ($q->have_posts()) : $q->the_post(); ?>
      <article class="ud-card">
        <p class="ud-kicker"><?php echo esc_html(get_post_meta(get_the_ID(),'city',true)); ?></p>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p><?php echo esc_html(wp_trim_words(get_the_content(), 24)); ?></p>
      </article>
    <?php endwhile; wp_reset_postdata(); ?>
  </div>
</section>
<?php get_footer();
"""
    )
    req_fields = "\n    ".join(
        f'<label>{lab}<input type="{typ}" name="{nm}"></label>' for nm, lab, typ in a["request_fields"]
    )
    (pdir / "views" / "single.php").write_text(
        f"""<?php get_header(); the_post(); $id = get_the_ID(); ?>
<article class="ud-band">
  <p class="ud-kicker"><?php echo esc_html(get_post_meta($id,'city',true)); ?></p>
  <h1><?php the_title(); ?></h1>
  <div class="ud-prose"><?php the_content(); ?></div>
</article>
<section class="ud-band ud-alt">
  <h2>Send a {a['request_label'].lower()}</h2>
  <?php if (!empty($_GET['sent'])) : ?><p class="ud-notice">Sent. The owner will see it on their dashboard.</p><?php endif; ?>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="{prefix}_req">
    <input type="hidden" name="item_id" value="<?php echo (int) $id; ?>">
    <?php wp_nonce_field('{prefix}_req', '_n'); ?>
    <label>Name<input name="name" required></label>
    <label>Email<input type="email" name="email" required></label>
    {req_fields}
    <label>Message<textarea name="message" rows="4" required></textarea></label>
    <button type="submit">Send</button>
  </form>
</section>
<?php get_footer();
"""
    )
    (pdir / "views" / "contact.php").write_text(
        f"""<?php get_header(); ?>
<section class="ud-split">
  <div><h1>Contact {a['name']}</h1></div>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php if (!empty($_GET['sent'])) : ?><p class="ud-notice">Received.</p><?php endif; ?>
    <input type="hidden" name="action" value="{prefix}_req">
    <input type="hidden" name="item_id" value="0">
    <?php wp_nonce_field('{prefix}_req', '_n'); ?>
    <label>Name<input name="name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Message<textarea name="message" rows="4" required></textarea></label>
    <button type="submit">Send</button>
  </form>
</section>
<?php get_footer();
"""
    )

    t = ROOT / "themes" / slug
    t.mkdir(parents=True, exist_ok=True)
    (t / "style.css").write_text(
        f"""/*
Theme Name: {a['name']}
Author: Dr. Devesh Kumar Sharma
Description: Product UI for {a['name']}. Requires the {a['name']} App plugin.
Version: 2.0.0
*/
body {{ margin:0; background:#f3eee4; color:#14110e; font-family:Figtree,ui-sans-serif,system-ui,sans-serif; line-height:1.6; }}
a {{ color:{a['color']}; }}
.site-header,.site-footer {{ width:min(1120px,calc(100% - 2rem)); margin:0 auto; padding:1rem 0; }}
.site-header {{ display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.75rem; border-bottom:1px solid #d6cdb8; }}
.brand small {{ display:block; letter-spacing:.14em; text-transform:uppercase; font-size:.68rem; color:{a['accent']}; }}
.brand a {{ color:inherit; text-decoration:none; font-family:Fraunces,Georgia,serif; font-size:1.35rem; }}
.site-nav {{ display:flex; flex-wrap:wrap; gap:.8rem 1rem; }}
.site-nav a {{ text-decoration:none; color:#3d3832; }}
.site-footer {{ margin-top:2rem; border-top:1px solid #d6cdb8; color:#3d3832; font-size:.9rem; }}
"""
    )
    (t / "functions.php").write_text(
        """<?php
if (!defined('ABSPATH')) { exit; }
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('html5', ['search-form','style','script']);
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('dg-fonts', 'https://fonts.bunny.net/css?family=figtree:400,500,600,700|fraunces:500,560,700', [], null);
    wp_enqueue_style('dg-theme', get_stylesheet_uri(), ['dg-fonts'], wp_get_theme()->get('Version'));
});
"""
    )
    (t / "header.php").write_text(
        f"""<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
  <div class="brand"><small><a href="https://mydoapp.com">Do Galaxy</a></small>
  <a href="<?php echo esc_url(home_url('/')); ?>">{a['name']}</a></div>
  <nav class="site-nav">
    <a href="<?php echo esc_url(home_url('/{a['dir']}/')); ?>">{a['cpt_label']}</a>
    <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a>
    <?php if (is_user_logged_in()) : ?>
      <a href="<?php echo esc_url(home_url('/dashboard/')); ?>">Dashboard</a>
    <?php else : ?>
      <a href="<?php echo esc_url(home_url('/join/')); ?>">Join</a>
      <a href="<?php echo esc_url(home_url('/login/')); ?>">Log in</a>
    <?php endif; ?>
  </nav>
</header>
<main>
"""
    )
    (t / "footer.php").write_text(
        """</main>
<footer class="site-footer">
  <p><a href="https://mydoapp.com">MyDoApp</a> · <a href="https://doudyog.com">Udyog</a> · <a href="https://dovishram.com">Vishram</a> · <a href="https://dorojgar.com">Rojgar</a> · <a href="https://doswagat.com">Swagat</a> · <a href="https://dorishta.com">Rishta</a> · <a href="https://dobajar.com">Bajar</a></p>
</footer>
<?php wp_footer(); ?>
</body></html>
"""
    )
    (t / "index.php").write_text("<?php get_header(); if (have_posts()) { while (have_posts()) { the_post(); the_title('<h1>','</h1>'); the_content(); } } get_footer();")
    print("app", slug)


def main():
    for slug, a in APPS.items():
        write_app(slug, a)


if __name__ == "__main__":
    main()
