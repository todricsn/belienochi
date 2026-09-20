( function ( wp ) {
	'use strict';

	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;
	const registerBlockType = wp.blocks.registerBlockType;
	const { TextControl, TextareaControl, SelectControl, ToggleControl, Button, Notice } = wp.components;
	const { MediaUpload, MediaUploadCheck, InspectorControls } = wp.blockEditor;
	const { PanelBody } = wp.components;

	function Preview( props ) {
		return el(
			'div',
			{ className: 'bn-block-preview' },
			el( 'span', { className: 'bn-block-preview__eyebrow' }, props.eyebrow || 'Белые Ночи' ),
			el( 'strong', null, props.title ),
			props.text ? el( 'p', null, props.text ) : null
		);
	}

	function isVideoMedia( media ) {
		const type = String( media && media.type || '' ).toLowerCase();
		const mime = String( media && media.mime || '' ).toLowerCase();
		return 'video' === type || type.indexOf( 'video/' ) === 0 || mime.indexOf( 'video/' ) === 0;
	}

	function mediaDimension( media, key ) {
		const direct = Number( media && media[ key ] );
		const details = media && media.media_details ? Number( media.media_details[ key ] ) : 0;
		return direct > 0 ? direct : details > 0 ? details : 0;
	}

	function GalleryControl( { value, onChange, label, allowVideo } ) {
		const canUseVideo = true === allowVideo;
		const images = ( Array.isArray( value ) ? value : [] ).filter( function ( image ) { return canUseVideo || ! isVideoMedia( image ); } );
		const mediaLabel = label || ( canUseVideo ? 'Фотографии и видео' : 'Фотографии' );
		return el(
			'div',
			{ className: 'bn-gallery-control' },
			el( 'p', { className: 'bn-gallery-control__label' }, mediaLabel ),
			images.length
				? el( 'div', { className: 'bn-gallery-control__grid' }, images.map( function ( image, index ) {
				const video = isVideoMedia( image );
				return el( 'div', { className: 'bn-gallery-control__image' + ( video ? ' is-video' : '' ), key: ( image.id || image.url ) + '-' + index },
					video
						? el( 'video', { src: image.url, width: image.width || undefined, height: image.height || undefined, controls: true, muted: true, playsInline: true, preload: 'metadata', 'aria-label': image.alt || 'Предпросмотр видео', style: { display: 'block', width: '100%', height: 'auto', marginBottom: '4px', background: '#111' } } )
						: el( 'img', { src: image.url, alt: image.alt || '' } ),
					el( Button, { isDestructive: true, isSmall: true, onClick: function () { onChange( images.filter( function ( item, itemIndex ) { return itemIndex !== index; } ) ); } }, 'Убрать' )
				);
			} ) )
				: el( Notice, { status: 'info', isDismissible: false }, canUseVideo ? 'Фотографии и видео пока не выбраны.' : 'Фотографии пока не выбраны.' ),
			el( MediaUploadCheck, null,
				el( MediaUpload, {
					onSelect: function ( media ) {
						const selectedMedia = Array.isArray( media ) ? media : [ media ];
						const selected = selectedMedia.map( function ( item ) {
							const existing = images.find( function ( image ) {
								return item.id && image.id && String( item.id ) === String( image.id );
							} ) || {};
							const video = canUseVideo && ( isVideoMedia( item ) || isVideoMedia( existing ) );
							return {
								id: item.id || 0,
								url: item.url,
								alt: item.alt || item.caption || existing.alt || '',
								type: video ? 'video' : 'image',
								mime: video ? ( item.mime || existing.mime || '' ) : '',
								width: Number( existing.width ) > 0 ? Number( existing.width ) : mediaDimension( item, 'width' ),
								height: Number( existing.height ) > 0 ? Number( existing.height ) : mediaDimension( item, 'height' )
							};
						} );
						const selectedUrls = selectedMedia.map( function ( item ) { return item.url; } ).filter( Boolean );
						const legacy = canUseVideo
							? images.filter( function ( image ) {
								return ! image.id && ! isVideoMedia( image ) && selectedUrls.indexOf( image.url ) === -1;
							} )
							: [];
						onChange( legacy.concat( selected ) );
					},
					allowedTypes: canUseVideo ? [ 'image', 'video' ] : [ 'image' ],
					multiple: true,
					gallery: ! canUseVideo,
					value: images.map( function ( image ) { return image.id; } ).filter( Boolean ),
					render: function ( { open } ) { return el( Button, { variant: 'secondary', onClick: open }, images.length ? 'Изменить галерею' : ( canUseVideo ? 'Выбрать медиа' : 'Выбрать фотографии' ) ); }
				} )
			)
		);
	}

	function registerDynamicBlock( name, settings ) {
		registerBlockType( name, Object.assign( {
			category: 'belye-nochi',
			icon: 'building',
			supports: { html: false },
			save: function () { return null; }
		}, settings ) );
	}

	registerDynamicBlock( 'belye-nochi/hero', {
		title: 'Белые Ночи: главный экран',
		attributes: { eyebrow: { type: 'string', default: 'Гостиница · хостел · Красноярск' }, copy: { type: 'string', default: 'Круглосуточные отдельные номера\nи места в хостеле — на въезде в Красноярск.' }, mapUrl: { type: 'string', default: 'https://yandex.ru/maps/-/CTwFQZ9y' }, phone: { type: 'string', default: '+79293386160' } },
		edit: function ( { attributes, setAttributes } ) {
			return el( Fragment, null,
				el( InspectorControls, null, el( PanelBody, { title: 'Содержание', initialOpen: true },
					el( TextControl, { label: 'Надзаголовок', value: attributes.eyebrow || '', onChange: function ( value ) { setAttributes( { eyebrow: value } ); } } ),
					el( TextareaControl, { label: 'Подпись', value: attributes.copy || '', onChange: function ( value ) { setAttributes( { copy: value } ); } } ),
					el( TextControl, { label: 'Ссылка на карту', value: attributes.mapUrl || '', onChange: function ( value ) { setAttributes( { mapUrl: value } ); } } ),
					el( TextControl, { label: 'Телефон', value: attributes.phone || '', onChange: function ( value ) { setAttributes( { phone: value } ); } } )
				) ),
				el( Preview, { eyebrow: attributes.eyebrow, title: 'Главный экран', text: attributes.copy } )
			);
		}
	} );

	registerDynamicBlock( 'belye-nochi/photo-gallery', {
		title: 'Белые Ночи: фотогалерея',
		icon: 'format-gallery',
		attributes: { variant: { type: 'string', default: 'hotel' }, ariaLabel: { type: 'string', default: 'Фотографии Белых Ночей' }, images: { type: 'array', default: [] } },
		edit: function ( { attributes, setAttributes } ) {
			return el( Fragment, null,
				el( InspectorControls, null, el( PanelBody, { title: 'Настройки галереи', initialOpen: true },
					el( SelectControl, { label: 'Вид галереи', value: attributes.variant || 'hotel', options: [ { label: 'Гостиница', value: 'hotel' }, { label: 'Ресторан', value: 'restaurant' } ], onChange: function ( value ) { setAttributes( { variant: value } ); } } ),
					el( TextControl, { label: 'Описание для доступности', value: attributes.ariaLabel || '', onChange: function ( value ) { setAttributes( { ariaLabel: value } ); } } )
				) ),
				el( GalleryControl, { label: ( attributes.variant || 'hotel' ) === 'restaurant' ? 'Свои фотографии и видео (если не выбраны, используются утверждённые)' : 'Свои фотографии (если не выбраны, используются утверждённые)', allowVideo: ( attributes.variant || 'hotel' ) === 'restaurant', value: attributes.images || [], onChange: function ( value ) { setAttributes( { images: value } ); } } )
			);
		}
	} );

	registerDynamicBlock( 'belye-nochi/stays', {
		title: 'Белые Ночи: варианты проживания',
		attributes: { hotelTitle: { type: 'string', default: 'Гостиница' }, hostelTitle: { type: 'string', default: 'Хостел' } },
		edit: function ( { attributes, setAttributes } ) {
			return el( Fragment, null,
				el( InspectorControls, null, el( PanelBody, { title: 'Заголовки', initialOpen: true },
					el( TextControl, { label: 'Гостиница', value: attributes.hotelTitle || '', onChange: function ( value ) { setAttributes( { hotelTitle: value } ); } } ),
					el( TextControl, { label: 'Хостел', value: attributes.hostelTitle || '', onChange: function ( value ) { setAttributes( { hostelTitle: value } ); } } )
				) ),
				el( Preview, { title: ( attributes.hotelTitle || 'Гостиница' ) + ' / ' + ( attributes.hostelTitle || 'Хостел' ), text: 'Варианты проживания, цены и кнопки бронирования' } )
			);
		}
	} );

	registerDynamicBlock( 'belye-nochi/action-link', {
		title: 'Белые Ночи: кнопка-ссылка',
		icon: 'button',
		attributes: { label: { type: 'string', default: 'Забронировать' }, url: { type: 'string', default: 'tel:+79293386160' }, ariaLabel: { type: 'string', default: '' }, className: { type: 'string', default: '' }, showArrow: { type: 'boolean', default: true } },
		edit: function ( { attributes, setAttributes } ) {
			return el( Fragment, null,
				el( InspectorControls, null, el( PanelBody, { title: 'Ссылка', initialOpen: true },
					el( TextControl, { label: 'Текст', value: attributes.label || '', onChange: function ( value ) { setAttributes( { label: value } ); } } ),
					el( TextControl, { label: 'Адрес', value: attributes.url || '', onChange: function ( value ) { setAttributes( { url: value } ); } } ),
					el( TextControl, { label: 'Описание ссылки', value: attributes.ariaLabel || '', onChange: function ( value ) { setAttributes( { ariaLabel: value } ); } } ),
					el( ToggleControl, { label: 'Показывать стрелку', checked: attributes.showArrow !== false, onChange: function ( value ) { setAttributes( { showArrow: value } ); } } )
				) ),
				el( Preview, { title: attributes.label || 'Ссылка', text: attributes.url } )
			);
		}
	} );

	registerDynamicBlock( 'belye-nochi/feature-list', {
		title: 'Белые Ночи: список удобств',
		icon: 'list-view',
		attributes: { items: { type: 'array', default: [] } },
		edit: function () { return el( Preview, { title: 'Удобства и сервис', text: 'Утверждённый список из 10 услуг с пиктограммами' } ); }
	} );

	registerDynamicBlock( 'belye-nochi/contact-map', {
		title: 'Белые Ночи: карта',
		icon: 'location-alt',
		attributes: { title: { type: 'string', default: 'Гостиница Белые Ночи на Яндекс Картах' }, url: { type: 'string', default: 'https://yandex.ru/map-widget/v1/?ll=92.767812%2C56.048454&mode=search&oid=202440014712&ol=biz&z=17' } },
		edit: function ( { attributes, setAttributes } ) {
			return el( Fragment, null,
				el( InspectorControls, null, el( PanelBody, { title: 'Карта', initialOpen: true },
					el( TextControl, { label: 'Заголовок', value: attributes.title || '', onChange: function ( value ) { setAttributes( { title: value } ); } } ),
					el( TextControl, { label: 'Адрес виджета Яндекс Карт', value: attributes.url || '', onChange: function ( value ) { setAttributes( { url: value } ); } } )
				) ),
				el( Preview, { title: 'Карта', text: attributes.title } )
			);
		}
	} );

	registerDynamicBlock( 'belye-nochi/room-filter', {
		title: 'Белые Ночи: фильтр номеров',
		icon: 'filter',
		attributes: { title: { type: 'string', default: 'Выберите тип размещения' }, hotelLabel: { type: 'string', default: 'Гостиница' }, hostelLabel: { type: 'string', default: 'Хостел' } },
		edit: function ( { attributes, setAttributes } ) {
			return el( Fragment, null,
				el( InspectorControls, null, el( PanelBody, { title: 'Фильтр', initialOpen: true },
					el( TextControl, { label: 'Заголовок', value: attributes.title || '', onChange: function ( value ) { setAttributes( { title: value } ); } } ),
					el( TextControl, { label: 'Первая вкладка', value: attributes.hotelLabel || '', onChange: function ( value ) { setAttributes( { hotelLabel: value } ); } } ),
					el( TextControl, { label: 'Вторая вкладка', value: attributes.hostelLabel || '', onChange: function ( value ) { setAttributes( { hostelLabel: value } ); } } )
				) ),
				el( Preview, { title: attributes.title || 'Выберите тип размещения', text: ( attributes.hotelLabel || 'Гостиница' ) + ' / ' + ( attributes.hostelLabel || 'Хостел' ) } )
			);
		}
	} );

	registerDynamicBlock( 'belye-nochi/room-catalog', {
		title: 'Белые Ночи: карточки номеров',
		icon: 'grid-view',
		edit: function () { return el( Preview, { title: 'Карточки номеров', text: 'Автоматически выводит все опубликованные записи из раздела «Номера».' } ); }
	} );

	function RoomSettingsPanel() {
		const postType = wp.data.useSelect( function ( select ) { return select( 'core/editor' ).getCurrentPostType(); }, [] );
		const meta = wp.data.useSelect( function ( select ) { return select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {}; }, [] );
		const editPost = wp.data.useDispatch( 'core/editor' ).editPost;
		if ( postType !== 'bn_room' ) { return null; }
		const update = function ( key, value ) { editPost( { meta: Object.assign( {}, meta, { [ key ]: value } ) } ); };
		let gallery = [];
		try { gallery = JSON.parse( meta._bn_room_gallery || '[]' ); } catch ( error ) { gallery = []; }
		const Panel = ( wp.editor && wp.editor.PluginDocumentSettingPanel ) || ( wp.editPost && wp.editPost.PluginDocumentSettingPanel );
		if ( ! Panel ) { return null; }
		return el( Panel, { name: 'bn-room-settings', title: 'Параметры карточки', className: 'bn-room-settings' },
			el( SelectControl, { label: 'Тип размещения', value: meta._bn_room_type || 'hotel', options: [ { label: 'Гостиница', value: 'hotel' }, { label: 'Хостел', value: 'hostel' } ], onChange: function ( value ) { update( '_bn_room_type', value ); } } ),
			el( TextControl, { label: 'Подзаголовок', help: 'Например: «Две отдельные кровати»', value: meta._bn_room_subtitle || '', onChange: function ( value ) { update( '_bn_room_subtitle', value ); } } ),
			el( TextControl, { label: 'Цена', help: 'Например: «2 500 ₽»', value: meta._bn_room_price || '', onChange: function ( value ) { update( '_bn_room_price', value ); } } ),
			el( TextControl, { label: 'Название характеристики', value: meta._bn_room_placement_label || 'Размещение', onChange: function ( value ) { update( '_bn_room_placement_label', value ); } } ),
			el( TextControl, { label: 'Значение характеристики', help: 'Например: «2 кровати»', value: meta._bn_room_placement || '', onChange: function ( value ) { update( '_bn_room_placement', value ); } } ),
			el( TextControl, { label: 'Единица цены', help: 'Например: «за номер» или «за место»', value: meta._bn_room_price_unit || 'за номер', onChange: function ( value ) { update( '_bn_room_price_unit', value ); } } ),
			el( GalleryControl, { value: gallery, allowVideo: false, onChange: function ( value ) { update( '_bn_room_gallery', JSON.stringify( value ) ); } } )
		);
	}

	wp.plugins.registerPlugin( 'belye-nochi-room-settings', { render: RoomSettingsPanel, icon: 'building' } );
} )( window.wp );
