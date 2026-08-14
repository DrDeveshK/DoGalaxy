<?php
if (!defined('ABSPATH')) { exit; }

function dorishta_activate(): void {
    add_role('rishta_member', 'Member', ['read' => true, 'upload_files' => true]);
    foreach (['join'=>'Join','login'=>'Login','dashboard'=>'Dashboard','profiles'=>'Profiles','contact'=>'Contact'] as $s=>$t) {
        if (!get_page_by_path($s)) {
            wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>$t,'post_name'=>$s]);
        }
    }
    dorishta_types();
    flush_rewrite_rules();
}

add_action('init', 'dorishta_types');
function dorishta_types(): void {
    register_post_type('profile', [
        'labels' => ['name' => 'Profiles', 'singular_name' => 'Profile'],
        'public' => true, 'has_archive' => false, 'rewrite' => ['slug' => 'profiles'],
        'show_in_rest' => true, 'menu_icon' => 'dashicons-admin-site', 'supports' => ['title','editor','excerpt'],
    ]);
    register_post_type('dorishta_req', [
        'labels' => ['name' => 'Interests', 'singular_name' => 'Interest'],
        'public' => false, 'show_ui' => true, 'supports' => ['title','editor'],
    ]);
}

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('dorishta-app', DORISHTA_URL . 'assets/app.css', [], '1.0.0');
});

function dorishta_mine($uid = 0) {
    $uid = $uid ?: get_current_user_id();
    if (!$uid) return null;
    $q = new WP_Query(['post_type'=>'profile','author'=>$uid,'posts_per_page'=>1,'post_status'=>['publish','pending','draft']]);
    return $q->have_posts() ? $q->posts[0] : null;
}
function dorishta_need_login() {
    if (!is_user_logged_in()) { wp_safe_redirect(home_url('/login/')); exit; }
}

add_action('admin_post_nopriv_dorishta_register', 'dorishta_register');
add_action('admin_post_dorishta_register', 'dorishta_register');
function dorishta_register() {
    if (!wp_verify_nonce($_POST['_n'] ?? '', 'dorishta_register')) wp_die('bad nonce');
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $name = sanitize_text_field(wp_unslash($_POST['full_name'] ?? ''));
    $title = sanitize_text_field(wp_unslash($_POST['item_name'] ?? ''));
    if (!$name || !is_email($email) || strlen($pass) < 8 || !$title) {
        wp_safe_redirect(home_url('/join/?err=missing')); exit;
    }
    if (email_exists($email)) { wp_safe_redirect(home_url('/join/?err=exists')); exit; }
    $role = sanitize_text_field(wp_unslash($_POST['as_role'] ?? 'rishta_member'));
    $allowed = ['rishta_member'];
    if (!in_array($role, $allowed, true)) $role = 'rishta_member';
    $uid = wp_insert_user(['user_login'=>sanitize_user(current(explode('@',$email)),true).wp_rand(10,99),'user_email'=>$email,'user_pass'=>$pass,'display_name'=>$name,'role'=>$role]);
    if (is_wp_error($uid)) { wp_safe_redirect(home_url('/join/?err=user')); exit; }
    if ($role === 'rishta_member') {
        $id = wp_insert_post(['post_type'=>'profile','post_status'=>'pending','post_title'=>$title,'post_author'=>$uid,'post_content'=>'']);
        if (!is_wp_error($id)) update_post_meta($id, 'city', sanitize_text_field(wp_unslash($_POST['city'] ?? '')));
    }
    wp_set_current_user($uid); wp_set_auth_cookie($uid, true);
    wp_safe_redirect(home_url('/dashboard/?joined=1')); exit;
}

add_action('admin_post_nopriv_dorishta_login', 'dorishta_login');
add_action('admin_post_dorishta_login', 'dorishta_login');
function dorishta_login() {
    if (!wp_verify_nonce($_POST['_n'] ?? '', 'dorishta_login')) wp_die('bad nonce');
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $u = get_user_by('email', $email);
    $user = $u ? wp_signon(['user_login'=>$u->user_login,'user_password'=>(string)($_POST['password'] ?? ''),'remember'=>true], is_ssl()) : new WP_Error();
    if (is_wp_error($user)) { wp_safe_redirect(home_url('/login/?err=1')); exit; }
    wp_safe_redirect(home_url('/dashboard/')); exit;
}

add_action('admin_post_dorishta_save', function () {
    if (!wp_verify_nonce($_POST['_n'] ?? '', 'dorishta_save')) wp_die('bad nonce');
    dorishta_need_login();
    $item = dorishta_mine();
    if (!$item) { wp_safe_redirect(home_url('/join/')); exit; }
    wp_update_post(['ID'=>$item->ID,'post_title'=>sanitize_text_field(wp_unslash($_POST['item_name'] ?? $item->post_title)),'post_content'=>wp_kses_post(wp_unslash($_POST['about'] ?? ''))]);
    foreach (['city','kind','stay_type','job_type','category','price','pay','guests','capacity','age','community','phone'] as $k) {
        if (isset($_POST[$k])) update_post_meta($item->ID, $k, sanitize_text_field(wp_unslash($_POST[$k])));
    }
    wp_safe_redirect(home_url('/dashboard/?saved=1')); exit;
});

add_action('admin_post_nopriv_dorishta_req', 'dorishta_req');
add_action('admin_post_dorishta_req', 'dorishta_req');
function dorishta_req() {
    if (!wp_verify_nonce($_POST['_n'] ?? '', 'dorishta_req')) wp_die('bad nonce');
    $pid = (int) ($_POST['item_id'] ?? 0);
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $msg = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    if (!$name || !is_email($email) || !$msg) { wp_safe_redirect(wp_get_referer() ?: home_url('/')); exit; }
    $rid = wp_insert_post(['post_type'=>'dorishta_req','post_status'=>'private','post_title'=>$name.' → '.get_the_title($pid),'post_content'=>$msg]);
    if (!is_wp_error($rid)) {
        update_post_meta($rid, 'item_id', $pid);
        update_post_meta($rid, 'email', $email);
        foreach (['checkin','checkout','guests','experience','phone','event_date','event_type','qty','note'] as $k) {
            if (!empty($_POST[$k])) update_post_meta($rid, $k, sanitize_text_field(wp_unslash($_POST[$k])));
        }
        $owner = (int) get_post_field('post_author', $pid);
        if ($owner && ($u = get_userdata($owner))) wp_mail($u->user_email, 'Interest on Do Rishta', $name.' <'.$email.'>\n\n'.$msg);
    }
    wp_safe_redirect(add_query_arg('sent','1', wp_get_referer() ?: home_url('/'))); exit;
}

function dorishta_inbox($item_id) {
    return (new WP_Query(['post_type'=>'dorishta_req','post_status'=>'private','posts_per_page'=>30,'meta_key'=>'item_id','meta_value'=>$item_id]))->posts;
}

add_filter('template_include', function ($t) {
    if (is_front_page()) return DORISHTA_DIR . 'views/home.php';
    if (is_singular('profile')) return DORISHTA_DIR . 'views/single.php';
    if (!is_page()) return $t;
    $s = get_post_field('post_name', get_queried_object_id());
    $map = ['join'=>'join.php','login'=>'login.php','dashboard'=>'dash.php','profiles'=>'dir.php','contact'=>'contact.php'];
    if (isset($map[$s])) {
        if ($s === 'dashboard') dorishta_need_login();
        return DORISHTA_DIR . 'views/' . $map[$s];
    }
    return $t;
});
