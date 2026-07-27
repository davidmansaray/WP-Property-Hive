<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Search result template set callbacks.
 */
trait PH_Template_Set_Search {

	/**
	 * Add template set body classes.
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public static function body_classes( $classes ) {
		if ( ! self::is_enabled() ) {
			return $classes;
		}

		$settings      = self::get_settings();
		$is_detail     = is_property();
		$button_style  = $is_detail ? PH_Template_Set_Request_Context::get_button_style() : $settings['template_set_button_style'];
		$contact_style = $is_detail ? PH_Template_Set_Request_Context::get_contact_card_style() : $settings['template_set_contact_card_style'];
		$classes[] = 'ph-template-set-active';
		$classes[] = 'ph-template-images-' . sanitize_html_class( $settings['template_set_image_style'] );
		$classes[] = 'ph-template-buttons-' . sanitize_html_class( $button_style );
		$classes[] = 'ph-template-contact-card-' . sanitize_html_class( $contact_style );
		$classes[] = 'ph-template-editor-mode-' . sanitize_html_class( $settings['template_set_editor_mode'] );
		$classes[] = 'yes' === $settings['template_set_show_branch'] ? 'ph-template-show-branch' : 'ph-template-hide-branch';
		$classes[] = 'yes' === $settings['template_set_show_badges'] ? 'ph-template-show-badges' : 'ph-template-hide-badges';
		$classes[] = 'yes' === ( $is_detail ? PH_Template_Set_Request_Context::get_show_mobile_cta() : $settings['template_set_show_mobile_cta'] ) ? 'ph-template-show-mobile-cta' : 'ph-template-hide-mobile-cta';
		$classes[] = 'yes' === ( $is_detail ? PH_Template_Set_Request_Context::get_show_floorplans() : $settings['template_set_show_floorplans'] ) ? 'ph-template-show-floorplans' : 'ph-template-hide-floorplans';
		$classes[] = 'yes' === ( $is_detail ? PH_Template_Set_Request_Context::get_show_virtual_tours() : $settings['template_set_show_virtual_tours'] ) ? 'ph-template-show-virtual-tours' : 'ph-template-hide-virtual-tours';
		$classes[] = 'yes' === ( $is_detail ? PH_Template_Set_Request_Context::get_show_recommended() : $settings['template_set_show_recommended'] ) ? 'ph-template-show-recommended' : 'ph-template-hide-recommended';
		$classes[] = 'ph-template-recommended-count-' . absint( $is_detail ? PH_Template_Set_Request_Context::get_recommended_count() : $settings['template_set_recommended_count'] );
		$classes[] = 'ph-template-recommended-layout-' . sanitize_html_class( $is_detail ? PH_Template_Set_Request_Context::get_recommended_layout() : $settings['template_set_recommended_layout'] );
		$classes[] = 'ph-template-recommended-images-' . sanitize_html_class( $is_detail ? PH_Template_Set_Request_Context::get_recommended_image_size() : $settings['template_set_recommended_image_size'] );

		if ( self::is_template_editor_active() ) {
			$classes[] = 'ph-template-editor-active';
		}

		if ( self::is_demo_preview() ) {
			$classes[] = 'ph-template-preview-mode';
		}

		if ( is_property() ) {
			$detail_template = self::get_detail_template();
			$public_template = PH_Template_Set_Catalog::get_detail_template_public_slug( $detail_template );
			$classes[] = 'ph-detail-template-' . sanitize_html_class( $detail_template );
			$classes[] = 'ph-detail-template-' . sanitize_html_class( $public_template );

			if ( 'conversion-first-sales-detail' === $detail_template ) {
				$classes[] = 'yes' === PH_Template_Set_Request_Context::get_portal_show_costs() ? 'ph-template-show-portal-costs' : 'ph-template-hide-portal-costs';
			}

			if ( 'immersive-cinema-detail' === $detail_template ) {
				$classes[] = 'ph-template-cinema-card-' . sanitize_html_class( PH_Template_Set_Request_Context::get_cinema_card_position() );
			}

			if ( 'premium-editorial-detail' === $detail_template ) {
				$classes[] = 'yes' === PH_Template_Set_Request_Context::get_editorial_show_brief() ? 'ph-template-show-editorial-brief' : 'ph-template-hide-editorial-brief';
			}
		}

		if ( PH_Template_Set_Request_Context::is_search_results_request() ) {
			$presentation = PH_Template_Set_Request_Context::get_search_presentation();
			$map_state    = PH_Template_Set_Request_Context::get_map_search_state();
			$classes[] = 'ph-search-template-' . sanitize_html_class( self::get_search_template() );
			$classes[] = 'ph-search-view-' . sanitize_html_class( self::get_search_view() );
			$classes[] = 'ph-search-layout-' . sanitize_html_class( $presentation['card_layout'] );
			$classes[] = 'ph-search-map-' . sanitize_html_class( $presentation['map_mode'] );
			$classes[] = 'ph-search-card-size-' . sanitize_html_class( $settings['template_set_search_card_size'] );
			$classes[] = 'ph-search-grid-columns-' . PH_Template_Set_Settings::get_search_grid_columns_for_template( self::get_search_template(), $settings );

			if ( $map_state['manages_archive'] ) {
				$classes[] = 'ph-template-map-search-active';
			}

			if ( $presentation['has_map'] ) {
				$classes[] = 'ph-template-map-search-real-map';
			}

			if ( '' !== $map_state['fallback_reason'] && self::can_show_template_switcher() ) {
				$classes[] = 'ph-search-map-fallback-' . sanitize_html_class( $map_state['fallback_reason'] );
			}

			if ( in_array( $map_state['fallback_reason'], array( 'unavailable', 'unusable', 'unconfigured' ), true ) && ! self::can_manage_template_set() ) {
				$classes[] = 'ph-search-map-panel-suppressed';
			}

			if ( class_exists( 'PH_Radial_Search' ) && self::is_add_on_usable( 'propertyhive-radial-search' ) ) {
				$classes[] = 'ph-template-radial-search-active';
			}

			if ( class_exists( 'PH_Location_Autocomplete' ) && self::is_add_on_usable( 'propertyhive-location-autocomplete' ) ) {
				$classes[] = 'ph-template-location-autocomplete-active';
			}
		}

		if ( self::is_module_preview() ) {
			$classes[] = 'ph-module-template-' . sanitize_html_class( self::get_module_template() );
			$classes[] = 'ph-module-template-preview-active';
		}

		return $classes;
	}

	/**
	 * Add template set classes to property cards/details.
	 *
	 * @param array       $classes Post classes.
	 * @param string|array $class Extra classes.
	 * @param int         $post_id Post ID.
	 * @return array
	 */
	public static function post_classes( $classes, $class = '', $post_id = 0 ) {
		if ( in_array( 'promo', $classes, true ) ) {
			$classes[] = 'ph-template-search-promo';
			if ( 'full' === apply_filters( 'propertyhive_template_set_promo_grid_span', 1 ) ) {
				$classes[] = 'ph-template-search-promo-full';
			}
			return array_values( array_unique( $classes ) );
		}

		if ( 'property' !== get_post_type( $post_id ) ) {
			return $classes;
		}

		if ( self::is_enabled() && is_property() && (int) get_the_ID() === (int) $post_id ) {
			$detail_template = self::get_detail_template();
			$public_template = PH_Template_Set_Catalog::get_detail_template_public_slug( $detail_template );
			$classes[] = 'ph-template-set';
			$classes[] = 'ph-template-detail';
			$classes[] = 'ph-detail-template-' . sanitize_html_class( $detail_template );
			$classes[] = 'ph-detail-template-' . sanitize_html_class( $public_template );
		}

		if ( self::$rendering_module || self::is_search_card_rendering() ) {
			$settings  = self::get_settings();
			$classes[] = 'ph-template-card';
			$classes[] = 'ph-search-template-' . sanitize_html_class( self::get_search_template() );
			$classes[] = 'ph-template-images-' . sanitize_html_class( $settings['template_set_image_style'] );
			$classes[] = 'ph-search-card-size-' . sanitize_html_class( $settings['template_set_search_card_size'] );

			if ( self::$rendering_module ) {
				$classes[] = 'ph-template-module-card';
				$classes[] = 'ph-home-template-' . sanitize_html_class( self::get_module_template() );
			}
		}

		return $classes;
	}

