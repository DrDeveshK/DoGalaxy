<?php
get_header();
$err = !empty($_GET['err']);
$next = isset($_GET['next']) ? wp_validate_redirect(wp_unslash($_GET['next']), home_url('/dashboard/')) : home_url('/dashboard/');
?>
<section class="ud-split">
  <div>
    <p class="ud-kicker">Owner access</p>
    <h1>Log in to your Do Udyog dashboard.</h1>
  </div>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php if ($err) : ?><p class="ud-notice ud-warn">Email or password is incorrect.</p><?php endif; ?>
    <input type="hidden" name="action" value="doudyog_login">
    <input type="hidden" name="next" value="<?php echo esc_attr($next); ?>">
    <?php wp_nonce_field('doudyog_login', 'doudyog_nonce'); ?>
    <label>Email<input type="email" name="email" required></label>
    <label>Password<input type="password" name="password" required></label>
    <button type="submit">Log in</button>
    <p class="ud-fine"><a href="<?php echo esc_url(home_url('/join/')); ?>">Register a business</a></p>
  </form>
</section>
<?php get_footer();