<?php
/**
 * Plugin Name: VAVA Living — August 2026 Patch V3
 * Description: Corrective fixes after live V2 verification. Safe to run beside V1/V2.
 * Version: 3.0.0
 * Author: Tamiyouz
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'VAVA_AUG_2026_V3_VERSION' ) ) {
	define( 'VAVA_AUG_2026_V3_VERSION', '3.0.0' );
}

/**
 * Login: disable only the custom press-and-hold gate that can deadlock on a
 * stale/non-working AJAX challenge. Native WordPress username/password auth and
 * the VAVA CSS remain unchanged.
 */
function vava_aug_v3_disable_login_hold_gate(): void {
	remove_action( 'login_form', 'vava_admin_brand_login_guard_markup' );
	remove_filter( 'authenticate', 'vava_admin_brand_validate_login_guard', 5 );
}
add_action( 'after_setup_theme', 'vava_aug_v3_disable_login_hold_gate', PHP_INT_MAX );

function vava_aug_v3_login_assets_cleanup(): void {
	// The theme JS hides the native submit button until the hold challenge passes.
	wp_dequeue_script( 'vava-login-ui' );
	wp_deregister_script( 'vava-login-ui' );
}
add_action( 'login_enqueue_scripts', 'vava_aug_v3_login_assets_cleanup', 10000 );

function vava_aug_v3_login_no_cache(): void {
	nocache_headers();
	if ( ! headers_sent() ) {
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private', true );
		header( 'Pragma: no-cache', true );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
	}
}
add_action( 'login_init', 'vava_aug_v3_login_no_cache', -10000 );

/** Cache purge shared by Homepage/Paths image saves. */
function vava_aug_v3_purge_page_caches( int $post_id ): void {
	if ( $post_id <= 0 ) { return; }
	clean_post_cache( $post_id );
	wp_cache_delete( $post_id, 'post_meta' );
	if ( function_exists( 'wp_cache_flush_group' ) ) { wp_cache_flush_group( 'post_meta' ); }
	if ( function_exists( 'rocket_clean_post' ) ) { rocket_clean_post( $post_id ); }
	if ( function_exists( 'w3tc_flush_post' ) ) { w3tc_flush_post( $post_id ); }
	do_action( 'litespeed_purge_post', $post_id );
	$url = get_permalink( $post_id );
	if ( $url ) { do_action( 'litespeed_purge_url', $url ); }
	$front = absint( get_option( 'page_on_front' ) );
	if ( $front ) {
		clean_post_cache( $front );
		wp_cache_delete( $front, 'post_meta' );
		do_action( 'litespeed_purge_post', $front );
		do_action( 'litespeed_purge_url', home_url( '/' ) );
		if ( function_exists( 'rocket_clean_post' ) ) { rocket_clean_post( $front ); }
		if ( function_exists( 'w3tc_flush_post' ) ) { w3tc_flush_post( $front ); }
	}
}

/**
 * Persist both known Paths image controls late, after older theme save handlers.
 * This is deliberately scoped to the two exact meta keys.
 */
