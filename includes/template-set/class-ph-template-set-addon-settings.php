<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Registry and persistence service for visual-editor add-on settings.
 *
 * Add-on settings remain owned by their add-on. This service only exposes a
 * deliberately small, editor-safe subset of those settings and writes the
 * approved keys back into the existing option arrays.
 */
class PH_Template_Set_Addon_Settings {

	const REQUEST_NAMESPACE = 'ph_template_set_addons';

	/**
	 * Get the registered add-on settings definitions.
	 *
	 * @return array
	 */
	public static function get_definitions() {
		$viewing_time_options = self::get_viewing_request_time_options();
		$viewing_time_keys    = array_keys( $viewing_time_options );
		$viewing_time_from    = ! empty( $viewing_time_keys ) ? (string) reset( $viewing_time_keys ) : '00:00';
		$viewing_time_to      = ! empty( $viewing_time_keys ) ? (string) end( $viewing_time_keys ) : '23:00';

		$definitions = array(
			array(
				'id'              => 'map_search',
				'slug'            => 'propertyhive-map-search',
				'context'         => 'search',
				'label'           => __( 'Map Search', 'propertyhive' ),
				'description'     => __( 'Controls how Map Search is shown on search result pages.', 'propertyhive' ),
				'scope'           => 'site_wide',
				'scope_label'     => __( 'Applies across the site.', 'propertyhive' ),
				'advanced_url'    => add_query_arg( array( 'page' => 'ph-settings', 'tab' => 'mapsearch' ), admin_url( 'admin.php' ) ),
				'advanced_label'  => __( 'Edit advanced map settings', 'propertyhive' ),
				'reload_after_save' => false,
				'option_name'       => 'propertyhive_map_search',
				'symbols'           => array( __CLASS__, 'has_map_search_symbols' ),
				'controls'          => array(
					'format' => array(
						'type'       => 'select',
						'label'      => __( 'Show map search', 'propertyhive' ),
						'options'    => array(
							''      => __( "Don't show map", 'propertyhive' ),
							'view'  => __( 'List and map toggle', 'propertyhive' ),
							'split' => __( 'Map beside results', 'propertyhive' ),
						),
						'option_key' => 'format',
						'default'   => '',
					),
				),
			),
			array(
				'id'              => 'infinite_scroll',
				'slug'            => 'propertyhive-infinite-scroll',
				'context'         => 'search',
				'label'           => __( 'Infinite Scroll', 'propertyhive' ),
				'description'     => __( 'Controls how additional search results load on search result pages.', 'propertyhive' ),
				'scope'           => 'site_wide',
				'scope_label'     => __( 'Applies across the site.', 'propertyhive' ),
				'advanced_url'    => add_query_arg( array( 'page' => 'ph-settings', 'tab' => 'infinitescroll' ), admin_url( 'admin.php' ) ),
				'advanced_label'  => __( 'Edit advanced infinite scroll settings', 'propertyhive' ),
				'reload_after_save' => false,
				'option_name'       => 'propertyhive_infinite_scroll',
				'symbols'           => array( __CLASS__, 'has_infinite_scroll_symbols' ),
				'controls'          => array(
					'functionality' => array(
						'type'       => 'select',
						'label'      => __( 'Load more behaviour', 'propertyhive' ),
						'options'    => array(
							''       => __( 'Automatically while scrolling', 'propertyhive' ),
							'button' => __( 'Load More button', 'propertyhive' ),
						),
						'option_key' => 'functionality',
						'default'   => '',
					),
					'devices' => array(
						'type'       => 'select',
						'label'      => __( 'Devices', 'propertyhive' ),
						'options'    => array(
							''       => __( 'All devices', 'propertyhive' ),
							'mobile' => __( 'Mobile only', 'propertyhive' ),
						),
						'option_key' => 'devices',
						'default'   => '',
					),
				),
			),
		);

		$definitions = apply_filters( 'propertyhive_template_set_addon_settings_definitions', $definitions );

		return is_array( $definitions ) ? $definitions : array();
	}

