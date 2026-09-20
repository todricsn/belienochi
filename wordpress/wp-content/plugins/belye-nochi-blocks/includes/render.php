<?php
/**
 * Front-end rendering for the Белые Ночи blocks.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bn_blocks_theme_asset( $relative ) {
	return get_theme_file_uri( 'assets/' . ltrim( $relative, '/' ) );
}

/**
 * Load one approved section from the bundled theme source.
 *
 * Complex visual blocks retain their tested markup, while Gutenberg stores a
 * real block with structured attributes instead of a Custom HTML fragment.
 */
function bn_blocks_source_fragment( $source, $id = '', $class = '', $data_attribute = '' ) {
	$path = get_theme_file_path( 'source/' . $source );
	if ( ! is_readable( $path ) ) {
		return '';
	}

	$html = file_get_contents( $path );
	if ( false === $html ) {
		return '';
	}

	libxml_use_internal_errors( true );
	$document = new DOMDocument();
	$document->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
	$xpath = new DOMXPath( $document );
	if ( $id ) {
		$query = '//*[@id="' . $id . '"]';
	} elseif ( $class ) {
		$query = '//*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]';
	} elseif ( preg_match( '/\Adata-[a-z][a-z0-9-]*\z/i', $data_attribute ) ) {
		$query = '//*[@' . $data_attribute . ']';
	} else {
		libxml_clear_errors();
		return '';
	}
	$node = $xpath->query( $query )->item( 0 );
	if ( ! $node ) {
		libxml_clear_errors();
		return '';
	}

	$fragment = $document->saveHTML( $node );
	libxml_clear_errors();
	$fragment = str_replace( 'assets/', trailingslashit( get_theme_file_uri( 'assets' ) ), $fragment );
	$fragment = str_replace( 'rooms.html', home_url( '/rooms/' ), $fragment );
	$fragment = str_replace( 'index.html#', home_url( '/' ) . '#', $fragment );
	$fragment = str_replace( 'index.html', home_url( '/' ), $fragment );
	return $fragment;
}

function bn_blocks_replace_text( $html, $selector_class, $value ) {
	if ( '' === $value ) {
		return $html;
	}
	$pattern = '/(<[^>]+class="[^"]*\b' . preg_quote( $selector_class, '/' ) . '\b[^"]*"[^>]*>)(.*?)(<\/[^>]+>)/si';
	return preg_replace( $pattern, '$1' . nl2br( esc_html( $value ) ) . '$3', $html, 1 );
}

function bn_blocks_render_hero( $attributes ) {
	$html = bn_blocks_source_fragment( 'index.html', 'top' );
	$html = bn_blocks_replace_text( $html, 'hero-eyebrow', $attributes['eyebrow'] ?? '' );
	$html = bn_blocks_replace_text( $html, 'hero-copy', $attributes['copy'] ?? '' );
	if ( ! empty( $attributes['mapUrl'] ) ) {
		$html = preg_replace( '/(<a class="hero-corner-action" href=")[^"]*/', '$1' . esc_url( $attributes['mapUrl'] ), $html, 1 );
	}
	if ( ! empty( $attributes['phone'] ) ) {
		$phone = preg_replace( '/[^0-9+]/', '', $attributes['phone'] );
		$html  = str_replace( 'tel:+79293386160', 'tel:' . $phone, $html );
	}
	return $html;
}

function bn_blocks_default_gallery_images( $variant ) {
	$id       = 'restaurant' === $variant ? 'restaurant' : 'about';
	$fragment = bn_blocks_source_fragment( 'index.html', $id );
	$images   = array();
	if ( preg_match_all( '/<img[^>]+src="([^"]+)"[^>]+alt="([^"]*)"/i', $fragment, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $match ) {
			$images[] = array( 'url' => html_entity_decode( $match[1] ), 'alt' => html_entity_decode( $match[2] ) );
		}
	}
	return $images;
}

/**
 * Normalize one gallery item without allowing media type to leak into room galleries.
 *
 * Explicit dimensions are preferred because video metadata can describe the encoded
 * canvas rather than the displayed orientation (for example, a rotated phone video).
 */
