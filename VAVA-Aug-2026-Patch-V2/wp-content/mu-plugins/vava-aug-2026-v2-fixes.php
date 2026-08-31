<?php
/**
 * Plugin Name: VAVA Living — August 2026 Patch V2
 * Description: Hardening fixes for the August 2026 review. Safe to run beside the V1 MU patch.
 * Version: 2.0.0
 * Author: Tamiyouz
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'VAVA_AUG_2026_V2_VERSION' ) ) {
	define( 'VAVA_AUG_2026_V2_VERSION', '2.0.0' );
}

/** Never cache the WP login page or its anti-bot nonce. */
function vava_aug_v2_is_login_request(): bool {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	return false !== strpos( $uri, 'wp-login.php' ) || ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] );
}
if ( vava_aug_v2_is_login_request() ) {
	foreach ( array( 'DONOTCACHEPAGE', 'DONOTCACHEDB', 'DONOTCACHEOBJECT' ) as $constant ) {
		if ( ! defined( $constant ) ) { define( $constant, true ); }
	}
}
add_action( 'login_init', static function (): void {
	nocache_headers();
	if ( ! headers_sent() ) {
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
		header( 'Pragma: no-cache', true );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
	}
}, -9999 );

/** Keep the requested tagline and retired glyph corrected even when old DB values still render. */
add_action( 'init', static function (): void {
	if ( 'نحو حياة مزدهرة' !== (string) get_option( 'blogdescription' ) ) {
		update_option( 'blogdescription', 'نحو حياة مزدهرة' );
	}
}, 90 );

function vava_aug_v2_output_filter( string $html ): string {
	return str_replace(
		array( 'حيث تزدهر الحياة', '❧', '&#10087;', '&#x2767;', '&#X2767;' ),
		array( 'نحو حياة مزدهرة', '✦', '✦', '✦', '✦' ),
		$html
	);
}
add_action( 'template_redirect', static function (): void {
	if ( is_admin() || wp_doing_ajax() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) { return; }
	ob_start( 'vava_aug_v2_output_filter' );
}, -9999 );