	/**
	 * Get definitions that are relevant and usable in an editor context.
	 *
	 * @param string $context Editor context.
	 * @return array
	 */
	public static function get_available_definitions( $context = '' ) {
		$context     = sanitize_key( $context );
		$definitions = array();

		foreach ( self::get_definitions() as $definition ) {
			if ( ! is_array( $definition ) || empty( $definition['id'] ) || empty( $definition['slug'] ) ) {
				continue;
			}

			if ( $context && ( empty( $definition['context'] ) || $context !== $definition['context'] ) ) {
				continue;
			}

			if ( ! self::is_definition_available( $definition ) ) {
				continue;
			}

			$definitions[] = $definition;
		}

		return $definitions;
	}

	/**
	 * Build sidebar group metadata for the current editor context.
	 *
	 * @param string $context Editor context.
	 * @return array
	 */
	public static function get_sidebar_groups( $context ) {
		$groups = array();

		foreach ( self::get_available_definitions( $context ) as $definition ) {
			$controls = array();

			foreach ( (array) $definition['controls'] as $key => $control ) {
				if ( ! is_array( $control ) || empty( $control['option_key'] ) ) {
					continue;
				}

				$controls[] = self::get_field_name( $definition['id'], $key, 'multiselect' === $control['type'] );
			}

			if ( empty( $controls ) ) {
				continue;
			}

			$groups[] = array(
				'id'              => 'addon-' . sanitize_title( $definition['id'] ),
				'label'           => isset( $definition['label'] ) ? $definition['label'] : '',
				'controls'        => $controls,
				'addon_id'        => sanitize_key( $definition['id'] ),
				'scope'           => isset( $definition['scope'] ) ? sanitize_key( $definition['scope'] ) : 'site_wide',
				'scopeLabel'      => isset( $definition['scope_label'] ) ? $definition['scope_label'] : '',
				'description'     => isset( $definition['description'] ) ? $definition['description'] : '',
				'advancedUrl'     => isset( $definition['advanced_url'] ) ? esc_url_raw( $definition['advanced_url'] ) : '',
				'advancedLabel'   => isset( $definition['advanced_label'] ) ? $definition['advanced_label'] : '',
				'reloadAfterSave' => ! empty( $definition['reload_after_save'] ),
			);
		}

		return $groups;
	}

	/**
	 * Get the public definition data used by the editor configuration.
	 *
	 * @param string $context Editor context.
	 * @return array
	 */
	public static function get_public_definitions( $context = '' ) {
		$public = array();

		foreach ( self::get_available_definitions( $context ) as $definition ) {
			$controls = array();

			foreach ( (array) $definition['controls'] as $key => $control ) {
				if ( ! is_array( $control ) || empty( $control['option_key'] ) ) {
					continue;
				}

				$controls[ $key ] = array(
					'name'    => self::get_field_name( $definition['id'], $key, 'multiselect' === $control['type'] ),
					'label'   => isset( $control['label'] ) ? $control['label'] : '',
					'type'    => isset( $control['type'] ) ? $control['type'] : 'text',
					'options' => isset( $control['options'] ) && is_array( $control['options'] ) ? $control['options'] : array(),
					'value'   => self::get_control_value( $definition, $control ),
					'checkedValue' => isset( $control['checked_value'] ) ? $control['checked_value'] : '1',
				);
			}

			$public[] = array(
				'id'                => sanitize_key( $definition['id'] ),
				'label'             => isset( $definition['label'] ) ? $definition['label'] : '',
				'context'           => isset( $definition['context'] ) ? sanitize_key( $definition['context'] ) : '',
				'scope'           => isset( $definition['scope'] ) ? sanitize_key( $definition['scope'] ) : 'site_wide',
				'scopeLabel'      => isset( $definition['scope_label'] ) ? $definition['scope_label'] : '',
				'description'     => isset( $definition['description'] ) ? $definition['description'] : '',
				'advancedUrl'     => isset( $definition['advanced_url'] ) ? esc_url_raw( $definition['advanced_url'] ) : '',
				'advancedLabel'   => isset( $definition['advanced_label'] ) ? $definition['advanced_label'] : '',
				'reloadAfterSave' => ! empty( $definition['reload_after_save'] ),
				'controls'        => $controls,
			);
		}

		return $public;
	}

