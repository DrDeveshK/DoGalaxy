<?php get_header(); $err = sanitize_text_field(wp_unslash($_GET['err'] ?? '')); ?>
<section class="ud-split">
  <div><p class="ud-kicker">Join</p><h1>Create profile</h1><p class="ud-lede">Family-friendly matrimony. 21+ only. Interest is private — no public chat wall.</p></div>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php if ($err) : ?><p class="ud-notice ud-warn">Check name, email, password (8+) and the title.</p><?php endif; ?>
    <input type="hidden" name="action" value="dorishta_register">
    <?php wp_nonce_field('dorishta_register', '_n'); ?>
    <label>Your name<input name="full_name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Password<input type="password" name="password" minlength="8" required></label>
    
    <label>Title / name<input name="item_name" required></label>
    <label>City<input name="city" required></label>
    <button type="submit">Create account</button>
    <p class="ud-fine"><a href="<?php echo esc_url(home_url('/login/')); ?>">Log in</a></p>
  </form>
</section>
<?php get_footer();
