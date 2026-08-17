<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div class="topbar"><div class="container">
  <span>DoBajar — Local marketplace under Do Galaxy.</span>
  <span>Powered by Kusumit Universe · MyDoApp</span>
</div></div>
<header class="site-header"><div class="container header-inner">
  <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
    <span class="brand-mark">B<span>o</span></span><span>DoBajar</span>
  </a>
  <nav class="nav" id="mainNav"><a href="/">Home</a><a href="/listings">Listings</a><a href="/join">Join</a></nav>
  <a class="btn" href="/join">Become a seller</a>
</div></header>