function vava_aug_v3_force_paths_image_save( int $post_id, WP_Post $post ): void {
	if ( 'page' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) { return; }
	$changed = false;
	foreach ( array( '_vava_home_paths_image_id', '_vava_paths_hero_image_id' ) as $key ) {
		if ( array_key_exists( $key, $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_post_meta( $post_id, $key, absint( wp_unslash( $_POST[ $key ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$changed = true;
		}
	}
	if ( $changed ) { vava_aug_v3_purge_page_caches( $post_id ); }
}
add_action( 'save_post_page', 'vava_aug_v3_force_paths_image_save', 9999, 2 );

/** Immediate AJAX persistence for image selectors on older dashboard builds. */
function vava_aug_v3_save_paths_image_ajax(): void {
	$post_id = absint( $_POST['post_id'] ?? 0 );
	$key     = sanitize_key( (string) ( $_POST['meta_key'] ?? '' ) );
	$allowed = array( 'vava_home_paths_image_id' => '_vava_home_paths_image_id', 'vava_paths_hero_image_id' => '_vava_paths_hero_image_id' );
	if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => 'غير مصرح بتعديل هذه الصفحة.' ), 403 );
	}
	check_ajax_referer( 'vava_aug_v3_admin_' . $post_id, 'nonce' );
	if ( ! isset( $allowed[ $key ] ) ) {
		wp_send_json_error( array( 'message' => 'حقل الصورة غير معروف.' ), 422 );
	}
	$attachment_id = absint( $_POST['attachment_id'] ?? 0 );
	if ( $attachment_id && ! wp_attachment_is_image( $attachment_id ) ) {
		wp_send_json_error( array( 'message' => 'الملف المختار ليس صورة.' ), 422 );
	}
	update_post_meta( $post_id, $allowed[ $key ], $attachment_id );
	vava_aug_v3_purge_page_caches( $post_id );
	wp_send_json_success( array( 'attachment_id' => $attachment_id, 'meta_key' => $allowed[ $key ] ) );
}
add_action( 'wp_ajax_vava_aug_v3_save_paths_image', 'vava_aug_v3_save_paths_image_ajax' );

/**
 * Keep full_description values posted by the V3 compatibility field even when
 * an older live theme sanitizer knows only about the short description.
 */
function vava_aug_v3_save_full_descriptions( int $post_id, WP_Post $post ): void {
	if ( 'page' !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) { return; }
	foreach ( array( 'ar', 'en' ) as $lang ) {
		$post_key = '_vava_selections_products_' . $lang;
		if ( ! isset( $_POST[ $post_key ] ) || ! is_array( $_POST[ $post_key ] ) ) { continue; } // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted = wp_unslash( $_POST[ $post_key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$saved  = get_post_meta( $post_id, $post_key, true );
		$saved  = is_array( $saved ) ? $saved : array();
		$changed = false;
		foreach ( $posted as $group => $rows ) {
			$group = sanitize_key( (string) $group );
			if ( ! is_array( $rows ) || ! isset( $saved[ $group ] ) || ! is_array( $saved[ $group ] ) ) { continue; }
			foreach ( array_values( $rows ) as $index => $row ) {
				if ( ! is_array( $row ) || ! array_key_exists( 'full_description', $row ) || ! isset( $saved[ $group ][ $index ] ) || ! is_array( $saved[ $group ][ $index ] ) ) { continue; }
				$value = sanitize_textarea_field( (string) $row['full_description'] );
				if ( '' === trim( $value ) && ! empty( $saved[ $group ][ $index ]['full_description'] ) ) { continue; }
				$saved[ $group ][ $index ]['full_description'] = $value;
				$changed = true;
			}
		}
		if ( $changed ) { update_post_meta( $post_id, $post_key, $saved ); }
	}
}
add_action( 'save_post_page', 'vava_aug_v3_save_full_descriptions', 10020, 2 );

/** Load the V3 compatibility JS from the correct MU-plugin URL. */
function vava_aug_v3_admin_assets( string $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) { return; }
	$screen = get_current_screen();
	if ( ! $screen || 'page' !== $screen->post_type ) { return; }
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $post_id && isset( $_POST['post_ID'] ) ) { $post_id = absint( $_POST['post_ID'] ); }
	if ( ! $post_id ) { return; }
	$url = defined( 'WPMU_PLUGIN_URL' )
		? trailingslashit( WPMU_PLUGIN_URL ) . 'vava-aug-2026-v3-admin.js'
		: content_url( 'mu-plugins/vava-aug-2026-v3-admin.js' );
	wp_enqueue_script( 'vava-aug-v3-admin', $url, array(), VAVA_AUG_2026_V3_VERSION, true );
	wp_localize_script( 'vava-aug-v3-admin', 'VAVA_AUG_V3_ADMIN', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'postId'  => $post_id,
		'nonce'   => wp_create_nonce( 'vava_aug_v3_admin_' . $post_id ),
		'lang'    => is_rtl() ? 'ar' : 'en',
	) );
	wp_register_style( 'vava-aug-v3-admin-inline', false, array(), VAVA_AUG_2026_V3_VERSION );
	wp_enqueue_style( 'vava-aug-v3-admin-inline' );
	wp_add_inline_style( 'vava-aug-v3-admin-inline',
		'.vava-v3-full-description{display:block!important;grid-column:1/-1!important}.vava-v3-full-description textarea,[data-product-local-field="full_description"]{display:block!important;visibility:visible!important;min-height:150px!important;width:100%!important}'
	);
}
add_action( 'admin_enqueue_scripts', 'vava_aug_v3_admin_assets', 10000 );

/** Session details: hide truly empty list cards and equalize the rest. */
function vava_aug_v3_session_layout_assets(): void {
	if ( is_admin() ) { return; }
	wp_register_style( 'vava-aug-v3-session-layout', false, array(), VAVA_AUG_2026_V3_VERSION );
	wp_enqueue_style( 'vava-aug-v3-session-layout' );
	wp_add_inline_style( 'vava-aug-v3-session-layout',
		'.vava-session-summary-grid{display:grid!important;grid-template-columns:repeat(auto-fit,minmax(min(100%,300px),1fr))!important;align-items:stretch!important}.vava-session-summary-grid>article{min-width:0!important;width:100%!important;height:100%!important}.vava-session-summary-grid>article[hidden]{display:none!important}'
	);
}
add_action( 'wp_enqueue_scripts', 'vava_aug_v3_session_layout_assets', 3000 );

function vava_aug_v3_session_layout_script(): void {
	if ( is_admin() ) { return; }
	?>
	<script id="vava-aug-v3-session-layout-script">
	(function(){
	  'use strict';
	  function tidySessionCards(){
	    document.querySelectorAll('.vava-session-summary-grid').forEach(function(grid){
	      grid.querySelectorAll(':scope > article').forEach(function(card){
	        var list=card.querySelector('ul');
	        if(!list)return;
	        var realItems=Array.prototype.filter.call(list.querySelectorAll('li'),function(li){return (li.textContent||'').replace(/\s+/g,'').length>0;});
	        if(realItems.length===0)card.hidden=true;
	        else card.hidden=false;
	      });
	    });
	  }
	  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',tidySessionCards);else tidySessionCards();
	  window.addEventListener('pageshow',tidySessionCards);
	}());
	</script>
	<?php
}
add_action( 'wp_footer', 'vava_aug_v3_session_layout_script', 1001 );

