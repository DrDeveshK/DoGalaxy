<?php
if (!defined('ABSPATH')) {
    exit;
}

function doudyog_owner_business(int $user_id = 0): ?WP_Post
{
    $user_id = $user_id ?: get_current_user_id();
    if (!$user_id) {
        return null;
    }
    $q = new WP_Query([
        'post_type' => 'business',
        'author' => $user_id,
        'posts_per_page' => 1,
        'post_status' => ['publish', 'pending', 'draft'],
    ]);
    return $q->have_posts() ? $q->posts[0] : null;
}

function doudyog_require_login(): void
{
    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/login/?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/')));
        exit;
    }
}

add_action('admin_post_nopriv_doudyog_register', 'doudyog_handle_register');
add_action('admin_post_doudyog_register', 'doudyog_handle_register');

function doudyog_handle_register(): void
{
    if (!wp_verify_nonce($_POST['doudyog_nonce'] ?? '', 'doudyog_register')) {
        wp_die('Invalid request');
    }
    $name = sanitize_text_field(wp_unslash($_POST['full_name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $biz = sanitize_text_field(wp_unslash($_POST['business_name'] ?? ''));
    $industry = sanitize_text_field(wp_unslash($_POST['industry'] ?? ''));
    $city = sanitize_text_field(wp_unslash($_POST['city'] ?? ''));
    $phone = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));

    if (!$name || !is_email($email) || strlen($pass) < 8 || !$biz) {
        wp_safe_redirect(home_url('/join/?err=missing'));
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
        'role' => 'udyog_owner',
        'first_name' => $name,
    ]);
    if (is_wp_error($uid)) {
        wp_safe_redirect(home_url('/join/?err=user'));
        exit;
    }
    update_user_meta($uid, 'phone', $phone);

    $bid = wp_insert_post([
        'post_type' => 'business',
        'post_status' => 'pending',
        'post_title' => $biz,
        'post_author' => $uid,
        'post_excerpt' => $industry . ($city ? " · {$city}" : ''),
        'post_content' => '',
    ]);
    if (!is_wp_error($bid)) {
        update_post_meta($bid, 'industry', $industry);
        update_post_meta($bid, 'city', $city);
        update_post_meta($bid, 'phone', $phone);
        update_post_meta($bid, 'verify', 'pending');
        foreach (array_keys(doudyog_compliance_items()) as $key) {
            update_post_meta($bid, 'comp_' . $key, '0');
        }
    }

    wp_set_current_user($uid);
    wp_set_auth_cookie($uid, true);
    wp_safe_redirect(home_url('/dashboard/?joined=1'));
    exit;
}

add_action('admin_post_nopriv_doudyog_login', 'doudyog_handle_login');
add_action('admin_post_doudyog_login', 'doudyog_handle_login');

function doudyog_handle_login(): void
{
    if (!wp_verify_nonce($_POST['doudyog_nonce'] ?? '', 'doudyog_login')) {
        wp_die('Invalid request');
    }
    $user = wp_signon([
        'user_login' => sanitize_email(wp_unslash($_POST['email'] ?? '')),
        'user_password' => (string) ($_POST['password'] ?? ''),
        'remember' => true,
    ], is_ssl());
    if (is_wp_error($user)) {
        // email login: try by email
        $u = get_user_by('email', sanitize_email(wp_unslash($_POST['email'] ?? '')));
        if ($u) {
            $user = wp_signon([
                'user_login' => $u->user_login,
                'user_password' => (string) ($_POST['password'] ?? ''),
                'remember' => true,
            ], is_ssl());
        }
    }
    if (is_wp_error($user)) {
        wp_safe_redirect(home_url('/login/?err=1'));
        exit;
    }
    $next = wp_validate_redirect($_POST['next'] ?? '/dashboard/', home_url('/dashboard/'));
    wp_safe_redirect($next);
    exit;
}

add_action('admin_post_doudyog_logout', function (): void {
    wp_logout();
    wp_safe_redirect(home_url('/'));
    exit;
});
