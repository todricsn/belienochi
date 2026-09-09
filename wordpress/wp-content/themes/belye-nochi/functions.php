<?php
/**
 * Theme setup and one-time Gutenberg content import.
 *
 * @package Belye_Nochi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function belye_nochi_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'html5', array( 'style', 'script', 'navigation-widgets' ) );
}
add_action( 'after_setup_theme', 'belye_nochi_setup' );

function belye_nochi_enqueue_assets() {
	$theme = wp_get_theme();
	$version = $theme->get( 'Version' );

	wp_enqueue_style( 'belye-nochi-base', get_stylesheet_uri(), array(), $version );

	if ( is_page_template( 'page-rooms.php' ) || is_page( 'rooms' ) ) {
		wp_enqueue_style( 'belye-nochi-rooms', get_theme_file_uri( 'rooms.css' ), array( 'belye-nochi-base' ), $version );
		wp_enqueue_script( 'belye-nochi-rooms', get_theme_file_uri( 'rooms.js' ), array(), $version, true );
		return;
	}

	wp_enqueue_style( 'belye-nochi-front', get_theme_file_uri( 'front.css' ), array( 'belye-nochi-base' ), $version );
	wp_enqueue_script( 'belye-nochi-front', get_theme_file_uri( 'front.js' ), array(), $version, true );
}
add_action( 'wp_enqueue_scripts', 'belye_nochi_enqueue_assets' );

function belye_nochi_favicon() {
	printf(
		'<link rel="icon" href="%s">' . "\n",
		esc_url( get_theme_file_uri( 'assets/images/logo-heritage.png' ) )
	);
}
add_action( 'wp_head', 'belye_nochi_favicon', 5 );
add_action( 'admin_head', 'belye_nochi_favicon', 5 );

function belye_nochi_meta_description() {
	if ( is_front_page() ) {
		$description = 'Гостиница, хостел и ресторан «Белые Ночи» в Красноярске. Номера с удобствами, круглосуточная работа, парковка и банкетный зал.';
	} elseif ( is_page( 'rooms' ) ) {
		$description = 'Номера гостиницы и места в хостеле «Белые Ночи» в Красноярске: фотографии, варианты размещения и стоимость.';
	} else {
		return;
	}

	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
}
add_action( 'wp_head', 'belye_nochi_meta_description', 4 );

/**
 * Convert the approved static page into section-level Gutenberg HTML blocks.
 */
function belye_nochi_build_block_content( $source_file ) {
	$path = get_theme_file_path( 'source/' . $source_file );
	if ( ! is_readable( $path ) ) {
		return '';
	}

	$html = file_get_contents( $path );
	if ( false === $html ) {
		return '';
	}

	$fragments = array();
	if ( preg_match( '/<main[^>]*>(.*?)<\/main>/si', $html, $main_match ) ) {
		preg_match_all( '/<section\b.*?<\/section>/si', $main_match[1], $sections );
		$fragments = $sections[0];
	}

	preg_match_all( '/<dialog\b.*?<\/dialog>/si', $html, $dialogs );
	$fragments = array_merge( $fragments, $dialogs[0] );

	$asset_path = wp_parse_url( get_theme_file_uri( 'assets/' ), PHP_URL_PATH );
	$asset_path = trailingslashit( $asset_path );

	foreach ( $fragments as &$fragment ) {
		$fragment = str_replace( 'assets/', $asset_path, $fragment );
		$fragment = str_replace( 'index.html#', '/#', $fragment );
		$fragment = str_replace( 'index.html', '/', $fragment );
		$fragment = str_replace( 'rooms.html', '/rooms/', $fragment );
		$fragment = trim( $fragment );
		$fragment = "<!-- wp:html -->\n{$fragment}\n<!-- /wp:html -->";
	}
	unset( $fragment );

	return implode( "\n\n", $fragments );
}

/**
 * Create the two editable pages only during the initial theme activation.
 */
function belye_nochi_seed_pages() {
	$pages = array(
		'home' => array(
			'title'    => 'Главная',
			'source'   => 'index.html',
			'template' => 'default',
		),
		'rooms' => array(
			'title'    => 'Номера',
			'source'   => 'rooms.html',
			'template' => 'page-rooms.php',
		),
	);

	$created = array();
	foreach ( $pages as $slug => $page ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing ) {
			$created[ $slug ] = $existing->ID;
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'     => 'page',
				'post_status'   => 'publish',
				'post_title'    => $page['title'],
				'post_name'     => $slug,
				'post_content'  => belye_nochi_build_block_content( $page['source'] ),
				'page_template' => $page['template'],
			),
			true
		);

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_belye_nochi_seeded', '1' );
			if ( 'default' !== $page['template'] ) {
				update_post_meta( $post_id, '_wp_page_template', $page['template'] );
			}
			$created[ $slug ] = $post_id;
		}
	}

	if ( ! empty( $created['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $created['home'] );
	}

	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'belye_nochi_seed_pages' );
