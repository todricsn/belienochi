<?php
/**
 * Native Gutenberg patterns used by the two site pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bn_blocks_dynamic_block( $name, $attributes = array() ) {
	$json = $attributes ? ' ' . wp_json_encode( $attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) : '';
	return '<!-- wp:' . $name . $json . ' /-->';
}

function bn_blocks_pattern_hero() {
	return bn_blocks_dynamic_block( 'belye-nochi/hero' );
}

function bn_blocks_pattern_about() {
	return <<<'BLOCKS'
<!-- wp:group {"tagName":"section","anchor":"about","className":"intro section","layout":{"type":"default"}} -->
<section class="wp-block-group intro section" id="about">
<!-- wp:paragraph {"className":"section-kicker reveal"} -->
<p class="section-kicker reveal">О нас</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"className":"display-title reveal"} -->
<h2 class="wp-block-heading display-title reveal">Удобное место<br>для спокойного<br>отдыха</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"intro-text reveal"} -->
<p class="intro-text reveal">«Белые Ночи» — круглосуточная гостиница и хостел на въезде в Красноярск. В гостинице 16 номеров — от одноместных до четырёхместных; можно выбрать отдельный номер или практичное место в хостеле. В номерах есть душ, санузел и телевизор, на территории — Wi-Fi, собственная парковка и кафе. Всё необходимое, чтобы спокойно отдохнуть после дороги и продолжить путь.</p>
<!-- /wp:paragraph -->
<!-- wp:belye-nochi/photo-gallery {"variant":"hotel","ariaLabel":"Фотографии гостиницы и номеров Белых Ночей"} /-->
</section>
<!-- /wp:group -->
BLOCKS;
}

function bn_blocks_pattern_stays() {
	return bn_blocks_dynamic_block( 'belye-nochi/stays' );
}

function bn_blocks_pattern_restaurant() {
	return <<<'BLOCKS'
<!-- wp:group {"tagName":"section","anchor":"restaurant","className":"restaurant section","layout":{"type":"default"}} -->
<section class="wp-block-group restaurant section" id="restaurant">
<!-- wp:paragraph {"className":"section-kicker reveal"} -->
<p class="section-kicker reveal">Ресторан</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"anchor":"restaurant-title","className":"display-title reveal"} -->
<h2 class="wp-block-heading display-title reveal" id="restaurant-title">Место для ужина<br>и важных событий</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"className":"restaurant-text reveal"} -->
<p class="restaurant-text reveal">При гостинице работает ресторан, где можно встретиться с близкими или провести отдельное событие. Банкетный зал вмещает до 160 гостей и подойдёт для свадьбы, юбилея, дня рождения, корпоратива или семейного торжества.</p>
<!-- /wp:paragraph -->
<!-- wp:group {"className":"restaurant-details reveal","layout":{"type":"default"}} -->
<div class="wp-block-group restaurant-details reveal">
<!-- wp:paragraph --><p><span>Вместимость зала</span><strong>до 160 гостей</strong></p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p><span>Режим работы</span><strong>ежедневно 10:00—23:00</strong></p><!-- /wp:paragraph -->
<!-- wp:belye-nochi/action-link {"label":"Забронировать зал","url":"tel:+79293386160","ariaLabel":"Позвонить и забронировать банкетный зал","className":"restaurant-booking"} /-->
</div>
<!-- /wp:group -->
<!-- wp:belye-nochi/photo-gallery {"variant":"restaurant","ariaLabel":"Фотографии ресторана и банкетного зала"} /-->
<!-- wp:group {"className":"restaurant-note-group reveal","layout":{"type":"default"}} -->
<div class="wp-block-group restaurant-note-group reveal">
<!-- wp:paragraph {"className":"restaurant-note"} --><p class="restaurant-note">Позвоните — обсудим дату, количество гостей и формат мероприятия.</p><!-- /wp:paragraph -->
<!-- wp:belye-nochi/action-link {"label":"Забронировать","url":"tel:+79293386160","ariaLabel":"Позвонить и забронировать банкетный зал","className":"restaurant-note-booking"} /-->
</div>
<!-- /wp:group -->
</section>
<!-- /wp:group -->
BLOCKS;
}

function bn_blocks_pattern_features() {
	return <<<'BLOCKS'
<!-- wp:group {"tagName":"section","anchor":"features","className":"features section","layout":{"type":"default"}} -->
<section class="wp-block-group features section" id="features">
<!-- wp:group {"className":"features-heading","layout":{"type":"default"}} -->
<div class="wp-block-group features-heading">
<!-- wp:group {"layout":{"type":"default"}} --><div class="wp-block-group">
<!-- wp:paragraph {"className":"section-kicker reveal"} --><p class="section-kicker reveal">Удобства и сервис</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"anchor":"features-title","className":"display-title reveal"} --><h2 class="wp-block-heading display-title reveal" id="features-title">Всё необходимое<br>в одном месте</h2><!-- /wp:heading -->
</div><!-- /wp:group -->
<!-- wp:paragraph {"className":"features-lead reveal"} --><p class="features-lead reveal">Услуги, которые делают короткую остановку, семейную поездку или длительное проживание удобнее.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:belye-nochi/feature-list /-->
</section>
<!-- /wp:group -->
BLOCKS;
}

function bn_blocks_pattern_contacts() {
	return <<<'BLOCKS'
<!-- wp:group {"tagName":"section","anchor":"contacts","className":"contacts section","layout":{"type":"default"}} -->
<section class="wp-block-group contacts section" id="contacts">
<!-- wp:heading {"level":2,"anchor":"contacts-title","className":"contacts-title reveal"} --><h2 class="wp-block-heading contacts-title reveal" id="contacts-title">Контакты</h2><!-- /wp:heading -->
<!-- wp:group {"className":"contacts-info","layout":{"type":"default"}} --><div class="wp-block-group contacts-info">
<!-- wp:group {"className":"contacts-info-item reveal","layout":{"type":"default"}} --><div class="wp-block-group contacts-info-item reveal"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Время работы</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Мы работаем круглосуточно.</p><!-- /wp:paragraph --></div><!-- /wp:group -->
<!-- wp:group {"className":"contacts-info-item contacts-location reveal","layout":{"type":"default"}} --><div class="wp-block-group contacts-info-item contacts-location reveal"><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Наше местоположение</h3><!-- /wp:heading --><!-- wp:paragraph --><p><a href="https://yandex.ru/maps/-/CTwFQZ9y" target="_blank" rel="noreferrer noopener">Красноярск, 2-я Красногорская улица, 3</a></p><!-- /wp:paragraph --></div><!-- /wp:group -->
</div><!-- /wp:group -->
<!-- wp:group {"className":"contacts-booking reveal","layout":{"type":"default"}} --><div class="wp-block-group contacts-booking reveal">
<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Забронировать</h3><!-- /wp:heading -->
<!-- wp:group {"className":"contacts-phone-list","layout":{"type":"default"}} --><div class="wp-block-group contacts-phone-list"><!-- wp:belye-nochi/action-link {"label":"+7 929 338-61-60","url":"tel:+79293386160","showArrow":false} /--><!-- wp:belye-nochi/action-link {"label":"+7 913 538-60-27","url":"tel:+79135386027","showArrow":false} /--></div><!-- /wp:group -->
</div><!-- /wp:group -->
<!-- wp:belye-nochi/contact-map /-->
</section>
<!-- /wp:group -->
BLOCKS;
}

function bn_blocks_native_home_content() {
	return implode( "\n\n", array( bn_blocks_pattern_hero(), bn_blocks_pattern_about(), bn_blocks_pattern_stays(), bn_blocks_pattern_restaurant(), bn_blocks_pattern_features(), bn_blocks_pattern_contacts() ) );
}

function bn_blocks_pattern_rooms_catalog() {
	return <<<'BLOCKS'
<!-- wp:group {"tagName":"section","className":"rooms-catalog-section","layout":{"type":"default"}} -->
<section class="wp-block-group rooms-catalog-section">
<!-- wp:belye-nochi/room-filter /-->
<!-- wp:belye-nochi/room-catalog /-->
</section>
<!-- /wp:group -->
BLOCKS;
}

function bn_blocks_pattern_rooms_cta() {
	return <<<'BLOCKS'
<!-- wp:group {"tagName":"section","className":"rooms-cta","layout":{"type":"default"}} -->
<section class="wp-block-group rooms-cta">
<!-- wp:paragraph {"className":"section-kicker"} --><p class="section-kicker">Бронирование</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2,"anchor":"rooms-cta-title"} --><h2 class="wp-block-heading" id="rooms-cta-title">Поможем подобрать<br>подходящий вариант</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Позвоните — администратор проверит наличие и расскажет об актуальной стоимости.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p><a href="tel:+79293386160">+7 929 338-61-60</a></p><!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
BLOCKS;
}

function bn_blocks_native_rooms_content() {
	return bn_blocks_pattern_rooms_catalog() . "\n\n" . bn_blocks_pattern_rooms_cta();
}

function bn_blocks_register_patterns() {
	register_block_pattern_category( 'belye-nochi', array( 'label' => 'Белые Ночи' ) );
	$patterns = array(
		'hero'          => array( 'Главный экран', bn_blocks_pattern_hero() ),
		'about'         => array( 'О гостинице и галерея', bn_blocks_pattern_about() ),
		'stays'         => array( 'Гостиница и хостел', bn_blocks_pattern_stays() ),
		'restaurant'    => array( 'Ресторан', bn_blocks_pattern_restaurant() ),
		'features'      => array( 'Удобства и сервис', bn_blocks_pattern_features() ),
		'contacts'      => array( 'Контакты', bn_blocks_pattern_contacts() ),
		'rooms-catalog' => array( 'Каталог номеров', bn_blocks_pattern_rooms_catalog() ),
		'rooms-cta'     => array( 'Бронирование номеров', bn_blocks_pattern_rooms_cta() ),
		'home-page'     => array( 'Страница: Главная', bn_blocks_native_home_content() ),
		'rooms-page'    => array( 'Страница: Номера', bn_blocks_native_rooms_content() ),
		'privacy-page'  => array( 'Страница: Политика конфиденциальности', bn_blocks_privacy_policy_content() ),
	);
	foreach ( $patterns as $slug => $pattern ) {
		register_block_pattern(
			'belye-nochi/' . $slug,
			array(
				'title'      => $pattern[0],
				'categories' => array( 'belye-nochi' ),
				'content'    => $pattern[1],
			)
		);
	}
}
add_action( 'init', 'bn_blocks_register_patterns', 30 );
