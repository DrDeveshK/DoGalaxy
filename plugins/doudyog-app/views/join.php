<?php
get_header();
$err = sanitize_text_field(wp_unslash($_GET['err'] ?? ''));
$msg = [
    'missing' => 'Name, email, password (8+ characters) and business name are required.',
    'exists' => 'That email is already registered. Log in instead.',
    'user' => 'Could not create the account. Try another email.',
][$err] ?? '';
?>
<section class="ud-split">
  <div>
    <p class="ud-kicker">Create account</p>
    <h1>Register your business on Do Udyog.</h1>
    <p class="ud-lede">This creates your owner login and a pending business profile. Complete compliance from the dashboard to request verification.</p>
  </div>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php if ($msg) : ?><p class="ud-notice ud-warn"><?php echo esc_html($msg); ?></p><?php endif; ?>
    <input type="hidden" name="action" value="doudyog_register">
    <?php wp_nonce_field('doudyog_register', 'doudyog_nonce'); ?>
    <label>Your name<input name="full_name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Password (min 8)<input type="password" name="password" minlength="8" required></label>
    <label>Phone<input type="tel" name="phone"></label>
    <label>Business name<input name="business_name" required></label>
    <label>Industry
      <select name="industry" required>
        <option value="">Select</option>
        <?php foreach (doudyog_industries() as $i) : ?>
          <option><?php echo esc_html($i); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>City<input name="city" required></label>
    <button type="submit">Create profile</button>
    <p class="ud-fine">Already registered? <a href="<?php echo esc_url(home_url('/login/')); ?>">Log in</a></p>
  </form>
</section>
<?php get_footer();