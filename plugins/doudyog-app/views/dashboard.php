<?php
get_header();
$biz = doudyog_owner_business();
if (!$biz) {
    echo '<section class="ud-band"><p>No business yet. <a href="' . esc_url(home_url('/join/')) . '">Register</a></p></section>';
    get_footer();
    return;
}
$score = doudyog_compliance_score($biz->ID);
$verify = get_post_meta($biz->ID, 'verify', true) ?: 'pending';
$enquiries = doudyog_enquiries_for($biz->ID);
?>
<section class="ud-app">
  <aside class="ud-side">
    <p class="ud-kicker">Owner</p>
    <strong><?php echo esc_html($biz->post_title); ?></strong>
    <p>Status: <em><?php echo esc_html($verify); ?></em></p>
    <p>Compliance: <strong><?php echo (int) $score; ?>%</strong></p>
    <nav>
      <a href="<?php echo esc_url(home_url('/dashboard/')); ?>">Profile</a>
      <a href="<?php echo esc_url(home_url('/compliance/')); ?>">Compliance</a>
      <a href="<?php echo esc_url(get_permalink($biz)); ?>">Public page</a>
      <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Log out</a>
    </nav>
  </aside>
  <div>
    <?php if (!empty($_GET['joined'])) : ?><p class="ud-notice">Welcome. Your profile is pending verification — complete compliance next.</p><?php endif; ?>
    <?php if (!empty($_GET['saved'])) : ?><p class="ud-notice">Saved.</p><?php endif; ?>
    <h1>Business profile</h1>
    <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="doudyog_save_business">
      <?php wp_nonce_field('doudyog_save_business', 'doudyog_nonce'); ?>
      <label>Business name<input name="business_name" value="<?php echo esc_attr($biz->post_title); ?>" required></label>
      <label>Tagline<input name="tagline" value="<?php echo esc_attr($biz->post_excerpt); ?>"></label>
      <label>Industry
        <select name="industry">
          <?php $cur = get_post_meta($biz->ID, 'industry', true); foreach (doudyog_industries() as $i) : ?>
            <option <?php selected($cur, $i); ?>><?php echo esc_html($i); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="ud-row">
        <label>City<input name="city" value="<?php echo esc_attr(get_post_meta($biz->ID, 'city', true)); ?>"></label>
        <label>Phone<input name="phone" value="<?php echo esc_attr(get_post_meta($biz->ID, 'phone', true)); ?>"></label>
      </div>
      <div class="ud-row">
        <label>Year started<input name="year_started" value="<?php echo esc_attr(get_post_meta($biz->ID, 'year_started', true)); ?>"></label>
        <label>Employees<input name="employees" value="<?php echo esc_attr(get_post_meta($biz->ID, 'employees', true)); ?>"></label>
      </div>
      <label>Website<input type="url" name="website" value="<?php echo esc_attr(get_post_meta($biz->ID, 'website', true)); ?>"></label>
      <label>About<textarea name="about" rows="5"><?php echo esc_textarea($biz->post_content); ?></textarea></label>
      <button type="submit">Save profile</button>
    </form>

    <h2>Enquiries</h2>
    <?php if (!$enquiries) : ?>
      <p>No enquiries yet. Share your public page.</p>
    <?php else : foreach ($enquiries as $e) : ?>
      <article class="ud-card">
        <h3><?php echo esc_html($e->post_title); ?></h3>
        <p><?php echo nl2br(esc_html($e->post_content)); ?></p>
      </article>
    <?php endforeach; endif; ?>
  </div>
</section>
<?php get_footer();