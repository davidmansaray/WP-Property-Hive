<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Template set request, preview, and page context.
 */
class PH_Template_Set_Request_Context {

	/**
	 * Is the request a capability-gated template preview (query-arg driven)?
	 *
	 * Demo/sample content only renders in this context so that a live site that
	 * simply enables the template set never has its real data overwritten.
	 *
	 * @return bool
	 */
	public static function is_demo_preview() {
		return self::is_enabled() && self::can_manage_template_set() && self::is_previewing_template();
	}

	/**
	 * Is the global template set enabled?
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$settings = PH_Template_Set_Settings::get_settings();

		if ( isset( $settings[ PH_Template_Set::OPTION_ENABLED ] ) && 'yes' === $settings[ PH_Template_Set::OPTION_ENABLED ] ) {
			// Page Builder and Developer experiences stand the visual template
			// set down so page builders or theme/template overrides stay in
			// control of the front end.
			$editor_mode = isset( $settings['template_set_editor_mode'] ) ? $settings['template_set_editor_mode'] : PH_Template_Set::EDITOR_MODE_VISUAL;

			if ( ! PH_Template_Set::editor_mode_stands_down( $editor_mode ) ) {
				return true;
			}
		}

		return self::can_render_preview_request();
	}

	/**
	 * Is this the main, Property Hive-owned search-results request?
	 *
	 * @return bool
	 */
	public static function is_search_results_request() {
		if ( ! self::is_enabled() || ! function_exists( 'is_search_results' ) || ! is_search_results() ) {
			return false;
		}

		if ( class_exists( 'PH_Template_Loader' ) && ! PH_Template_Loader::owns_search_results_template() ) {
			return false;
		}

		global $wp_query;

		return $wp_query instanceof WP_Query && $wp_query->is_main_query();
	}

	/**
	 * Get the selected detail template.
	 *
	 * @return string
	 */
	public static function get_detail_template() {
		$settings  = PH_Template_Set_Settings::get_settings();
		$templates = PH_Template_Set_Catalog::get_detail_templates();
		$template  = self::can_manage_template_set() ? self::get_query_template( PH_Template_Set::DETAIL_QUERY_ARG, $templates ) : '';

		if ( empty( $template ) ) {
			$template = sanitize_title( $settings['template_set_detail_template'] );
		}

		$template = PH_Template_Set_Catalog::normalize_detail_template_slug( $template );

		return isset( $templates[ $template ] ) ? $template : PH_Template_Set_Catalog::get_default_detail_template();
	}

	/**
	 * Get the selected search template.
	 *
	 * @return string
	 */
	public static function get_search_template() {
		$settings  = PH_Template_Set_Settings::get_settings();
		$templates = PH_Template_Set_Catalog::get_search_templates();
		$template  = self::can_manage_template_set() ? self::get_query_template( PH_Template_Set::SEARCH_QUERY_ARG, $templates ) : '';

		if ( empty( $template ) ) {
			$template = sanitize_title( $settings['template_set_search_template'] );
		}

		return isset( $templates[ $template ] ) ? $template : PH_Template_Set_Catalog::get_default_search_template();
	}

	/**
	 * Get the selected homepage/module template.
	 *
	 * @return string
	 */
	public static function get_module_template() {
		$templates = PH_Template_Set_Catalog::get_module_templates();
		$template  = self::can_manage_template_set() ? self::get_query_template( PH_Template_Set::MODULE_QUERY_ARG, $templates ) : '';

		return isset( $templates[ $template ] ) ? $template : '';
	}

