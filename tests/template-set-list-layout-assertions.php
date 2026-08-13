<?php
/**
 * List-view layout assertions for the three search templates.
 *
 * Reads the shipped CSS files and fails if list-view overlap/spacing
 * rules are missing. Run with:
 *
 * wp-local-wp.sh eval-file tests/template-set-list-layout-assertions.php
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

$plugin_path   = trailingslashit( PH()->plugin_path() );
$structure_css = file_get_contents( $plugin_path . 'assets/css/template-set-search-structure.css' );
$template_css  = file_get_contents( $plugin_path . 'assets/css/template-set.css' );
$preview_trait = file_get_contents( $plugin_path . 'includes/template-set/traits/trait-ph-template-set-preview.php' );
$gallery_js    = file_get_contents( $plugin_path . 'assets/js/frontend/template-set/gallery.js' );

$assert(
	'structure_scopes_strip_to_grid_and_atlas',
	false !== strpos( $structure_css, '.ph-search-template-portal-grid-search-results' )
		&& false !== strpos( $structure_css, '.ph-search-template-map-led-search-results' )
		&& false !== strpos( $structure_css, '--ph-search-strip-height' )
);

$assert(
	'atlas_and_grid_share_unlayered_strip_selector',
	false !== strpos(
		$structure_css,
		'.ph-template-search:is(.ph-search-template-portal-grid-search-results, .ph-search-template-map-led-search-results) form.property-search-form'
	)
);

$assert(
	'portal_style_stretches_form_controls',
	false !== strpos( $template_css, '.ph-search-template-portal-style-search-results form.property-search-form' )
		&& false !== strpos( $template_css, 'align-items: stretch' )
		&& false !== strpos( $template_css, 'min-height: 44px' )
);

$assert(
	'portal_style_card_footer_is_own_grid_row',
	(bool) preg_match(
		'/\.ph-template-card-footer\s*\{[^}]*grid-row:\s*2/s',
		$template_css
	)
);

$assert(
	'portal_style_details_no_longer_use_overlap_spacer',
	false === strpos( $template_css, 'padding: 2px 0 86px' )
		&& false === strpos( $template_css, 'padding-bottom: 74px' )
		&& false === strpos( $template_css, 'padding-bottom: 94px' )
);

$assert(
	'portal_style_list_thumbnail_fills_card_cell',
	(bool) preg_match(
		'/\.ph-search-template-portal-style-search-results ul\.properties li\.ph-template-card \.thumbnail a\s*\{[^}]*position:\s*absolute/s',
		$template_css
	)
);

$assert(
	'portal_grid_list_layout_uses_horizontal_cards',
	false !== strpos(
		$structure_css,
		'.ph-template-search.ph-search-template-portal-grid-search-results.ph-search-layout-list ul.properties > li.ph-template-card'
	)
		&& (bool) preg_match(
			'/\.ph-search-template-portal-grid-search-results\.ph-search-layout-list ul\.properties > li\.ph-template-card \.ph-template-card-footer\s*\{[^}]*grid-row:\s*2/s',
			$structure_css
		)
);

$assert(
	'portal_grid_list_facts_are_not_boxed_cells',
	(bool) preg_match(
		'/\.ph-search-template-portal-grid-search-results\.ph-search-layout-list ul\.properties > li\.ph-template-card \.ph-template-facts(?:,|\s*>)[^\{]*\{[^}]*border:\s*0/s',
		$structure_css
	)
);

$assert(
	'portal_grid_gallery_controls_have_no_full_image_overlay',
	(bool) preg_match(
		'/\.ph-search-template-portal-grid-search-results ul\.properties > li\.ph-template-card \.ph-template-card-gallery-controls\s*\{[^}]*background:\s*transparent/s',
		$structure_css
	)
		&& (bool) preg_match(
			'/\.ph-search-template-portal-grid-search-results ul\.properties > li\.ph-template-card \.ph-template-card-gallery-count\s*\{[^}]*position:\s*absolute/s',
			$structure_css
		)
);

$assert(
	'portal_grid_gallery_does_not_render_duplicate_server_counter',
	false === strpos( $preview_trait, 'ph-template-card-gallery-controls' )
		&& false !== strpos( $gallery_js, 'data-ph-card-gallery-count' )
		&& false !== strpos( $gallery_js, 'thumbnail.appendChild(controls)' )
);

$assert(
	'atlas_list_and_split_keep_horizontal_card_columns',
	false !== strpos(
		$structure_css,
		'.ph-template-search.ph-search-template-map-led-search-results.ph-search-layout-list ul.properties > li.ph-template-card'
	)
		&& false !== strpos(
			$structure_css,
			'.ph-template-search.ph-search-template-map-led-search-results.ph-search-map-split ul.properties > li.ph-template-card'
		)
);

$assert(
	'more_filters_panel_not_clipped_by_form_overflow_hidden',
	(bool) preg_match(
		'/\.ph-template-search:is\([^)]*map-led-search-results\) form\.property-search-form\s*\{[^}]*overflow:\s*visible/s',
		$structure_css
	)
);

$assert(
	'more_filters_panel_cells_have_no_boxed_border',
	(bool) preg_match(
		'/\.ph-template-search-advanced-filters-panel \.control\s*\{[^}]*border:\s*0/s',
		$structure_css
	)
);

if ( ! empty( $failures ) ) {
	WP_CLI::error( count( $failures ) . ' list-layout assertion(s) failed: ' . implode( ', ', $failures ) );
}

WP_CLI::success( 'list-layout assertions passed' );