/** Append an attachment revision on the homepage/Paths page to defeat browser/CDN stale-image URLs. */
function vava_aug_v2_revisioned_attachment_url( string $url, int $attachment_id ): string {
	if ( is_admin() || ! wp_attachment_is_image( $attachment_id ) ) { return $url; }
	if ( ! ( is_front_page() || is_page_template( 'page-templates/paths-vava.php' ) ) ) { return $url; }
	$file = get_attached_file( $attachment_id );
	if ( ! $file || ! is_file( $file ) ) { return $url; }
	$stamp = max( (int) @filemtime( $file ), (int) get_post_modified_time( 'U', true, $attachment_id ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	return $stamp ? (string) add_query_arg( 'vava_rev', $stamp, $url ) : $url;
}
add_filter( 'wp_get_attachment_url', 'vava_aug_v2_revisioned_attachment_url', 80, 2 );

/** Purge common page/object caches after saving homepage or Paths settings. */
function vava_aug_v2_purge_page_cache( int $post_id, WP_Post $post ): void {
	if ( 'page' !== $post->post_type ) { return; }
	$is_home  = (int) get_option( 'page_on_front' ) === $post_id || 'page-templates/homepage.php' === get_page_template_slug( $post_id );
	$is_paths = 'page-templates/paths-vava.php' === get_page_template_slug( $post_id );
	if ( ! $is_home && ! $is_paths ) { return; }
	clean_post_cache( $post_id );
	wp_cache_delete( $post_id, 'post_meta' );
	if ( function_exists( 'wp_cache_flush_group' ) ) { wp_cache_flush_group( 'post_meta' ); }
	if ( function_exists( 'rocket_clean_post' ) ) { rocket_clean_post( $post_id ); }
	if ( function_exists( 'w3tc_flush_post' ) ) { w3tc_flush_post( $post_id ); }
	do_action( 'litespeed_purge_post', $post_id );
	do_action( 'litespeed_purge_url', get_permalink( $post_id ) );
	if ( $is_paths ) { do_action( 'litespeed_purge_url', home_url( '/' ) ); }
}
add_action( 'save_post_page', 'vava_aug_v2_purge_page_cache', 2000, 2 );

/** Equal-width session summary cards; deleted/empty blocks never leave a hole. */
add_action( 'wp_enqueue_scripts', static function (): void {
	wp_register_style( 'vava-aug-v2-inline', false, array(), VAVA_AUG_2026_V2_VERSION );
	wp_enqueue_style( 'vava-aug-v2-inline' );
	wp_add_inline_style( 'vava-aug-v2-inline',
		'.vava-session-summary-grid{display:grid!important;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr))!important;align-items:stretch!important}.vava-session-summary-grid>article{width:100%!important;min-width:0!important}.vava-session-summary-grid>article:empty{display:none!important}html,body{max-width:100%;overflow-x:clip}@supports not (overflow:clip){html,body{overflow-x:hidden}}'
	);
}, 2000 );

add_action( 'wp_footer', static function (): void {
	if ( is_admin() ) { return; }
	?>
	<script id="vava-aug-v2-session-layout">
	(function(){function tidy(){document.querySelectorAll('.vava-session-summary-grid>article').forEach(function(a){var t=(a.textContent||'').replace(/\s+/g,'');var meaningful=a.querySelector('img,picture,video,iframe,a[href],button,input,textarea,select,ul>li,p');if(!t&&!meaningful)a.hidden=true;});}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',tidy);else tidy();}());
	</script>
	<?php
}, 999 );

/**
 * Backward-compatible full-description saving for servers that still run the older
 * Selections editor. V2 admin JS injects the field when the theme itself does not.
 */
function vava_aug_v2_products_meta_key( string $lang ): string {
	return '_vava_selections_products_' . ( 'en' === $lang ? 'en' : 'ar' );
}
function vava_aug_v2_save_full_product_descriptions( int $post_id, WP_Post $post ): void {
	if ( 'page' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) { return; }
	foreach ( array( 'ar', 'en' ) as $lang ) {
		$post_key = '_vava_selections_products_' . $lang;
		if ( ! isset( $_POST[ $post_key ] ) || ! is_array( $_POST[ $post_key ] ) ) { continue; } // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted = wp_unslash( $_POST[ $post_key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$saved  = get_post_meta( $post_id, vava_aug_v2_products_meta_key( $lang ), true );
		$saved  = is_array( $saved ) ? $saved : array();
		$changed = false;
		foreach ( $posted as $group => $rows ) {
			if ( ! is_array( $rows ) ) { continue; }
			foreach ( array_values( $rows ) as $index => $row ) {
				if ( ! is_array( $row ) || ! array_key_exists( 'full_description', $row ) ) { continue; }
				if ( ! isset( $saved[ $group ][ $index ] ) || ! is_array( $saved[ $group ][ $index ] ) ) { continue; }
				$saved[ $group ][ $index ]['full_description'] = sanitize_textarea_field( (string) $row['full_description'] );
				$changed = true;
			}
		}
		if ( $changed ) { update_post_meta( $post_id, vava_aug_v2_products_meta_key( $lang ), $saved ); }
	}
}
add_action( 'save_post_page', 'vava_aug_v2_save_full_product_descriptions', 3000, 2 );

/** Load V2 admin compatibility JS on the Selections editor. */
add_action( 'admin_enqueue_scripts', static function ( string $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) { return; }
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $post_id ) { return; }
	$template = get_page_template_slug( $post_id );
	if ( false === strpos( (string) $template, 'selections' ) && ! metadata_exists( 'post', $post_id, '_vava_selections_shared' ) ) { return; }
	wp_enqueue_script( 'vava-aug-v2-admin', plugins_url( 'vava-aug-2026-v2-admin.js', __FILE__ ), array(), VAVA_AUG_2026_V2_VERSION, true );
}, 9999 );

/** Chunked protected-PDF upload endpoint. It prevents one long 50MB request from timing out. */
function vava_aug_v2_pdf_chunk_upload(): void {
	$post_id = absint( $_POST['post_id'] ?? 0 );
	$uid     = sanitize_key( (string) ( $_POST['uid'] ?? '' ) );
	$nonce   = (string) ( $_POST['nonce'] ?? '' );
	if ( ! $post_id || ! $uid || ! current_user_can( 'edit_post', $post_id ) || ! wp_verify_nonce( $nonce, 'vava_digital_product_admin_' . $post_id ) ) {
		wp_send_json_error( array( 'message' => 'انتهت صلاحية الجلسة. حدّث الصفحة وحاول مرة أخرى.' ), 403 );
	}
	if ( ! function_exists( 'vava_digital_products_private_root' ) || ! function_exists( 'vava_digital_products_file_map' ) || ! function_exists( 'vava_digital_products_schedule_processing' ) ) {
		wp_send_json_error( array( 'message' => 'مكوّن المنتجات الرقمية غير متاح على هذه النسخة.' ), 500 );
	}
	$index = absint( $_POST['chunk_index'] ?? 0 );
	$total = max( 1, absint( $_POST['total_chunks'] ?? 1 ) );
	$upload_id = sanitize_key( (string) ( $_POST['upload_id'] ?? '' ) );
	$name = sanitize_file_name( (string) ( $_POST['file_name'] ?? 'product.pdf' ) );
	$size = absint( $_POST['file_size'] ?? 0 );
	if ( ! $upload_id || $index >= $total || $size <= 0 || $size > 50 * MB_IN_BYTES || ! preg_match( '/\.pdf$/i', $name ) ) {
		wp_send_json_error( array( 'message' => 'بيانات ملف PDF غير صالحة أو يتجاوز 50 ميجابايت.' ), 422 );
	}
	$file = isset( $_FILES['chunk'] ) && is_array( $_FILES['chunk'] ) ? $_FILES['chunk'] : array();
	if ( UPLOAD_ERR_OK !== absint( $file['error'] ?? UPLOAD_ERR_NO_FILE ) || ! is_uploaded_file( (string) ( $file['tmp_name'] ?? '' ) ) ) {
		wp_send_json_error( array( 'message' => 'تعذر رفع جزء من الملف. حاول مرة أخرى.' ), 422 );
	}
	$uploads = wp_upload_dir();
	$chunk_dir = trailingslashit( $uploads['basedir'] ) . 'vava-private-products/.chunks/' . get_current_user_id() . '/' . $upload_id;
	if ( ! is_dir( $chunk_dir ) && ! wp_mkdir_p( $chunk_dir ) ) { wp_send_json_error( array( 'message' => 'تعذر إنشاء مساحة الرفع المؤقتة.' ), 500 ); }
	$part = trailingslashit( $chunk_dir ) . sprintf( '%05d.part', $index );
	if ( ! @move_uploaded_file( (string) $file['tmp_name'], $part ) ) { wp_send_json_error( array( 'message' => 'تعذر حفظ جزء من الملف.' ), 500 ); } // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( $index + 1 < $total ) { wp_send_json_success( array( 'complete' => false, 'received' => $index + 1, 'total' => $total ) ); }

	$assembled = trailingslashit( $chunk_dir ) . 'assembled.pdf';
	$out = @fopen( $assembled, 'wb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	if ( ! $out ) { wp_send_json_error( array( 'message' => 'تعذر تجميع ملف PDF.' ), 500 ); }
	for ( $i = 0; $i < $total; $i++ ) {
		$source = trailingslashit( $chunk_dir ) . sprintf( '%05d.part', $i );
		$in = @fopen( $source, 'rb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $in ) { fclose( $out ); wp_send_json_error( array( 'message' => 'أحد أجزاء الملف مفقود. أعد الرفع.' ), 409 ); }
		stream_copy_to_stream( $in, $out ); fclose( $in );
	}
	fclose( $out );
	if ( ! is_file( $assembled ) || filesize( $assembled ) !== $size ) { wp_send_json_error( array( 'message' => 'حجم الملف بعد التجميع غير مطابق. أعد الرفع.' ), 409 ); }
	$checked = wp_check_filetype_and_ext( $assembled, $name, array( 'pdf' => 'application/pdf' ) );
	if ( 'pdf' !== strtolower( (string) ( $checked['ext'] ?? '' ) ) ) { wp_send_json_error( array( 'message' => 'يُسمح بملفات PDF فقط.' ), 422 ); }

	$root = vava_digital_products_private_root();
	$fingerprint = wp_generate_password( 24, false, false );
	$filename = $uid . '-' . $fingerprint . '.pdf';
	$target = trailingslashit( $root['path'] ) . $filename;
	if ( ! @rename( $assembled, $target ) ) { wp_send_json_error( array( 'message' => 'تعذر نقل PDF إلى التخزين المحمي.' ), 500 ); } // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	@chmod( $target, 0640 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	$old = function_exists( 'vava_digital_products_file_record' ) ? vava_digital_products_file_record( $uid, $post_id ) : array();
	if ( $old && function_exists( 'vava_digital_products_delete_private_record' ) ) { vava_digital_products_delete_private_record( $old ); }
	$record = array(
		'relative_path' => trailingslashit( $root['relative'] ) . $filename,
		'original_name' => $name,
		'size' => $size,
		'updated_at' => current_time( 'mysql' ),
		'fingerprint' => $fingerprint,
		'processing_status' => 'queued',
		'processing_progress' => 1,
		'processing_message' => 'اكتمل رفع الملف. جاري تجهيز صفحات العرض المحمية.',
		'page_count' => 0,
	);
	$map = vava_digital_products_file_map( $post_id );
	$map[ $uid ] = $record;
	update_post_meta( $post_id, '_vava_digital_product_files', $map );
	vava_digital_products_schedule_processing( $post_id, $uid, $fingerprint );
	foreach ( glob( trailingslashit( $chunk_dir ) . '*.part' ) ?: array() as $piece ) { @unlink( $piece ); } // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	@rmdir( $chunk_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	$payload = function_exists( 'vava_digital_products_admin_record_payload' ) ? vava_digital_products_admin_record_payload( $post_id, $uid, $record ) : $record;
	wp_send_json_success( array( 'complete' => true, 'record' => $payload, 'message' => 'اكتمل رفع PDF وبدأ تجهيز صفحات المشاهدة.' ) );
}
add_action( 'wp_ajax_vava_aug_v2_pdf_chunk_upload', 'vava_aug_v2_pdf_chunk_upload' );