	/**
	 * Build a preview URL for a catalogue template.
	 *
	 * @param string $template Template slug.
	 * @return string
	 */
	public static function get_template_preview_url( $template ) {
		$catalog = PH_Template_Set_Catalog::get_template_catalog();
		$template = sanitize_title( $template );

		if ( ! isset( $catalog[ $template ] ) ) {
			return self::get_current_url();
		}

		if ( 'detail' === $catalog[ $template ]['type'] ) {
			$url = self::get_sample_property_url( $template );
			return add_query_arg( PH_Template_Set::DETAIL_QUERY_ARG, $template, $url );
		}

		$archive_url = get_post_type_archive_link( 'property' );
		if ( ! $archive_url ) {
			$archive_url = home_url( '/' );
		}

		if ( 'module' === $catalog[ $template ]['type'] ) {
			return add_query_arg( PH_Template_Set::MODULE_QUERY_ARG, $template, $archive_url );
		}

		if ( self::is_search_results_request() ) {
			$archive_url = remove_query_arg(
				array_merge( self::get_preview_clear_query_args(), array( 'paged', 'page' ) ),
				self::get_current_url()
			);
		}

		return add_query_arg( PH_Template_Set::SEARCH_QUERY_ARG, $template, $archive_url );
	}

	/**
	 * Keep the selected template active when previewing/editing and opening another property.
	 *
	 * @param string  $url  Property URL.
	 * @param WP_Post $post Post object.
	 * @return string
	 */
	public static function preserve_template_preview_on_property_links( $url, $post ) {
		if ( is_admin() || ! $post || 'property' !== get_post_type( $post ) ) {
			return $url;
		}

		if ( ! self::can_show_template_switcher() ) {
			return $url;
		}

		if ( ! self::is_previewing_template() && ! self::is_template_editor_active() ) {
			return $url;
		}

		$args = array(
			PH_Template_Set::DETAIL_QUERY_ARG => self::get_detail_template(),
		);

		if ( self::is_template_editor_active() ) {
			$args[ PH_Template_Set::EDIT_QUERY_ARG ] = '1';
		} elseif ( self::is_template_editor_closed_request() ) {
			$args[ PH_Template_Set::EDIT_CLOSED_QUERY_ARG ] = '1';
		}

		return add_query_arg( $args, $url );
	}

	/**
	 * Redirect generic catalogue preview requests to the correct page type.
	 */
	public static function redirect_catalog_preview_request() {
		if ( empty( $_GET[ PH_Template_Set::CATALOG_QUERY_ARG ] ) ) {
			return;
		}

		$template = sanitize_title( wp_unslash( $_GET[ PH_Template_Set::CATALOG_QUERY_ARG ] ) );
		$catalog  = PH_Template_Set_Catalog::get_template_catalog();

		if ( ! isset( $catalog[ $template ] ) ) {
			return;
		}

		wp_safe_redirect( self::get_template_preview_url( $template ) );
		exit;
	}

	/**
	 * Add a front-end WP admin bar menu for opening the visual template editor.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public static function add_admin_bar_menu( $wp_admin_bar ) {
		if ( ! self::can_show_template_switcher() ) {
			return;
		}

		$is_detail = is_property();
		$is_search = self::is_search_results_request();

		if ( ! $is_detail && ! $is_search ) {
			return;
		}

		$root_id    = 'ph-template-set';
		$editor_url = add_query_arg(
			array(
				PH_Template_Set::EDIT_QUERY_ARG      => '1',
				PH_Template_Set::EDIT_OPEN_QUERY_ARG => '1',
			),
			remove_query_arg( PH_Template_Set::EDIT_CLOSED_QUERY_ARG, self::get_current_url() )
		);

		$wp_admin_bar->add_node(
			array(
				'id'    => $root_id,
				'title' => __( 'Open Visual Editor', 'propertyhive' ),
				'href'  => $editor_url,
			)
		);
	}

	/**
	 * Is the current archive rendering the module preview?
	 *
	 * @return bool
	 */
	public static function is_module_preview() {
		if ( ! self::is_search_results_request() ) {
			return false;
		}

		return '' !== self::get_query_template( PH_Template_Set::MODULE_QUERY_ARG, PH_Template_Set_Catalog::get_module_templates() );
	}