	/**
	 * Get current public values for available definitions.
	 *
	 * @param string $context Editor context.
	 * @return array
	 */
	public static function get_public_settings( $context = '' ) {
		$settings = array();

		foreach ( self::get_available_definitions( $context ) as $definition ) {
			$settings[ sanitize_key( $definition['id'] ) ] = array();

			foreach ( (array) $definition['controls'] as $key => $control ) {
				if ( ! is_array( $control ) || empty( $control['option_key'] ) ) {
					continue;
				}

				$settings[ sanitize_key( $definition['id'] ) ][ $key ] = self::get_control_value( $definition, $control );
			}
		}

		return $settings;
	}

	/**
	 * Build the namespaced field name expected in editor form submissions.
	 *
	 * @param string $definition_id Add-on definition ID.
	 * @param string $control_key   Control key.
	 * @return string
	 */
	public static function get_field_name( $definition_id, $control_key, $is_array = false ) {
		$name = self::REQUEST_NAMESPACE . '[' . sanitize_key( $definition_id ) . '][' . sanitize_key( $control_key ) . ']';

		return $is_array ? $name . '[]' : $name;
	}

	/**
	 * Prepare strict add-on option updates without writing them.
	 *
	 * @param array  $raw_request Raw editor request.
	 * @param string $context     Editor context.
	 * @return array|WP_Error
	 */
	public static function prepare_save( $raw_request, $context ) {
		$raw_request = is_array( $raw_request ) ? wp_unslash( $raw_request ) : array();
		$context     = sanitize_key( $context );
		$submitted   = isset( $raw_request[ self::REQUEST_NAMESPACE ] ) && is_array( $raw_request[ self::REQUEST_NAMESPACE ] ) ? $raw_request[ self::REQUEST_NAMESPACE ] : array();
		$prepared    = array(
			'updates'          => array(),
			'changed'          => false,
			'reload_after_save' => false,
		);

		if ( ! in_array( $context, array( 'search', 'detail' ), true ) ) {
			return $prepared;
		}

		foreach ( self::get_available_definitions( $context ) as $definition ) {
			$definition_id = sanitize_key( $definition['id'] );
			$group         = isset( $submitted[ $definition_id ] ) && is_array( $submitted[ $definition_id ] ) ? $submitted[ $definition_id ] : array();

			if ( empty( $group ) ) {
				continue;
			}

			$current_settings = get_option( $definition['option_name'], array() );
			$current_settings = is_array( $current_settings ) ? $current_settings : array();
			$next_settings    = $current_settings;
			$definition_changed = false;
			$has_known_control  = false;

			foreach ( (array) $definition['controls'] as $key => $control ) {
				if ( ! is_array( $control ) || empty( $control['option_key'] ) || ! array_key_exists( $key, $group ) ) {
					continue;
				}

				$has_known_control = true;
				$value             = self::sanitize_control_value( $group[ $key ], $control );

				if ( is_wp_error( $value ) ) {
					return $value;
				}

				$has_current_value = array_key_exists( $control['option_key'], $current_settings );
				$current_value     = $has_current_value ? self::sanitize_control_value( $current_settings[ $control['option_key'] ], $control ) : self::get_control_default( $control );

				if ( is_wp_error( $current_value ) ) {
					$current_value = self::get_control_default( $control );
				}

				$values_match = self::values_are_equal( $current_value, $value );

				if ( ! $values_match ) {
					$definition_changed = true;
				}

				if ( $has_current_value || ! self::values_are_equal( self::get_control_default( $control ), $value ) ) {
					$next_settings[ $control['option_key'] ] = $value;
				}
			}

			if ( ! $has_known_control ) {
				continue;
			}

			if ( $definition_changed ) {
				$prepared['updates'][] = array(
					'option_name'     => $definition['option_name'],
					'settings'        => $next_settings,
					'definition_id'   => $definition_id,
					'post_save'       => self::get_post_save_callbacks( $definition ),
				);
				$prepared['changed'] = true;
				if ( ! empty( $definition['reload_after_save'] ) ) {
					$prepared['reload_after_save'] = true;
				}
			}
		}

		return $prepared;
	}

