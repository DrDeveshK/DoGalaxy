<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
  <div class="brand">
    <small><a href="https://mydoapp.com">Do Galaxy</a></small>
    <a href="<?php echo esc_url(home_url('/')); ?>">Do Udyog</a>
  </div>
  <nav class="site-nav">
    <a href="<?php echo esc_url(home_url('/businesses/')); ?>">Directory</a>
    <a href="<?php echo esc_url(home_url('/services/')); ?>">Services</a>
    <a href="<?php echo esc_url(home_url('/growth/')); ?>">Growth</a>
    <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a>
    <?php if (is_user_logged_in()) : ?>
      <a href="<?php echo esc_url(home_url('/dashboard/')); ?>">Dashboard</a>
    <?php else : ?>
      <a href="<?php echo esc_url(home_url('/join/')); ?>">Register</a>
      <a href="<?php echo esc_url(home_url('/login/')); ?>">Log in</a>
    <?php endif; ?>
  </nav>
</header>
<main>
