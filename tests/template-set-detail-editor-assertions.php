<?php
/**
 * Detail visual-editor regression assertions.
 *
 * Run with:
 *
 * wp eval-file tests/template-set-detail-editor-assertions.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$failures = array();
$assert   = static function ( $name, $condition, $details = '' ) use ( &$failures ) {
	if ( $condition ) {
		WP_CLI::log( 'PASS ' . $name );
		return;
	}

	$failures[] = $name;
	WP_CLI::warning( 'FAIL ' . $name . ( $details ? ': ' . $details : '' ) );
};

$detail_trait = file_get_contents( dirname( __DIR__ ) . '/includes/template-set/traits/trait-ph-template-set-detail.php' );
$template_set = file_get_contents( dirname( __DIR__ ) . '/includes/class-ph-template-set.php' );
$template_css  = file_get_contents( dirname( __DIR__ ) . '/assets/css/template-set.css' );
$gallery       = file_get_contents( dirname( __DIR__ ) . '/templates/template-set/detail/gallery.php' );

$assert(
	'editor_frame_uses_editor_detail_data_path',
	false !== strpos( $detail_trait, 'self::is_template_editor_active() || PH_Template_Set_Request_Context::is_template_editor_frame_request()' )
);

$assert(
	'editor_frame_keeps_real_property_document_urls',
	false !== strpos( $detail_trait, '$is_editor_preview = self::is_detail_editor_preview()' )
		&& false !== strpos( $detail_trait, 'self::is_demo_preview() && ! $is_editor_preview' )
);

$assert(
	'full_description_is_passed_to_detail_modules',
	false !== strpos( $detail_trait, "'description'         => \$description" )
);

$assert(
	'legacy_trust_note_is_not_registered',
	false === strpos( $template_set, "add_action( 'propertyhive_property_actions_end', array( __CLASS__, 'render_trust_note' )" )
);

$assert(
	'editor_frame_toggles_rooms_and_full_description_together',
	false !== strpos( $template_css, 'body:is(.ph-template-editor-active, .ph-template-editor-frame).ph-template-hide-rooms-breakdown' )
		&& false !== strpos( $template_css, 'body:is(.ph-template-editor-active, .ph-template-editor-frame).ph-template-show-rooms-breakdown' )
);

$assert(
	'editor_gallery_uses_real_media_and_map_destinations',
	false !== strpos( $gallery, '$show_floor_panel    = $is_preview && ! $is_editor_preview' )
		&& false !== strpos( $gallery, 'href="#ph-template-detail-location-map"' )
		&& false !== strpos( $gallery, '! empty( $virtual_tours[0][\'url\'] )' )
);

if ( ! empty( $failures ) ) {
	WP_CLI::error( count( $failures ) . ' detail editor assertion(s) failed: ' . implode( ', ', $failures ) );
}

WP_CLI::success( 'Detail editor assertions passed.' );
