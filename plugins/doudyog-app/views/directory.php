<?php
get_header();
$q = doudyog_query_businesses();
$industry = sanitize_text_field(wp_unslash($_GET['industry'] ?? ''));
$city = sanitize_text_field(wp_unslash($_GET['city'] ?? ''));
$search = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
?>
<section class="ud-band">
  <p class="ud-kicker">Directory</p>
  <h1>Find a business on Do Udyog.</h1>
  <form class="ud-filters" method="get" action="<?php echo esc_url(home_url('/businesses/')); ?>">
    <input name="q" placeholder="Name or keyword" value="<?php echo esc_attr($search); ?>">
    <select name="industry">
      <option value="">All industries</option>
      <?php foreach (doudyog_industries() as $i) : ?>
        <option <?php selected($industry, $i); ?>><?php echo esc_html($i); ?></option>
      <?php endforeach; ?>
    </select>
    <input name="city" placeholder="City" value="<?php echo esc_attr($city); ?>">
    <button type="submit">Search</button>
  </form>
  <div class="ud-grid2">
    <?php if (!$q->have_posts()) : ?>
      <p>No matching businesses. <a href="<?php echo esc_url(home_url('/join/')); ?>">Register yours</a>.</p>
    <?php endif; ?>
    <?php while ($q->have_posts()) : $q->the_post();
        $id = get_the_ID(); ?>
      <article class="ud-card">
        <p class="ud-kicker"><?php echo esc_html(get_post_meta($id, 'industry', true)); ?> · <?php echo esc_html(get_post_meta($id, 'city', true)); ?></p>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p><?php echo esc_html(get_the_excerpt()); ?></p>
        <p>Readiness <?php echo (int) doudyog_compliance_score($id); ?>%</p>
      </article>
    <?php endwhile;
    wp_reset_postdata(); ?>
  </div>
</section>
<?php get_footer();