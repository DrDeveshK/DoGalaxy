<?php get_header(); ?>
<section class="ud-hero">
  <p class="ud-kicker">रोज़गार आपके पास</p>
  <h1>Post a job. Apply. Track the outcome.</h1>
  <p class="ud-lede">Employers post local roles. Seekers apply with a short profile. Both sides see status on a dashboard.</p>
  <div class="ud-actions">
    <a class="ud-btn" href="<?php echo esc_url(home_url('/join/')); ?>">I am hiring</a>
    <a class="ud-btn ud-btn-ghost" href="<?php echo esc_url(home_url('/jobs/')); ?>">Browse jobs</a>
  </div>
</section>
<section class="ud-band">
  <div class="ud-grid3">
    <article class="ud-card"><h3>1. Join</h3><p>Create an account and your first listing or profile.</p></article>
    <article class="ud-card"><h3>2. Publish</h3><p>Admin verifies. The item appears in search.</p></article>
    <article class="ud-card"><h3>3. Application</h3><p>The other side sends a request. You see it on the dashboard.</p></article>
  </div>
</section>
<?php get_footer();