	/**
	 * Commit prepared add-on option updates.
	 *
	 * @param array $prepared Prepared updates from prepare_save().
	 * @return bool|WP_Error
	 */
	public static function commit_save( $prepared, $core_settings = null ) {
		if ( ! is_array( $prepared ) ) {
			return new WP_Error( 'template_set_save_invalid_payload', __( 'The template settings could not be saved. Please try again.', 'propertyhive' ) );
		}

		if ( null !== $core_settings && ! is_array( $core_settings ) ) {
			return new WP_Error( 'template_set_save_invalid_core_settings', __( 'The template settings could not be saved. Please try again.', 'propertyhive' ) );
		}

		$addon_updates = isset( $prepared['updates'] ) ? $prepared['updates'] : array();
		if ( ! is_array( $addon_updates ) ) {
			return new WP_Error( 'template_set_save_invalid_addon_updates', __( 'The add-on settings could not be saved. Please try again.', 'propertyhive' ) );
		}

		$transactions        = array();
		$seen_options        = array();
		$post_save_callbacks = array();

		if ( null !== $core_settings ) {
			$transactions[] = array(
				'option_name' => 'propertyhive_template_assistant',
				'value'       => $core_settings,
			);
			$seen_options['propertyhive_template_assistant'] = true;
		}

		foreach ( $addon_updates as $update ) {
			if ( ! is_array( $update ) || empty( $update['option_name'] ) || ! is_string( $update['option_name'] ) || ! isset( $update['settings'] ) || ! is_array( $update['settings'] ) ) {
				return new WP_Error( 'template_set_save_invalid_addon_update', __( 'The add-on settings could not be saved. Please try again.', 'propertyhive' ) );
			}

			$option_name = $update['option_name'];
			if ( isset( $seen_options[ $option_name ] ) ) {
				return new WP_Error( 'template_set_save_duplicate_option', __( 'The template settings could not be saved. Please try again.', 'propertyhive' ) );
			}

			$seen_options[ $option_name ] = true;
			$transactions[]              = array(
				'option_name' => $option_name,
				'value'       => $update['settings'],
			);

			if ( ! empty( $update['post_save'] ) && is_array( $update['post_save'] ) ) {
				$post_save_callbacks = array_merge( $post_save_callbacks, $update['post_save'] );
			}
		}

		if ( empty( $transactions ) ) {
			return false;
		}

		// Capture every old value and its existence before the first write. This
		// lets rollback restore the exact option state, including absent rows.
		foreach ( $transactions as $index => $transaction ) {
			$transactions[ $index ]['before'] = self::capture_option_state( $transaction['option_name'] );
		}

		foreach ( $transactions as $transaction ) {
			$updated = update_option( $transaction['option_name'], $transaction['value'] );
			$after   = self::capture_option_state( $transaction['option_name'] );

			// WordPress returns false when the stored value already matches. The
			// post-write state is authoritative, so that is a successful no-op.
			if ( ! self::option_state_matches_value( $after, $transaction['value'] ) ) {
				$error = new WP_Error(
					'template_set_option_save_failed',
					__( 'A template or add-on setting could not be saved. Please try again.', 'propertyhive' ),
					array( 'option_name' => $transaction['option_name'], 'update_result' => $updated )
				);

				if ( ! self::rollback_option_states( $transactions ) ) {
					$error->add_data(
						array(
							'option_name'    => $transaction['option_name'],
							'rollback_failed' => true,
						),
						'template_set_option_save_failed'
					);
				}

				return $error;
			}
		}

		foreach ( array_unique( $post_save_callbacks ) as $callback_id ) {
			if ( 'location_autocomplete_rebuild' === $callback_id ) {
				do_action( 'phlocationautocompletecronhook' );
			}
		}

		return true;
	}

	/**
	 * Capture an option value without losing whether the row exists.
	 *
	 * @param string $option_name Option name.
	 * @return array
	 */
	private static function capture_option_state( $option_name ) {
		$missing = new stdClass();
		$value   = get_option( $option_name, $missing );

		return array(
			'exists' => $value !== $missing,
			'value'  => $value,
		);
	}

	/**
	 * Check whether an option contains the requested value.
	 *
	 * @param array $state Option state.
	 * @param mixed $value  Expected value.
	 * @return bool
	 */
	private static function option_state_matches_value( $state, $value ) {
		return ! empty( $state['exists'] ) && $state['value'] === $value;
	}

	/**
	 * Compare two captured option states.
	 *
	 * @param array $left  First state.
	 * @param array $right Second state.
	 * @return bool
	 */
	private static function option_states_equal( $left, $right ) {
		if ( empty( $left['exists'] ) || empty( $right['exists'] ) ) {
			return empty( $left['exists'] ) && empty( $right['exists'] );
		}

		return $left['value'] === $right['value'];
	}

