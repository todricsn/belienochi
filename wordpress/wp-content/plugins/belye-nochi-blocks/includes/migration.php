<?php
/**
 * One-time migration from the approved static source to native blocks and rooms.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bn_blocks_node_text( DOMXPath $xpath, $query, DOMNode $context ) {
	$node = $xpath->query( $query, $context )->item( 0 );
	return $node ? trim( preg_replace( '/\s+/u', ' ', $node->textContent ) ) : '';
}

function bn_blocks_room_title( DOMXPath $xpath, DOMNode $card ) {
	$heading = $xpath->query( './/div[contains(@class,"room-card-body")]/h2', $card )->item( 0 );
	if ( ! $heading ) {
		return '';
	}
	$title = '';
	foreach ( $heading->childNodes as $child ) {
		if ( XML_TEXT_NODE === $child->nodeType ) {
			$title .= $child->nodeValue;
		}
	}
	return trim( preg_replace( '/\s+/u', ' ', $title ) );
}

function bn_blocks_parse_source_rooms() {
	$path = get_theme_file_path( 'source/rooms.html' );
	if ( ! is_readable( $path ) ) {
		return array();
	}
	libxml_use_internal_errors( true );
	$document = new DOMDocument();
	$document->loadHTML( '<?xml encoding="utf-8" ?>' . file_get_contents( $path ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	$xpath = new DOMXPath( $document );
	$cards = $xpath->query( '//article[contains(concat(" ",normalize-space(@class)," ")," room-card ")]' );
	$rooms = array();
	foreach ( $cards as $card ) {
		$images = array();
		foreach ( $xpath->query( './/div[contains(@class,"room-gallery")]//img', $card ) as $image ) {
			$source   = $image->getAttribute( 'src' );
			$images[] = array(
				'id'  => 0,
				'url' => 0 === strpos( $source, 'assets/' ) ? bn_blocks_theme_asset( substr( $source, 7 ) ) : $source,
				'alt' => $image->getAttribute( 'alt' ),
			);
		}
		$meta_divs = $xpath->query( './/dl[contains(@class,"room-meta")]/div', $card );
		$rooms[] = array(
			'slug'            => $card->getAttribute( 'id' ),
			'type'            => $card->getAttribute( 'data-room-type' ) ?: 'hotel',
			'title'           => bn_blocks_room_title( $xpath, $card ),
			'subtitle'        => bn_blocks_node_text( $xpath, './/div[contains(@class,"room-card-body")]/h2/span', $card ),
			'description'     => bn_blocks_node_text( $xpath, './/*[contains(concat(" ",normalize-space(@class)," ")," room-card-description ")]', $card ),
			'price'           => bn_blocks_node_text( $xpath, './/*[contains(concat(" ",normalize-space(@class)," ")," room-price ")]', $card ),
			'placement_label' => $meta_divs->length ? bn_blocks_node_text( $xpath, './dt', $meta_divs->item( 0 ) ) : 'Размещение',
			'placement'       => $meta_divs->length ? bn_blocks_node_text( $xpath, './dd', $meta_divs->item( 0 ) ) : '',
			'price_unit'      => $meta_divs->length > 1 ? bn_blocks_node_text( $xpath, './dd/small', $meta_divs->item( 1 ) ) : 'за номер',
			'images'          => $images,
		);
	}
	libxml_clear_errors();
	return $rooms;
}

function bn_blocks_seed_rooms_from_source() {
	$created = 0;
	$skipped = 0;
	foreach ( bn_blocks_parse_source_rooms() as $index => $room ) {
		$existing = get_page_by_path( $room['slug'], OBJECT, 'bn_room' );
		if ( $existing ) {
			++$skipped;
			continue;
		}
		$content = '<!-- wp:paragraph -->' . "\n" . '<p>' . esc_html( $room['description'] ) . '</p>' . "\n" . '<!-- /wp:paragraph -->';
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'bn_room',
				'post_status'  => 'publish',
				'post_title'   => $room['title'],
				'post_name'    => $room['slug'],
				'post_content' => $content,
				'menu_order'   => $index + 1,
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			continue;
		}
		update_post_meta( $post_id, '_bn_room_type', $room['type'] );
		update_post_meta( $post_id, '_bn_room_subtitle', $room['subtitle'] );
		update_post_meta( $post_id, '_bn_room_price', $room['price'] );
		update_post_meta( $post_id, '_bn_room_placement_label', $room['placement_label'] );
		update_post_meta( $post_id, '_bn_room_placement', $room['placement'] );
		update_post_meta( $post_id, '_bn_room_price_unit', $room['price_unit'] );
		update_post_meta( $post_id, '_bn_room_gallery', wp_json_encode( $room['images'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		update_post_meta( $post_id, '_bn_room_seeded', '1' );
		++$created;
	}
	return array( 'created' => $created, 'skipped' => $skipped );
}

function bn_blocks_update_seeded_page( $slug, $content, $template = 'default' ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	$is_theme_seeded = $page && '1' === get_post_meta( $page->ID, '_belye_nochi_seeded', true );
	$is_legacy_rooms = $page && 'rooms' === $slug && false !== strpos( $page->post_content, 'data-room-filter' ) && false !== strpos( $page->post_content, '<!-- wp:html -->' );
	if ( ! $page || ( ! $is_theme_seeded && ! $is_legacy_rooms ) ) {
		return false;
	}
	wp_save_post_revision( $page->ID );
	$result = wp_update_post(
		array(
			'ID'            => $page->ID,
			'post_content'  => $content,
			'page_template' => $template,
		),
		true
	);
	if ( is_wp_error( $result ) ) {
		return false;
	}
	update_post_meta( $page->ID, '_bn_native_blocks_migrated', BN_BLOCKS_VERSION );
	return $page->ID;
}

function bn_blocks_privacy_policy_content() {
	return <<<'BLOCKS'
<!-- wp:group {"className":"bn-policy","layout":{"type":"default"}} -->
<div class="wp-block-group bn-policy">
<!-- wp:paragraph {"className":"bn-policy__updated"} --><p class="bn-policy__updated">Редакция от 10 сентября 2026 года</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>Настоящая политика объясняет, какие данные могут обрабатываться при посещении сайта гостиницы «Белые Ночи», для каких целей используются cookies и как посетитель может изменить свой выбор.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">1. Оператор и контакты</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Оператор сайта: администрация гостиницы «Белые Ночи». Адрес: Красноярск, 2-я Красногорская улица, 3. По вопросам обработки данных можно обратиться по телефонам +7 929 338-61-60 и +7 913 538-60-27.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">2. Какие данные обрабатываются</h2><!-- /wp:heading -->
<!-- wp:list --><ul class="wp-block-list"><li>технические сведения о браузере, устройстве и операционной системе;</li><li>IP-адрес, дата и время посещения, просмотренные страницы и источник перехода;</li><li>файлы cookies и идентификаторы в localStorage;</li><li>выбор пользователя относительно аналитических cookies.</li></ul><!-- /wp:list -->
<!-- wp:paragraph --><p>На сайте нет формы бронирования. При нажатии на телефонную ссылку звонок выполняется средствами устройства посетителя, и сайт не получает содержание разговора.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">3. Цели и основания обработки</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Необходимые технические данные используются для работы сайта, безопасности и сохранения настроек. Аналитические данные могут использоваться для оценки посещаемости и улучшения сайта только после отдельного согласия посетителя.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">4. Cookies и Яндекс Метрика</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Сайт сохраняет необходимый cookie с выбранной настройкой конфиденциальности сроком до одного года. В дальнейшем на сайте может быть подключена Яндекс Метрика. Сервис использует cookies и localStorage для статистики посещений. Код аналитики и встроенная карта Яндекса загружаются только после согласия на аналитические cookies.</p><!-- /wp:paragraph -->
<!-- wp:paragraph --><p>Если аналитика будет подключена, Яндекс будет обрабатывать данные по поручению оператора в соответствии со своими условиями и политикой конфиденциальности. Посетитель может в любое время изменить выбор кнопкой «Настроить cookies» внизу страницы.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">5. Передача и хранение</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Данные передаются только поставщикам технических и аналитических сервисов в объёме, необходимом для их работы, либо в случаях, предусмотренных законом. Срок хранения зависит от типа данных, настроек сервиса и требований законодательства. После достижения цели данные удаляются или обезличиваются.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">6. Права посетителя</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Посетитель вправе запросить сведения об обработке своих данных, потребовать их уточнения, блокирования или удаления, а также отозвать согласие. Отказ от аналитических cookies не ограничивает доступ к основному содержанию сайта.</p><!-- /wp:paragraph -->
<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">7. Изменение политики</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>Политика может обновляться при изменении сайта, используемых сервисов или законодательства. Актуальная редакция всегда размещается на этой странице.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
BLOCKS;
}

function bn_blocks_create_privacy_page() {
	$page = get_page_by_path( 'privacy-policy', OBJECT, 'page' );
	if ( $page ) {
		$is_wordpress_placeholder = 'Privacy Policy' === $page->post_title && false !== strpos( $page->post_content, 'Suggested text:' );
		$is_our_page              = '1' === get_post_meta( $page->ID, '_bn_policy_seeded', true );
		if ( $is_wordpress_placeholder || $is_our_page ) {
			$updates = array(
				'ID'           => $page->ID,
				'post_title'   => 'Политика конфиденциальности и cookies',
				'post_content' => bn_blocks_privacy_policy_content(),
			);
			if ( $is_wordpress_placeholder ) {
				$updates['post_status'] = 'publish';
			}
			wp_update_post( $updates );
			update_post_meta( $page->ID, '_bn_policy_seeded', '1' );
		}
		update_option( 'wp_page_for_privacy_policy', $page->ID );
		return $page->ID;
	}
	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Политика конфиденциальности и cookies',
			'post_name'    => 'privacy-policy',
			'post_content' => bn_blocks_privacy_policy_content(),
		),
		true
	);
	if ( is_wp_error( $page_id ) ) {
		return false;
	}
	update_post_meta( $page_id, '_bn_policy_seeded', '1' );
	update_option( 'wp_page_for_privacy_policy', $page_id );
	return $page_id;
}

function bn_blocks_maybe_upgrade_data() {
	$data_version = get_option( 'bn_blocks_data_version', '0' );
	if ( version_compare( $data_version, '1.1.2', '<' ) ) {
		bn_blocks_create_privacy_page();
	}
	if ( version_compare( $data_version, '1.1.3', '<' ) ) {
		$home_page  = get_page_by_path( 'home', OBJECT, 'page' );
		$rooms_page = get_page_by_path( 'rooms', OBJECT, 'page' );
		if ( $rooms_page && ! get_post_meta( $rooms_page->ID, '_bn_native_blocks_migrated', true ) ) {
			bn_blocks_update_seeded_page( 'rooms', bn_blocks_native_rooms_content(), 'page-rooms.php' );
		}
		if ( $home_page && $rooms_page && get_post_meta( $home_page->ID, '_bn_native_blocks_migrated', true ) && get_post_meta( $rooms_page->ID, '_bn_native_blocks_migrated', true ) ) {
			update_option( 'bn_blocks_migration_complete', BN_BLOCKS_VERSION, false );
		}
	}
	update_option( 'bn_blocks_data_version', BN_BLOCKS_VERSION, false );
}
add_action( 'init', 'bn_blocks_maybe_upgrade_data', 40 );

function bn_blocks_migrate_to_native() {
	bn_blocks_register_room_type();
	$rooms = bn_blocks_seed_rooms_from_source();
	$result = array(
		'rooms' => $rooms,
		'home'  => bn_blocks_update_seeded_page( 'home', bn_blocks_native_home_content(), 'default' ),
		'rooms_page' => bn_blocks_update_seeded_page( 'rooms', bn_blocks_native_rooms_content(), 'page-rooms.php' ),
		'privacy_page' => bn_blocks_create_privacy_page(),
	);
	if ( $result['home'] && $result['rooms_page'] && $result['privacy_page'] ) {
		update_option( 'bn_blocks_migration_complete', BN_BLOCKS_VERSION, false );
		update_option( 'bn_blocks_data_version', BN_BLOCKS_VERSION, false );
	}
	return $result;
}

function bn_blocks_activate() {
	bn_blocks_register_room_type();
	if ( ! get_option( 'bn_blocks_migration_complete' ) ) {
		bn_blocks_migrate_to_native();
	}
	flush_rewrite_rules();
}
register_activation_hook( BN_BLOCKS_FILE, 'bn_blocks_activate' );

function bn_blocks_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( BN_BLOCKS_FILE, 'bn_blocks_deactivate' );
