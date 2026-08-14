<?php
get_header();
$path = get_user_meta(get_current_user_id(), 'galaxy_path', true);
$planets = mydoapp_planets();
$p = $path && isset($planets[$path]) ? $planets[$path] : null;
?>
<section class="ud-app">
  <aside class="ud-side">
    <p class="ud-kicker">Member</p>
    <strong><?php echo esc_html(wp_get_current_user()->display_name); ?></strong>
    <nav>
      <a href="<?php echo esc_url(home_url('/start/')); ?>">Change path</a>
      <a href="<?php echo esc_url(home_url('/products/')); ?>">All products</a>
      <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Log out</a>
    </nav>
  </aside>
  <div>
    <h1>Your galaxy</h1>
    <?php if ($p) : ?>
      <article class="ud-card">
        <p class="ud-kicker">Current path</p>
        <h3><?php echo esc_html($p[0]); ?></h3>
        <p><?php echo esc_html($p[3]); ?></p>
        <p><a class="ud-btn" href="<?php echo esc_url($p[1]); ?>">Continue on <?php echo esc_html($p[0]); ?></a></p>
      </article>
    <?php else : ?>
      <p>No path yet. <a href="<?php echo esc_url(home_url('/start/')); ?>">Choose one</a>.</p>
    <?php endif; ?>
    <h2>All doors</h2>
    <div class="ud-grid2">
      <?php foreach ($planets as $row) : ?>
        <article class="ud-card"><h3><?php echo esc_html($row[0]); ?></h3><p><a href="<?php echo esc_url($row[1]); ?>">Open</a></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php get_footer();