/** Exact Journey Impact config supplied by the client screenshot on 2026-08-31. */
function vava_aug_v3_impact_questionnaire(): array {
	return array(
		'enabled' => 1,
		'title' => array( 'ar' => 'استبيان أثر الرحلة', 'en' => 'Journey Impact Questionnaire' ),
		'description' => array( 'ar' => 'استبيان أثر الرحلة', 'en' => 'Journey Impact Questionnaire' ),
		'groups' => array(
			'journey_impact' => array(
				'label' => array( 'ar' => 'أثر الرحلة', 'en' => 'Journey Impact' ),
				'fields' => array(
					array( 'id' => 'full_name', 'type' => 'text', 'required' => 0, 'label' => array( 'ar' => 'الاسم الكريم (ثنائي)', 'en' => 'Full name' ), 'options' => array() ),
					array( 'id' => 'journey_stuck', 'type' => 'textarea', 'required' => 0, 'label' => array( 'ar' => 'وش أكثر شيء علقت فيه من هالرحلة؟', 'en' => 'What stayed with you most from this journey?' ), 'options' => array() ),
					array( 'id' => 'most_helpful_part', 'type' => 'radio', 'required' => 0, 'label' => array( 'ar' => 'وش أكثر جزء من التجربة حسيت إنه أفادك؟', 'en' => 'Which part of the experience helped you most?' ), 'options' => array(
						array( 'value' => 'consultation', 'ar' => 'الجلسة الاستشارية', 'en' => 'Consultation session' ),
						array( 'value' => 'personal_plan', 'ar' => 'الخطة الشخصية', 'en' => 'Personal plan' ),
						array( 'value' => 'educational_files', 'ar' => 'الملفات التعليمية', 'en' => 'Educational files' ),
						array( 'value' => 'followup_if_any', 'ar' => 'جلسة المتابعة (إن وجدت)', 'en' => 'Follow-up session (if any)' ),
						array( 'value' => 'whatsapp_if_any', 'ar' => 'دعم الواتساب (إن وجدت)', 'en' => 'WhatsApp support (if any)' ),
						array( 'value' => 'other', 'ar' => 'أخرى', 'en' => 'Other' ),
					) ),
					array( 'id' => 'wanted_clearer', 'type' => 'textarea', 'required' => 0, 'label' => array( 'ar' => 'هل كان فيه شيء تمنيت يكون أوضح أو مختلف؟', 'en' => 'Was there anything you wished had been clearer or different?' ), 'options' => array() ),
					array( 'id' => 'recommend_services', 'type' => 'radio', 'required' => 0, 'label' => array( 'ar' => 'هل ترشح الخدمات الاستشارية المتنوعة المقدمة من فافا لشخص آخر؟', 'en' => 'Would you recommend VAVA consultation services to someone else?' ), 'options' => array(
						array( 'value' => 'definitely', 'ar' => 'أكيد', 'en' => 'Definitely' ),
						array( 'value' => 'maybe', 'ar' => 'ممكن', 'en' => 'Maybe' ),
						array( 'value' => 'no', 'ar' => 'لا', 'en' => 'No' ),
						array( 'value' => 'other', 'ar' => 'أخرى', 'en' => 'Other' ),
					) ),
					array( 'id' => 'share_feedback', 'type' => 'radio', 'required' => 0, 'label' => array( 'ar' => 'هل تسمح لي أشارك جزء من كلامك عن تجربتك (بدون اسم أو أي معلومات شخصية)؟', 'en' => 'May I share part of your feedback about your experience without your name or personal information?' ), 'options' => array(
						array( 'value' => 'yes', 'ar' => 'نعم', 'en' => 'Yes' ),
						array( 'value' => 'no', 'ar' => 'لا', 'en' => 'No' ),
					) ),
				),
			),
		),
	);
}

/** Replace old saved Impact override once. Historical response snapshots remain untouched. */
function vava_aug_v3_migrate_impact_settings(): void {
	if ( get_option( 'vava_aug_2026_v3_impact_migrated_v1' ) ) { return; }
	if ( ! function_exists( 'vava_booking_page_id' ) ) { return; }
	$page_id = absint( vava_booking_page_id() );
	if ( ! $page_id ) { return; }
	$stored = get_post_meta( $page_id, '_vava_booking_questionnaires', true );
	$stored = is_array( $stored ) ? $stored : array();
	$stored['impact'] = vava_aug_v3_impact_questionnaire();
	update_post_meta( $page_id, '_vava_booking_questionnaires', $stored );
	clean_post_cache( $page_id );
	wp_cache_delete( $page_id, 'post_meta' );
	update_option( 'vava_aug_2026_v3_impact_migrated_v1', current_time( 'mysql' ), false );
}
add_action( 'admin_init', 'vava_aug_v3_migrate_impact_settings', 1500 );