function bn_blocks_gallery_media_info( $item ) {
	$item = is_array( $item ) ? $item : array();
	$id   = ! empty( $item['id'] ) ? absint( $item['id'] ) : 0;
	$mime = strtolower( sanitize_mime_type( (string) ( $item['mime'] ?? '' ) ) );

	if ( $id ) {
		$attachment_mime = get_post_mime_type( $id );
		if ( $attachment_mime ) {
			$mime = strtolower( sanitize_mime_type( $attachment_mime ) );
		}
	}

	$metadata       = $id ? wp_get_attachment_metadata( $id ) : array();
	$metadata_width = is_array( $metadata ) ? absint( $metadata['width'] ?? 0 ) : 0;
	$metadata_height = is_array( $metadata ) ? absint( $metadata['height'] ?? 0 ) : 0;
	$width          = ! empty( $item['width'] ) ? absint( $item['width'] ) : $metadata_width;
	$height         = ! empty( $item['height'] ) ? absint( $item['height'] ) : $metadata_height;
	$type           = strtolower( (string) ( $item['type'] ?? '' ) );
	$is_video       = 'video' === $type || 0 === strpos( $type, 'video/' ) || 0 === strpos( $mime, 'video/' );

	return array(
		'type'   => $is_video ? 'video' : 'image',
		'mime'   => $mime,
		'width'  => $width,
		'height' => $height,
	);
}

function bn_blocks_render_photo_gallery( $attributes ) {
	$variant = ( $attributes['variant'] ?? 'hotel' ) === 'restaurant' ? 'restaurant' : 'hotel';
	if ( empty( $attributes['images'] ) ) {
		return bn_blocks_source_fragment( 'index.html', '', 'restaurant' === $variant ? 'restaurant-gallery' : 'photo-gallery' );
	}
	$images  = array_values( array_filter( (array) $attributes['images'], 'is_array' ) );
	if ( ! $images ) {
		return '';
	}

	$restaurant = 'restaurant' === $variant;
	if ( ! $restaurant ) {
		$images = array_values(
			array_filter(
				$images,
				static function ( $image ) {
					return 'video' !== bn_blocks_gallery_media_info( $image )['type'];
				}
			)
		);
		if ( ! $images ) {
			return '';
		}
	}
	$root_class = $restaurant ? 'restaurant-gallery reveal' : 'photo-gallery reveal';
	$data_root  = $restaurant ? 'data-restaurant-gallery' : 'data-photo-gallery';
	$label      = $attributes['ariaLabel'] ?? ( $restaurant ? 'Фотографии ресторана Белые Ночи' : 'Фотографии гостиницы и номеров Белых Ночей' );

	ob_start();
	?>
	<div class="<?php echo esc_attr( $root_class ); ?>" <?php echo esc_attr( $data_root ); ?> tabindex="0" role="region" aria-roledescription="карусель" aria-label="<?php echo esc_attr( $label ); ?>">
		<?php if ( $restaurant ) : ?>
			<?php foreach ( $images as $index => $image ) : ?>
				<?php
				if ( empty( $image['url'] ) ) {
					continue;
				}
				$media_info  = bn_blocks_gallery_media_info( $image );
				$width       = $media_info['width'];
				$height      = $media_info['height'];
				$orientation = ( $width && $height && $height > $width ) ? 'portrait' : 'landscape';
				$media_class = 'restaurant-gallery-item restaurant-gallery-item-' . $orientation;
				if ( 'video' === $media_info['type'] ) {
					$media_class .= ' restaurant-gallery-item-video';
				}
				$media_style = '';
				if ( $width && $height ) {
					$ratio       = number_format( $width / $height, 5, '.', '' );
					$media_style = ' style="--restaurant-media-ratio:' . esc_attr( $ratio ) . ';"';
				}
				?>
				<figure class="<?php echo esc_attr( $media_class ); ?>"<?php echo $media_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> >
					<?php if ( 'video' === $media_info['type'] ) : ?>
						<?php
						$video_label = ! empty( $image['alt'] ) ? $image['alt'] : 'Видео ресторана ' . ( $index + 1 );
						$video_dims  = '';
						if ( $width ) {
							$video_dims .= ' width="' . esc_attr( $width ) . '"';
						}
						if ( $height ) {
							$video_dims .= ' height="' . esc_attr( $height ) . '"';
						}
						$source_type = 0 === strpos( $media_info['mime'], 'video/' ) ? ' type="' . esc_attr( $media_info['mime'] ) . '"' : '';
						?>
						<video src="<?php echo esc_url( $image['url'] ); ?>"<?php echo $video_dims; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> controls playsinline preload="metadata" aria-label="<?php echo esc_attr( $video_label ); ?>">
							<source src="<?php echo esc_url( $image['url'] ); ?>"<?php echo $source_type; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						</video>
					<?php else : ?>
						<button type="button" data-restaurant-gallery-open aria-label="Открыть фотографию <?php echo esc_attr( $index + 1 ); ?>">
							<img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ?? '' ); ?>" loading="lazy" decoding="async">
						</button>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		<?php else : ?>
		<div class="photo-gallery-track" data-gallery-track>
			<?php foreach ( $images as $index => $image ) : ?>
				<figure class="photo-gallery-slide" data-gallery-slide>
					<button class="photo-gallery-image" type="button" data-gallery-open aria-label="Открыть фотографию <?php echo esc_attr( $index + 1 ); ?>">
						<img src="<?php echo esc_url( $image['url'] ?? '' ); ?>" alt="<?php echo esc_attr( $image['alt'] ?? '' ); ?>" loading="lazy" decoding="async">
					</button>
				</figure>
			<?php endforeach; ?>
		</div>
		<button class="photo-gallery-edge photo-gallery-edge-prev" type="button" data-gallery-prev aria-label="Предыдущая фотография"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"/></svg></button>
		<button class="photo-gallery-edge photo-gallery-edge-next" type="button" data-gallery-next aria-label="Следующая фотография"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg></button>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

