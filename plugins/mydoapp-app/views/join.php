<?php get_header(); $path = sanitize_key(wp_unslash($_POST['path'] ?? $_GET['path'] ?? '')); ?>
<section class="ud-split">
  <div>
    <p class="ud-kicker">Galaxy member</p>
    <h1>Create your MyDoApp account.</h1>
    <p class="ud-lede">One login for the hub. Each Do product still has its own working account — this remembers your path.</p>
  </div>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php if (!empty($_GET['err'])) : ?><p class="ud-notice ud-warn">Name, email and password (8+) required. That email may already exist.</p><?php endif; ?>
    <input type="hidden" name="action" value="mydoapp_register">
    <input type="hidden" name="path" value="<?php echo esc_attr($path); ?>">
    <?php wp_nonce_field('mydoapp_register', '_n'); ?>
    <label>Name<input name="full_name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Password<input type="password" name="password" minlength="8" required></label>
    <label>First path
      <select name="path">
        <option value="">Choose later</option>
        <?php foreach (mydoapp_planets() as $k => $p) : ?>
          <option value="<?php echo esc_attr($k); ?>" <?php selected($path, $k); ?>><?php echo esc_html($p[0]); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button type="submit">Create account</button>
  </form>
</section>
<?php get_footer();