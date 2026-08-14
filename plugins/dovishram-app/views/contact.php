<?php get_header(); ?>
<section class="ud-split">
  <div><h1>Contact Do Vishram</h1></div>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php if (!empty($_GET['sent'])) : ?><p class="ud-notice">Received.</p><?php endif; ?>
    <input type="hidden" name="action" value="dovishram_req">
    <input type="hidden" name="item_id" value="0">
    <?php wp_nonce_field('dovishram_req', '_n'); ?>
    <label>Name<input name="name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Message<textarea name="message" rows="4" required></textarea></label>
    <button type="submit">Send</button>
  </form>
</section>
<?php get_footer();