function bn_blocks_stays_attribute_schema() {
	return array(
		'hotelTitle'  => array( 'type' => 'string', 'default' => 'Гостиница' ),
		'hostelTitle' => array( 'type' => 'string', 'default' => 'Хостел' ),
	);
}

function bn_blocks_render_stays( $attributes ) {
	$html = bn_blocks_source_fragment( 'index.html', 'stays' );
	if ( ! empty( $attributes['hotelTitle'] ) ) {
		$html = preg_replace( '/(<article[^>]+id="hotel".*?<h2[^>]*>).*?(<\/h2>)/si', '$1' . esc_html( $attributes['hotelTitle'] ) . '$2', $html, 1 );
	}
	if ( ! empty( $attributes['hostelTitle'] ) ) {
		$html = preg_replace( '/(<article[^>]+id="hostel".*?<h2[^>]*>).*?(<\/h2>)/si', '$1' . esc_html( $attributes['hostelTitle'] ) . '$2', $html, 1 );
	}
	return $html;
}

function bn_blocks_render_action_link( $attributes ) {
	$label = $attributes['label'] ?? 'Забронировать';
	$url   = $attributes['url'] ?? 'tel:+79293386160';
	$class = trim( 'bn-action-link ' . ( $attributes['className'] ?? '' ) );
	$aria  = ! empty( $attributes['ariaLabel'] ) ? ' aria-label="' . esc_attr( $attributes['ariaLabel'] ) . '"' : '';
	$arrow = ! isset( $attributes['showArrow'] ) || $attributes['showArrow'];
	return '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '"' . $aria . '><span>' . esc_html( $label ) . '</span>' . ( $arrow ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m0 0-5-5m5 5-5 5"/></svg>' : '' ) . '</a>';
}

function bn_blocks_default_features() {
	return array(
		array( 'title' => 'Круглосуточно', 'text' => 'Работаем и принимаем гостей 24 часа в сутки.' ),
		array( 'title' => 'Парковка', 'text' => 'Удобная парковка рядом с гостиницей.' ),
		array( 'title' => 'Wi-Fi', 'text' => 'Бесплатный интернет для гостей.' ),
		array( 'title' => 'Ресторан', 'text' => 'Домашняя кухня и банкетный зал на территории.' ),
	);
}

function bn_blocks_render_feature_list( $attributes ) {
	// Retain the approved illustrated feature cards from the theme source.
	return bn_blocks_source_fragment( 'index.html', '', 'feature-list' );
}

function bn_blocks_render_contact_map( $attributes ) {
	return sprintf(
		'<div class="contacts-map reveal" aria-label="Белые Ночи на Яндекс Картах"><iframe src="about:blank" data-cookie-src="%s" title="%s" loading="lazy" allowfullscreen></iframe><div class="bn-map-consent" data-map-consent><p>Для показа карты требуется согласие на аналитические cookies.</p><button type="button" data-cookie-settings-open>Настроить cookies</button><a href="https://yandex.ru/maps/-/CTwFQZ9y" target="_blank" rel="noopener noreferrer">Открыть адрес в Яндекс Картах</a></div></div>',
		esc_url( $attributes['url'] ?? '' ),
		esc_attr( $attributes['title'] ?? 'Гостиница Белые Ночи на Яндекс Картах' )
	);
}

function bn_blocks_render_room_filter( $attributes ) {
	return sprintf(
		'<div class="rooms-filter-bar"><p class="rooms-filter-title" id="rooms-filter-title">%s</p><div class="rooms-filter" role="group" aria-label="Тип размещения"><button class="is-active" type="button" data-room-filter="hotel" aria-pressed="true">%s</button><button type="button" data-room-filter="hostel" aria-pressed="false">%s</button></div></div>',
		esc_html( $attributes['title'] ?? 'Выберите тип размещения' ),
		esc_html( $attributes['hotelLabel'] ?? 'Гостиница' ),
		esc_html( $attributes['hostelLabel'] ?? 'Хостел' )
	);
}

