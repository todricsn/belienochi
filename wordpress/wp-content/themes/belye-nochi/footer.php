  <footer class="site-footer">
    <a class="footer-brand" href="<?php echo esc_url( home_url( '/#top' ) ); ?>">
      <img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-heritage.png' ) ); ?>" alt="Белые Ночи">
    </a>
    <nav aria-label="Навигация в подвале">
      <a href="<?php echo esc_url( home_url( '/#hotel' ) ); ?>">Гостиница</a><a href="<?php echo esc_url( home_url( '/rooms/' ) ); ?>">Номера</a><a href="<?php echo esc_url( home_url( '/#hostel' ) ); ?>">Хостел</a><a href="<?php echo esc_url( home_url( '/#restaurant' ) ); ?>">Ресторан</a><a href="<?php echo esc_url( home_url( '/#about' ) ); ?>">О нас</a><a href="<?php echo esc_url( home_url( '/#contacts' ) ); ?>">Контакты</a>
    </nav>
    <div class="footer-bottom">
      <p>© <span data-year><?php echo esc_html( wp_date( 'Y' ) ); ?></span> «Белые Ночи»</p>
      <p>Цены на сайте не являются публичной офертой</p>
    </div>
  </footer>
  <?php wp_footer(); ?>
</body>
</html>
