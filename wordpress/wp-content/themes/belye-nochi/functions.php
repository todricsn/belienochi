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
