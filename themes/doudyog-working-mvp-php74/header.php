<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div class="topbar"><div class="container">
  <span>DoUdyog — Business identity, compliance, growth and MSME enablement</span>
  <span>Powered by Kusumit Universe · MyDoApp</span>
</div></div>
<header class="site-header"><div class="container header-inner">
  <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
    <span class="brand-mark">D<span>o</span></span><span>DoUdyog</span>
  </a>
  <nav class="nav" id="mainNav">
    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
    <a href="<?php echo esc_url(home_url('/businesses')); ?>">Businesses</a>
    <a href="<?php echo esc_url(home_url('/services')); ?>">Services</a>
    <a href="<?php echo esc_url(home_url('/compliance-center')); ?>">Compliance</a>
    <a href="<?php echo esc_url(home_url('/growth-programs')); ?>">Growth</a>
    <a href="<?php echo esc_url(home_url('/pricing')); ?>">Pricing</a>
    <a href="<?php echo esc_url(home_url('/app/?p=dash')); ?>">Dashboard</a>
  </nav>
  <button class="btn light mobile-toggle" id="menuToggle">Menu</button>
  <a class="btn" href="<?php echo esc_url(home_url('/app/?p=join')); ?>">Join Udyog</a>
</div></header>