	/**
	 * Restore every transaction option to its captured state.
	 *
	 * @param array $transactions Prepared transaction options.
	 * @return bool
	 */
	private static function rollback_option_states( $transactions ) {
		$rollback_succeeded = true;

		for ( $index = count( $transactions ) - 1; $index >= 0; $index-- ) {
			if ( ! isset( $transactions[ $index ]['option_name'], $transactions[ $index ]['before'] ) ) {
				continue;
			}

			$option_name = $transactions[ $index ]['option_name'];
			$before      = $transactions[ $index ]['before'];
			$current     = self::capture_option_state( $option_name );

			if ( self::option_states_equal( $current, $before ) ) {
				continue;
			}

			if ( empty( $before['exists'] ) ) {
				delete_option( $option_name );
			} else {
				update_option( $option_name, $before['value'] );
			}

			if ( ! self::option_states_equal( self::capture_option_state( $option_name ), $before ) ) {
				$rollback_succeeded = false;
			}
		}

		return $rollback_succeeded;
	}

	/**
	 * Detect the Map Search add-on symbols.
	 *
	 * @return bool
	 */
	public static function has_map_search_symbols() {
		return class_exists( 'PH_Map_Search' ) || function_exists( 'PHMAP' );
	}

	/**
	 * Detect the Infinite Scroll add-on symbols.
	 *
	 * @return bool
	 */
	public static function has_infinite_scroll_symbols() {
		return class_exists( 'PH_Infinite_Scroll' ) || function_exists( 'PHIS' );
	}

	/**
	 * Detect the Printable Brochures add-on symbols.
	 *
	 * @return bool
	 */
	public static function has_printable_brochures_symbols() {
		return class_exists( 'PH_Printable_Brochures' ) || function_exists( 'PHPB' );
	}

	/**
	 * Detect the Location Autocomplete add-on symbols.
	 *
	 * @return bool
	 */
	public static function has_location_autocomplete_symbols() {
		return class_exists( 'PH_Location_Autocomplete' ) || function_exists( 'PHLA' );
	}

	/**
	 * Detect the Radial Search add-on symbols.
	 *
	 * @return bool
	 */
	public static function has_radial_search_symbols() {
		return class_exists( 'PH_Radial_Search' ) || function_exists( 'PHRS' );
	}

	/**
	 * Detect the Search Results Promos add-on symbols.
	 *
	 * @return bool
	 */
	public static function has_search_results_promos_symbols() {
		return class_exists( 'PH_Search_Results_Promos' ) || function_exists( 'PHSRP' );
	}

	/**
	 * Detect the Viewing Request add-on symbols.
	 *
	 * @return bool
	 */
	public static function has_viewing_request_symbols() {
		return class_exists( 'PH_Viewing_Request' ) || function_exists( 'PHVR' );
	}

	/**
	 * Detect the Locrating add-on symbols.
	 *
	 * @return bool
	 */
	public static function has_locrating_symbols() {
		return class_exists( 'PH_Locrating' ) || function_exists( 'PHLOC' );
	}

	/**
	 * Detect the Home Reports add-on symbols.
	 *
	 * @return bool
	 */
	public static function has_home_reports_symbols() {
		return class_exists( 'PH_Home_Reports' ) || function_exists( 'PHHR' );
	}

	/**
	 * Detect the OneDome add-on symbols.
	 *
	 * @return bool
	 */
	public static function has_onedome_symbols() {
		return class_exists( 'PH_OneDome' ) || function_exists( 'PHOD' );
	}

	/**
	 * Detect the PropertyFile add-on symbols.
	 *
	 * @return bool
	 */
	public static function has_propertyfile_symbols() {
		return class_exists( 'PH_PropertyFile' ) || function_exists( 'PHPF' );
	}

