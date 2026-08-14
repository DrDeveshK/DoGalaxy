<?php
get_header();
$item = dorishta_mine();
?>
<section class="ud-app">
  <aside class="ud-side">
    <p class="ud-kicker">Dashboard</p>
    <strong><?php echo esc_html($item ? $item->post_title : wp_get_current_user()->display_name); ?></strong>
    <nav>
      <a href="<?php echo esc_url(home_url('/dashboard/')); ?>">Home</a>
      <a href="<?php echo esc_url(home_url('/profiles/')); ?>">Browse</a>
      <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Log out</a>
    </nav>
  </aside>
  <div>
    <?php if (!empty($_GET['joined'])) : ?><p class="ud-notice">Account created. Complete the listing, then wait for verification to appear in search.</p><?php endif; ?>
    <?php if (!$item) : ?>
      <p>No listing on this account (seeker / guest). <a href="<?php echo esc_url(home_url('/profiles/')); ?>">Browse</a></p>
    <?php else : $inbox = dorishta_inbox($item->ID); ?>
    <h1>Your Profile</h1>
    <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="dorishta_save">
      <?php wp_nonce_field('dorishta_save', '_n'); ?>
      <label>Name<input name="item_name" value="<?php echo esc_attr($item->post_title); ?>" required></label>
      <label>City<input name="city" value="<?php echo esc_attr(get_post_meta($item->ID,'city',true)); ?>"></label>
      <label>About<textarea name="about" rows="5"><?php echo esc_textarea($item->post_content); ?></textarea></label>
      <button type="submit">Save</button>
    </form>
    <h2>Interests</h2>
    <?php if (!$inbox) : ?><p>None yet.</p><?php endif; ?>
    <?php foreach ($inbox as $r) : ?>
      <article class="ud-card"><h3><?php echo esc_html($r->post_title); ?></h3><p><?php echo nl2br(esc_html($r->post_content)); ?></p></article>
    <?php endforeach; endif; ?>
  </div>
</section>
<?php get_footer();
