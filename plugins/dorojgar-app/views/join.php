<?php get_header(); $err = sanitize_text_field(wp_unslash($_GET['err'] ?? '')); ?>
<section class="ud-split">
  <div><p class="ud-kicker">Join</p><h1>I am hiring</h1><p class="ud-lede">Employers post local roles. Seekers apply with a short profile. Both sides see status on a dashboard.</p></div>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php if ($err) : ?><p class="ud-notice ud-warn">Check name, email, password (8+) and the title.</p><?php endif; ?>
    <input type="hidden" name="action" value="dorojgar_register">
    <?php wp_nonce_field('dorojgar_register', '_n'); ?>
    <label>Your name<input name="full_name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Password<input type="password" name="password" minlength="8" required></label>
    
    <label>I am
      <select name="as_role">
        <option value="rojgar_employer">Employer — I want to hire</option>
        <option value="rojgar_seeker">Seeker — I want work</option>
      </select>
    </label>
    <label>Title / name<input name="item_name" required></label>
    <label>City<input name="city" required></label>
    <button type="submit">Create account</button>
    <p class="ud-fine"><a href="<?php echo esc_url(home_url('/login/')); ?>">Log in</a></p>
  </form>
</section>
<?php get_footer();