	/**
	 * Return the allowlisted declarative post-save callback IDs for a definition.
	 *
	 * Definitions may declare callback IDs, but never executable callables or
	 * arbitrary action names. The IDs are resolved by commit_save() below.
	 *
	 * @param array $definition Add-on definition.
	 * @return array
	 */
	private static function get_post_save_callbacks( $definition ) {
		$callbacks = isset( $definition['post_save'] ) && is_array( $definition['post_save'] ) ? $definition['post_save'] : array();
		$callbacks = array_map( 'sanitize_key', array_filter( $callbacks, 'is_scalar' ) );

		return array_values( array_unique( array_intersect( $callbacks, array( 'location_autocomplete_rebuild' ) ) ) );
	}

	/**
	 * Determine whether a definition can be rendered or saved.
	 *
	 * @param array $definition Definition.
	 * @return bool
	 */
	private static function is_definition_available( $definition ) {
		if ( empty( $definition['slug'] ) || empty( $definition['symbols'] ) || ! is_callable( $definition['symbols'] ) ) {
			return false;
		}

		if ( ! call_user_func( $definition['symbols'] ) ) {
			return false;
		}

		return class_exists( 'PH_Template_Set' ) && PH_Template_Set::is_add_on_usable( $definition['slug'] );
	}

	/**
	 * Get a safe current value for a registered control.
	 *
	 * @param array $definition Add-on definition.
	 * @param array $control    Control definition.
	 * @return mixed
	 */
	private static function get_control_value( $definition, $control ) {
		$current_settings = get_option( $definition['option_name'], array() );
		$current_settings = is_array( $current_settings ) ? $current_settings : array();
		$value             = array_key_exists( $control['option_key'], $current_settings ) ? $current_settings[ $control['option_key'] ] : self::get_control_default( $control );
		$sanitized         = self::sanitize_control_value( $value, $control );

		return is_wp_error( $sanitized ) ? self::get_control_default( $control ) : $sanitized;
	}

	/**
	 * Get a registered control's effective default.
	 *
	 * @param array $control Control definition.
	 * @return mixed
	 */
	private static function get_control_default( $control ) {
		return isset( $control['default'] ) ? $control['default'] : '';
	}

	/**
	 * Create a native checkbox definition with its add-on storage encoding.
	 *
	 * @param string $label         Control label.
	 * @param string $option_key    Option key.
	 * @param string $default       Effective default.
	 * @param string $checked_value Stored checked value.
	 * @return array
	 */
	private static function get_checkbox_control( $label, $option_key, $default, $checked_value ) {
		return array(
			'type'            => 'checkbox',
			'label'           => $label,
			'option_key'      => $option_key,
			'default'         => $default,
			'checked_value'   => $checked_value,
			'unchecked_value' => '',
		);
	}

	/**
	 * Get the days used by the installed Viewing Request settings screen.
	 *
	 * @return array
	 */
	private static function get_viewing_request_day_options() {
		return array(
			'0' => __( 'Sunday', 'propertyhive' ),
			'1' => __( 'Monday', 'propertyhive' ),
			'2' => __( 'Tuesday', 'propertyhive' ),
			'3' => __( 'Wednesday', 'propertyhive' ),
			'4' => __( 'Thursday', 'propertyhive' ),
			'5' => __( 'Friday', 'propertyhive' ),
			'6' => __( 'Saturday', 'propertyhive' ),
		);
	}

	/**
	 * Get the time choices used by the installed Viewing Request settings screen.
	 *
	 * @return array
	 */
	private static function get_viewing_request_time_options() {
		$interval = absint( apply_filters( 'propertyhive_viewing_request_interval', 30 ) );
		$interval = $interval > 0 ? $interval : 30;
		$times    = array();

		foreach ( range( 0, 84400, $interval * 60 ) as $increment ) {
			$value          = gmdate( 'H:i', $increment );
			$times[ $value ] = $value;
		}

		return $times;
	}

	/**
	 * Compare scalar and multi-value controls consistently.
	 *
	 * @param mixed $left  First value.
	 * @param mixed $right Second value.
	 * @return bool
	 */
	private static function values_are_equal( $left, $right ) {
		if ( is_array( $left ) || is_array( $right ) ) {
			$left  = array_map( 'strval', is_array( $left ) ? $left : array( $left ) );
			$right = array_map( 'strval', is_array( $right ) ? $right : array( $right ) );
			sort( $left, SORT_STRING );
			sort( $right, SORT_STRING );

			return $left === $right;
		}

		return (string) $left === (string) $right;
	}

