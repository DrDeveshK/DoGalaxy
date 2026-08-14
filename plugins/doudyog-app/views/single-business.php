<?php
get_header();
the_post();
$id = get_the_ID();
$sent = !empty($_GET['sent']);
?>
<article class="ud-band">
  <p class="ud-kicker"><?php echo esc_html(get_post_meta($id, 'industry', true)); ?> · <?php echo esc_html(get_post_meta($id, 'city', true)); ?></p>
  <h1><?php the_title(); ?></h1>
  <p class="ud-lede"><?php echo esc_html(get_the_excerpt()); ?></p>
  <p>Verification: <strong><?php echo esc_html(get_post_meta($id, 'verify', true) ?: 'pending'); ?></strong> · Compliance <?php echo (int) doudyog_compliance_score($id); ?>%</p>
  <div class="ud-prose"><?php the_content(); ?></div>
  <dl class="ud-meta">
    <div><dt>Phone</dt><dd><?php echo esc_html(get_post_meta($id, 'phone', true) ?: '—'); ?></dd></div>
    <div><dt>Website</dt><dd><?php echo esc_html(get_post_meta($id, 'website', true) ?: '—'); ?></dd></div>
    <div><dt>Employees</dt><dd><?php echo esc_html(get_post_meta($id, 'employees', true) ?: '—'); ?></dd></div>
    <div><dt>Since</dt><dd><?php echo esc_html(get_post_meta($id, 'year_started', true) ?: '—'); ?></dd></div>
  </dl>
</article>
<section class="ud-band ud-alt" id="enquire">
  <h2>Enquire with this business</h2>
  <?php if ($sent) : ?><p class="ud-notice">Enquiry sent.</p><?php endif; ?>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="doudyog_enquiry">
    <input type="hidden" name="business_id" value="<?php echo (int) $id; ?>">
    <?php wp_nonce_field('doudyog_enquiry', 'doudyog_nonce'); ?>
    <label>Name<input name="name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Phone<input name="phone"></label>
    <label>Intent
      <select name="intent">
        <option value="intro">Introduction</option>
        <option value="supply">Supply / purchase</option>
        <option value="partner">Partnership</option>
        <option value="compliance">Compliance help</option>
      </select>
    </label>
    <label>Message<textarea name="message" rows="4" required></textarea></label>
    <button type="submit">Send enquiry</button>
  </form>
</section>
<?php get_footer();