	/**
	 * Template set can control search result columns safely.
	 *
	 * @param int $columns Existing columns.
	 * @return int
	 */
	public static function search_result_columns( $columns ) {
		if ( ! PH_Template_Set_Request_Context::is_search_results_request() ) {
			return $columns;
		}

		$presentation = PH_Template_Set_Request_Context::get_search_presentation();
		if ( 'list' === $presentation['card_layout'] || 'split' === $presentation['map_mode'] ) {
			return 1;
		}

		$settings = self::get_settings();
		return max( 1, PH_Template_Set_Settings::get_search_grid_columns_for_template( self::get_search_template(), $settings ) );
	}

	/**
	 * Align the base search-result card hooks to the template-set card pattern.
	 */
	public static function prepare_search_result_cards() {
		if ( ! self::is_search_card_rendering() && ! PH_Template_Set_Request_Context::is_search_results_request() ) {
			return;
		}

		if ( self::uses_prototype_search_chrome() ) {
			// The prototype toolbar owns these core controls, while retaining the
			// existing ordering template and its query-string behaviour.
			remove_action( 'propertyhive_before_search_results_loop', 'propertyhive_result_count', 20 );
			remove_action( 'propertyhive_before_search_results_loop', 'propertyhive_catalog_ordering', 30 );
		}

		$hook = 'propertyhive_after_search_results_loop_item_title';

		foreach ( self::remove_named_action_callbacks( $hook, 'propertyhive_template_loop_price' ) as $priority ) {
			remove_action( $hook, array( __CLASS__, 'render_linked_card_price' ), $priority );
			add_action( $hook, array( __CLASS__, 'render_linked_card_price' ), $priority );
		}

		$action_priorities = self::remove_named_action_callbacks( $hook, 'propertyhive_template_loop_actions' );
		$template_assistant = get_option( 'propertyhive_template_assistant', array() );
		$configured_fields  = is_array( $template_assistant ) && isset( $template_assistant['search_result_fields'] ) && is_array( $template_assistant['search_result_fields'] )
			? $template_assistant['search_result_fields']
			: array();

		// Template Assistant is core functionality. Re-add its normal renderer at
		// the saved priorities so theme overrides of search/actions.php still apply.
		if ( in_array( 'actions', $configured_fields, true ) ) {
			foreach ( $action_priorities as $priority ) {
				add_action( $hook, 'propertyhive_template_loop_actions', $priority );
			}
		}

		if ( self::is_portal_card_attribution_enabled() ) {
			global $wp_query;

			if ( $wp_query instanceof WP_Query ) {
				self::prime_portal_card_attribution( wp_list_pluck( $wp_query->posts, 'ID' ) );
			}
		}

		remove_action( $hook, array( __CLASS__, 'render_map_card_location' ), 8 );
		if ( 'map-led-search-results' === self::get_search_template() ) {
			add_action( $hook, array( __CLASS__, 'render_map_card_location' ), 8 );
		}
	}

	/**
	 * Render the locality eyebrow and address used by Map Atlas cards.
	 */
	public static function render_map_card_location() {
		global $property;

		if ( ! $property || 'map-led-search-results' !== self::get_search_template() ) {
			return;
		}

		$area              = self::get_map_property_area( $property );
		$address_remainder = self::get_map_card_address_remainder( $property );

		if ( '' !== $area ) {
			echo '<p class="ph-template-map-card-area">' . esc_html( $area ) . '</p>';
		}

		if ( '' !== $address_remainder ) {
			echo '<p class="ph-template-map-card-address">' . esc_html( $address_remainder ) . '</p>';
		}
	}

	/**
	 * Get only the formatted-address content not already present in the title.
	 *
	 * @param PH_Property $property Property object.
	 * @return string
	 */
	private static function get_map_card_address_remainder( $property ) {
		$address = trim( wp_strip_all_tags( (string) $property->get_formatted_summary_address() ) );
		$title   = trim( wp_strip_all_tags( (string) get_the_title( $property->id ) ) );

		if ( '' === $address || '' === $title || sanitize_title( $address ) === sanitize_title( $title ) ) {
			return '';
		}

		if ( 0 === stripos( $address, $title ) ) {
			return trim( preg_replace( '/^[\s,–—-]+/u', '', substr( $address, strlen( $title ) ) ) );
		}

		$title_key = sanitize_title( $title );
		$remainder = array();

		foreach ( preg_split( '/\s*,\s*/u', $address ) as $part ) {
			$part     = trim( $part );
			$part_key = sanitize_title( $part );

			if ( '' !== $part && '' !== $part_key && false === strpos( $title_key, $part_key ) ) {
				$remainder[] = $part;
			}
		}

		return implode( ', ', $remainder );
	}

	/**
	 * Get the most useful available locality for a Map Atlas property.
	 *
	 * @param PH_Property $property Property object.
	 * @return string
	 */
	private static function get_map_property_area( $property ) {
		foreach ( array( 'address_three', 'address_four' ) as $field ) {
			$area = trim( wp_strip_all_tags( (string) $property->{$field} ) );
			if ( '' !== $area ) {
				return $area;
			}
		}

		return '';
	}

	/**
	 * Replace core's repeated bulk custom-field callback with one field per slot.
	 */
	public static function normalize_configured_search_custom_fields( $force = false ) {
		if ( ! $force && ! PH_Template_Set_Request_Context::is_search_results_request() ) {
			return;
		}

		$settings = get_option( 'propertyhive_template_assistant', array() );
		$fields   = is_array( $settings ) && isset( $settings['search_result_fields'] ) && is_array( $settings['search_result_fields'] )
			? $settings['search_result_fields']
			: array();
		$priority = 5;

		foreach ( $fields as $field ) {
			if ( 0 === strpos( (string) $field, 'custom_field' ) ) {
				$custom_field = sanitize_key( substr( (string) $field, 12 ) );
				remove_action( 'propertyhive_after_search_results_loop_item_title', 'propertyhive_template_loop_custom_field', $priority );
				if ( '' !== $custom_field ) {
					self::$search_custom_fields[ $priority ] = $custom_field;
					remove_action( 'propertyhive_after_search_results_loop_item_title', array( __CLASS__, 'render_configured_search_custom_field' ), $priority );
					add_action( 'propertyhive_after_search_results_loop_item_title', array( __CLASS__, 'render_configured_search_custom_field' ), $priority );
				}
			}
			$priority += 5;
		}
	}

	/**
	 * Render the single custom field assigned to the current configured slot.
	 */
	public static function render_configured_search_custom_field() {
		global $property, $wp_filter;

		$hook = 'propertyhive_after_search_results_loop_item_title';
		if ( ! $property || empty( $wp_filter[ $hook ] ) || ! is_a( $wp_filter[ $hook ], 'WP_Hook' ) ) {
			return;
		}

		$priority = $wp_filter[ $hook ]->current_priority();
		if ( ! isset( self::$search_custom_fields[ $priority ] ) ) {
			return;
		}

		$field = self::$search_custom_fields[ $priority ];
		$value = $property->{$field};
		$value = is_array( $value ) ? implode( ', ', array_filter( $value ) ) : $value;

		if ( '' !== trim( (string) $value ) ) {
			echo '<div class="custom-field custom-field-' . esc_attr( sanitize_title( trim( $field, '_' ) ) ) . '">' . esc_html( $value ) . '</div>';
		}
	}

