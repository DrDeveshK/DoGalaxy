<?php get_header(); ?>
<section class="ud-hero">
  <p class="ud-kicker">अपना रिश्ता यहीं</p>
  <h1>Create a verified profile. Express interest with care.</h1>
  <p class="ud-lede">Family-friendly matrimony. 21+ only. Interest is private — no public chat wall.</p>
  <div class="ud-actions">
    <a class="ud-btn" href="<?php echo esc_url(home_url('/join/')); ?>">Create profile</a>
    <a class="ud-btn ud-btn-ghost" href="<?php echo esc_url(home_url('/profiles/')); ?>">Browse profiles</a>
  </div>
</section>
<section class="ud-band">
  <div class="ud-grid3">
    <article class="ud-card"><h3>1. Join</h3><p>Create an account and your first listing or profile.</p></article>
    <article class="ud-card"><h3>2. Publish</h3><p>Admin verifies. The item appears in search.</p></article>
    <article class="ud-card"><h3>3. Interest</h3><p>The other side sends a request. You see it on the dashboard.</p></article>
  </div>
</section>
<?php get_footer();
