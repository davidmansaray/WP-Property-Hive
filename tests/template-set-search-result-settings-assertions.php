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
