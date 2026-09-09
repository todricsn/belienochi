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
  <div class="page-loader" aria-hidden="true">
    <div class="loader-mark"><span></span></div>
    <p>Белые Ночи</p>
  </div>

  <div class="scroll-progress" aria-hidden="true"></div>

  <header class="site-header" data-header>
    <nav class="desktop-nav desktop-nav-left" aria-label="Навигация слева">
      <a href="<?php echo esc_url( home_url( '/#about' ) ); ?>">О нас</a>
      <a href="<?php echo esc_url( home_url( '/#hotel' ) ); ?>">Гостиница</a>
      <a href="<?php echo esc_url( home_url( '/rooms/' ) ); ?>">Номера</a>
    </nav>

    <a class="header-logo" href="<?php echo esc_url( home_url( '/#top' ) ); ?>" aria-label="Белые Ночи — на главную">
      <img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-heritage-wordmark.png' ) ); ?>" alt="Белые Ночи">
    </a>

    <nav class="desktop-nav desktop-nav-right" aria-label="Навигация справа">
      <a href="<?php echo esc_url( home_url( '/#hostel' ) ); ?>">Хостел</a>
      <a href="<?php echo esc_url( home_url( '/#restaurant' ) ); ?>">Ресторан</a>
      <a href="<?php echo esc_url( home_url( '/#contacts' ) ); ?>">Контакты</a>
    </nav>

    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-menu">
      <span></span><span></span>
      <span class="sr-only">Открыть меню</span>
    </button>

    <div class="mobile-menu" id="mobile-menu" aria-hidden="true">
      <nav aria-label="Мобильная навигация">
        <a href="<?php echo esc_url( home_url( '/#about' ) ); ?>">О нас</a>
        <a href="<?php echo esc_url( home_url( '/#hotel' ) ); ?>">Гостиница</a>
        <a href="<?php echo esc_url( home_url( '/rooms/' ) ); ?>">Номера</a>
        <a href="<?php echo esc_url( home_url( '/#hostel' ) ); ?>">Хостел</a>
        <a href="<?php echo esc_url( home_url( '/#restaurant' ) ); ?>">Ресторан</a>
        <a href="<?php echo esc_url( home_url( '/#contacts' ) ); ?>">Контакты</a>
      </nav>
      <a href="tel:+79293386160">+7 929 338-61-60</a>
    </div>
  </header>
