( function () {
	'use strict';

	const cookieName = 'bn_cookie_consent';
	const metrikaId = 112460419;
	const banner = document.querySelector( '[data-cookie-banner]' );
	const settingsButton = document.querySelector( '[data-cookie-settings]' );
	const analyticsCheckbox = document.querySelector( '[data-cookie-analytics]' );
	const acceptButton = document.querySelector( '[data-cookie-accept]' );
	const essentialButton = document.querySelector( '[data-cookie-essential]' );

	if ( ! banner || ! settingsButton || ! analyticsCheckbox || ! acceptButton || ! essentialButton ) {
		return;
	}

	let metrikaStarted = false;

	function startMetrika() {
		if ( metrikaStarted ) {
			return;
		}

		( function ( m, e, t, r, i, k, a ) {
			m[ i ] = m[ i ] || function () { ( m[ i ].a = m[ i ].a || [] ).push( arguments ); };
			m[ i ].l = 1 * new Date();
			for ( let j = 0; j < e.scripts.length; j++ ) {
				if ( e.scripts[ j ].src === r ) {
					return;
				}
			}
			k = e.createElement( t );
			a = e.getElementsByTagName( t )[ 0 ];
			k.async = 1;
			k.src = r;
			k.dataset.bnMetrika = String( metrikaId );
			a.parentNode.insertBefore( k, a );
		} )( window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=' + metrikaId, 'ym' );

		window.ym( metrikaId, 'init', {
			ssr: true,
			webvisor: true,
			clickmap: true,
			ecommerce: 'dataLayer',
			referrer: document.referrer,
			url: location.href,
			accurateTrackBounce: true,
			trackLinks: true,
		} );
		metrikaStarted = true;
	}

	function stopMetrika() {
		if ( metrikaStarted && typeof window.ym === 'function' ) {
			window.ym( metrikaId, 'destruct' );
		}
		metrikaStarted = false;
	}

	function readChoice() {
		const match = document.cookie.match( new RegExp( '(?:^|; )' + cookieName + '=([^;]*)' ) );
		return match ? decodeURIComponent( match[ 1 ] ) : '';
	}

	function writeChoice( value ) {
		const secure = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = cookieName + '=' + encodeURIComponent( value ) + '; Max-Age=31536000; Path=/; SameSite=Lax' + secure;
	}

	function deleteAnalyticsCookies() {
		document.cookie.split( ';' ).forEach( function ( item ) {
			const name = item.split( '=' )[ 0 ].trim();
			if ( name.indexOf( '_ym_' ) === 0 || [ 'yandexuid', 'yuidss', 'ymex' ].indexOf( name ) !== -1 ) {
				document.cookie = name + '=; Max-Age=0; Path=/; SameSite=Lax';
			}
		} );
	}

	function applyChoice( choice ) {
		const allowed = choice === 'analytics';
		if ( allowed ) {
			startMetrika();
		} else {
			stopMetrika();
		}
		document.querySelectorAll( '[data-cookie-src]' ).forEach( function ( frame ) {
			if ( allowed && frame.src !== frame.dataset.cookieSrc ) {
				frame.src = frame.dataset.cookieSrc;
			}
			if ( ! allowed && frame.getAttribute( 'src' ) !== 'about:blank' ) {
				frame.setAttribute( 'src', 'about:blank' );
			}
		} );
		document.querySelectorAll( '[data-map-consent]' ).forEach( function ( item ) { item.hidden = allowed; } );
		window.bnAnalyticsConsent = allowed;
		window.dispatchEvent( new CustomEvent( 'bn:analytics-consent', { detail: { allowed: allowed } } ) );
	}

	function closeBanner( choice ) {
		writeChoice( choice );
		banner.hidden = true;
		settingsButton.hidden = false;
		document.documentElement.classList.remove( 'bn-cookie-open' );
		if ( choice !== 'analytics' ) { deleteAnalyticsCookies(); }
		applyChoice( choice );
	}

	function openBanner() {
		const choice = readChoice();
		analyticsCheckbox.checked = choice === 'analytics';
		acceptButton.disabled = ! analyticsCheckbox.checked;
		banner.hidden = false;
		settingsButton.hidden = true;
		document.documentElement.classList.add( 'bn-cookie-open' );
		analyticsCheckbox.focus();
	}

	analyticsCheckbox.addEventListener( 'change', function () { acceptButton.disabled = ! analyticsCheckbox.checked; } );
	acceptButton.addEventListener( 'click', function () { closeBanner( 'analytics' ); } );
	essentialButton.addEventListener( 'click', function () { closeBanner( 'essential' ); } );
	settingsButton.addEventListener( 'click', openBanner );
	document.querySelectorAll( '[data-cookie-settings-open]' ).forEach( function ( button ) { button.addEventListener( 'click', openBanner ); } );

	document.querySelectorAll( '.footer-bottom' ).forEach( function ( footer ) {
		if ( footer.querySelector( '.bn-footer-policy' ) ) { return; }
		const link = document.createElement( 'a' );
		link.className = 'bn-footer-policy';
		link.href = ( window.bnCookieSettings && window.bnCookieSettings.policyUrl ) || '/privacy-policy/';
		link.textContent = 'Политика конфиденциальности и cookies';
		footer.appendChild( link );
	} );

	const initialChoice = readChoice();
	if ( initialChoice ) {
		settingsButton.hidden = false;
		applyChoice( initialChoice );
	} else {
		applyChoice( 'essential' );
		openBanner();
	}

	window.bnCookieConsent = { get: readChoice, open: openBanner };
} )();
