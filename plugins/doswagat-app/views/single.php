<?php get_header(); the_post(); $id = get_the_ID(); ?>
<article class="ud-band">
  <p class="ud-kicker"><?php echo esc_html(get_post_meta($id,'city',true)); ?></p>
  <h1><?php the_title(); ?></h1>
  <div class="ud-prose"><?php the_content(); ?></div>
</article>
<section class="ud-band ud-alt">
  <h2>Send a event request</h2>
  <?php if (!empty($_GET['sent'])) : ?><p class="ud-notice">Sent. The owner will see it on their dashboard.</p><?php endif; ?>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="doswagat_req">
    <input type="hidden" name="item_id" value="<?php echo (int) $id; ?>">
    <?php wp_nonce_field('doswagat_req', '_n'); ?>
    <label>Name<input name="name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Event date<input type="date" name="event_date"></label>
    <label>Guests<input type="text" name="guests"></label>
    <label>Event type<input type="text" name="event_type"></label>
    <label>Message<textarea name="message" rows="4" required></textarea></label>
    <button type="submit">Send</button>
  </form>
</section>
<?php get_footer();