function bn_blocks_room_gallery( $images, $label ) {
	$count = count( $images );
	ob_start();
	?>
	<div class="room-gallery" data-room-gallery aria-label="<?php echo esc_attr( $label ); ?>">
		<div class="room-gallery-viewport"><div class="room-gallery-track" data-room-track>
			<?php foreach ( $images as $index => $image ) : ?>
				<button class="room-photo" type="button" data-room-photo aria-label="Открыть фотографию <?php echo esc_attr( $index + 1 ); ?>"><img src="<?php echo esc_url( $image['url'] ?? '' ); ?>" alt="<?php echo esc_attr( $image['alt'] ?? $label ); ?>" loading="lazy" decoding="async"></button>
			<?php endforeach; ?>
		</div></div>
		<div class="room-gallery-bar"><span><b data-room-current>01</b> / <?php echo esc_html( str_pad( (string) $count, 2, '0', STR_PAD_LEFT ) ); ?></span><div class="room-gallery-controls"><button type="button" data-room-prev aria-label="Предыдущая фотография"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12H5m0 0 5-5m-5 5 5 5"/></svg></button><button type="button" data-room-next aria-label="Следующая фотография"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14m0 0-5-5m5 5-5 5"/></svg></button></div></div>
	</div>
	<?php
	return ob_get_clean();
}

function bn_blocks_render_room_card( $post, $number ) {
	$type       = get_post_meta( $post->ID, '_bn_room_type', true ) ?: 'hotel';
	$subtitle   = get_post_meta( $post->ID, '_bn_room_subtitle', true );
	$price      = get_post_meta( $post->ID, '_bn_room_price', true );
	$meta_label = get_post_meta( $post->ID, '_bn_room_placement_label', true ) ?: 'Размещение';
	$placement  = get_post_meta( $post->ID, '_bn_room_placement', true );
	$price_unit = get_post_meta( $post->ID, '_bn_room_price_unit', true ) ?: 'за номер';
	$images     = json_decode( (string) get_post_meta( $post->ID, '_bn_room_gallery', true ), true );
	$images     = is_array( $images ) ? $images : array();
	$description = trim( wp_strip_all_tags( do_blocks( $post->post_content ) ) );
	$category    = 'hostel' === $type ? 'Хостел' : 'Гостиница';
	$title       = get_the_title( $post );

	ob_start();
	?>
	<article class="room-card" id="<?php echo esc_attr( $post->post_name ); ?>" data-room-type="<?php echo esc_attr( $type ); ?>">
		<?php echo bn_blocks_room_gallery( $images, 'Фотографии: ' . $title ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="room-card-body">
			<div class="room-card-head"><p><?php echo esc_html( $category ); ?></p><span><?php echo esc_html( str_pad( (string) $number, 2, '0', STR_PAD_LEFT ) ); ?></span></div>
			<h2><?php echo esc_html( $title ); ?><?php if ( $subtitle ) : ?> <span><?php echo esc_html( $subtitle ); ?></span><?php endif; ?></h2>
			<p class="room-card-description"><?php echo esc_html( $description ); ?></p>
			<p class="room-price"><?php echo esc_html( $price ); ?></p>
			<dl class="room-meta"><div><dt><?php echo esc_html( $meta_label ); ?></dt><dd><?php echo esc_html( $placement ); ?></dd></div><div><dt>Стоимость</dt><dd><?php echo esc_html( $price ); ?><small><?php echo esc_html( $price_unit ); ?></small></dd></div></dl>
			<a class="room-action" href="tel:+79293386160"><span>Забронировать</span></a>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

function bn_blocks_render_room_catalog() {
	$rooms = get_posts(
		array(
			'post_type'      => 'bn_room',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
		)
	);
	$html = '<div class="rooms-catalog" data-room-catalog aria-live="polite">';
	foreach ( $rooms as $index => $room ) {
		$html .= bn_blocks_render_room_card( $room, $index + 1 );
	}
	return $html . '</div>';
}

function bn_blocks_page_has_block( $name ) {
	return is_singular() && has_block( $name, get_queried_object_id() );
}

function bn_blocks_render_dialogs() {
	if ( bn_blocks_page_has_block( 'belye-nochi/photo-gallery' ) ) {
		echo bn_blocks_source_fragment( 'index.html', '', 'photo-lightbox' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo bn_blocks_source_fragment( 'index.html', '', '', 'data-restaurant-lightbox' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	if ( bn_blocks_page_has_block( 'belye-nochi/room-catalog' ) ) {
		echo bn_blocks_source_fragment( 'rooms.html', '', 'room-lightbox' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
add_action( 'wp_footer', 'bn_blocks_render_dialogs', 2 );
