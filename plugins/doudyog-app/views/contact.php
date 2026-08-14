<?php
get_header();
$sent = !empty($_GET['sent']);
?>
<section class="ud-split">
  <div>
    <p class="ud-kicker">Support</p>
    <h1>Talk to Do Udyog.</h1>
    <p class="ud-lede">Registration help, compliance, or a partnership. Enquiries land in WP Admin and the owner inbox when they attach to a business.</p>
  </div>
  <form class="ud-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php if ($sent) : ?><p class="ud-notice">Received. We will respond shortly.</p><?php endif; ?>
    <input type="hidden" name="action" value="doudyog_enquiry">
    <input type="hidden" name="business_id" value="0">
    <?php wp_nonce_field('doudyog_enquiry', 'doudyog_nonce'); ?>
    <label>Name<input name="name" required></label>
    <label>Email<input type="email" name="email" required></label>
    <label>Phone<input name="phone"></label>
    <label>Intent
      <select name="intent">
        <option value="register">Help registering</option>
        <option value="compliance">Compliance</option>
        <option value="partner">Partnership</option>
        <option value="general">General</option>
      </select>
    </label>
    <label>Message<textarea name="message" rows="4" required></textarea></label>
    <button type="submit">Send</button>
  </form>
</section>
<?php get_footer();