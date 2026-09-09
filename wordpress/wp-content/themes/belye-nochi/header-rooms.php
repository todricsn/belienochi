<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#ffffff">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
  <header class="rooms-header">
    <nav class="rooms-nav" aria-label="Основная навигация">
      <a class="nav-link nav-home" href="<?php echo esc_url( home_url( '/' ) ); ?>">На главную</a>
      <a class="rooms-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Белые Ночи — перейти на главную страницу">
        <img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-heritage-wordmark.png' ) ); ?>" alt="Белые Ночи">
      </a>
      <a class="nav-link nav-call" href="tel:+79293386160">Позвонить</a>
    </nav>
  </header>
