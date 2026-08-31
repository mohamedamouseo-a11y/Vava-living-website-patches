<?php
/**
 * Plugin Name: VAVA Living — August 2026 Requirements Patch
 * Description: Safe runtime fixes for the August 2026 client requirements without modifying the active theme files.
 * Version: 1.0.0
 * Author: Tamiyouz
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'VAVA_AUG_2026_PATCH_VERSION' ) ) {
	define( 'VAVA_AUG_2026_PATCH_VERSION', '1.0.0' );
}

/**
 * Avoid stale cached login pages/nonces. This runs from MU plugins, before the
 * theme login guard is registered.
 */
function vava_aug_2026_is_login_request(): bool {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
	return false !== strpos( $uri, 'wp-login.php' ) || ( isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'] );
}

if ( vava_aug_2026_is_login_request() ) {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
	if ( ! defined( 'DONOTCACHEDB' ) ) { define( 'DONOTCACHEDB', true ); }
	if ( ! defined( 'DONOTCACHEOBJECT' ) ) { define( 'DONOTCACHEOBJECT', true ); }
}

function vava_aug_2026_login_no_cache(): void {
	nocache_headers();
	if ( ! headers_sent() ) {
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
		header( 'Pragma: no-cache', true );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
	}
}
add_action( 'login_init', 'vava_aug_2026_login_no_cache', -999 );

/** Change the public tagline once, while leaving unrelated site settings alone. */
function vava_aug_2026_update_tagline(): void {
	if ( get_option( 'vava_aug_2026_tagline_applied_v1' ) ) { return; }
	update_option( 'blogdescription', 'نحو حياة مزدهرة' );
	update_option( 'vava_aug_2026_tagline_applied_v1', current_time( 'mysql' ), false );
}
add_action( 'init', 'vava_aug_2026_update_tagline', 80 );

/**
 * Final copy guard for legacy theme/meta values that can still render the old
 * tagline or the retired ❧ glyph from database content.
 */
function vava_aug_2026_frontend_buffer( string $html ): string {
	return str_replace(
		array( 'حيث تزدهر الحياة', '❧', '&#10087;', '&#x2767;', '&#X2767;' ),
		array( 'نحو حياة مزدهرة', '✦', '✦', '✦', '✦' ),
		$html
	);
}

function vava_aug_2026_start_frontend_buffer(): void {
	if ( is_admin() || wp_doing_ajax() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) { return; }
	ob_start( 'vava_aug_2026_frontend_buffer' );
}
add_action( 'template_redirect', 'vava_aug_2026_start_frontend_buffer', -999 );

/** Mobile containment and safe cleanup of genuinely empty sections. */
function vava_aug_2026_frontend_assets(): void {
	if ( is_admin() ) { return; }
	wp_register_style( 'vava-aug-2026-fixes', false, array(), VAVA_AUG_2026_PATCH_VERSION );
	wp_enqueue_style( 'vava-aug-2026-fixes' );
	wp_add_inline_style(
		'vava-aug-2026-fixes',
		'html,body{max-width:100%;overflow-x:clip}@supports not (overflow:clip){html,body{overflow-x:hidden}}@media(max-width:782px){body.home #page,body.home .site,body.home main,body.home main>section,body.home main>div{max-width:100%;min-width:0;overflow-x:hidden}body.home img,body.home picture,body.home video,body.home iframe,body.home svg{max-width:100%;height:auto}body.home [class*="container"],body.home [class*="wrapper"]{min-width:0;max-width:100%}}'
	);

}
add_action( 'wp_enqueue_scripts', 'vava_aug_2026_frontend_assets', 1200 );

function vava_aug_2026_frontend_footer_script(): void {
	if ( is_admin() ) { return; }
	?>
	<script id="vava-aug-2026-layout-cleanup">
	(function(){
	  'use strict';
	  function cleanEmptySections(){
	    document.querySelectorAll('main section').forEach(function(section){
	      if (section.closest('[data-booking-root], form, dialog')) return;
	      var text = (section.textContent || '').replace(/\s+/g, '');
	      var meaningful = section.querySelector('img,picture,video,iframe,form,button,a[href],input,textarea,select,canvas');
	      if (!text && !meaningful) section.hidden = true;
	    });
	  }
	  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', cleanEmptySections);
	  else cleanEmptySections();
	}());
	</script>
	<?php
}
add_action( 'wp_footer', 'vava_aug_2026_frontend_footer_script', 999 );

/**
 * Flush Paths page/meta caches after save and add a file revision to attachment
 * URLs while the Paths page/editor is being rendered. This prevents an old
 * image URL from surviving a media replacement through browser/CDN caches.
 */
function vava_aug_2026_is_paths_context(): bool {
	$post_id = get_queried_object_id();
	if ( is_admin() && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$post_id = absint( $_GET['post'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	return $post_id > 0 && 'page-templates/paths-vava.php' === get_page_template_slug( $post_id );
}

function vava_aug_2026_paths_attachment_revision( string $url, int $post_id ): string {
	if ( ! vava_aug_2026_is_paths_context() || ! wp_attachment_is_image( $post_id ) ) { return $url; }
	$file = get_attached_file( $post_id );
	if ( ! $file || ! is_file( $file ) ) { return $url; }
	return (string) add_query_arg( 'vava_rev', (string) filemtime( $file ), $url );
}
add_filter( 'wp_get_attachment_url', 'vava_aug_2026_paths_attachment_revision', 30, 2 );

function vava_aug_2026_paths_flush_after_save( int $post_id, WP_Post $post ): void {
	if ( 'page' !== $post->post_type || 'page-templates/paths-vava.php' !== get_page_template_slug( $post_id ) ) { return; }
	clean_post_cache( $post_id );
	wp_cache_delete( $post_id, 'post_meta' );
	if ( function_exists( 'wp_cache_flush_group' ) ) {
		wp_cache_flush_group( 'post_meta' );
	}
}
add_action( 'save_post_page', 'vava_aug_2026_paths_flush_after_save', 999, 2 );

/** Remove the retired midpoint question if it exists in saved overrides. */
function vava_aug_2026_normalize_question_label( string $label ): string {
	$label = preg_replace( '/[؟?!،,.؛:ـ\-–—\s]+/u', '', $label );
	return is_string( $label ) ? $label : '';
}

function vava_aug_2026_remove_midpoint_daily_activity(): void {
	if ( get_option( 'vava_aug_2026_midpoint_cleanup_v1' ) ) { return; }
	if ( ! function_exists( 'vava_booking_page_id' ) ) { return; }
	$page_id = absint( vava_booking_page_id() );
	if ( ! $page_id ) { return; }
	$settings = get_post_meta( $page_id, '_vava_booking_questionnaires', true );
	$changed  = false;
	$target   = vava_aug_2026_normalize_question_label( 'كيف كان نشاطك اليومي' );

	if ( is_array( $settings ) && ! empty( $settings['midpoint']['groups'] ) && is_array( $settings['midpoint']['groups'] ) ) {
		foreach ( $settings['midpoint']['groups'] as &$group ) {
			if ( empty( $group['fields'] ) || ! is_array( $group['fields'] ) ) { continue; }
			$before = count( $group['fields'] );
			$group['fields'] = array_values( array_filter( $group['fields'], static function ( $field ) use ( $target ) {
				if ( ! is_array( $field ) ) { return true; }
				$ar = (string) ( $field['label']['ar'] ?? '' );
				return vava_aug_2026_normalize_question_label( $ar ) !== $target;
			} ) );
			if ( count( $group['fields'] ) !== $before ) { $changed = true; }
		}
		unset( $group );
	}

	if ( $changed ) { update_post_meta( $page_id, '_vava_booking_questionnaires', $settings ); }
	update_option( 'vava_aug_2026_midpoint_cleanup_v1', $changed ? 'removed' : 'not-present', false );
}
add_action( 'admin_init', 'vava_aug_2026_remove_midpoint_daily_activity', 120 );

/** Install the supplied Arabic booking/payment/cancellation policy verbatim. */
function vava_aug_2026_booking_policy_html(): string {
	return '<h2>🔹 سياسة الحجز</h2><ol>'
		. '<li>يتم تأكيد الحجز بعد إتمام الدفع الكامل للجلسة أو الباقة.</li>'
		. '<li>يتم تحديد الموعد حسب المتاح وبالاتفاق أو عبر نظام الحجز.</li>'
		. '<li>يُرجى الالتزام بالموعد المحدد لضمان جودة التجربة والاستفادة الكاملة من الجلسة.</li>'
		. '<li>في حال عدم الحضور دون إشعار مسبق، تُعتبر الجلسة منفذة بالكامل.</li>'
		. '</ol>'
		. '<h2>💳 سياسة الدفع والإسترجاع</h2><ol>'
		. '<li>يتم الدفع مقدمًا لتأكيد الحجز و جميع الأسعار بالريال السعودي.</li>'
		. '<li>في حال شراء باقة، يجب الالتزام باستخدام مزاياها خلال المدة المحددة (إن وجدت).</li>'
		. '<li>أي رسوم تحويل أو عمولات دفع (إن وجدت) يتحملها العميل.</li>'
		. '<li>يحق لمقدم الخدمة تعديل الأسعار أو العروض في أي وقت، دون التأثير على العملاء الحاليين.</li>'
		. '<li>يحق للعميل طلب استرجاع كامل المبلغ فقط في حال تم الإلغاء قبل بدء أي جلسة وبمدة لا تقل عن 24 ساعة من الموعد الأول.</li>'
		. '<li>في حال بدء الخدمة الإستشارية، لا يكون المبلغ قابلًا للاسترجاع.</li>'
		. '<li>في حال شراء باقة وتم استخدامها جزئيًا ويرغب العميل بفسخ الخدمة، يتم احتساب الجلسات المنفذة فقط بسعر الجلسة المفردة، ويُعاد المتبقي من المبلغ إن وجد بشرط أن يكون ذلك قبل بدء الخدمة المقدمة بمدة لاتقل عن 24 ساعة</li>'
		. '<li>لا تشمل الاسترجاعات حالات الإلغاء المتأخر أو عدم الحضور.</li>'
		. '<li>يتم الاسترجاع فقط في الحالات الاستثنائية مثل عدم القدرة على تقديم الخدمة من قبل مقدم الخدمة أو حدوث خطأ تقني في الدفع.</li>'
		. '</ol>'
		. '<h2>🔁 سياسة الإلغاء والتأجيل</h2><ol>'
		. '<li>يمكن إلغاء أو تأجيل الجلسة قبل 24 ساعة على الأقل من موعدها.</li>'
		. '<li>في حال الإلغاء قبل أقل من 24 ساعة، تُحتسب الجلسة كاملة.</li>'
		. '<li>في حال عدم الحضور دون إشعار مسبق، تُعتبر الجلسة مستخدمة بالكامل.</li>'
		. '<li>في حال تأخر العميل أكثر من 10 دقائق، تُعتبر الجلسة مستخدمة بالكامل.</li>'
		. '<li>في حال تأخر مقدم الخدمة أكثر من 10 دقائق، يتم تعويض الجلسة أو إعادة جدولتها بالكامل.</li>'
		. '<li>يمكن إعادة جدولة الجلسة حسب التوفر وباتفاق مسبق.</li>'
		. '</ol>'
		. '<h2>✨ ملاحظة مهمة</h2><p><strong>حجزك للجلسة يعني موافقتك على جميع السياسات أعلاه.</strong></p>';
}

function vava_aug_2026_apply_booking_policy(): void {
	if ( get_option( 'vava_aug_2026_booking_policy_v1' ) ) { return; }
	if ( ! function_exists( 'vava_legal_page_type' ) ) { return; }
	$page_ids = get_posts( array(
		'post_type' => 'page', 'post_status' => array( 'publish', 'draft', 'private' ),
		'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true,
	) );
	$updated = 0;
	foreach ( $page_ids as $page_id ) {
		$page_id = absint( $page_id );
		if ( 'booking' !== vava_legal_page_type( $page_id ) ) { continue; }
		$key  = function_exists( 'vava_legal_text_meta_key' ) ? vava_legal_text_meta_key( 'ar' ) : '_vava_legal_text_ar';
		$data = get_post_meta( $page_id, $key, true );
		$data = is_array( $data ) ? $data : array();
		$data['eyebrow']       = '🌿 سياسات Vava Living';
		$data['title']         = 'سياسة الحجز والدفع والاسترجاع والإلغاء';
		$data['intro']         = 'تنظم هذه السياسات الحجز والدفع والاسترجاع والإلغاء والتأجيل بما يضمن وضوح مسؤوليات العميل ومقدم الخدمة.';
		$data['updated_label'] = 'آخر تحديث';
		$data['updated_value'] = 'أغسطس 2026';
		$data['content']       = vava_aug_2026_booking_policy_html();
		update_post_meta( $page_id, $key, $data );
		clean_post_cache( $page_id );
		$updated++;
	}
	if ( $updated ) { update_option( 'vava_aug_2026_booking_policy_v1', $updated, false ); }
}
add_action( 'admin_init', 'vava_aug_2026_apply_booking_policy', 121 );

/** Excel-compatible CSV export for all native booking questionnaire answers. */
function vava_aug_2026_booking_capability(): string {
	return function_exists( 'vava_booking_admin_capability' ) ? vava_booking_admin_capability() : 'manage_options';
}

function vava_aug_2026_questionnaire_export_menu(): void {
	add_submenu_page(
		'edit.php?post_type=vava_booking',
		'تصدير الاستبيانات',
		'تصدير الاستبيانات',
		vava_aug_2026_booking_capability(),
		'vava-export-questionnaires',
		'vava_aug_2026_questionnaire_export_screen'
	);
}
add_action( 'admin_menu', 'vava_aug_2026_questionnaire_export_menu', 130 );

function vava_aug_2026_questionnaire_export_screen(): void {
	if ( ! current_user_can( vava_aug_2026_booking_capability() ) ) { wp_die( esc_html__( 'You are not allowed to access this page.' ) ); }
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=vava_aug_export_questionnaires_csv' ), 'vava_aug_export_questionnaires' );
	?>
	<div class="wrap" dir="rtl"><h1>تصدير استبيانات VAVA</h1><p>يتم تصدير إجابات استبيان بداية الرحلة، منتصف الرحلة، وأثر الرحلة بصيغة CSV متوافقة مع Excel.</p><p><a class="button button-primary" href="<?php echo esc_url( $url ); ?>">تنزيل ملف CSV</a></p></div>
	<?php
}

function vava_aug_2026_csv_value( $value ): string {
	if ( is_array( $value ) ) { return implode( ' | ', array_map( 'strval', $value ) ); }
	return is_scalar( $value ) ? (string) $value : '';
}

function vava_aug_2026_export_questionnaires_csv(): void {
	if ( ! current_user_can( vava_aug_2026_booking_capability() ) ) { wp_die( 'Forbidden', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'vava_aug_export_questionnaires' );
	$ids = get_posts( array(
		'post_type' => 'vava_booking', 'post_status' => 'any', 'posts_per_page' => -1,
		'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true,
	) );
	while ( ob_get_level() ) { ob_end_clean(); }
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="vava-questionnaires-' . wp_date( 'Y-m-d' ) . '.csv"' );
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	$out = fopen( 'php://output', 'w' );
	if ( false === $out ) { exit; }
	fwrite( $out, "\xEF\xBB\xBF" );
	fputcsv( $out, array( 'booking_id', 'questionnaire_type', 'completed_at', 'customer_name', 'customer_email', 'service', 'date', 'time', 'question_id', 'question', 'answer' ) );
	foreach ( $ids as $booking_id ) {
		$booking_id = absint( $booking_id );
		$customer   = (array) get_post_meta( $booking_id, '_vava_booking_customer', true );
		$datasets   = array(
			get_post_meta( $booking_id, '_vava_booking_questionnaire', true ),
			get_post_meta( $booking_id, '_vava_booking_impact_questionnaire', true ),
		);
		foreach ( $datasets as $data ) {
			if ( ! is_array( $data ) || empty( $data['answers'] ) || ! is_array( $data['answers'] ) ) { continue; }
			$snapshot = isset( $data['snapshot'] ) && is_array( $data['snapshot'] ) ? $data['snapshot'] : array();
			$map = function_exists( 'vava_booking_questionnaire_field_map' ) ? vava_booking_questionnaire_field_map( $snapshot ) : array();
			foreach ( $data['answers'] as $question_id => $answer ) {
				$field = isset( $map[ $question_id ] ) && is_array( $map[ $question_id ] ) ? $map[ $question_id ] : array();
				$label = (string) ( $field['label']['ar'] ?? $question_id );
				if ( $field && function_exists( 'vava_booking_questionnaire_option_label' ) ) {
					$answer = vava_booking_questionnaire_option_label( $field, $answer, 'ar' );
				}
				fputcsv( $out, array(
					$booking_id,
					(string) ( $data['type'] ?? '' ),
					(string) ( $data['completed_at'] ?? '' ),
					(string) ( $customer['name'] ?? '' ),
					(string) ( $customer['email'] ?? '' ),
					(string) get_post_meta( $booking_id, '_vava_booking_service_title', true ),
					(string) get_post_meta( $booking_id, '_vava_booking_date', true ),
					(string) get_post_meta( $booking_id, '_vava_booking_time', true ),
					(string) $question_id,
					$label,
					vava_aug_2026_csv_value( $answer ),
				) );
			}
		}
	}
	fclose( $out );
	exit;
}
add_action( 'admin_post_vava_aug_export_questionnaires_csv', 'vava_aug_2026_export_questionnaires_csv' );

/** Daily booking capacity: 1x90, 2x30, 2x20, 1x10-15; max 205 minutes/day. */
function vava_aug_2026_service_duration( array $service, array $shared = array() ): int {
	if ( function_exists( 'vava_booking_effective_duration' ) ) {
		return max( 1, absint( vava_booking_effective_duration( $service, $shared ) ) );
	}
	return max( 1, absint( $service['duration'] ?? $service['minutes'] ?? 0 ) );
}

function vava_aug_2026_service_bucket( array $service, array $shared = array() ): array {
	$duration = vava_aug_2026_service_duration( $service, $shared );
	$text = strtolower( wp_strip_all_tags( implode( ' ', array_filter( array(
		(string) ( $service['uid'] ?? '' ), (string) ( $service['title'] ?? '' ),
		(string) ( $service['category'] ?? '' ), (string) ( $service['session_type'] ?? '' ),
	) ) ) ) );
	if ( $duration >= 75 || preg_match( '/شامل|comprehensive/u', $text ) ) { return array( 'key' => 'comprehensive', 'limit' => 1, 'duration' => $duration ); }
	if ( ( $duration >= 25 && $duration <= 40 ) || preg_match( '/متابعة|follow.?up/u', $text ) ) { return array( 'key' => 'followup', 'limit' => 2, 'duration' => $duration ); }
	if ( ( $duration >= 16 && $duration <= 24 ) || preg_match( '/استفسار|inquir|question/u', $text ) ) { return array( 'key' => 'inquiry', 'limit' => 2, 'duration' => $duration ); }
	return array( 'key' => 'exploratory', 'limit' => 1, 'duration' => $duration );
}

function vava_aug_2026_active_bookings_for_date( string $date ): array {
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { return array(); }
	$ids = get_posts( array(
		'post_type' => 'vava_booking', 'post_status' => 'any', 'posts_per_page' => -1,
		'fields' => 'ids', 'no_found_rows' => true,
		'meta_query' => array( array( 'key' => '_vava_booking_date', 'value' => $date ) ),
	) );
	$blocked_statuses = array( 'cancelled', 'canceled', 'rejected', 'failed', 'refunded', 'expired', 'deleted' );
	return array_values( array_filter( array_map( 'absint', $ids ), static function ( $booking_id ) use ( $blocked_statuses ) {
		$order_type = sanitize_key( (string) get_post_meta( $booking_id, '_vava_booking_order_type', true ) );
		if ( in_array( $order_type, array( 'digital_product', 'tangible_product', 'physical_product' ), true ) ) { return false; }
		$status = sanitize_key( (string) get_post_meta( $booking_id, '_vava_booking_status', true ) );
		return ! in_array( $status, $blocked_statuses, true );
	} ) );
}

function vava_aug_2026_day_usage( string $date, array $shared = array() ): array {
	$usage = array( 'comprehensive' => 0, 'followup' => 0, 'inquiry' => 0, 'exploratory' => 0, 'minutes' => 0 );
	foreach ( vava_aug_2026_active_bookings_for_date( $date ) as $booking_id ) {
		$duration = max( 1, absint( get_post_meta( $booking_id, '_vava_booking_duration', true ) ) );
		$service = array(
			'uid' => (string) get_post_meta( $booking_id, '_vava_booking_service_uid', true ),
			'title' => (string) get_post_meta( $booking_id, '_vava_booking_service_title', true ),
			'duration' => $duration,
		);
		$bucket = vava_aug_2026_service_bucket( $service, $shared );
		$usage[ $bucket['key'] ]++;
		$usage['minutes'] += $duration;
	}
	return $usage;
}

function vava_aug_2026_capacity_reached( array $service, string $date, array $shared = array() ): bool {
	$bucket = vava_aug_2026_service_bucket( $service, $shared );
	$usage  = vava_aug_2026_day_usage( $date, $shared );
	return $usage[ $bucket['key'] ] >= $bucket['limit'] || ( $usage['minutes'] + $bucket['duration'] ) > 205;
}

function vava_aug_2026_capacity_message( array $service, array $shared = array(), string $lang = 'ar' ): string {
	$bucket = vava_aug_2026_service_bucket( $service, $shared );
	$labels_ar = array( 'comprehensive' => 'الجلسة الشاملة', 'followup' => 'جلسات المتابعة', 'inquiry' => 'جلسات الاستفسارات', 'exploratory' => 'الجلسة الاستكشافية' );
	$labels_en = array( 'comprehensive' => 'comprehensive session', 'followup' => 'follow-up sessions', 'inquiry' => 'inquiry sessions', 'exploratory' => 'exploratory session' );
	return 'en' === $lang
		? 'The daily capacity for ' . $labels_en[ $bucket['key'] ] . ' has been reached. Please choose another date.'
		: 'اكتمل الحد اليومي المتاح لـ ' . $labels_ar[ $bucket['key'] ] . '؛ يرجى اختيار يوم آخر.';
}

function vava_aug_2026_booking_slots_capacity_guard(): void {
	if ( false === check_ajax_referer( 'vava_booking_frontend', 'nonce', false ) ) { return; }
	if ( ! function_exists( 'vava_booking_resolve_service' ) || ! function_exists( 'vava_booking_shared_data' ) ) { return; }
	$lang = isset( $_POST['lang'] ) && 'en' === sanitize_key( wp_unslash( $_POST['lang'] ) ) ? 'en' : 'ar';
	$uid  = isset( $_POST['service'] ) ? sanitize_key( wp_unslash( $_POST['service'] ) ) : '';
	$date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
	$service = vava_booking_resolve_service( $uid, $lang );
	$shared  = vava_booking_shared_data( function_exists( 'vava_booking_page_id' ) ? vava_booking_page_id() : 0 );
	if ( $service && vava_aug_2026_capacity_reached( $service, $date, $shared ) ) {
		wp_send_json_success( array( 'slots' => array(), 'capacityReached' => true ) );
	}
}
add_action( 'wp_ajax_vava_booking_slots', 'vava_aug_2026_booking_slots_capacity_guard', 1 );
add_action( 'wp_ajax_nopriv_vava_booking_slots', 'vava_aug_2026_booking_slots_capacity_guard', 1 );

function vava_aug_2026_booking_dates_capacity_guard(): void {
	if ( false === check_ajax_referer( 'vava_booking_frontend', 'nonce', false ) ) { return; }
	if ( ! function_exists( 'vava_booking_resolve_service' ) || ! function_exists( 'vava_booking_shared_data' ) || ! function_exists( 'vava_booking_available_slots' ) ) { return; }
	$lang  = isset( $_POST['lang'] ) && 'en' === sanitize_key( wp_unslash( $_POST['lang'] ) ) ? 'en' : 'ar';
	$uid   = isset( $_POST['service'] ) ? sanitize_key( wp_unslash( $_POST['service'] ) ) : '';
	$start = isset( $_POST['start'] ) ? sanitize_text_field( wp_unslash( $_POST['start'] ) ) : '';
	$service = vava_booking_resolve_service( $uid, $lang );
	$shared  = vava_booking_shared_data( function_exists( 'vava_booking_page_id' ) ? vava_booking_page_id() : 0 );
	if ( ! $service || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) ) { return; }
	try { $timezone = new DateTimeZone( (string) ( $shared['timezone'] ?? wp_timezone_string() ) ); } catch ( Exception $error ) { $timezone = wp_timezone(); }
	$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $start, $timezone );
	if ( ! $date || $date->format( 'Y-m-d' ) !== $start ) { return; }
	$availability = array();
	for ( $offset = 0; $offset < 7; $offset++ ) {
		$value = $date->modify( '+' . $offset . ' days' )->format( 'Y-m-d' );
		$availability[ $value ] = ! vava_aug_2026_capacity_reached( $service, $value, $shared ) && ! empty( vava_booking_available_slots( $service, $value, $shared ) );
	}
	wp_send_json_success( array( 'availability' => $availability ) );
}
add_action( 'wp_ajax_vava_booking_dates', 'vava_aug_2026_booking_dates_capacity_guard', 1 );
add_action( 'wp_ajax_nopriv_vava_booking_dates', 'vava_aug_2026_booking_dates_capacity_guard', 1 );

function vava_aug_2026_booking_submit_capacity_guard(): void {
	if ( false === check_ajax_referer( 'vava_booking_frontend', 'nonce', false ) ) { return; }
	if ( ! function_exists( 'vava_booking_resolve_service' ) || ! function_exists( 'vava_booking_shared_data' ) ) { return; }
	$lang = isset( $_POST['lang'] ) && 'en' === sanitize_key( wp_unslash( $_POST['lang'] ) ) ? 'en' : 'ar';
	$uid  = isset( $_POST['service'] ) ? sanitize_key( wp_unslash( $_POST['service'] ) ) : '';
	$date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
	$service = vava_booking_resolve_service( $uid, $lang );
	$shared  = vava_booking_shared_data( function_exists( 'vava_booking_page_id' ) ? vava_booking_page_id() : 0 );
	if ( $service && vava_aug_2026_capacity_reached( $service, $date, $shared ) ) {
		wp_send_json_error( array( 'message' => vava_aug_2026_capacity_message( $service, $shared, $lang ) ), 409 );
	}
}
add_action( 'wp_ajax_vava_booking_submit', 'vava_aug_2026_booking_submit_capacity_guard', 1 );
add_action( 'wp_ajax_nopriv_vava_booking_submit', 'vava_aug_2026_booking_submit_capacity_guard', 1 );

/** Recover protected-PDF jobs that were queued but lost their cron event. */
function vava_aug_2026_recover_digital_pdf_jobs(): void {
	if ( ! current_user_can( 'edit_pages' ) || ! function_exists( 'vava_selections_page_id' ) || ! function_exists( 'vava_digital_products_file_map' ) || ! function_exists( 'vava_digital_products_schedule_processing' ) ) { return; }
	$post_id = absint( vava_selections_page_id() );
	if ( ! $post_id ) { return; }
	$map = vava_digital_products_file_map( $post_id );
	foreach ( array_slice( $map, 0, 100, true ) as $raw_uid => $record ) {
		if ( ! is_array( $record ) ) { continue; }
		$uid = sanitize_key( (string) $raw_uid );
		$fingerprint = (string) ( $record['fingerprint'] ?? '' );
		$status = sanitize_key( (string) ( $record['processing_status'] ?? '' ) );
		if ( ! $uid || ! $fingerprint || ! in_array( $status, array( 'queued', 'processing' ), true ) ) { continue; }
		if ( function_exists( 'vava_digital_products_private_file_path' ) && ! vava_digital_products_private_file_path( $record ) ) { continue; }
		$args = array( $post_id, $uid, $fingerprint );
		if ( ! wp_next_scheduled( 'vava_digital_products_process_pdf', $args ) ) {
			vava_digital_products_schedule_processing( $post_id, $uid, $fingerprint );
		}
	}
}
add_action( 'admin_init', 'vava_aug_2026_recover_digital_pdf_jobs', 150 );

/** Make the recovery check run on the status poll as well as admin page loads. */
function vava_aug_2026_recover_pdf_on_status_poll(): void {
	if ( false === check_ajax_referer( 'vava_digital_product_admin_' . absint( $_POST['post_id'] ?? 0 ), 'nonce', false ) ) { return; }
	vava_aug_2026_recover_digital_pdf_jobs();
}
add_action( 'wp_ajax_vava_digital_private_pdf_status', 'vava_aug_2026_recover_pdf_on_status_poll', 1 );
