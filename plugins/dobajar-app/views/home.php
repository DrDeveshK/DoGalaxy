<?php get_header(); ?>
<section class="ud-hero">
  <p class="ud-kicker">बाज़ार आपके द्वार</p>
  <h1>List a product. Discover a stall. Send an order request.</h1>
  <p class="ud-lede">Sellers from Do Udyog get a stall. Buyers browse and request an order — the commerce layer of Do Galaxy.</p>
  <div class="ud-actions">
    <a class="ud-btn" href="<?php echo esc_url(home_url('/join/')); ?>">Open a stall</a>
    <a class="ud-btn ud-btn-ghost" href="<?php echo esc_url(home_url('/listings/')); ?>">Browse listings</a>
  </div>
</section>
<section class="ud-band">
  <div class="ud-grid3">
    <article class="ud-card"><h3>1. Join</h3><p>Create an account and your first listing or profile.</p></article>
    <article class="ud-card"><h3>2. Publish</h3><p>Admin verifies. The item appears in search.</p></article>
    <article class="ud-card"><h3>3. Order request</h3><p>The other side sends a request. You see it on the dashboard.</p></article>
  </div>
</section>
<?php get_footer();