	/**
	 * Is the current archive rendering a search-template preview?
	 *
	 * @return bool
	 */
	public static function is_search_preview() {
		if ( ! self::is_search_results_request() ) {
			return false;
		}

		return '' !== self::get_query_template( PH_Template_Set::SEARCH_QUERY_ARG, PH_Template_Set_Catalog::get_search_templates() );
	}

	/**
	 * Is any template preview query active?
	 *
	 * @return bool
	 */
	public static function is_previewing_template() {
		if ( ! self::can_manage_template_set() ) {
			return false;
		}

		foreach ( self::get_preview_query_args() as $arg ) {
			if ( ! empty( $_GET[ $arg ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Is the visual template editor active on this request?
	 *
	 * @return bool
	 */
	public static function is_template_editor_active() {
		if ( ! self::can_show_template_switcher() ) {
			return false;
		}

		if ( self::is_template_editor_closed_request() ) {
			return false;
		}

		return self::is_template_editor_request();
	}

	/**
	 * Is this request explicitly asking for the front-end editor?
	 *
	 * @return bool
	 */
	public static function is_template_editor_request() {
		return ! empty( $_GET[ PH_Template_Set::EDIT_QUERY_ARG ] );
	}

	/**
	 * Is this request explicitly asking to hide the front-end editor?
	 *
	 * @return bool
	 */
	public static function is_template_editor_closed_request() {
		return ! empty( $_GET[ PH_Template_Set::EDIT_CLOSED_QUERY_ARG ] );
	}

	/**
	 * Can a valid template preview render while the global setting is inactive?
	 *
	 * @return bool
	 */
	public static function can_render_preview_request() {
		if ( is_admin() || ! self::can_manage_template_set() ) {
			return false;
		}

		return self::has_valid_preview_request();
	}

	/**
	 * Is the current request asking for a known preview template?
	 *
	 * @return bool
	 */
	public static function has_valid_preview_request() {
		if ( self::is_template_editor_request() && ( is_property() || is_post_type_archive( 'property' ) ) ) {
			return true;
		}

		if ( '' !== self::get_query_template( PH_Template_Set::DETAIL_QUERY_ARG, PH_Template_Set_Catalog::get_detail_templates() ) ) {
			return true;
		}

		if ( '' !== self::get_query_template( PH_Template_Set::SEARCH_QUERY_ARG, PH_Template_Set_Catalog::get_search_templates() ) ) {
			return true;
		}

		if ( '' !== self::get_query_template( PH_Template_Set::MODULE_QUERY_ARG, PH_Template_Set_Catalog::get_module_templates() ) ) {
			return true;
		}

		if ( empty( $_GET[ PH_Template_Set::CATALOG_QUERY_ARG ] ) ) {
			return false;
		}

		$template = sanitize_title( wp_unslash( $_GET[ PH_Template_Set::CATALOG_QUERY_ARG ] ) );
		$catalog  = PH_Template_Set_Catalog::get_template_catalog();

		return isset( $catalog[ $template ] );
	}

	/**
	 * Query args used by template preview routes.
	 *
	 * @return array
	 */
	public static function get_preview_query_args() {
		return array( PH_Template_Set::DETAIL_QUERY_ARG, PH_Template_Set::SEARCH_QUERY_ARG, PH_Template_Set::MODULE_QUERY_ARG, PH_Template_Set::CATALOG_QUERY_ARG, PH_Template_Set::EDIT_QUERY_ARG, PH_Template_Set::EDIT_OPEN_QUERY_ARG, 'ph_view' );
	}

	/**
	 * Query args to clear when leaving the preview/editor flow.
	 *
	 * @return array
	 */
	public static function get_preview_clear_query_args() {
		return array_merge( self::get_preview_query_args(), array( PH_Template_Set::EDIT_CLOSED_QUERY_ARG ) );
	}

	/**
	 * Get the currently represented catalogue template.
	 *
	 * @return string
	 */
	public static function get_current_catalog_template() {
		if ( is_property() ) {
			return self::get_detail_template();
		}

		if ( self::is_search_results_request() ) {
			if ( self::is_module_preview() ) {
				return self::get_module_template();
			}

			return self::get_search_template();
		}

		$settings = PH_Template_Set_Settings::get_settings();
		return sanitize_title( $settings['template_set_detail_template'] );
	}

	/**
	 * Can the current user switch templates on this front-end page?
	 *
	 * @return bool
	 */
	public static function can_show_template_switcher() {
		if ( is_admin() || ! self::can_manage_template_set() ) {
			return false;
		}

		return is_property() || self::is_search_results_request();
	}

	/**
	 * Can the current user manage the template set?
	 *
	 * @return bool
	 */
	public static function can_manage_template_set() {
		/**
		 * Filters the capability required to preview, edit, and save global
		 * Template Set settings from the front end.
		 *
		 * @since 2.2.7
		 *
		 * @param string $capability Required capability.
		 */
		$capability = apply_filters( 'propertyhive_template_set_editor_capability', 'manage_options' );

		return is_string( $capability ) && '' !== trim( $capability ) && current_user_can( $capability );
	}

	/**
	 * Get the selected gallery layout.
	 *
	 * @return string
	 */
	public static function get_gallery_layout() {
		$layout = sanitize_title( self::get_detail_setting( 'template_set_gallery_layout' ) );

		return isset( PH_Template_Set_Options::get_gallery_layouts()[ $layout ] ) ? $layout : 'showcase';
	}

	/**
	 * Get a setting resolved for the current detail template.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get_detail_setting( $key ) {
		return PH_Template_Set_Settings::get_for_template( $key, self::get_detail_template() );
	}

	public static function get_button_style() {
		$value = sanitize_title( self::get_detail_setting( 'template_set_button_style' ) );
		return isset( PH_Template_Set_Options::get_button_styles()[ $value ] ) ? $value : 'filled';
	}

	public static function get_contact_card_style() {
		$value = sanitize_title( self::get_detail_setting( 'template_set_contact_card_style' ) );
		return isset( PH_Template_Set_Options::get_contact_card_styles()[ $value ] ) ? $value : 'classic';
	}

	public static function get_show_mobile_cta() {
		return 'yes' === self::get_detail_setting( 'template_set_show_mobile_cta' ) ? 'yes' : '';
	}

	public static function get_show_floorplans() {
		return 'yes' === self::get_detail_setting( 'template_set_show_floorplans' ) ? 'yes' : '';
	}

	/**
	 * Get the selected Location module map treatment.
	 *
	 * @return string
	 */
	public static function get_location_map() {
		return 'real-map' === self::get_detail_setting( 'template_set_location_map' ) ? 'real-map' : 'illustration';
	}

	public static function get_show_virtual_tours() {
		return 'yes' === self::get_detail_setting( 'template_set_show_virtual_tours' ) ? 'yes' : '';
	}

	public static function get_show_recommended() {
		return 'yes' === self::get_detail_setting( 'template_set_show_recommended' ) ? 'yes' : '';
	}

	public static function get_recommended_count() {
		$value = absint( self::get_detail_setting( 'template_set_recommended_count' ) );
		return isset( PH_Template_Set_Options::get_recommended_property_counts()[ $value ] ) ? $value : 3;
	}

	public static function get_recommended_layout() {
		$value = sanitize_title( self::get_detail_setting( 'template_set_recommended_layout' ) );
		return isset( PH_Template_Set_Options::get_recommended_property_layouts()[ $value ] ) ? $value : 'grid';
	}

	public static function get_recommended_image_size() {
		$value = sanitize_title( self::get_detail_setting( 'template_set_recommended_image_size' ) );
		return isset( PH_Template_Set_Options::get_recommended_property_image_sizes()[ $value ] ) ? $value : 'standard';
	}

	public static function get_portal_show_costs() {
		return 'yes' === self::get_detail_setting( 'template_set_portal_show_costs' ) ? 'yes' : '';
	}

	public static function get_cinema_card_position() {
		$value = sanitize_title( self::get_detail_setting( 'template_set_cinema_card_position' ) );
		return in_array( $value, array( 'right', 'left' ), true ) ? $value : 'right';
	}

	public static function get_editorial_show_brief() {
		return 'yes' === self::get_detail_setting( 'template_set_editorial_show_brief' ) ? 'yes' : '';
	}

	/**
	 * Get a template-scoped search visibility setting.
	 *
	 * @param string $key Setting key.
	 * @return string
	 */
	public static function get_search_visibility_setting( $key ) {
		return 'yes' === PH_Template_Set_Settings::get_search_for_template( $key, self::get_search_template() ) ? 'yes' : '';
	}

	public static function get_show_save_search() {
		return self::get_search_visibility_setting( 'template_set_show_save_search' );
	}

	public static function get_show_shortlist_cards() {
		return self::get_search_visibility_setting( 'template_set_show_shortlist_cards' );
	}

	public static function get_show_shortlist_detail() {
		return 'yes' === self::get_detail_setting( 'template_set_show_shortlist_detail' ) ? 'yes' : '';
	}

	public static function get_show_send_to_friend() {
		return 'yes' === self::get_detail_setting( 'template_set_show_send_to_friend' ) ? 'yes' : '';
	}

	public static function get_show_rooms_breakdown() {
		return 'yes' === self::get_detail_setting( 'template_set_show_rooms_breakdown' ) ? 'yes' : '';
	}

	/**
	 * Get a valid preview template from the query string.
	 *
	 * @param string $query_arg Query arg name.
	 * @param array  $templates Allowed templates.
	 * @return string
	 */
	public static function get_query_template( $query_arg, $templates ) {
		if ( empty( $_GET[ $query_arg ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return '';
		}

		$template = sanitize_title( wp_unslash( $_GET[ $query_arg ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( PH_Template_Set::DETAIL_QUERY_ARG === $query_arg ) {
			$template = PH_Template_Set_Catalog::normalize_detail_template_slug( $template );
		}

		return isset( $templates[ $template ] ) ? $template : '';
	}

	/**
	 * Build a switch URL for the current template page.
	 *
	 * @param string $query_arg Query arg name.
	 * @param string $template Template slug.
	 * @return string
	 */
	public static function get_template_switch_url( $query_arg, $template ) {
		$clear = self::get_preview_clear_query_args();
		if ( PH_Template_Set::SEARCH_QUERY_ARG === $query_arg ) {
			$clear = array_merge( $clear, array( 'paged', 'page' ) );
		}
		$url = remove_query_arg( $clear, self::get_current_url() );

		return add_query_arg( $query_arg, sanitize_title( $template ), $url );
	}

	/**
	 * Get a published property URL for detail template previews.
	 *
	 * @param string $template Template slug.
	 * @return string
	 */
	public static function get_sample_property_url( $template = '' ) {
		$preferred_department = self::get_detail_template_sample_department( $template );

		if ( is_property() ) {
			$property_id = get_queried_object_id();

			if ( self::property_matches_sample_department( $property_id, $preferred_department ) ) {
				return get_permalink( $property_id );
			}
		}

		$properties = get_posts(
			array(
				'post_type'      => 'property',
				'post_status'    => 'publish',
				'posts_per_page' => 25,
				'fields'         => 'ids',
			)
		);

		foreach ( $properties as $property_id ) {
			if ( self::property_matches_sample_department( $property_id, $preferred_department ) ) {
				return get_permalink( $property_id );
			}
		}

		if ( ! empty( $properties ) ) {
			return get_permalink( $properties[0] );
		}

		$archive_url = get_post_type_archive_link( 'property' );
		return $archive_url ? $archive_url : home_url( '/' );
	}

	/**
	 * Preferred sample property department for a detail template.
	 *
	 * @param string $template Template slug.
	 * @return string
	 */
	public static function get_detail_template_sample_department( $template ) {
		return 'lettings-detail' === $template ? 'residential-lettings' : 'residential-sales';
	}

	/**
	 * Does a property match the preferred preview department?
	 *
	 * @param int    $property_id Property ID.
	 * @param string $department  Expected Property Hive department.
	 * @return bool
	 */
	public static function property_matches_sample_department( $property_id, $department ) {
		if ( empty( $property_id ) || empty( $department ) ) {
			return false;
		}

		$property = new PH_Property( $property_id );

		return $department === $property->department || $department === ph_get_custom_department_based_on( $property->department );
	}

	/**
	 * Get the current front-end URL.
	 *
	 * @return string
	 */
	public static function get_current_url() {
		return home_url( add_query_arg( null, null ) );
	}

	/**
	 * Get the active search department from the request/settings.
	 *
	 * @return string
	 */
	public static function get_current_search_department() {
		if ( ! empty( $_REQUEST['department'] ) ) {
			return sanitize_text_field( wp_unslash( $_REQUEST['department'] ) );
		}

		$department = get_option( 'propertyhive_primary_department', 'residential-sales' );

		if ( '' !== $department ) {
			return sanitize_text_field( $department );
		}

		foreach ( ph_get_departments() as $key => $label ) {
			if ( 'yes' === get_option( 'propertyhive_active_departments_' . str_replace( 'residential-', '', $key ) ) ) {
				return sanitize_text_field( $key );
			}
		}

		return 'residential-sales';
	}

	/**
	 * Get the department constraint that should validate a taxonomy dropdown.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return string
	 */
	public static function get_search_department_for_taxonomy( $taxonomy ) {
		$department = self::get_current_search_department();
		$base       = ph_get_custom_department_based_on( $department );

		if ( false === $base ) {
			$base = $department;
		}

		if ( 'commercial_property_type' === $taxonomy ) {
			return 'commercial' === $base ? $department : '';
		}

		if ( 'property_type' === $taxonomy ) {
			return in_array( $base, array( 'residential-sales', 'residential-lettings' ), true ) ? $department : '';
		}

		return '';
	}

	/**
	 * Get current search view.
	 *
	 * @return string
	 */
	public static function get_search_view() {
		$presentation = self::get_search_presentation();

		return 'map-only' === $presentation['map_mode'] ? 'map' : $presentation['card_layout'];
	}

	/**
	 * Resolve the current Map Search capability and request state.
	 *
	 * @return array
	 */
	public static function get_map_search_state() {
		$available      = class_exists( 'PH_Map_Search' );
		$usable         = $available && class_exists( 'PH_Template_Set' ) && PH_Template_Set::is_add_on_usable( 'propertyhive-map-search' );
		$settings       = get_option( 'propertyhive_map_search', array() );
		$format         = is_array( $settings ) && isset( $settings['format'] ) ? sanitize_key( $settings['format'] ) : '';
		$requested_view = isset( $_GET['view'] ) && is_scalar( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';

		if ( ! in_array( $format, array( 'view', 'split' ), true ) ) {
			$format = '';
		}

		$manages_archive = $usable && '' !== $format;
		$shows_real_map  = $manages_archive && (
			( 'view' === $format && 'map' === $requested_view )
			|| ( 'split' === $format && in_array( $requested_view, array( '', 'map' ), true ) )
		);
		$shows_results   = ! ( 'view' === $format && 'map' === $requested_view && $usable );
		$fallback_reason = '';

		if ( ! $available ) {
			$fallback_reason = 'unavailable';
		} elseif ( ! $usable ) {
			$fallback_reason = 'unusable';
		} elseif ( '' === $format ) {
			$fallback_reason = 'unconfigured';
		} elseif ( ! $shows_real_map ) {
			$fallback_reason = 'list-state';
		}

		$state = array(
			'available'         => $available,
			'usable'            => $usable,
			'format'            => $format,
			'requested_view'    => $requested_view,
			'manages_archive'   => $manages_archive,
			'shows_real_map'    => $shows_real_map,
			'shows_results'     => $shows_results,
			'shows_view_switch' => $usable && 'view' === $format,
			'fallback_reason'   => $fallback_reason,
		);

		$state = apply_filters( 'propertyhive_template_set_map_search_state', $state );
		$state = wp_parse_args( is_array( $state ) ? $state : array(), array(
			'available'         => false,
			'usable'            => false,
			'format'            => '',
			'requested_view'    => '',
			'manages_archive'   => false,
			'shows_real_map'    => false,
			'shows_results'     => true,
			'shows_view_switch' => false,
			'fallback_reason'   => 'unavailable',
		) );

		foreach ( array( 'available', 'usable', 'manages_archive', 'shows_real_map', 'shows_results', 'shows_view_switch' ) as $key ) {
			$state[ $key ] = (bool) $state[ $key ];
		}
		$state['format']          = in_array( $state['format'], array( 'view', 'split' ), true ) ? $state['format'] : '';
		$state['requested_view']  = sanitize_key( $state['requested_view'] );
		$state['fallback_reason'] = in_array( $state['fallback_reason'], array( '', 'unavailable', 'unusable', 'unconfigured', 'list-state' ), true ) ? $state['fallback_reason'] : '';

		return $state;
	}

	/**
	 * Pure search presentation resolver.
	 *
	 * @param string $template          Search template.
	 * @param string $saved_layout      Saved card layout.
	 * @param string $requested_ph_view Template Set card layout request.
	 * @param string $requested_map_view Map Search view request.
	 * @param array  $map_state         Normalized Map Search state.
	 * @return array
	 */
	public static function resolve_search_presentation( $template, $saved_layout, $requested_ph_view, $requested_map_view, $map_state ) {
		$manifest    = PH_Template_Set_Catalog::get_search_template_manifest( $template );
		$card_layout = in_array( $saved_layout, $manifest['supported_layouts'], true ) ? $saved_layout : $manifest['default_layout'];
		$ph_view     = in_array( $requested_ph_view, array( 'grid', 'list' ), true ) ? $requested_ph_view : '';
		$map_view    = sanitize_key( $requested_map_view );
		$format      = isset( $map_state['format'] ) && in_array( $map_state['format'], array( 'view', 'split' ), true ) ? $map_state['format'] : '';
		$usable      = ! empty( $map_state['usable'] );

		if ( '' !== $ph_view ) {
			$card_layout = $ph_view;
		}

		$map_mode = 'none';
		$has_map  = false;

		if ( $usable && 'view' === $format ) {
			if ( 'map' === $map_view ) {
				$map_mode = 'map-only';
				$has_map  = true;
			} else {
				$map_mode = 'toggle-list';
			}
		} elseif ( $usable && 'split' === $format && in_array( $map_view, array( '', 'map' ), true ) ) {
			$map_mode    = 'split';
			$has_map     = true;
			$card_layout = 'list';
		}

		return array(
			'card_layout' => 'list' === $card_layout ? 'list' : 'grid',
			'map_mode'    => $map_mode,
			'has_map'     => $has_map,
		);
	}

	/**
	 * Get the current normalized search presentation.
	 *
	 * @return array
	 */
	public static function get_search_presentation() {
		$settings = PH_Template_Set_Settings::get_settings();
		$ph_view  = isset( $_GET['ph_view'] ) && is_scalar( $_GET['ph_view'] ) ? sanitize_key( wp_unslash( $_GET['ph_view'] ) ) : '';
		$map_view = isset( $_GET['view'] ) && is_scalar( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';

		return self::resolve_search_presentation(
			self::get_search_template(),
			isset( $settings['template_set_search_layout'] ) ? sanitize_key( $settings['template_set_search_layout'] ) : '',
			$ph_view,
			$map_view,
			self::get_map_search_state()
		);
	}
}
