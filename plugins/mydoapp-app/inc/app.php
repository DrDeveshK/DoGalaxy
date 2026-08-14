<?php
if (!defined('ABSPATH')) {
    exit;
}

function mydoapp_planets(): array
{
    return [
        'business' => ['Do Udyog', 'https://doudyog.com', 'I run or am starting a business', 'Register identity, compliance, then hire and sell.'],
        'stay' => ['Do Vishram', 'https://dovishram.com', 'I need a stay or I host rooms', 'List a stay or request dates.'],
        'job' => ['Do Rojgar', 'https://dorojgar.com', 'I want work or I want to hire', 'Post a job or apply.'],
        'event' => ['Do Swagat', 'https://doswagat.com', 'I am planning an event', 'Venues and partners, one request.'],
        'rishta' => ['Do Rishta', 'https://dorishta.com', 'I am looking for a life partner', 'Family-friendly, verified, 21+.'],
        'buy' => ['Do Bajar', 'https://dobajar.com', 'I want to sell or buy locally', 'Stalls and order requests.'],
    ];
}

function mydoapp_activate(): void
{
    add_role('galaxy_member', 'Galaxy member', ['read' => true]);
    foreach (['join' => 'Join', 'login' => 'Login', 'dashboard' => 'Dashboard', 'products' => 'Products', 'start' => 'Start', 'contact' => 'Contact'] as $s => $t) {
        if (!get_page_by_path($s)) {
            wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $t, 'post_name' => $s]);
        }
    }
    register_post_type('journey', [
        'labels' => ['name' => 'Journeys', 'singular_name' => 'Journey'],
        'public' => false,
        'show_ui' => true,
        'supports' => ['title', 'editor'],
    ]);
    flush_rewrite_rules();
}

add_action('init', function (): void {
    register_post_type('journey', [
        'labels' => ['name' => 'Journeys', 'singular_name' => 'Journey'],
        'public' => false,
        'show_ui' => true,
        'supports' => ['title', 'editor'],
    ]);
});

add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style('mydoapp-app', MYDOAPP_URL . 'assets/app.css', [], '1.0.0');
});

add_action('admin_post_nopriv_mydoapp_register', 'mydoapp_register');
add_action('admin_post_mydoapp_register', 'mydoapp_register');
function mydoapp_register(): void
{
    if (!wp_verify_nonce($_POST['_n'] ?? '', 'mydoapp_register')) {
        wp_die('bad nonce');
    }
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $name = sanitize_text_field(wp_unslash($_POST['full_name'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $path = sanitize_key(wp_unslash($_POST['path'] ?? ''));
    if (!$name || !is_email($email) || strlen($pass) < 8) {
        wp_safe_redirect(home_url('/join/?err=1'));
        exit;
    }
    if (email_exists($email)) {
        wp_safe_redirect(home_url('/join/?err=exists'));
        exit;
    }
    $uid = wp_insert_user([
        'user_login' => sanitize_user(current(explode('@', $email)), true) . wp_rand(10, 99),
        'user_email' => $email,
        'user_pass' => $pass,
        'display_name' => $name,
        'role' => 'galaxy_member',
    ]);
    if (is_wp_error($uid)) {
        wp_safe_redirect(home_url('/join/?err=1'));
        exit;
    }
    if ($path && isset(mydoapp_planets()[$path])) {
        update_user_meta($uid, 'galaxy_path', $path);
        wp_insert_post([
            'post_type' => 'journey',
            'post_status' => 'private',
            'post_title' => $name . ' · ' . $path,
            'post_author' => $uid,
            'post_content' => $path,
        ]);
    }
    wp_set_current_user($uid);
    wp_set_auth_cookie($uid, true);
    wp_safe_redirect(home_url('/dashboard/'));
    exit;
}

add_action('admin_post_nopriv_mydoapp_login', 'mydoapp_login');
add_action('admin_post_mydoapp_login', 'mydoapp_login');
function mydoapp_login(): void
{
    if (!wp_verify_nonce($_POST['_n'] ?? '', 'mydoapp_login')) {
        wp_die('bad nonce');
    }
    $u = get_user_by('email', sanitize_email(wp_unslash($_POST['email'] ?? '')));
    $user = $u ? wp_signon(['user_login' => $u->user_login, 'user_password' => (string) ($_POST['password'] ?? ''), 'remember' => true], is_ssl()) : new WP_Error();
    if (is_wp_error($user)) {
        wp_safe_redirect(home_url('/login/?err=1'));
        exit;
    }
    wp_safe_redirect(home_url('/dashboard/'));
    exit;
}

add_action('admin_post_mydoapp_path', function (): void {
    if (!is_user_logged_in() || !wp_verify_nonce($_POST['_n'] ?? '', 'mydoapp_path')) {
        wp_die('bad nonce');
    }
    $path = sanitize_key(wp_unslash($_POST['path'] ?? ''));
    if (isset(mydoapp_planets()[$path])) {
        update_user_meta(get_current_user_id(), 'galaxy_path', $path);
        wp_insert_post([
            'post_type' => 'journey',
            'post_status' => 'private',
            'post_author' => get_current_user_id(),
            'post_title' => wp_get_current_user()->display_name . ' · ' . $path,
            'post_content' => $path,
        ]);
    }
    wp_safe_redirect(home_url('/dashboard/'));
    exit;
});

add_action('admin_post_nopriv_mydoapp_contact', 'mydoapp_contact');
add_action('admin_post_mydoapp_contact', 'mydoapp_contact');
function mydoapp_contact(): void
{
    if (!wp_verify_nonce($_POST['_n'] ?? '', 'mydoapp_contact')) {
        wp_die('bad nonce');
    }
    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $msg = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
    if ($name && is_email($email) && $msg) {
        wp_mail(get_option('admin_email'), 'MyDoApp: ' . $name, $email . "\n\n" . $msg);
    }
    wp_safe_redirect(home_url('/contact/?sent=1'));
    exit;
}

add_filter('template_include', function (string $t): string {
    if (is_front_page()) {
        return MYDOAPP_DIR . 'views/home.php';
    }
    if (!is_page()) {
        return $t;
    }
    $s = get_post_field('post_name', get_queried_object_id());
    $map = ['join' => 'join.php', 'login' => 'login.php', 'dashboard' => 'dash.php', 'products' => 'products.php', 'start' => 'start.php', 'contact' => 'contact.php'];
    if (isset($map[$s])) {
        if ($s === 'dashboard' && !is_user_logged_in()) {
            wp_safe_redirect(home_url('/login/'));
            exit;
        }
        return MYDOAPP_DIR . 'views/' . $map[$s];
    }
    return $t;
});
