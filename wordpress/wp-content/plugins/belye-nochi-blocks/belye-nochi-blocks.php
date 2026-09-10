<?php
/**
 * Plugin Name: Белые Ночи — блоки и номера
 * Description: Нативные блоки Gutenberg, паттерны страниц и управляемый каталог номеров.
 * Version: 1.1.5
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Белые Ночи
 * Text Domain: belye-nochi-blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BN_BLOCKS_VERSION', '1.1.5' );
define( 'BN_BLOCKS_FILE', __FILE__ );
define( 'BN_BLOCKS_DIR', plugin_dir_path( __FILE__ ) );
define( 'BN_BLOCKS_URL', plugin_dir_url( __FILE__ ) );

require_once BN_BLOCKS_DIR . 'includes/render.php';
require_once BN_BLOCKS_DIR . 'includes/patterns.php';
require_once BN_BLOCKS_DIR . 'includes/migration.php';

/**
 * Register the room content type used by the dynamic catalog block.
 */
function bn_blocks_register_room_type() {
	register_post_type(
		'bn_room',
		array(
			'labels' => array(
				'name'               => 'Номера',
				'singular_name'      => 'Номер',
				'add_new'            => 'Добавить номер',
				'add_new_item'       => 'Добавить номер',
				'edit_item'          => 'Редактировать номер',
				'new_item'           => 'Новый номер',
				'view_item'          => 'Посмотреть номер',
				'search_items'       => 'Найти номер',
				'not_found'          => 'Номера не найдены',
				'not_found_in_trash' => 'В корзине номеров нет',
				'all_items'          => 'Все номера',
				'menu_name'          => 'Номера',
			),
			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-building',
			'supports'           => array( 'title', 'editor', 'page-attributes', 'revisions', 'custom-fields' ),
			'has_archive'        => false,
			'publicly_queryable' => false,
			'rewrite'            => false,
			'template'           => array(
				array(
					'core/paragraph',
					array( 'placeholder' => 'Короткое описание номера для карточки' ),
				),
			),
		)
	);

	$meta_fields = array(
		'_bn_room_type'            => array( 'type' => 'string', 'default' => 'hotel' ),
		'_bn_room_subtitle'        => array( 'type' => 'string', 'default' => '' ),
		'_bn_room_price'           => array( 'type' => 'string', 'default' => '' ),
		'_bn_room_placement_label' => array( 'type' => 'string', 'default' => 'Размещение' ),
		'_bn_room_placement'       => array( 'type' => 'string', 'default' => '' ),
		'_bn_room_price_unit'      => array( 'type' => 'string', 'default' => 'за номер' ),
		'_bn_room_gallery'         => array( 'type' => 'string', 'default' => '[]' ),
	);

	foreach ( $meta_fields as $key => $schema ) {
		register_post_meta(
			'bn_room',
			$key,
			array(
				'type'              => $schema['type'],
				'single'            => true,
				'default'           => $schema['default'],
				'show_in_rest'      => true,
				'sanitize_callback' => '_bn_room_gallery' === $key ? 'bn_blocks_sanitize_gallery_json' : 'sanitize_text_field',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'bn_blocks_register_room_type' );

function bn_blocks_sanitize_gallery_json( $value ) {
	$decoded = json_decode( (string) $value, true );
	if ( ! is_array( $decoded ) ) {
		return '[]';
	}

	$clean = array();
	foreach ( $decoded as $image ) {
		if ( ! is_array( $image ) || empty( $image['url'] ) ) {
			continue;
		}

		$clean[] = array(
			'id'  => isset( $image['id'] ) ? absint( $image['id'] ) : 0,
			'url' => esc_url_raw( $image['url'] ),
			'alt' => isset( $image['alt'] ) ? sanitize_text_field( $image['alt'] ) : '',
		);
	}

	return wp_json_encode( $clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

/**
 * Register editor assets and all server-rendered blocks.
 */
function bn_blocks_register_blocks() {
	wp_register_script(
		'bn-blocks-editor',
		BN_BLOCKS_URL . 'assets/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-data', 'wp-plugins', 'wp-edit-post', 'wp-editor', 'wp-i18n' ),
		BN_BLOCKS_VERSION,
		true
	);

	wp_register_style(
		'bn-blocks-editor',
		BN_BLOCKS_URL . 'assets/editor.css',
		array( 'wp-edit-blocks' ),
		BN_BLOCKS_VERSION
	);

	$blocks = array(
		'belye-nochi/hero' => array(
			'render_callback' => 'bn_blocks_render_hero',
			'attributes'      => array(
				'eyebrow' => array( 'type' => 'string', 'default' => 'Гостиница · хостел · Красноярск' ),
				'copy'     => array( 'type' => 'string', 'default' => "Круглосуточные отдельные номера\nи места в хостеле — на въезде в Красноярск." ),
				'mapUrl'   => array( 'type' => 'string', 'default' => 'https://yandex.ru/maps/-/CTwFQZ9y' ),
				'phone'    => array( 'type' => 'string', 'default' => '+79293386160' ),
			),
		),
		'belye-nochi/photo-gallery' => array(
			'render_callback' => 'bn_blocks_render_photo_gallery',
			'attributes'      => array(
				'variant'   => array( 'type' => 'string', 'default' => 'hotel' ),
				'ariaLabel' => array( 'type' => 'string', 'default' => 'Фотографии Белых Ночей' ),
				'images'    => array( 'type' => 'array', 'default' => array() ),
			),
		),
		'belye-nochi/stays' => array(
			'render_callback' => 'bn_blocks_render_stays',
			'attributes'      => bn_blocks_stays_attribute_schema(),
		),
		'belye-nochi/action-link' => array(
			'render_callback' => 'bn_blocks_render_action_link',
			'attributes'      => array(
				'label'     => array( 'type' => 'string', 'default' => 'Забронировать' ),
				'url'       => array( 'type' => 'string', 'default' => 'tel:+79293386160' ),
				'ariaLabel' => array( 'type' => 'string', 'default' => '' ),
				'className' => array( 'type' => 'string', 'default' => '' ),
				'showArrow' => array( 'type' => 'boolean', 'default' => true ),
			),
		),
		'belye-nochi/feature-list' => array(
			'render_callback' => 'bn_blocks_render_feature_list',
			'attributes'      => array(
				'items' => array( 'type' => 'array', 'default' => bn_blocks_default_features() ),
			),
		),
		'belye-nochi/contact-map' => array(
			'render_callback' => 'bn_blocks_render_contact_map',
			'attributes'      => array(
				'title' => array( 'type' => 'string', 'default' => 'Гостиница Белые Ночи на Яндекс Картах' ),
				'url'   => array( 'type' => 'string', 'default' => 'https://yandex.ru/map-widget/v1/?ll=92.767812%2C56.048454&mode=search&oid=202440014712&ol=biz&z=17' ),
			),
		),
		'belye-nochi/room-filter' => array(
			'render_callback' => 'bn_blocks_render_room_filter',
			'attributes'      => array(
				'title'       => array( 'type' => 'string', 'default' => 'Выберите тип размещения' ),
				'hotelLabel'  => array( 'type' => 'string', 'default' => 'Гостиница' ),
				'hostelLabel' => array( 'type' => 'string', 'default' => 'Хостел' ),
			),
		),
		'belye-nochi/room-catalog' => array(
			'render_callback' => 'bn_blocks_render_room_catalog',
			'attributes'      => array(),
		),
	);

	foreach ( $blocks as $name => $settings ) {
		$settings['editor_script'] = 'bn-blocks-editor';
		$settings['editor_style']  = 'bn-blocks-editor';
		$settings['api_version']   = 3;
		register_block_type( $name, $settings );
	}
}
add_action( 'init', 'bn_blocks_register_blocks' );

function bn_blocks_enqueue_frontend_assets() {
	wp_enqueue_style( 'bn-blocks-frontend', BN_BLOCKS_URL . 'assets/frontend.css', array(), BN_BLOCKS_VERSION );
	wp_enqueue_script( 'bn-cookie-consent', BN_BLOCKS_URL . 'assets/cookie-consent.js', array(), BN_BLOCKS_VERSION, true );
	wp_localize_script(
		'bn-cookie-consent',
		'bnCookieSettings',
		array(
			'policyUrl' => home_url( '/privacy-policy/' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'bn_blocks_enqueue_frontend_assets', 30 );

function bn_blocks_enqueue_room_editor() {
	$screen = get_current_screen();
	if ( ! $screen || 'bn_room' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_script( 'bn-blocks-editor' );
	wp_enqueue_style( 'bn-blocks-editor' );
}
add_action( 'enqueue_block_editor_assets', 'bn_blocks_enqueue_room_editor' );

function bn_blocks_register_category( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'belye-nochi',
			'title' => 'Белые Ночи',
		)
	);
	return $categories;
}
add_filter( 'block_categories_all', 'bn_blocks_register_category' );

/**
 * Keep room ordering stable and append newly created rooms to the end.
 */
function bn_blocks_set_new_room_order( $post_id, $post, $update ) {
	if ( $update || 'bn_room' !== $post->post_type || wp_is_post_revision( $post_id ) ) {
		return;
	}

	$last = get_posts(
		array(
			'post_type'      => 'bn_room',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => 1,
			'post__not_in'   => array( $post_id ),
			'orderby'        => 'menu_order',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);

	$next_order = $last ? (int) get_post_field( 'menu_order', $last[0] ) + 1 : 1;
	remove_action( 'save_post_bn_room', 'bn_blocks_set_new_room_order', 10 );
	wp_update_post( array( 'ID' => $post_id, 'menu_order' => $next_order ) );
	add_action( 'save_post_bn_room', 'bn_blocks_set_new_room_order', 10, 3 );
}
add_action( 'save_post_bn_room', 'bn_blocks_set_new_room_order', 10, 3 );

function bn_blocks_cookie_notice() {
	?>
	<div class="bn-cookie" data-cookie-banner hidden role="dialog" aria-modal="true" aria-labelledby="bn-cookie-title">
		<div class="bn-cookie__inner">
			<div class="bn-cookie__copy">
				<p class="bn-cookie__eyebrow">Настройки cookies</p>
				<h2 id="bn-cookie-title">Ваш выбор важен</h2>
				<p>Необходимые cookies помогают сайту работать. Аналитические cookies будут использоваться только с вашего согласия, когда мы подключим Яндекс Метрику.</p>
				<a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>">Политика конфиденциальности и cookies</a>
			</div>
			<div class="bn-cookie__controls">
				<label><input type="checkbox" data-cookie-analytics> <span>Разрешить аналитические cookies</span></label>
				<div class="bn-cookie__actions">
					<button type="button" data-cookie-essential>Только необходимые</button>
					<button type="button" data-cookie-accept disabled>Сохранить согласие</button>
				</div>
			</div>
		</div>
	</div>
	<button class="bn-cookie-settings" type="button" data-cookie-settings hidden>Настроить cookies</button>
	<?php
}
add_action( 'wp_footer', 'bn_blocks_cookie_notice', 1 );
