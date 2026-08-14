<?php get_header(); ?>
<section class="ud-band">
  <p class="ud-kicker">Choose a path</p>
  <h1>What do you need to do first?</h1>
  <form class="ud-form" method="post" action="<?php echo esc_url(is_user_logged_in() ? admin_url('admin-post.php') : home_url('/join/')); ?>">
    <?php if (is_user_logged_in()) : ?>
      <input type="hidden" name="action" value="mydoapp_path">
      <?php wp_nonce_field('mydoapp_path', '_n'); ?>
    <?php endif; ?>
    <?php foreach (mydoapp_planets() as $key => $p) : ?>
      <label class="ud-check"><input type="radio" name="path" value="<?php echo esc_attr($key); ?>" required> <strong><?php echo esc_html($p[0]); ?></strong> — <?php echo esc_html($p[2]); ?></label>
    <?php endforeach; ?>
    <?php if (is_user_logged_in()) : ?>
      <button type="submit">Save to my dashboard</button>
    <?php else : ?>
      <p class="ud-fine">Next you create a MyDoApp account; we keep this choice.</p>
      <button type="submit">Continue to join</button>
    <?php endif; ?>
  </form>
</section>
<?php get_footer();