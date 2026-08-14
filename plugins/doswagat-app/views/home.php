<?php get_header(); ?>
<section class="ud-hero">
  <p class="ud-kicker">स्वागत हो</p>
  <h1>Book a venue. Brief a partner. Track the request.</h1>
  <p class="ud-lede">Partners list venues and services. Families and firms send an event request with date and guest count.</p>
  <div class="ud-actions">
    <a class="ud-btn" href="<?php echo esc_url(home_url('/join/')); ?>">List a venue</a>
    <a class="ud-btn ud-btn-ghost" href="<?php echo esc_url(home_url('/venues/')); ?>">Browse venues</a>
  </div>
</section>
<section class="ud-band">
  <div class="ud-grid3">
    <article class="ud-card"><h3>1. Join</h3><p>Create an account and your first listing or profile.</p></article>
    <article class="ud-card"><h3>2. Publish</h3><p>Admin verifies. The item appears in search.</p></article>
    <article class="ud-card"><h3>3. Event request</h3><p>The other side sends a request. You see it on the dashboard.</p></article>
  </div>
</section>
<?php get_footer();