	/**
	 * Render a linked result price so the image, address and price all lead to the property.
	 */
	public static function render_linked_card_price() {
		global $property;

		if ( ! $property ) {
			return;
		}

		$price = $property->get_formatted_price();
		if ( '' === trim( wp_strip_all_tags( (string) $price ) ) ) {
			return;
		}

		$fees = '';
		if ( 'yes' === get_option( 'propertyhive_lettings_fees_display_search_results', '' ) ) {
			if ( 'residential-lettings' === $property->department && '' !== get_option( 'propertyhive_lettings_fees', '' ) ) {
				$fees = nl2br( get_option( 'propertyhive_lettings_fees', '' ) );
			}

			if ( 'commercial' === $property->department && 'yes' === $property->to_rent && '' !== get_option( 'propertyhive_lettings_fees_commercial', '' ) ) {
				$fees = nl2br( get_option( 'propertyhive_lettings_fees_commercial', '' ) );
			}
		}

		$price_qualifier = '';
		if (
			(
				'residential-sales' === $property->department ||
				'residential-sales' === ph_get_custom_department_based_on( $property->department ) ||
				'commercial' === $property->department ||
				'commercial' === ph_get_custom_department_based_on( $property->department )
			) &&
			'' !== $property->price_qualifier
		) {
			$price_qualifier = $property->price_qualifier;
		}

		PH_Template_Set_Template_Loader::render(
			'search',
			self::get_search_template(),
			'linked-card-price',
			array(
				'property'        => $property,
				'price'           => $price,
				'price_qualifier' => $price_qualifier,
				'fees'            => $fees,
				'permalink'       => get_permalink(),
			)
		);
	}

	/**
	 * Remove a named callback wherever the saved search-result field order placed it.
	 *
	 * @param string $hook_name     Hook name.
	 * @param string $callback_name Callback function name.
	 */
	private static function remove_named_action_callbacks( $hook_name, $callback_name ) {
		global $wp_filter;

		if ( empty( $wp_filter[ $hook_name ] ) || ! is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {
			return array();
		}

		$priorities = array();

		foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( isset( $callback['function'] ) && $callback_name === $callback['function'] ) {
					$priorities[] = (int) $priority;
				}
			}
		}

		foreach ( array_unique( $priorities ) as $priority ) {
			remove_action( $hook_name, $callback_name, $priority );
		}

