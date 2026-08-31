<?php
/**
 * Plugin Name: VAVA Living — Phase 01 Login Fix
 * Description: Deterministic removal of the legacy press-and-hold login gate plus login cache protection.
 * Version: 1.0.0
 * Author: Tamiyouz
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'VAVA_PHASE01_LOGIN_VERSION' ) ) {
	define( 'VAVA_PHASE01_LOGIN_VERSION', '1.0.0' );
}

/** Detect the native WordPress login endpoint without affecting normal frontend/admin requests. */
function vava_phase01_is_login_request(): bool {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	return false !== strpos( $request_uri, 'wp-login.php' );
}

if ( vava_phase01_is_login_request() ) {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
	if ( ! defined( 'DONOTCACHEDB' ) ) { define( 'DONOTCACHEDB', true ); }
	if ( ! defined( 'DONOTCACHEOBJECT' ) ) { define( 'DONOTCACHEOBJECT', true ); }
}

/**
 * Remove the legacy VAVA press-and-hold gate after the theme has registered it.
 * Native WordPress username/password authentication remains untouched.
 */
function vava_phase01_remove_legacy_login_gate(): void {
	remove_action( 'login_form', 'vava_admin_brand_login_guard_markup' );
	remove_filter( 'authenticate', 'vava_admin_brand_validate_login_guard', 5 );
}
add_action( 'wp_loaded', 'vava_phase01_remove_legacy_login_gate', PHP_INT_MAX );
add_action( 'login_init', 'vava_phase01_remove_legacy_login_gate', -100000 );

/** Force the login response to be non-cacheable at PHP/application level. */
function vava_phase01_login_no_cache(): void {
	if ( ! vava_phase01_is_login_request() ) { return; }
	nocache_headers();
	if ( headers_sent() ) { return; }
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
	header( 'Pragma: no-cache', true );
	header( 'Expires: 0', true );
	header( 'X-VAVA-Login-Fix: phase-01', true );
}
add_action( 'login_init', 'vava_phase01_login_no_cache', -99999 );

/**
 * Keep the VAVA login stylesheet, but prevent the old JS from loading because
 * that script hides/disables the submit button until the AJAX hold challenge succeeds.
 */
function vava_phase01_disable_legacy_login_script(): void {
	if ( ! vava_phase01_is_login_request() ) { return; }
	wp_dequeue_script( 'vava-login-ui' );
	wp_deregister_script( 'vava-login-ui' );

	wp_register_style( 'vava-phase01-login-inline', false, array(), VAVA_PHASE01_LOGIN_VERSION );
	wp_enqueue_style( 'vava-phase01-login-inline' );
	wp_add_inline_style(
		'vava-phase01-login-inline',
		'.login .vava-login-hold{display:none!important}.login .submit{display:block!important}.login .submit[hidden]{display:block!important}.login .button-primary{pointer-events:auto!important;opacity:1!important}'
	);
}
add_action( 'login_enqueue_scripts', 'vava_phase01_disable_legacy_login_script', PHP_INT_MAX );

/** Defense in depth: strip a legacy login-vava.js tag if another component re-enqueues it. */
function vava_phase01_strip_legacy_login_script_tag( string $tag, string $handle, string $src ): string {
	if ( ! vava_phase01_is_login_request() ) { return $tag; }
	if ( 'vava-login-ui' === $handle || false !== strpos( $src, '/assets/js/login-vava.js' ) ) {
		return '';
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'vava_phase01_strip_legacy_login_script_tag', PHP_INT_MAX, 3 );

/** Remove leftover guard markup in case an older theme registered it again late. */
function vava_phase01_login_footer_cleanup(): void {
	if ( ! vava_phase01_is_login_request() ) { return; }
	?>
	<script id="vava-phase01-login-cleanup">
	(function(){
	  'use strict';
	  var form=document.getElementById('loginform');
	  if(!form)return;
	  form.querySelectorAll('[data-vava-login-hold],[data-vava-login-token]').forEach(function(el){el.remove();});
	  var submitWrap=form.querySelector('.submit');
	  var submit=submitWrap?submitWrap.querySelector('input[type="submit"],button[type="submit"]'):null;
	  if(submitWrap){submitWrap.hidden=false;submitWrap.removeAttribute('hidden');}
	  if(submit){submit.disabled=false;submit.removeAttribute('disabled');submit.classList.remove('is-processing');}
	}());
	</script>
	<?php
}
add_action( 'login_footer', 'vava_phase01_login_footer_cleanup', PHP_INT_MAX );
