<?php
/**
 * Search-result visual editor regression assertions.
 *
 * Run with:
 *
 * wp-local-wp.sh eval-file tests/template-set-search-result-settings-assertions.php
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

$facts_method = new ReflectionMethod( 'PH_Template_Set', 'filter_search_card_facts' );
$facts_method->setAccessible( true );
$sample_facts = array(
	array( 'label' => 'Bed', 'quantity' => true ),
	array( 'label' => 'Type', 'quantity' => false ),
);

$assert(
	'legacy_absent_result_fields_preserve_template_set_room_facts',
	2 === count( $facts_method->invoke( null, $sample_facts, array() ) )
);
$assert(
	'legacy_explicit_empty_result_fields_hide_template_set_room_facts',
	1 === count( $facts_method->invoke( null, $sample_facts, array( 'search_result_fields' => array() ) ) )
);
$assert(
	'legacy_rooms_result_field_preserves_template_set_room_facts',
	2 === count( $facts_method->invoke( null, $sample_facts, array( 'search_result_fields' => array( 'rooms' ) ) ) )
);

$sentinel_settings = PH_Template_Set_Settings::sanitize_search_result_global_settings(
	array( 'search_result_fields_present' => '1' ),
	array( 'search_result_fields' => array( 'price' ) )
);
$assert(
	'explicit_empty_result_fields_sentinel_clears_existing_fields',
	array_key_exists( 'search_result_fields', $sentinel_settings ) && array() === $sentinel_settings['search_result_fields']
);

$preview_js = file_get_contents( dirname( __DIR__ ) . '/assets/js/frontend/template-set/editor-responsive-preview.js' );
$assert(
	'map_atlas_save_reconciles_effective_format_from_editor_control',
	false !== strpos( $preview_js, 'function reconcileMapSearchPreview(options)' )
		&& false !== strpos( $preview_js, 'setMapSearchPreviewFormat(value, options)' )
		&& false !== strpos( $preview_js, 'reconcileMapSearchPreview: reconcileMapSearchPreview' )
);

$editor_controller = file_get_contents( dirname( __DIR__ ) . '/includes/template-set/class-ph-template-set-editor-controller.php' );
$assert(
	'editor_marks_search_result_fields_as_present_when_none_are_checked',
	false !== strpos( $editor_controller, 'name="search_result_fields_present" value="1"' )
);

$image_storage_filter = static function () {
	return 'urls';
};
add_filter( 'pre_option_propertyhive_images_stored_as', $image_storage_filter );
$assert(
	'url_backed_photos_hide_search_result_image_size_control',
	! PH_Template_Set_Editor_Controller::should_render_search_result_image_size_control()
);
remove_filter( 'pre_option_propertyhive_images_stored_as', $image_storage_filter );

$image_storage_filter = static function () {
	return 'attachments';
};
add_filter( 'pre_option_propertyhive_images_stored_as', $image_storage_filter );
$assert(
	'attachment_photos_show_search_result_image_size_control',
	PH_Template_Set_Editor_Controller::should_render_search_result_image_size_control()
);
remove_filter( 'pre_option_propertyhive_images_stored_as', $image_storage_filter );

if ( $failures ) {
	WP_CLI::error( count( $failures ) . ' search-result settings assertion(s) failed.' );
}

WP_CLI::success( 'Search-result settings assertions passed.' );
