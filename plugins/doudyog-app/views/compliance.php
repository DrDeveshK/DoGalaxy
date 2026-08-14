<?php
get_header();
$biz = doudyog_owner_business();
if (!$biz) {
    wp_safe_redirect(home_url('/join/'));
    exit;
}
$score = doudyog_compliance_score($biz->ID);
?>
<section class="ud-app">
  <aside class="ud-side">
    <p class="ud-kicker">Readiness</p>
    <strong><?php echo (int) $score; ?>%</strong>
    <p>Tick what you already have. Add the number or note where asked.</p>
    <nav>
      <a href="<?php echo esc_url(home_url('/dashboard/')); ?>">Profile</a>
      <a href="<?php echo esc_url(home_url('/compliance/')); ?>">Compliance</a>
    </nav>
  </aside>
  <div>
    <?php if (!empty($_GET['saved'])) : ?><p class="ud-notice">Compliance updated.</p><?php endif; ?>
    <h1>Compliance centre</h1>
    <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="doudyog_save_compliance">
      <?php wp_nonce_field('doudyog_save_compliance', 'doudyog_nonce'); ?>
      <?php foreach (doudyog_compliance_items() as $key => $label) :
          $on = get_post_meta($biz->ID, 'comp_' . $key, true) === '1';
          $val = get_post_meta($biz->ID, 'comp_val_' . $key, true);
          ?>
        <fieldset class="ud-check">
          <label><input type="checkbox" name="comp[<?php echo esc_attr($key); ?>]" <?php checked($on); ?>> <?php echo esc_html($label); ?></label>
          <input name="comp_val[<?php echo esc_attr($key); ?>]" placeholder="Number or note" value="<?php echo esc_attr($val); ?>">
        </fieldset>
      <?php endforeach; ?>
      <button type="submit">Save readiness</button>
    </form>
  </div>
</section>
<?php get_footer();