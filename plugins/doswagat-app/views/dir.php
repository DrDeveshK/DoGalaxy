<?php
get_header();
$q = new WP_Query(['post_type'=>'venue','post_status'=>'publish','posts_per_page'=>12,'s'=>sanitize_text_field(wp_unslash($_GET['q'] ?? ''))]);
if (!empty($_GET['city'])) {
    $q = new WP_Query(['post_type'=>'venue','post_status'=>'publish','posts_per_page'=>12,'meta_key'=>'city','meta_value'=>sanitize_text_field(wp_unslash($_GET['city'])),'meta_compare'=>'LIKE']);
}
?>
<section class="ud-band">
  <h1>Venues</h1>
  <form class="ud-filters" method="get">
    <input name="q" placeholder="Search" value="<?php echo esc_attr(wp_unslash($_GET['q'] ?? '')); ?>">
    <input name="city" placeholder="City" value="<?php echo esc_attr(wp_unslash($_GET['city'] ?? '')); ?>">
    <button type="submit">Search</button>
  </form>
  <div class="ud-grid2">
    <?php if (!$q->have_posts()) : ?><p>Nothing public yet. <a href="<?php echo esc_url(home_url('/join/')); ?>">Be first</a>.</p><?php endif; ?>
    <?php while ($q->have_posts()) : $q->the_post(); ?>
      <article class="ud-card">
        <p class="ud-kicker"><?php echo esc_html(get_post_meta(get_the_ID(),'city',true)); ?></p>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p><?php echo esc_html(wp_trim_words(get_the_content(), 24)); ?></p>
      </article>
    <?php endwhile; wp_reset_postdata(); ?>
  </div>
</section>
<?php get_footer();
