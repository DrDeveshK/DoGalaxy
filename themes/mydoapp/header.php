<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo('charset'); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<header class="site-header">
  <div class="brand"><small>Do Galaxy</small><a href="<?php echo esc_url(home_url('/')); ?>">MyDoApp</a></div>
  <nav class="site-nav">
    <a href="<?php echo esc_url(home_url('/products/')); ?>">Products</a>
    <a href="<?php echo esc_url(home_url('/start/')); ?>">Start</a>
    <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a>
    <?php if (is_user_logged_in()) : ?><a href="<?php echo esc_url(home_url('/dashboard/')); ?>">Dashboard</a>
    <?php else : ?><a href="<?php echo esc_url(home_url('/join/')); ?>">Join</a><a href="<?php echo esc_url(home_url('/login/')); ?>">Log in</a><?php endif; ?>
  </nav>
</header><main>
