<?php
/**
 * Detail location-map preview regression assertions.
 *
 * Run with:
 *
 * wp-local-wp.sh eval-file tests/template-set-detail-location-map-assertions.php
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

$original_get             = $_GET;
$original_settings       = get_option( 'propertyhive_template_assistant', array() );
$original_property       = isset( $GLOBALS['property'] ) ? $GLOBALS['property'] : null;
$settings_filter          = static function () use ( $original_settings ) {
	$settings                                  = is_array( $original_settings ) ? $original_settings : array();
	$settings['template_set_enabled']          = 'yes';
	$settings['template_set_editor_mode']      = 'visual_editor';
	$settings['template_set_detail_template']  = 'conversion-first-sales-detail';
	$settings['template_set_location_map']     = 'real-map';

	return $settings;
};

// A closed editor frame is still a demo-preview request, but it is the exact
// request used by the responsive iframe. This catches regressions where the
// editor path falls back to a decorative map merely because the parent chrome
// is not present in the iframe document.
$_GET[ PH_Template_Set::EDIT_QUERY_ARG ]        = '1';
$_GET[ PH_Template_Set::EDIT_CLOSED_QUERY_ARG ] = '1';
$_GET[ PH_Template_Set::EDIT_FRAME_QUERY_ARG ]  = '1';
wp_set_current_user( 1 );

add_filter( 'pre_option_propertyhive_template_assistant', $settings_filter );

$sample_property           = new stdClass();
$sample_property->latitude  = '54.2090';
$sample_property->longitude = '-0.2890';
$GLOBALS['property']       = $sample_property;

$assert(
	'closed_editor_frame_is_a_demo_preview',
	PH_Template_Set::is_demo_preview()
);

ob_start();
PH_Template_Set::render_detail_location_map( $sample_property );
$map_markup = ob_get_clean();

$assert(
	'editor_preview_renders_a_real_map_surface',
	false !== strpos( $map_markup, 'property_map_canvas' )
);
$assert(
	'editor_preview_uses_bundled_osm_fallback_without_provider',
	false !== strpos( $map_markup, 'tile.openstreetmap.org' )
);
$assert(
	'editor_preview_map_uses_property_coordinates',
	false !== strpos( $map_markup, '54.209' ) && false !== strpos( $map_markup, '-0.289' )
);

remove_filter( 'pre_option_propertyhive_template_assistant', $settings_filter );
$_GET               = $original_get;
$GLOBALS['property'] = $original_property;

if ( $failures ) {
	WP_CLI::error( count( $failures ) . ' detail location-map assertion(s) failed.' );
}

WP_CLI::success( 'Detail location-map assertions passed.' );
