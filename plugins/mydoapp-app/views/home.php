<?php get_header(); ?>
<section class="ud-hero">
  <p class="ud-kicker">Do Galaxy</p>
  <h1>One universe. Six working products. Start where you are.</h1>
  <p class="ud-lede">MyDoApp is the door. Each Do product is a full app — register, act, track — not a brochure.</p>
  <div class="ud-actions">
    <a class="ud-btn" href="<?php echo esc_url(home_url('/start/')); ?>">Start a journey</a>
    <a class="ud-btn ud-btn-ghost" href="<?php echo esc_url(home_url('/products/')); ?>">See all six</a>
  </div>
</section>
<section class="ud-band">
  <div class="ud-grid3">
    <?php foreach (mydoapp_planets() as $key => $p) : ?>
      <article class="ud-card">
        <p class="ud-kicker"><?php echo esc_html($p[0]); ?></p>
        <h3><?php echo esc_html($p[2]); ?></h3>
        <p><?php echo esc_html($p[3]); ?></p>
        <p><a href="<?php echo esc_url($p[1]); ?>">Open <?php echo esc_html($p[0]); ?></a></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php get_footer();