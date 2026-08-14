<?php get_header(); ?>
<section class="ud-split">
  <div><h1>Log in to Do Rishta</h1></div>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php if (!empty($_GET['err'])) : ?><p class="ud-notice ud-warn">Incorrect email or password.</p><?php endif; ?>
    <input type="hidden" name="action" value="dorishta_login">
    <?php wp_nonce_field('dorishta_login', '_n'); ?>
    <label>Email<input type="email" name="email" required></label>
    <label>Password<input type="password" name="password" required></label>
    <button type="submit">Log in</button>
  </form>
</section>
<?php get_footer();
