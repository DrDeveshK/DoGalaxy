<?php get_header(); ?>
<section class="ud-band">
  <p class="ud-kicker">Portfolio</p>
  <h1>Six products. Each is a working app.</h1>
  <div class="ud-grid2">
    <?php foreach (mydoapp_planets() as $p) : ?>
      <article class="ud-card">
        <h3><?php echo esc_html($p[0]); ?></h3>
        <p><?php echo esc_html($p[3]); ?></p>
        <p><a class="ud-btn" href="<?php echo esc_url($p[1]); ?>">Use <?php echo esc_html($p[0]); ?></a></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php get_footer();