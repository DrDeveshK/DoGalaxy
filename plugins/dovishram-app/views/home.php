<?php get_header(); ?>
<section class="ud-hero">
  <p class="ud-kicker">विश्राम मिले, मन टिके</p>
  <h1>List a stay. Find a stay. Request dates.</h1>
  <p class="ud-lede">Hosts publish rooms and retreats. Guests search and send a stay request with dates.</p>
  <div class="ud-actions">
    <a class="ud-btn" href="<?php echo esc_url(home_url('/join/')); ?>">List your stay</a>
    <a class="ud-btn ud-btn-ghost" href="<?php echo esc_url(home_url('/stays/')); ?>">Browse stays</a>
  </div>
</section>
<section class="ud-band">
  <div class="ud-grid3">
    <article class="ud-card"><h3>1. Join</h3><p>Create an account and your first listing or profile.</p></article>
    <article class="ud-card"><h3>2. Publish</h3><p>Admin verifies. The item appears in search.</p></article>
    <article class="ud-card"><h3>3. Stay request</h3><p>The other side sends a request. You see it on the dashboard.</p></article>
  </div>
</section>
<?php get_footer();