		return array_values( array_unique( $priorities ) );
	}

	/**
	 * Keep search type controls aligned to real searchable stock.
	 *
	 * @param array $fields Search form fields.
	 * @return array
	 */
	public static function prepare_search_form_fields( $fields ) {
		if ( ! PH_Template_Set_Request_Context::is_search_results_request() ) {
			return $fields;
		}

		foreach ( array( 'property_type', 'commercial_property_type' ) as $field_id ) {
			if ( empty( $fields[ $field_id ] ) || ! is_array( $fields[ $field_id ] ) ) {
				continue;
			}

			$fields[ $field_id ]['hide_empty']   = true;
			$fields[ $field_id ]['blank_option'] = __( 'All property types', 'propertyhive' );
		}

		return $fields;
	}

	/**
	 * Reject malformed scalar Map Search request state before add-ons query it.
	 */
	public static function sanitize_map_search_request_state() {
		foreach ( array( 'view', 'draw', 'pgp' ) as $key ) {
			if ( ! isset( $_REQUEST[ $key ] ) ) {
				continue;
			}

			$value = $_REQUEST[ $key ];
			$valid = is_scalar( $value );
			if ( $valid ) {
				$value = sanitize_text_field( wp_unslash( $value ) );
				$valid = 'view' === $key
					? in_array( $value, array( 'list', 'map' ), true )
					: '' !== $value && strlen( $value ) <= 20000;
			}

			if ( ! $valid ) {
				unset( $_GET[ $key ], $_POST[ $key ], $_REQUEST[ $key ] );
				continue;
			}

			$_REQUEST[ $key ] = $value;
			if ( isset( $_GET[ $key ] ) ) {
				$_GET[ $key ] = $value;
			}
			if ( isset( $_POST[ $key ] ) ) {
				$_POST[ $key ] = $value;
			}
		}
	}

	/**
	 * Preserve one validated copy of Map Search state on the main results form.
	 *
	 * The shortcode form passes a second argument and is deliberately excluded.
	 *
	 * @param array      $controls Form controls.
	 * @param array|null $shortcode_atts Shortcode attributes when applicable.
	 * @return array
	 */
	public static function normalize_map_search_hidden_fields( $controls, $shortcode_atts = null ) {
		if ( null !== $shortcode_atts || ! PH_Template_Set_Request_Context::is_search_results_request() || ! is_array( $controls ) ) {
			return $controls;
		}

		$state_keys = array( 'view', 'draw', 'pgp' );
		foreach ( $controls as $key => $control ) {
			$name = is_array( $control ) && isset( $control['name'] ) ? (string) $control['name'] : (string) $key;
			$base = preg_replace( '/(?:\\[\\]|-\\d+)$/', '', $name );
			if ( in_array( $base, $state_keys, true ) || in_array( preg_replace( '/-\\d+$/', '', (string) $key ), $state_keys, true ) ) {
				unset( $controls[ $key ] );
			}
		}

		foreach ( $state_keys as $key ) {
			if ( ! isset( $_REQUEST[ $key ] ) || ! is_scalar( $_REQUEST[ $key ] ) ) {
				continue;
			}

			$value = sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ) );
			if ( 'view' === $key ) {
				if ( ! in_array( $value, array( 'list', 'map' ), true ) ) {
					continue;
				}
			} elseif ( '' === $value || strlen( $value ) > 20000 ) {
				continue;
			}

			$controls[ $key ] = array(
				'type'  => 'hidden',
				'name'  => $key,
				'value' => $value,
			);
		}

		return $controls;
	}

	/**
	 * Match hidden empty-term checks to the currently selected department.
	 *
	 * @param array $query_args Empty-check query args.
	 * @param array $field      Form field config.
	 * @param int   $term_id    Term ID being checked.
	 * @return array
	 */
	public static function filter_search_taxonomy_empty_check_args( $query_args, $field, $term_id ) {
		if ( ! PH_Template_Set_Request_Context::is_search_results_request() || empty( $field['type'] ) ) {
			return $query_args;
		}

		if ( ! in_array( $field['type'], array( 'property_type', 'commercial_property_type' ), true ) ) {
			return $query_args;
		}

		$department = self::get_search_department_for_taxonomy( $field['type'] );

		if ( '' === $department && 'property_type' === $field['type'] && self::is_rooms_search_active() && 'rooms' === self::get_current_search_department() ) {
			$department = 'rooms';
		}

		if ( '' === $department ) {
			return $query_args;
		}

		if ( empty( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array();
		}

		$query_args['post_status']      = 'publish';
		$query_args['posts_per_page']  = 1;
		$query_args['no_found_rows']   = true;
		$query_args['meta_query'][]    = array(
			'key'     => '_department',
			'value'   => $department,
			'compare' => '=',
		);

		return $query_args;
	}

	/**
	 * Open search wrapper.
	 */
	public static function open_search_wrapper() {
		if ( ! PH_Template_Set_Request_Context::is_search_results_request() ) {
			return;
		}

		$settings     = self::get_settings();
		$presentation = PH_Template_Set_Request_Context::get_search_presentation();
		$map_state    = PH_Template_Set_Request_Context::get_map_search_state();
		$classes = array(
			'ph-template-set',
			'ph-template-search',
			'ph-search-template-' . sanitize_html_class( self::get_search_template() ),
			'ph-search-view-' . sanitize_html_class( self::get_search_view() ),
			'ph-search-layout-' . sanitize_html_class( $presentation['card_layout'] ),
			'ph-search-map-' . sanitize_html_class( $presentation['map_mode'] ),
			'ph-search-card-size-' . sanitize_html_class( $settings['template_set_search_card_size'] ),
			'ph-search-grid-columns-' . PH_Template_Set_Settings::get_search_grid_columns_for_template( self::get_search_template(), $settings ),
		);

		if ( $map_state['manages_archive'] ) {
			$classes[] = 'ph-template-map-search-active';
		}

		if ( $presentation['has_map'] ) {
			$classes[] = 'ph-template-map-search-real-map';
		}

		if ( in_array( $map_state['fallback_reason'], array( 'unavailable', 'unusable', 'unconfigured' ), true ) && ! self::can_manage_template_set() ) {
			$classes[] = 'ph-search-map-panel-suppressed';
		}

		if ( self::is_module_preview() ) {
			$classes[] = 'ph-template-module-preview-shell';
			$classes[] = 'ph-module-template-' . sanitize_html_class( self::get_module_template() );
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
	}

	/**
	 * Close search wrapper.
	 */
	public static function close_search_wrapper() {
		if ( ! PH_Template_Set_Request_Context::is_search_results_request() ) {
			return;
		}

		echo '</div>';
	}

	/**
	 * Render search tools.
	 */
	public static function render_search_tools() {
		if ( ! PH_Template_Set_Request_Context::is_search_results_request() ) {
			return;
		}

		if ( ! self::uses_prototype_search_chrome() ) {
			return;
		}

		global $wp_query;

		$state                  = PH_Template_Set_Request_Context::get_map_search_state();
		$map_view               = ! empty( $state['manages_archive'] ) && 'map' === $state['requested_view'];
		$ordering_markup        = '';
		$save_search_markup     = self::get_toolbar_save_search_markup();
		$shortlist_enquiry      = self::get_shortlist_enquiry_markup();

		if ( ! $map_view && function_exists( 'propertyhive_catalog_ordering' ) ) {
			ob_start();
			propertyhive_catalog_ordering();
			$ordering_markup = (string) ob_get_clean();
		}

		PH_Template_Set_Template_Loader::render(
			'search',
			self::get_search_template(),
			'results-toolbar',
			array(
				'template'           => self::get_search_template(),
				'total'              => $wp_query instanceof WP_Query ? absint( $wp_query->found_posts ) : 0,
				'ordering_markup'    => $ordering_markup,
				'show_count'         => ! $map_view,
				'is_shortlist_view'  => self::is_shortlist_view(),
				'map_state'          => $state,
				'map_toggle_url'     => self::get_map_toggle_url( $state ),
				'save_search_button' => $save_search_markup['button'],
				'save_search_popup'  => $save_search_markup['popup'],
				'shortlist_button'   => $shortlist_enquiry['button'],
				'shortlist_popup'    => $shortlist_enquiry['popup'],
			)
		);
	}

	/**
	 * Move the Save Search add-on's existing control into the prototype toolbar.
	 *
	 * Calling the add-on callback preserves its script enqueue, localized data,
	 * popup markup and established JavaScript selectors.
	 *
	 * @return array
	 */
	private static function get_toolbar_save_search_markup() {
		$markup = array(
			'button' => '',
			'popup'  => '',
		);

		if ( ! self::uses_prototype_search_chrome() || ! class_exists( 'PH_Save_Search' ) ) {
			return $markup;
		}

		$callback = array( PH_Save_Search::instance(), 'save_search_button' );
		$priority = has_action( 'propertyhive_before_search_results_loop', $callback );

		if ( false === $priority || ! is_callable( $callback ) ) {
			return $markup;
		}

		remove_action( 'propertyhive_before_search_results_loop', $callback, $priority );

		ob_start();
		call_user_func( $callback );
		$add_on_markup = (string) ob_get_clean();

		if ( ! preg_match( '/(<a\b(?=[^>]*\bpropertyhive-save-search-button\b)[^>]*>.*?<\/a>)(.*)\z/is', $add_on_markup, $matches ) ) {
			add_action( 'propertyhive_before_search_results_loop', $callback, $priority );
			return $markup;
		}

		$bell_icon = '<svg class="ph-template-save-search-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"></path></svg>';

		$markup['button'] = (string) preg_replace_callback(
			'/<a\b[^>]*>/i',
			static function ( $opening_tag ) use ( $bell_icon ) {
				return $opening_tag[0] . $bell_icon;
			},
			$matches[1],
			1
		);
		$markup['popup'] = $matches[2];

		return $markup;
	}

	/**
	 * Move the Shortlist mass-enquiry control into the prototype toolbar.
	 *
	 * @return array
	 */
	private static function get_shortlist_enquiry_markup() {
		$markup = array(
			'button' => '',
			'popup'  => '',
		);

		if ( ! self::is_shortlist_view() ) {
			return $markup;
		}

		$callback = array( PH_Shortlist::instance(), 'shortlist_enquiry_button' );
		$priority = has_action( 'propertyhive_before_search_results_loop', $callback );

		if ( false === $priority || ! is_callable( $callback ) ) {
			return $markup;
		}

		remove_action( 'propertyhive_before_search_results_loop', $callback, $priority );

		ob_start();
		call_user_func( $callback );
		$add_on_markup = (string) ob_get_clean();

		if ( ! preg_match( '/(<a\b(?=[^>]*\bpropertyhive-shortlist-enquiry-button\b)[^>]*>.*?<\/a>)(.*)\z/is', $add_on_markup, $matches ) ) {
			add_action( 'propertyhive_before_search_results_loop', $callback, $priority );
			return $markup;
		}

		$markup['button'] = (string) preg_replace(
			"/\\bclass=([\"'])([^\"']*)\\1/i",
			'class=$1$2 ph-template-shortlist-enquiry-button$1',
			$matches[1],
			1
		);
		$markup['popup'] = $matches[2];

		return $markup;
	}

	/**
	 * Render a template-specific search header.
	 */
	public static function render_search_template_intro() {
		if ( ! PH_Template_Set_Request_Context::is_search_results_request() ) {
			return;
		}

		if ( self::is_module_preview() ) {
			return;
		}

		$state = PH_Template_Set_Request_Context::get_map_search_state();
		if ( self::is_shortlist_view() || ( ! empty( $state['manages_archive'] ) && 'map' === $state['requested_view'] ) ) {
			return;
		}

		$template = self::get_search_template();
		$content  = self::get_search_intro_content( $template );
		global $wp_query;

		if ( empty( $content ) ) {
			return;
		}

		PH_Template_Set_Template_Loader::render(
			'search',
			$template,
			'intro',
			array(
				'template' => $template,
				'content'  => $content,
				'total'    => $wp_query instanceof WP_Query ? absint( $wp_query->found_posts ) : 0,
			)
		);
	}

	/**
	 * Render the decorative Map Atlas rail when Map Search is unavailable.
	 */
	public static function render_map_fallback_panel() {
		if ( ! PH_Template_Set_Request_Context::is_search_results_request() || self::is_module_preview() || 'map-led-search-results' !== self::get_search_template() ) {
			return;
		}

		$state = PH_Template_Set_Request_Context::get_map_search_state();
		if ( $state['shows_real_map'] ) {
			return;
		}

		if ( in_array( $state['fallback_reason'], array( 'unavailable', 'unusable', 'unconfigured' ), true ) && ! self::can_manage_template_set() ) {
			return;
		}

		global $wp_query;
		$properties  = array();
		$area_labels = array();
		$positions   = array(
			array( 54, 42 ),
			array( 70, 57 ),
			array( 79, 30 ),
			array( 47, 48 ),
			array( 64, 64 ),
			array( 85, 45 ),
		);

		if ( $wp_query instanceof WP_Query && ! empty( $wp_query->posts ) ) {
			foreach ( array_slice( $wp_query->posts, 0, count( $positions ) ) as $index => $post ) {
				$property = new PH_Property( $post );
				$price    = trim( wp_strip_all_tags( (string) $property->get_formatted_price( true ) ) );
				$area     = self::get_map_property_area( $property );
				$properties[] = array(
					'id'       => absint( $property->id ),
					'title'    => get_the_title( $property->id ),
					'price'    => '' !== $price ? self::abbreviate_map_pin_price( $price ) : __( 'Price on request', 'propertyhive' ),
					'position' => $positions[ $index ],
				);

				if ( '' !== $area && ! in_array( $area, $area_labels, true ) && count( $area_labels ) < 3 ) {
					$area_labels[] = $area;
				}
			}
		}

		PH_Template_Set_Template_Loader::render(
			'search',
			'map-led-search-results',
			'map-panel',
			array(
				'properties'  => $properties,
				'area_labels' => $area_labels,
				'state'       => $state,
			)
		);
	}

	/**
	 * Abbreviate a numeric price for a compact decorative map pin.
	 *
	 * @param string $formatted_price Formatted property price.
	 * @return string
	 */
	private static function abbreviate_map_pin_price( $formatted_price ) {
		$price       = html_entity_decode( trim( wp_strip_all_tags( (string) $formatted_price ) ), ENT_QUOTES, 'UTF-8' );
		$from_label  = __( 'From', 'propertyhive' );
		$from_prefix = '';

		if ( 0 === stripos( $price, $from_label . ' ' ) ) {
			$from_prefix = $from_label . ' ';
			$price       = trim( substr( $price, strlen( $from_label ) ) );
		}

		if ( ! preg_match( '/^([£$€])\s*([0-9][0-9,]*(?:\.[0-9]+)?)$/u', $price, $matches ) ) {
			return trim( wp_strip_all_tags( (string) $formatted_price ) );
		}

		$currency = $matches[1];
		$amount   = (float) str_replace( ',', '', $matches[2] );

		if ( $amount >= 1000000 ) {
			$abbreviated = rtrim( rtrim( number_format( $amount / 1000000, 2, '.', '' ), '0' ), '.' ) . 'm';
		} elseif ( $amount >= 1000 ) {
			$abbreviated = number_format( round( $amount / 1000 ), 0, '.', '' ) . 'k';
		} else {
			return trim( wp_strip_all_tags( (string) $formatted_price ) );
		}

		return $from_prefix . $currency . $abbreviated;
	}

	/**
	 * Render a lightweight map panel for map-led layouts.
	 */
	public static function render_map_dependency_notice() {
		if ( ! PH_Template_Set_Request_Context::is_search_results_request() ) {
			return;
		}

		if ( self::is_module_preview() || 'map-led-search-results' !== self::get_search_template() || ! self::can_manage_template_set() ) {
			return;
		}

		$state = PH_Template_Set_Request_Context::get_map_search_state();
		if ( $state['shows_real_map'] ) {
			return;
		}

		PH_Template_Set_Template_Loader::render(
			'search',
			'map-led-search-results',
			'map-dependency',
			array(
				'state' => $state,
			)
		);
	}

	/**
	 * Enter the main archive card render seam.
	 */
	public static function begin_main_search_result_render() {
		if ( PH_Template_Set_Request_Context::is_search_results_request() ) {
			self::$search_render_contexts[] = 'main-search-result';
		}
	}

	/**
	 * Leave the main archive card render seam.
	 */
	public static function end_main_search_result_render() {
		if ( 'main-search-result' === end( self::$search_render_contexts ) ) {
			array_pop( self::$search_render_contexts );
		}
	}

	/**
	 * Enter the Infinite Scroll AJAX card render seam.
	 *
	 * @param string $template Located template.
	 * @param string $slug     Template slug.
	 * @param string $name     Template name.
	 */
	public static function begin_infinite_search_result_render( $template, $slug, $name ) {
		if ( ! self::is_infinite_scroll_property_render( $slug, $name ) || ! self::is_enabled() ) {
			return;
		}

		self::$search_render_contexts[] = 'infinite-search-result';

		if ( ! self::$infinite_search_hooks_prepared ) {
			if ( function_exists( 'template_assistant_search_result_field_changes' ) ) {
				template_assistant_search_result_field_changes();
			}
			self::normalize_configured_search_custom_fields( true );
			self::prepare_search_result_cards();
			self::$infinite_search_hooks_prepared = true;
		}
	}

	/**
	 * Leave the Infinite Scroll AJAX card render seam.
	 *
	 * @param string $template Located template.
	 * @param string $slug     Template slug.
	 * @param string $name     Template name.
	 */
	public static function end_infinite_search_result_render( $template, $slug, $name ) {
		if ( self::is_infinite_scroll_property_render( $slug, $name ) && 'infinite-search-result' === end( self::$search_render_contexts ) ) {
			array_pop( self::$search_render_contexts );
		}
	}

	/**
	 * Is a permitted Template Set card currently rendering?
	 *
	 * @return bool
	 */
	private static function is_search_card_rendering() {
		return in_array( end( self::$search_render_contexts ), array( 'main-search-result', 'infinite-search-result' ), true );
	}

	/**
	 * Is the current generic render the Infinite Scroll result card action?
	 *
	 * @param string $slug Template slug.
	 * @param string $name Template name.
	 * @return bool
	 */
	private static function is_infinite_scroll_property_render( $slug, $name ) {
		$action = isset( $_REQUEST['action'] ) && is_scalar( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

		return wp_doing_ajax() && 'propertyhive_infinite_load_properties' === $action && 'content' === $slug && 'property' === $name;
	}

	/**
	 * Prevent Infinite Scroll attaching where its paging/map state is unsafe.
	 */
	public static function suppress_infinite_scroll_when_unsafe() {
		if ( ! function_exists( 'PHIS' ) || ! PH_Template_Set_Request_Context::is_search_results_request() ) {
			return;
		}

		$presentation = PH_Template_Set_Request_Context::get_search_presentation();
		$paged        = max( 1, absint( get_query_var( 'paged' ) ), absint( get_query_var( 'page' ) ) );
		$unsaved      = self::is_previewing_template() || self::is_template_editor_active();

		if ( in_array( $presentation['map_mode'], array( 'split', 'map-only' ), true ) || $paged > 1 || $unsaved ) {
			remove_action( 'wp', array( PHIS(), 'do_load_infinite_scroll' ), 20 );
		}
	}

	/**
	 * Keep presentation/navigation state out of saved property criteria.
	 *
	 * @param array $params Save Search parameters.
	 * @return array
	 */
	public static function filter_save_search_params( $params ) {
		if ( ! PH_Template_Set_Request_Context::is_search_results_request() || ! is_array( $params ) ) {
			return $params;
		}

		$keys = array_merge(
			PH_Template_Set_Request_Context::get_preview_clear_query_args(),
			array( 'ph_view', 'view', 'paged', 'page', 'orderby' )
		);

		foreach ( array_unique( $keys ) as $key ) {
			unset( $params[ $key ] );
			if ( isset( $params['get_params'] ) && is_array( $params['get_params'] ) ) {
				unset( $params['get_params'][ $key ] );
			}
		}

		return $params;
	}

	/**
	 * Render the featured/homepage module preview on the property archive.
	 */
	public static function render_module_preview() {
		if ( ! self::is_enabled() || ! self::is_module_preview() ) {
			return;
		}

		echo '<section class="ph-template-module-preview">';
			echo '<div class="ph-template-module-preview-copy">';
				echo '<span>' . esc_html__( 'Property search', 'propertyhive' ) . '</span>';
				echo '<h1>' . esc_html__( 'Find your next home', 'propertyhive' ) . '</h1>';
				echo '<p>' . esc_html__( 'Search by location, price and type, or browse a selection of homes currently available through our offices.', 'propertyhive' ) . '</p>';
			echo '</div>';
			echo self::featured_template_shortcode(
				array(
					'title'       => __( 'Featured properties', 'propertyhive' ),
					'intro'       => __( 'A selection of homes currently available through our offices.', 'propertyhive' ),
					'show_search' => 'yes',
					'source'      => 'properties',
					'per_page'    => 3,
					'columns'     => 3,
				)
			);
		echo '</section>';
	}

	/**
	 * Hide archive results when rendering only a module preview.
	 *
	 * @param bool $show_results Existing state.
	 * @return bool
	 */
	public static function maybe_hide_results_for_module_preview( $show_results ) {
		return self::is_module_preview() ? false : $show_results;
	}

	/**
	 * Hide the archive title when template-owned content supplies the heading.
	 *
	 * @param bool $show_title Existing state.
	 * @return bool
	 */
	public static function maybe_hide_title_for_module_preview( $show_title ) {
		if ( self::is_module_preview() ) {
			return false;
		}

		if ( PH_Template_Set_Request_Context::is_search_results_request() && 'map-led-search-results' === self::get_search_template() && ! self::is_shortlist_view() ) {
			return false;
		}

		return $show_title;
	}

	/**
	 * Render card badges over thumbnails.
	 */
	public static function render_card_badges() {
		if ( ! self::should_render_card_extras() ) {
			return;
		}

		$settings = self::get_settings();
		if ( 'yes' !== $settings['template_set_show_badges'] && ! self::is_template_editor_active() ) {
			return;
		}

		global $property;

		if ( ! $property ) {
			return;
		}

		$badges = array();

		if ( 'yes' === $property->featured ) {
			$badges[] = __( 'Featured', 'propertyhive' );
		}

		$template_assistant = get_option( 'propertyhive_template_assistant', array() );
		$legacy_flag_active = is_array( $template_assistant ) && isset( $template_assistant['flags_active'] ) && '1' === (string) $template_assistant['flags_active'];
		$configured_fields  = is_array( $template_assistant ) && isset( $template_assistant['search_result_fields'] ) && is_array( $template_assistant['search_result_fields'] )
			? $template_assistant['search_result_fields']
			: array();
		if ( ! $legacy_flag_active ) {
			if ( $property->availability && ! in_array( 'availability', $configured_fields, true ) ) {
				$badges[] = $property->availability;
			}

			if ( $property->marketing_flag && ! in_array( 'marketing_flag', $configured_fields, true ) ) {
				$badges[] = $property->marketing_flag;
			}
		}

		$badges = apply_filters( 'propertyhive_template_set_search_badges', $badges, $property, self::get_search_template() );
		$badges = array_slice( array_unique( array_filter( is_array( $badges ) ? $badges : array() ) ), 0, 2 );

		if ( empty( $badges ) ) {
			return;
		}

		PH_Template_Set_Template_Loader::render(
			'search',
			self::get_search_template(),
			'card-badges',
			array(
				'property' => $property,
				'badges'   => $badges,
				'template' => self::get_search_template(),
			)
		);
	}

	/**
	 * Give an otherwise empty linked thumbnail an accessible name.
	 */
	public static function render_missing_card_image_label() {
		if ( ! self::should_render_card_extras() ) {
			return;
		}

		global $property;
		if ( ! $property || false !== $property->get_main_photo_src( apply_filters( 'property_search_results_thumbnail_size', 'medium' ) ) || ph_placeholder_img_src() ) {
			return;
		}

		echo '<span class="screen-reader-text">' . esc_html__( 'View property details', 'propertyhive' ) . '</span>';
	}

	/**
	 * Render card footer with branch contact and facts.
	 */
	public static function render_card_footer() {
		if ( ! self::should_render_card_extras() ) {
			return;
		}

		global $property;

		if ( ! $property ) {
			return;
		}

		$settings = self::get_settings();
		$facts    = self::get_fact_items( $property, self::get_search_fact_limit() );

		if ( 'map-led-search-results' === self::get_search_template() ) {
			$facts = array_values(
				array_filter(
					$facts,
					static function ( $fact ) {
						return is_array( $fact ) && ! empty( $fact['quantity'] );
					}
				)
			);
			$facts = array_slice( $facts, 0, 2 );
		}

		$phone            = $property->get_negotiator_telephone_number();
		$show_branch      = 'yes' === $settings['template_set_show_branch'] || self::is_template_editor_active();
		$shortlist_button    = self::get_shortlist_button_markup();
		$portal_attribution  = self::get_portal_card_attribution( $property );
		$has_card_meta_hooks = false !== has_action( 'propertyhive_template_set_card_meta' );

		if ( empty( $facts ) && ! $show_branch && '' === $shortlist_button && empty( $portal_attribution ) && ! $has_card_meta_hooks ) {
			return;
		}

		$office = trim( wp_strip_all_tags( (string) $property->get_office_name() ) );
		if ( preg_match( '/^(demo|example|test)(\s|$)/i', $office ) ) {
			$office = '';
		}

		PH_Template_Set_Template_Loader::render(
			'search',
			self::get_search_template(),
			'card-footer',
			array(
				'property'         => $property,
				'facts'            => $facts,
				'phone'            => $phone,
				'office'           => $office,
				'show_branch'      => $show_branch,
				'shortlist_button' => $shortlist_button,
				'portal_attribution' => $portal_attribution,
				'template'         => self::get_search_template(),
			)
		);
	}

	/**
	 * Whether the request is the Shortlist archive view.
	 *
	 * @return bool
	 */
	private static function is_shortlist_view() {
		return class_exists( 'PH_Shortlist' )
			&& self::is_add_on_usable( 'propertyhive-shortlist' )
			&& isset( $_REQUEST['shortlisted'] )
			&& 1 == $_REQUEST['shortlisted']; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- Matches the add-on's request gate.
	}

	/**
	 * Whether Rooms has registered its search department and fields.
	 *
	 * @return bool
	 */
	private static function is_rooms_search_active() {
		return class_exists( 'PH_Rooms' ) && self::is_add_on_usable( 'propertyhive-rooms' );
	}

	/**
	 * Whether Portal attribution may be rendered for this request.
	 *
	 * @return bool
	 */
	private static function is_portal_card_attribution_enabled() {
		return class_exists( 'PH_Property_Portal' ) && self::is_add_on_usable( 'propertyhive-property-portal' );
	}

	/**
	 * Get one property's already-primed Property Portal attribution data.
	 *
	 * @param PH_Property $property Property being rendered.
	 * @return array
	 */
	private static function get_portal_card_attribution( $property ) {
		if ( ! self::is_portal_card_attribution_enabled() || ! is_object( $property ) || empty( $property->id ) ) {
			return array();
		}

		$agent_id  = absint( get_post_meta( $property->id, '_agent_id', true ) );
		$branch_id = absint( get_post_meta( $property->id, '_branch_id', true ) );
		$logo_id   = $agent_id ? absint( get_post_meta( $agent_id, '_logo', true ) ) : 0;
		$agent     = $agent_id ? get_post( $agent_id ) : null;
		$branch    = $branch_id ? get_post( $branch_id ) : null;

		$attribution = array_filter(
			array(
				'logo_id'     => $logo_id,
				'agent_name'  => $agent instanceof WP_Post ? wp_strip_all_tags( $agent->post_title ) : '',
				'branch_name' => $branch instanceof WP_Post ? wp_strip_all_tags( $branch->post_title ) : '',
			)
		);

		return $attribution;
	}

	/**
	 * Prime Portal attribution posts and metadata for a full card set.
	 *
	 * @param array $property_ids Property post IDs.
	 * @return void
	 */
	private static function prime_portal_card_attribution( $property_ids ) {
		$property_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $property_ids ) ) ) );

		if ( empty( $property_ids ) ) {
			return;
		}

		self::prime_portal_post_caches( $property_ids, 'property' );

		$agent_ids  = array();
		$branch_ids = array();
		foreach ( $property_ids as $property_id ) {
			$agent_id  = absint( get_post_meta( $property_id, '_agent_id', true ) );
			$branch_id = absint( get_post_meta( $property_id, '_branch_id', true ) );

			if ( $agent_id ) {
				$agent_ids[] = $agent_id;
			}

			if ( $branch_id ) {
				$branch_ids[] = $branch_id;
			}
		}

		$related_ids = array_values( array_unique( array_merge( $agent_ids, $branch_ids ) ) );
		if ( empty( $related_ids ) ) {
			return;
		}

		self::prime_portal_post_caches( $related_ids );

		$logo_ids = array();
		foreach ( array_unique( $agent_ids ) as $agent_id ) {
			$logo_id = absint( get_post_meta( $agent_id, '_logo', true ) );
			if ( $logo_id ) {
				$logo_ids[] = $logo_id;
			}
		}

		if ( ! empty( $logo_ids ) ) {
			self::prime_portal_post_caches( $logo_ids, 'attachment' );
		}
	}

	/**
	 * Prime post objects and metadata in one request-level batch.
	 *
	 * @param array  $post_ids Post IDs.
	 * @param string $post_type Expected post type.
	 * @return void
	 */
	private static function prime_portal_post_caches( $post_ids, $post_type = 'any' ) {
		$post_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $post_ids ) ) ) );
		if ( empty( $post_ids ) ) {
			return;
		}

		get_posts(
			array(
				'post_type'              => $post_type,
				'post_status'            => 'attachment' === $post_type ? 'inherit' : 'any',
				'post__in'               => $post_ids,
				'posts_per_page'         => count( $post_ids ),
				'orderby'                => 'post__in',
				'no_found_rows'          => true,
				'suppress_filters'       => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);
		update_meta_cache( 'post', $post_ids );
	}

	/**
	 * Prime Infinite Scroll's page before its priority-10 renderer starts.
	 *
	 * @return void
	 */
	public static function prime_infinite_scroll_portal_attribution() {
		if ( ! self::is_portal_card_attribution_enabled() ) {
			return;
		}

		self::restore_infinite_scroll_request_state();
		self::prime_portal_card_attribution( self::get_infinite_scroll_property_ids() );
	}

	/**
	 * Mirror Infinite Scroll's query-string state restoration before rebuilding its page.
	 *
	 * @return void
	 */
	private static function restore_infinite_scroll_request_state() {
		if ( empty( $_POST['query_string'] ) || ! is_scalar( $_POST['query_string'] ) ) {
			return;
		}

		$request = json_decode( stripslashes( $_POST['query_string'] ), true );
		if ( is_array( $request ) ) {
			$_REQUEST = array_merge( $_REQUEST, $request );
			$_GET     = array_merge( $_GET, $request );
		}
	}

	/**
	 * Reconstruct the property IDs that Infinite Scroll will render next.
	 *
	 * @return array
	 */
	private static function get_infinite_scroll_property_ids() {
		if ( ! empty( $_POST['query_transient'] ) && is_scalar( $_POST['query_transient'] ) ) {
			global $wpdb;

			$query = get_transient( sanitize_text_field( wp_unslash( $_POST['query_transient'] ) ) );
			if ( is_string( $query ) && '' !== $query ) {
				$per_page = isset( $_POST['posts_per_page'] ) ? absint( $_POST['posts_per_page'] ) : 0;
				$paged    = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;

				if ( $per_page ) {
					$limit = $per_page * ( $paged - 1 );
					$results = $wpdb->get_results( $query . ' LIMIT ' . $limit . ', ' . $per_page ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- The trusted transient is generated by Infinite Scroll.
					return array_map( 'absint', wp_list_pluck( (array) $results, 'ID' ) );
				}
			}

			return array();
		}

		if ( empty( $_POST['query_vars'] ) || ! is_scalar( $_POST['query_vars'] ) ) {
			return array();
		}

		$query_vars = json_decode( stripslashes( $_POST['query_vars'] ), true );
		if ( ! is_array( $query_vars ) ) {
			return array();
		}

		if ( isset( $_POST['paged'] ) ) {
			$query_vars['paged'] = absint( $_POST['paged'] );
		}

		foreach ( $query_vars as $key => $value ) {
			if ( taxonomy_exists( $key ) ) {
				unset( $query_vars[ $key ] );
			}
		}

		$query_vars['post_status']            = 'publish';
		$query_vars['post_password']          = '';
		$query_vars['fields']                 = 'ids';
		$query_vars['no_found_rows']          = true;
		$query_vars['update_post_meta_cache'] = false;
		$query_vars['update_post_term_cache'] = false;

		$query = new WP_Query( $query_vars );

		return array_map( 'absint', (array) $query->posts );
	}

	/**
	 * Check whether a premium add-on can be used on this install.
	 *
	 * @param string $slug Add-on slug.
	 * @return bool
	 */
	public static function is_add_on_usable( $slug ) {
		$slug = sanitize_key( $slug );

		if ( '' === $slug ) {
			return false;
		}

		return apply_filters( 'propertyhive_add_on_can_be_used', true, $slug ) !== false;
	}

	/**
	 * Is Map Search active and configured to control the search map UI.
	 *
	 * @return bool
	 */
	private static function is_map_search_managing_search_view() {
		if ( ! class_exists( 'PH_Map_Search' ) || ! self::is_add_on_usable( 'propertyhive-map-search' ) ) {
			return false;
		}

		return in_array( self::get_map_search_format(), array( 'view', 'split' ), true );
	}

	/**
	 * Is a real Map Search map expected on the current results page.
	 *
	 * @return bool
	 */
	private static function is_real_map_search_view() {
		if ( ! self::is_map_search_managing_search_view() ) {
			return false;
		}

		$format = self::get_map_search_format();

		if ( 'split' === $format ) {
			return true;
		}

		$view = isset( $_GET['view'] ) ? sanitize_title( wp_unslash( $_GET['view'] ) ) : '';

		return 'map' === $view;
	}

	/**
	 * Get the Map Search display format.
	 *
	 * @return string
	 */
	private static function get_map_search_format() {
		$settings = get_option( 'propertyhive_map_search', array() );

		if ( ! is_array( $settings ) || empty( $settings['format'] ) ) {
			return '';
		}

		return sanitize_key( $settings['format'] );
	}

	/**
	 * Get a Shortlist add-on button for search result cards / detail panels.
	 *
	 * @param string $class  Anchor class attribute.
	 * @param array  $labels Optional add/remove labels: keys `add`, `remove`.
	 * @return string
	 */
	private static function get_shortlist_button_markup( $class = 'ph-template-shortlist-button', $labels = array() ) {
		if ( ! class_exists( 'PH_Shortlist' ) || ! self::is_add_on_usable( 'propertyhive-shortlist' ) || ! shortcode_exists( 'shortlist_button' ) ) {
			return '';
		}

		$class = trim( (string) $class );

		if ( '' === $class ) {
			$class = 'ph-template-shortlist-button';
		}

		$markup = do_shortcode( '[shortlist_button class="' . esc_attr( $class ) . '"]' );

		if ( '' === $markup || empty( $labels ) || ! is_array( $labels ) ) {
			return $markup;
		}

		$default_add    = __( 'Add To Shortlist', 'propertyhive' );
		$default_remove = __( 'Remove From Shortlist', 'propertyhive' );
		$add_label      = isset( $labels['add'] ) ? (string) $labels['add'] : $default_add;
		$remove_label   = isset( $labels['remove'] ) ? (string) $labels['remove'] : $default_remove;

		// Replace visible label text even when the shortcode wraps it in extra markup.
		$markup = preg_replace(
			'/(>(?:\s|&nbsp;)*)' . preg_quote( $default_add, '/' ) . '((?:\s|&nbsp;)*<\/)/u',
			'$1' . $add_label . '$2',
			$markup,
			1
		);
		$markup = preg_replace(
			'/(>(?:\s|&nbsp;)*)' . preg_quote( $default_remove, '/' ) . '((?:\s|&nbsp;)*<\/)/u',
			'$1' . $remove_label . '$2',
			$markup,
			1
		);
		$markup = str_replace(
			array( $default_add, $default_remove ),
			array( $add_label, $remove_label ),
			$markup
		);

		// Keep AJAX toggle labels in sync with custom microcopy.
		// Prefer late localization so later default shortlist buttons do not clobber cinema labels.
		$localize = static function() use ( $add_label, $remove_label ) {
			wp_localize_script(
				'ph-shortlist',
				'propertyhive_shortlist',
				array(
					'ajax_url'         => admin_url( 'admin-ajax.php' ),
					'add_link_text'    => $add_label,
					'remove_link_text' => $remove_label,
					'loading_text'     => __( 'Loading', 'propertyhive' ),
				)
			);
		};
		$localize();
		// Late footer pass so default shortlist localizations cannot clobber cinema labels.
		if ( ! has_action( 'wp_print_footer_scripts', $localize ) ) {
			add_action( 'wp_print_footer_scripts', $localize, 99 );
		}

		return $markup;
	}

	/**
	 * Whether the Send To Friend add-on is available for a Share control.
	 *
	 * @return bool
	 */
	private static function is_send_to_friend_available() {
		if ( shortcode_exists( 'send_to_friend_form' ) && self::is_add_on_usable( 'propertyhive-send-to-friend' ) ) {
			return true;
		}

		if ( class_exists( 'PH_Send_To_Friend' ) || class_exists( 'PH_Send_To_A_Friend' ) ) {
			return self::is_add_on_usable( 'propertyhive-send-to-friend' );
		}

		return false;
	}

	/**
	 * Get Share markup for the Send To Friend target captured by the template-set
	 * detail actions surface. It never invokes the actions filter itself.
	 *
	 * @param string $class Anchor class attribute.
	 * @return string
	 */
	private static function get_share_button_markup( $class = 'ph-template-button ph-template-button-secondary ph-template-share-button' ) {
		if ( ! self::is_send_to_friend_available() ) {
			return '';
		}

		$property = self::get_current_property();
		$post_id  = $property && ! empty( $property->id ) ? absint( $property->id ) : absint( get_queried_object_id() );

		if ( ! $post_id ) {
			return '';
		}

		if ( ! self::detail_actions_has_send_to_friend_form( $post_id ) ) {
			return '';
		}

		$class = trim( (string) $class );

		if ( '' === $class ) {
			$class = 'ph-template-button ph-template-button-secondary ph-template-share-button';
		}

		return '<a href="javascript:;" class="' . esc_attr( $class ) . '" data-fancybox data-src="#sendToFriend' . absint( $post_id ) . '"'
			. '>' . esc_html__( 'Share', 'propertyhive' ) . '</a>';
	}

	/**
	 * Get fact limit for current search template.
	 *
	 * @return int
	 */
	private static function get_search_fact_limit() {
		$template = self::get_search_template();

		if ( 'compact-list-search-results' === $template ) {
			return 3;
		}

		if ( 'map-led-search-results' === $template ) {
			return 3;
		}

		if ( 'brand-led-agency-search-results' === $template ) {
			return 4;
		}

		return 5;
	}

	/**
	 * Whether the selected template replaces the default count/order chrome.
	 *
	 * @return bool
	 */
	private static function uses_prototype_search_chrome() {
		return in_array( self::get_search_template(), array( 'portal-grid-search-results', 'map-led-search-results' ), true );
	}

	/**
	 * Build a Map Search view-switch link without discarding active criteria.
	 *
	 * @param array $state Normalized Map Search state.
	 * @return string
	 */
	private static function get_map_toggle_url( $state ) {
		if ( empty( $state['shows_view_switch'] ) ) {
			return '';
		}

		$args = $_GET;
		unset( $args['paged'], $args['page'] );
		$args['view'] = 'map' === $state['requested_view'] ? 'list' : 'map';

		return add_query_arg( array_map( 'wp_unslash', $args ), get_post_type_archive_link( 'property' ) );
	}

	/**
	 * Get a searched location for the Map Atlas eyebrow.
	 *
	 * @param string $fallback Default translated eyebrow.
	 * @return string
	 */
	private static function get_map_search_kicker( $fallback ) {
		if ( empty( $_GET['address_keyword'] ) ) {
			return $fallback;
		}

		$keyword = wp_unslash( $_GET['address_keyword'] );
		if ( is_array( $keyword ) ) {
			$locations = array();
			foreach ( array_slice( $keyword, 0, 3 ) as $location ) {
				if ( is_scalar( $location ) ) {
					$location = sanitize_text_field( (string) $location );
					if ( '' !== $location ) {
						$locations[] = $location;
					}
				}
			}
			$keyword = implode( ', ', $locations );
		} elseif ( is_scalar( $keyword ) ) {
			$keyword = sanitize_text_field( (string) $keyword );
		} else {
			$keyword = '';
		}

		return '' !== $keyword ? $keyword : $fallback;
	}

	/**
	 * Get search intro content for the active search template.
	 *
	 * @param string $template Template slug.
	 * @return array
	 */
	private static function get_search_intro_content( $template ) {
		$content = array(
			'portal-style-search-results'     => array(
				'kicker' => __( 'Property search', 'propertyhive' ),
				'title'  => __( 'Homes matching your search', 'propertyhive' ),
				'body'   => __( 'Compare listings with filters, sorting and a clear route into map view.', 'propertyhive' ),
				'items'  => array(
					__( 'Refine', 'propertyhive' ),
					__( 'Compare homes', 'propertyhive' ),
					__( 'Map view', 'propertyhive' ),
				),
			),
			'portal-grid-search-results'      => array(
				'kicker' => __( 'Property search', 'propertyhive' ),
				'title'  => __( 'Homes for sale in', 'propertyhive' ),
				'body'   => __( 'Explore properties selected around the way you want to live.', 'propertyhive' ),
				'items'  => array(
					__( 'Photography', 'propertyhive' ),
					__( 'Price', 'propertyhive' ),
					__( 'Key facts', 'propertyhive' ),
				),
			),
			'brand-led-agency-search-results' => array(
				'kicker' => __( 'Selected homes', 'propertyhive' ),
				'title'  => __( 'Browse our latest properties', 'propertyhive' ),
				'body'   => __( 'A calmer view of current stock with larger photography and the key facts kept close to each home.', 'propertyhive' ),
				'items'  => array(
					__( 'Featured stock', 'propertyhive' ),
					__( 'Larger photos', 'propertyhive' ),
					__( 'Branch contact', 'propertyhive' ),
				),
			),
			'map-led-search-results'          => array(
				'kicker' => __( 'Explore the area', 'propertyhive' ),
				'title'  => __( 'places to call home', 'propertyhive' ),
				'body'   => __( 'Browse a focused property list and, when Map Search is ready, explore the same results on a live map.', 'propertyhive' ),
				'items'  => array(
					__( 'Map first', 'propertyhive' ),
					__( 'List beside map', 'propertyhive' ),
					__( 'Location context', 'propertyhive' ),
				),
			),
			'compact-list-search-results'     => array(
				'kicker' => __( 'Quick comparison', 'propertyhive' ),
				'title'  => __( 'Scan the shortlist', 'propertyhive' ),
				'body'   => __( 'A tighter list for comparing price, location and core facts with less scrolling.', 'propertyhive' ),
				'items'  => array(
					__( 'Compact rows', 'propertyhive' ),
					__( 'Core facts', 'propertyhive' ),
					__( 'Fast browsing', 'propertyhive' ),
				),
			),
		);

		$map_state = PH_Template_Set_Request_Context::get_map_search_state();
		if ( 'portal-style-search-results' === $template && ! $map_state['manages_archive'] ) {
			$content[ $template ]['body']     = __( 'Compare listings with filters, sorting and clear results.', 'propertyhive' );
			$content[ $template ]['items'][2] = __( 'Sorted results', 'propertyhive' );
		}

		if ( 'map-led-search-results' === $template ) {
			$content[ $template ]['kicker'] = self::get_map_search_kicker( $content[ $template ]['kicker'] );
		}

		return isset( $content[ $template ] ) ? $content[ $template ] : $content['portal-style-search-results'];
	}
}
