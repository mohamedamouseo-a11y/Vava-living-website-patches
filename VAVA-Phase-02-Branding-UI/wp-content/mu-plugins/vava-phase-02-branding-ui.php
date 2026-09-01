<?php
/**
 * Plugin Name: VAVA Living — Phase 02 Branding & UI
 * Description: Phase 02 only: tagline, retired glyph cleanup, logo cache-busting, and mobile horizontal-overflow containment.
 * Version: 2.0.0
 * Author: Tamiyouz
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'VAVA_PHASE02_VERSION' ) ) {
	define( 'VAVA_PHASE02_VERSION', '2.0.0' );
}

/** Keep the approved Arabic tagline in WordPress settings. */
add_action( 'init', static function (): void {
	if ( 'نحو حياة مزدهرة' !== (string) get_option( 'blogdescription' ) ) {
		update_option( 'blogdescription', 'نحو حياة مزدهرة' );
	}
}, 90 );

/**
 * Backstop for legacy content stored in options/post meta or rendered by older templates.
 * Source files should still be updated where the old phrase/glyph is found.
 */
function vava_phase02_filter_frontend_html( string $html ): string {
	return str_replace(
		array( 'حيث تزدهر الحياة', '❧', '&#10087;', '&#x2767;', '&#X2767;' ),
		array( 'نحو حياة مزدهرة', '✦', '✦', '✦', '✦' ),
		$html
	);
}

add_action( 'template_redirect', static function (): void {
	if ( is_admin() || wp_doing_ajax() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'vava_phase02_filter_frontend_html' );
}, -9999 );

/** Cache-bust the shared VAVA logo whenever the image file changes. */
add_filter( 'theme_file_uri', static function ( string $url, string $file ): string {
	$file = ltrim( str_replace( '\\', '/', $file ), '/' );
	if ( 'assets/images/vava-logo.png' !== $file ) {
		return $url;
	}
	$absolute = trailingslashit( get_template_directory() ) . $file;
	$stamp    = is_file( $absolute ) ? (int) filemtime( $absolute ) : 0;
	return $stamp > 0 ? (string) add_query_arg( 'vava_brand', $stamp, $url ) : $url;
}, 100, 2 );

/**
 * Prevent page-level horizontal panning on phones without changing desktop layout.
 * Keep intentionally translated/animated children inside their viewport instead of
 * widening the document itself.
 */
add_action( 'wp_enqueue_scripts', static function (): void {
	wp_register_style( 'vava-phase02-branding-ui', false, array(), VAVA_PHASE02_VERSION );
	wp_enqueue_style( 'vava-phase02-branding-ui' );
	wp_add_inline_style(
		'vava-phase02-branding-ui',
		'@media (max-width:782px){html,body{max-width:100%!important;overflow-x:clip!important}body{width:100%!important}.app,#app{max-width:100vw!important;overflow-x:clip!important}img,video,iframe{max-width:100%;height:auto}}@supports not (overflow:clip){@media (max-width:782px){html,body,.app,#app{overflow-x:hidden!important}}}'
	);
}, 9999 );