	/**
	 * Sanitize one registered control value.
	 *
	 * @param mixed $value   Raw value.
	 * @param array $control Control definition.
	 * @return string|WP_Error
	 */
	private static function sanitize_control_value( $value, $control ) {
		$type = isset( $control['type'] ) ? $control['type'] : 'text';

		if ( 'positive_integer_list' === $type ) {
			if ( ! is_scalar( $value ) || is_bool( $value ) ) {
				return new WP_Error( 'invalid_template_set_addon_value', __( 'An add-on setting contained an invalid value.', 'propertyhive' ) );
			}

			$value  = trim( (string) $value );
			$parts  = explode( ',', $value );
			$values = array();

			if ( '' === $value || empty( $parts ) ) {
				return new WP_Error( 'invalid_template_set_addon_value', __( 'An add-on setting contained an invalid value.', 'propertyhive' ) );
			}

			foreach ( $parts as $part ) {
				$part = trim( $part );

				if ( '' === $part || ! preg_match( '/\A[0-9]+\z/', $part ) ) {
					return new WP_Error( 'invalid_template_set_addon_value', __( 'An add-on setting contained an invalid value.', 'propertyhive' ) );
				}

				$part = ltrim( $part, '0' );

				if ( '' === $part ) {
					return new WP_Error( 'invalid_template_set_addon_value', __( 'An add-on setting contained an invalid value.', 'propertyhive' ) );
				}

				$values[] = $part;
			}

			return implode( ',', $values );
		}

		if ( 'select' === $type ) {
			if ( ! is_scalar( $value ) ) {
				return new WP_Error( 'invalid_template_set_addon_value', __( 'An add-on setting contained an invalid value.', 'propertyhive' ) );
			}

			$value = (string) $value;
			$options = isset( $control['options'] ) && is_array( $control['options'] ) ? $control['options'] : array();

			if ( ! array_key_exists( $value, $options ) ) {
				return new WP_Error( 'invalid_template_set_addon_value', __( 'An add-on setting contained an invalid value.', 'propertyhive' ) );
			}

			return $value;
		}

		if ( 'multiselect' === $type ) {
			if ( '' === $value || null === $value ) {
				return array();
			}

			if ( ! is_array( $value ) ) {
				return new WP_Error( 'invalid_template_set_addon_value', __( 'An add-on setting contained an invalid value.', 'propertyhive' ) );
			}

			$options = isset( $control['options'] ) && is_array( $control['options'] ) ? $control['options'] : array();
			$values  = array();

			foreach ( $value as $item ) {
				if ( ! is_scalar( $item ) ) {
					return new WP_Error( 'invalid_template_set_addon_value', __( 'An add-on setting contained an invalid value.', 'propertyhive' ) );
				}

				$item = (string) $item;
				if ( '' === $item ) {
					continue;
				}

				if ( ! array_key_exists( $item, $options ) ) {
					return new WP_Error( 'invalid_template_set_addon_value', __( 'An add-on setting contained an invalid value.', 'propertyhive' ) );
				}

				$values[] = $item;
			}

			return array_values( array_unique( $values ) );
		}

		if ( 'checkbox' === $type ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				return new WP_Error( 'invalid_template_set_addon_value', __( 'An add-on setting contained an invalid value.', 'propertyhive' ) );
			}

			$checked_value   = isset( $control['checked_value'] ) ? (string) $control['checked_value'] : '1';
			$unchecked_value = isset( $control['unchecked_value'] ) ? (string) $control['unchecked_value'] : '';
			$value           = (string) $value;

			if ( $value === $checked_value ) {
				return $checked_value;
			}

			if ( $value === $unchecked_value || '' === $value ) {
				return $unchecked_value;
			}

			return new WP_Error( 'invalid_template_set_addon_value', __( 'An add-on setting contained an invalid value.', 'propertyhive' ) );
		}

		if ( ! is_scalar( $value ) ) {
			return new WP_Error( 'invalid_template_set_addon_value', __( 'An add-on setting contained an invalid value.', 'propertyhive' ) );
		}

		$value = sanitize_text_field( (string) $value );
		$max   = isset( $control['maxlength'] ) ? absint( $control['maxlength'] ) : 120;

		if ( $max > 0 && strlen( $value ) > $max ) {
			$value = function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max ) : substr( $value, 0, $max );
		}

		return $value;
	}
}
