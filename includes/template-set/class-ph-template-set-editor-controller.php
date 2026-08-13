<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Template set front-end editor shell and AJAX save.
 */
class PH_Template_Set_Editor_Controller {

	/**
	 * Render the front-end template editor shell.
	 */
	public static function render_template_editor() {
		if ( ! PH_Template_Set_Request_Context::is_template_editor_active() ) {
			return;
		}

		$settings     = PH_Template_Set_Settings::get_settings();
		$context      = self::get_template_editor_context();
		$settings_url = admin_url( 'admin.php?page=ph-settings&tab=frontend&section=template-set' );
		$logo_url     = apply_filters( 'propertyhive_template_editor_logo_url', PH()->plugin_url() . '/assets/images/admin/propertyhive-logo-onboarding.png' );

		echo '<aside class="ph-template-editor ph-template-editor-' . esc_attr( sanitize_html_class( $context ) ) . '" data-ph-template-editor data-ph-template-editor-context="' . esc_attr( $context ) . '" aria-label="' . esc_attr__( 'Template editor', 'propertyhive' ) . '">';
			echo '<form id="ph-template-editor-form" class="ph-template-editor-form" data-ph-template-editor-form>';
				echo '<header class="ph-template-editor-header">';
					echo '<div class="ph-template-editor-brand">';
						if ( ! ( class_exists( 'PH_White_Label' ) && PH_Template_Set::is_add_on_usable( 'propertyhive-white-label' ) && '' !== trim( (string) get_option( 'propertyhive_white_label', '' ) ) ) ) {
							echo '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr__( 'Property Hive', 'propertyhive' ) . '">';
						}
						echo '<span>' . esc_html__( 'Template editor', 'propertyhive' ) . '</span>';
						echo '<h2>' . esc_html( self::get_template_editor_title( $context ) ) . '</h2>';
					echo '</div>';
				echo '</header>';

				echo '<input type="hidden" name="template_set_enabled" value="yes">';
				echo '<input type="hidden" name="template_set_editor_mode" value="' . esc_attr( PH_Template_Set::EDITOR_MODE_VISUAL ) . '">';
				echo '<input type="hidden" name="template_set_editor_context" value="' . esc_attr( $context ) . '">';

				self::render_template_editor_section_start( __( 'Template', 'propertyhive' ) );
				if ( 'search' === $context ) {
					self::render_template_editor_hidden( 'template_set_detail_template', PH_Template_Set_Request_Context::get_detail_template() );
					self::render_template_editor_select( 'template_set_search_template', __( 'Search template', 'propertyhive' ), PH_Template_Set_Catalog::get_search_templates(), PH_Template_Set_Request_Context::get_search_template(), self::get_template_editor_preview_urls( PH_Template_Set_Catalog::get_search_templates() ) );
				} else {
					self::render_template_editor_hidden( 'template_set_search_template', PH_Template_Set_Request_Context::get_search_template() );
					self::render_template_editor_select( 'template_set_detail_template', __( 'Detail template', 'propertyhive' ), PH_Template_Set_Catalog::get_detail_templates(), PH_Template_Set_Request_Context::get_detail_template(), self::get_template_editor_preview_urls( PH_Template_Set_Catalog::get_detail_templates() ) );
				}
				self::render_template_editor_section_end();

				if ( 'search' === $context ) {
					PH_Template_Set_Search_Form_Editor::render_sidebar_section();

					self::render_template_editor_section_start( __( 'Search result cards', 'propertyhive' ) );
					$presentation = PH_Template_Set_Request_Context::get_search_presentation();
					self::render_template_editor_select( 'template_set_search_layout', __( 'Results layout', 'propertyhive' ), PH_Template_Set_Options::get_search_card_layouts(), $presentation['card_layout'] );
					self::render_template_editor_select( 'template_set_search_card_size', __( 'Card size', 'propertyhive' ), PH_Template_Set_Options::get_search_card_sizes(), $settings['template_set_search_card_size'] );
					self::render_template_editor_select( 'template_set_search_grid_columns', __( 'Cards per row', 'propertyhive' ), PH_Template_Set_Options::get_search_grid_column_options(), PH_Template_Set_Settings::get_search_grid_columns_for_template( PH_Template_Set_Request_Context::get_search_template(), $settings ) );
					self::render_template_editor_select( 'template_set_image_style', __( 'Photo shape', 'propertyhive' ), PH_Template_Set_Options::get_image_styles(), $settings['template_set_image_style'] );
					self::render_template_editor_checkbox( 'template_set_show_branch', __( 'Show branch contact details', 'propertyhive' ), $settings['template_set_show_branch'] );
					self::render_template_editor_checkbox( 'template_set_show_badges', __( 'Show property labels', 'propertyhive' ), $settings['template_set_show_badges'] );
					self::render_template_editor_section_end();
					self::render_search_result_global_controls( $settings );
					self::render_search_manifest_controls( PH_Template_Set_Request_Context::get_search_template() );
					self::render_addon_settings_sections( $context );
				} else {
					self::render_template_editor_hidden( 'template_set_search_layout', $settings['template_set_search_layout'] );
					self::render_template_editor_hidden( 'template_set_search_card_size', $settings['template_set_search_card_size'] );
					self::render_template_editor_hidden( 'template_set_search_grid_columns', PH_Template_Set_Settings::get_search_grid_columns_for_template( PH_Template_Set_Request_Context::get_search_template(), $settings ) );
					self::render_template_editor_hidden( 'template_set_image_style', $settings['template_set_image_style'] );
					self::render_template_editor_hidden( 'template_set_show_branch', $settings['template_set_show_branch'] );
					self::render_template_editor_hidden( 'template_set_show_badges', $settings['template_set_show_badges'] );

					self::render_detail_manifest_controls( PH_Template_Set_Request_Context::get_detail_template() );
					self::render_addon_settings_sections( $context );
				}

			echo '<footer class="ph-template-editor-footer">';
				echo '<div>';
					echo '<a class="ph-template-editor-secondary" data-ph-template-editor-settings-link href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Exit to Settings', 'propertyhive' ) . '</a>';
					echo '<button type="submit" class="ph-template-editor-save" data-ph-template-editor-save data-ph-template-editor-save-default-label="' . esc_attr__( 'Save', 'propertyhive' ) . '" aria-live="polite" disabled>' . esc_html__( 'Save', 'propertyhive' ) . '</button>';
				echo '</div>';
				echo '</footer>';
				echo '</form>';
			echo '<button type="button" class="ph-template-editor-collapse-toggle" data-ph-template-editor-collapse-toggle aria-controls="ph-template-editor-form" aria-expanded="true" data-ph-template-editor-collapse-label="' . esc_attr__( 'Collapse template editor', 'propertyhive' ) . '" data-ph-template-editor-expand-label="' . esc_attr__( 'Expand template editor', 'propertyhive' ) . '" aria-label="' . esc_attr__( 'Collapse template editor', 'propertyhive' ) . '" title="' . esc_attr__( 'Collapse template editor', 'propertyhive' ) . '"><span aria-hidden="true"></span></button>';
			echo '</aside>';
			self::render_template_editor_preview_workspace( $context );
			echo '<div class="ph-template-editor-unsaved-warning" data-ph-template-editor-unsaved-warning hidden aria-hidden="true">';
				echo '<div class="ph-template-editor-unsaved-warning-backdrop" data-ph-template-editor-unsaved-warning-dismiss></div>';
				echo '<div class="ph-template-editor-unsaved-warning-dialog" role="alertdialog" aria-modal="true" aria-labelledby="ph-template-editor-unsaved-warning-title" aria-describedby="ph-template-editor-unsaved-warning-description" tabindex="-1">';
					echo '<div class="ph-template-editor-unsaved-warning-icon" aria-hidden="true">!</div>';
					echo '<div class="ph-template-editor-unsaved-warning-copy">';
						echo '<span class="ph-template-editor-unsaved-warning-label">' . esc_html__( 'Unsaved changes', 'propertyhive' ) . '</span>';
						echo '<h2 id="ph-template-editor-unsaved-warning-title">' . esc_html__( 'Leave without saving?', 'propertyhive' ) . '</h2>';
						echo '<p id="ph-template-editor-unsaved-warning-description">' . esc_html__( 'You have changes that have not been saved. If you leave the editor now, those changes will be lost.', 'propertyhive' ) . '</p>';
					echo '</div>';
					echo '<div class="ph-template-editor-unsaved-warning-actions">';
						echo '<button type="button" class="ph-template-editor-unsaved-warning-stay" data-ph-template-editor-unsaved-warning-stay>' . esc_html__( 'Keep editing', 'propertyhive' ) . '</button>';
						echo '<button type="button" class="ph-template-editor-unsaved-warning-leave" data-ph-template-editor-unsaved-warning-leave>' . esc_html__( 'Leave without saving', 'propertyhive' ) . '</button>';
					echo '</div>';
				echo '</div>';
			echo '</div>';
			echo '<script type="application/json" data-ph-template-editor-config>' . wp_json_encode( self::get_script_data(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . '</script>';
	}

	/**
	 * Get the current visual editor page context.
	 *
	 * @return string
	 */
	public static function get_template_editor_context() {
		return PH_Template_Set_Request_Context::is_search_results_request() ? 'search' : 'detail';
	}

	/**
	 * Get the editor title for the current page context.
	 *
	 * @param string $context Editor context.
	 * @return string
	 */
	public static function get_template_editor_title( $context ) {
		return 'search' === $context ? __( 'Search results', 'propertyhive' ) : __( 'Property details', 'propertyhive' );
	}

	/**
	 * Render the responsive preview workspace outside the editor form.
	 *
	 * @param string $context Editor page context.
	 */
	private static function render_template_editor_preview_workspace( $context ) {
		$devices = array(
			'mobile'  => array(
				'label' => __( 'Mobile', 'propertyhive' ),
				'width' => 390,
			),
			'tablet'  => array(
				'label' => __( 'Tablet', 'propertyhive' ),
				'width' => 768,
			),
			'desktop' => array(
				'label' => __( 'Desktop', 'propertyhive' ),
				'width' => 1280,
			),
		);
		$default_device  = 'desktop';
		$preview_url     = self::get_template_editor_preview_url( $context );
		$iframe_title    = 'search' === $context ? __( 'Search results preview', 'propertyhive' ) : __( 'Property page preview', 'propertyhive' );
		$workspace_id    = 'ph-template-editor-preview-workspace';
		$toolbar_id      = 'ph-template-editor-preview-toolbar';
		$canvas_id       = 'ph-template-editor-preview-canvas';

		echo '<section id="' . esc_attr( $workspace_id ) . '" class="ph-template-editor-preview-workspace" data-ph-template-editor-preview-workspace data-ph-template-editor-preview-context="' . esc_attr( $context ) . '" data-ph-template-editor-preview-device="' . esc_attr( $default_device ) . '" data-ph-template-editor-preview-active-device="' . esc_attr( $default_device ) . '" data-ph-template-editor-preview-url="' . esc_url( $preview_url ) . '" data-ph-template-editor-preview-state="loading" aria-busy="true" aria-label="' . esc_attr__( 'Preview workspace', 'propertyhive' ) . '">';
			echo '<div id="' . esc_attr( $toolbar_id ) . '" class="ph-template-editor-preview-toolbar" data-ph-template-editor-preview-toolbar role="toolbar" aria-label="' . esc_attr__( 'Preview controls', 'propertyhive' ) . '">';
				echo '<div class="ph-template-editor-preview-toolbar-group ph-template-editor-preview-toolbar-meta ph-template-editor-preview-toolbar-heading">';
					echo '<span class="ph-template-editor-preview-toolbar-label ph-template-editor-preview-eyebrow">' . esc_html__( 'Preview', 'propertyhive' ) . '</span>';
				echo '</div>';
				echo '<div class="ph-template-editor-preview-toolbar-group ph-template-editor-preview-toolbar-controls">';
					echo '<div class="ph-template-editor-preview-device-group" data-ph-template-editor-preview-device-group role="group" aria-label="' . esc_attr__( 'Choose preview device', 'propertyhive' ) . '">';
						foreach ( $devices as $device => $settings ) {
							$is_active = $default_device === $device;
							echo '<button type="button" class="ph-template-editor-preview-device" data-ph-template-editor-preview-device="' . esc_attr( $device ) . '" data-ph-template-editor-preview-device-width="' . absint( $settings['width'] ) . '" aria-controls="' . esc_attr( $canvas_id ) . '" aria-pressed="' . ( $is_active ? 'true' : 'false' ) . '">' . esc_html( $settings['label'] ) . '</button>';
						}
					echo '</div>';
				echo '</div>';
			echo '</div>';
			echo '<div id="' . esc_attr( $canvas_id ) . '" class="ph-template-editor-preview-canvas" data-ph-template-editor-preview-canvas data-ph-template-editor-preview-state="loading" aria-busy="true">';
				echo '<div class="ph-template-editor-preview-frame-shell" data-ph-template-editor-preview-frame-shell>';
					echo '<div class="ph-template-editor-preview-frame ph-template-editor-preview-frame-viewport" data-ph-template-editor-preview-frame-viewport data-ph-template-editor-preview-frame-width="' . absint( $devices[ $default_device ]['width'] ) . '">';
						echo '<iframe class="ph-template-editor-preview-iframe" data-ph-template-editor-preview-iframe data-ph-template-editor-preview-frame data-ph-template-preview-frame src="' . esc_url( $preview_url ) . '" title="' . esc_attr( $iframe_title ) . '" loading="eager"></iframe>';
					echo '</div>';
				echo '</div>';
				echo '<div class="ph-template-editor-preview-loading" data-ph-template-editor-preview-loading role="status" aria-live="polite"><span class="ph-template-editor-preview-spinner" aria-hidden="true"></span><span>' . esc_html__( 'Loading preview…', 'propertyhive' ) . '</span></div>';
				echo '<div class="ph-template-editor-preview-error" data-ph-template-editor-preview-error role="alert" hidden aria-hidden="true">';
					echo '<p>' . esc_html__( 'The preview could not be loaded here.', 'propertyhive' ) . '</p>';
					echo '<a href="' . esc_url( $preview_url ) . '" data-ph-template-editor-preview-fallback target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open preview in a new tab', 'propertyhive' ) . '</a>';
				echo '</div>';
			echo '</div>';
			echo '<noscript><p class="ph-template-editor-preview-noscript">' . esc_html__( 'JavaScript is required for the responsive preview toolbar.', 'propertyhive' ) . ' <a href="' . esc_url( $preview_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open preview in a new tab', 'propertyhive' ) . '</a></p></noscript>';
		echo '</section>';
	}

	/**
	 * Build the closed responsive preview frame URL.
	 *
	 * @param string $context Editor page context.
	 * @return string
	 */
	private static function get_template_editor_preview_url( $context ) {
		$template = 'search' === $context ? PH_Template_Set_Request_Context::get_search_template() : PH_Template_Set_Request_Context::get_detail_template();
		$url      = PH_Template_Set_Request_Context::get_template_preview_url( $template );

		$url = remove_query_arg( PH_Template_Set::EDIT_OPEN_QUERY_ARG, $url );

		$url = add_query_arg(
			array(
				PH_Template_Set::EDIT_QUERY_ARG        => '1',
				PH_Template_Set::EDIT_CLOSED_QUERY_ARG => '1',
				PH_Template_Set::EDIT_FRAME_QUERY_ARG  => '1',
			),
			$url
		);

		if ( 'search' === $context && PH_Template_Set::MAP_SEARCH_REQUIRED_TEMPLATE === $template ) {
			$map_state = PH_Template_Set_Request_Context::get_map_search_state();

			if ( ! empty( $map_state['available'] ) && ! empty( $map_state['usable'] ) && ! in_array( $map_state['format'], array( PH_Template_Set::MAP_SEARCH_FORMAT_VIEW, PH_Template_Set::MAP_SEARCH_FORMAT_SPLIT ), true ) ) {
				$url = add_query_arg( PH_Template_Set::MAP_SEARCH_FORMAT_QUERY_ARG, PH_Template_Set::MAP_SEARCH_REQUIRED_DEFAULT_FORMAT, $url );
			}
		}

		return $url;
	}

	/**
	 * Render editor section start.
	 *
	 * @param string $title Section title.
	 */
	public static function render_template_editor_section_start( $title ) {
		echo '<section class="ph-template-editor-section ph-template-editor-source-section">';
			echo '<h3>' . esc_html( $title ) . '</h3>';
	}

	/**
	 * Render editor section end.
	 */
	public static function render_template_editor_section_end() {
		echo '</section>';
	}

	/**
	 * Render an editor select control.
	 *
	 * @param string $name Control name.
	 * @param string $label Control label.
	 * @param array  $options Options.
	 * @param string $selected Selected value.
	 */
	public static function render_template_editor_select( $name, $label, $options, $selected, $option_urls = array() ) {
		echo '<label class="ph-template-editor-field ph-template-editor-field-' . esc_attr( sanitize_html_class( $name ) ) . '">';
			echo '<span>' . esc_html( $label ) . '</span>';
			echo '<select name="' . esc_attr( $name ) . '" data-ph-template-editor-control>';
				foreach ( $options as $value => $option_label ) {
					$option_url = isset( $option_urls[ $value ] ) ? $option_urls[ $value ] : '';
					echo '<option value="' . esc_attr( $value ) . '"' . selected( $value, $selected, false ) . ( $option_url ? ' data-ph-template-preview-url="' . esc_url( $option_url ) . '"' : '' ) . '>' . esc_html( $option_label ) . '</option>';
				}
			echo '</select>';
		echo '</label>';
	}

	/**
	 * Render a safe editor textarea control.
	 *
	 * @param string $name  Control name.
	 * @param string $label Control label.
	 * @param mixed  $value Current value.
	 * @param int    $rows  Textarea rows.
	 */
	public static function render_template_editor_textarea( $name, $label, $value, $rows = 8 ) {
		echo '<label class="ph-template-editor-field ph-template-editor-field-' . esc_attr( sanitize_html_class( $name ) ) . '">';
			echo '<span>' . esc_html( $label ) . '</span>';
			echo '<textarea name="' . esc_attr( $name ) . '" rows="' . absint( $rows ) . '" data-ph-template-editor-control>' . esc_textarea( (string) $value ) . '</textarea>';
		echo '</label>';
	}

	/**
	 * Whether search-result image sizes apply to the configured photo storage.
	 *
	 * @return bool
	 */
	public static function should_render_search_result_image_size_control() {
		return 'urls' !== get_option( 'propertyhive_images_stored_as', '' );
	}

	/**
	 * Render the ordered legacy global search-result settings.
	 *
	 * These controls intentionally do not include the old columns or layout
	 * settings. Those concerns are owned by the template-set controls above.
	 *
	 * @param array $settings Current template-assistant settings.
	 */
	private static function render_search_result_global_controls( $settings ) {
		$settings      = is_array( $settings ) ? $settings : PH_Template_Set_Settings::get_settings();
		$legacy        = PH_Template_Set_Settings::get_search_result_global_settings( $settings );
		$order_options = PH_Template_Set_Settings::get_search_result_order_options();
		$image_options = PH_Template_Set_Settings::get_search_result_image_size_options();

		self::render_template_editor_section_start( __( 'Search result settings', 'propertyhive' ) );
		self::render_template_editor_select( 'search_result_default_order', __( 'Default sort order', 'propertyhive' ), $order_options, $legacy['search_result_default_order'] );
		self::render_search_result_fields_control( $settings, $legacy['search_result_fields'] );
		// URL-backed photos do not have WordPress image sizes to select. Match
		// the legacy settings screen and omit this control for that storage mode.
		if ( self::should_render_search_result_image_size_control() ) {
			self::render_template_editor_select( 'search_result_image_size', __( 'Image size', 'propertyhive' ), $image_options, $legacy['search_result_image_size'] );
		}
		self::render_template_editor_textarea( 'search_result_css', __( 'Custom CSS', 'propertyhive' ), $legacy['search_result_css'], 8 );
		self::render_template_editor_checkbox( 'search_result_css_all_pages', __( 'Apply custom CSS to all pages', 'propertyhive' ), $legacy['search_result_css_all_pages'] );
		self::render_template_editor_section_end();
	}

	/**
	 * Render the ordered search-result field selector.
	 *
	 * @param array $settings Current template-assistant settings.
	 * @param array $selected Ordered selected field tokens.
	 */
	private static function render_search_result_fields_control( $settings, $selected ) {
		$options  = PH_Template_Set_Settings::get_search_result_field_options( $settings );
		$selected = PH_Template_Set_Settings::sanitize_search_result_fields( $selected, '', $settings );
		$ordered  = array();

		foreach ( $selected as $field ) {
			if ( array_key_exists( $field, $options ) ) {
				$ordered[ $field ] = $options[ $field ];
			}
		}

		foreach ( $options as $field => $label ) {
			if ( ! array_key_exists( $field, $ordered ) ) {
				$ordered[ $field ] = $label;
			}
		}

		echo '<div class="ph-template-editor-field ph-template-editor-field-search-result-fields ph-template-editor-search-result-settings" data-ph-template-editor-search-result-settings>';
			echo '<span>' . esc_html__( 'Fields shown (in order)', 'propertyhive' ) . '</span>';
			echo '<div class="ph-template-editor-search-result-fields-list" data-ph-template-editor-search-result-fields-list role="list" aria-label="' . esc_attr__( 'Search result fields in display order', 'propertyhive' ) . '">';
			foreach ( $ordered as $field => $label ) {
				$is_selected = in_array( $field, $selected, true );
				echo '<div class="ph-template-editor-search-result-field" data-ph-template-editor-search-result-field="' . esc_attr( $field ) . '" data-ph-template-editor-search-result-field-label="' . esc_attr( $label ) . '" role="listitem">';
					echo '<button type="button" class="ph-template-editor-search-result-field-handle" data-ph-template-editor-search-result-field-handle draggable="true" aria-label="' . esc_attr( sprintf( __( 'Reorder %s', 'propertyhive' ), $label ) ) . '" title="' . esc_attr__( 'Drag to reorder', 'propertyhive' ) . '">↕</button>';
					echo '<label><input type="checkbox" name="search_result_fields[]" value="' . esc_attr( $field ) . '"' . checked( $is_selected, true, false ) . ' data-ph-template-editor-control data-ph-template-editor-search-result-field-checkbox><span>' . esc_html( $label ) . '</span></label>';
				echo '</div>';
			}
			echo '</div>';
			echo '<small class="ph-template-editor-field-help">' . esc_html__( 'Select the fields to show. Their order here is used on each result card.', 'propertyhive' ) . '</small>';
		echo '</div>';
	}

	/**
	 * Build editor preview URLs for a template select.
	 *
	 * @param array $templates Template choices.
	 * @return array
	 */
	public static function get_template_editor_preview_urls( $templates ) {
		$urls = array();

		foreach ( $templates as $template => $label ) {
			$urls[ $template ] = add_query_arg( PH_Template_Set::EDIT_QUERY_ARG, '1', PH_Template_Set_Request_Context::get_template_preview_url( $template ) );
		}

		return $urls;
	}

	/**
	 * Preserve a setting when it is not shown in the current editor context.
	 *
	 * @param string $name  Control name.
	 * @param string $value Current value.
	 */
	public static function render_template_editor_hidden( $name, $value ) {
		echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
	}

	/**
	 * Render an editor checkbox control.
	 *
	 * @param string $name Control name.
	 * @param string $label Control label.
	 * @param string $value Current value.
	 */
	public static function render_template_editor_checkbox( $name, $label, $value ) {
		echo '<label class="ph-template-editor-toggle">';
			echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="">';
			echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="yes"' . checked( 'yes', $value, false ) . ' data-ph-template-editor-control>';
			echo '<span>' . esc_html( $label ) . '</span>';
		echo '</label>';
	}

	/**
	 * Render currently available template-scoped search controls.
	 *
	 * @param string $template Search template slug.
	 */
	private static function render_search_manifest_controls( $template ) {
		$controls = PH_Template_Set_Catalog::get_available_search_template_controls( $template );
		$groups   = self::get_search_control_groups( $template );

		foreach ( $groups as $group ) {
			self::render_template_editor_section_start( $group['label'] );

			foreach ( $group['controls'] as $key ) {
				if ( ! isset( $controls[ $key ] ) ) {
					continue;
				}

				$control = $controls[ $key ];
				$value   = PH_Template_Set_Settings::get_search_for_template( $key, $template );

				if ( 'checkbox' === $control['type'] ) {
					self::render_template_editor_checkbox( $key, $control['label'], $value );
				} else {
					self::render_template_editor_select( $key, $control['label'], $control['options'], $value );
				}
			}

			self::render_template_editor_section_end();
		}
	}

	/**
	 * Render available add-on settings into the source sections that the
	 * existing sidebar organizer turns into accordion groups.
	 *
	 * @param string $context Editor context.
	 */
	private static function render_addon_settings_sections( $context ) {
		$definitions     = PH_Template_Set_Addon_Settings::get_available_definitions( $context );
		$public_settings = PH_Template_Set_Addon_Settings::get_public_settings( $context );

		foreach ( $definitions as $definition ) {
			self::render_template_editor_section_start( $definition['label'] );

			$first_control = true;
			foreach ( (array) $definition['controls'] as $key => $control ) {
				if ( ! is_array( $control ) || empty( $control['option_key'] ) ) {
					continue;
				}

				$value = isset( $public_settings[ $definition['id'] ][ $key ] ) ? $public_settings[ $definition['id'] ][ $key ] : '';

				self::render_addon_settings_control(
					$definition,
					$key,
					$control,
					$value,
					$first_control
				);
				$first_control = false;
			}

			self::render_template_editor_section_end();
		}
	}

	/**
	 * Render one registered add-on editor control.
	 *
	 * @param array  $definition Add-on definition.
	 * @param string $key        Control key.
	 * @param array  $control    Control definition.
	 * @param mixed  $value      Current value.
	 * @param bool   $with_meta  Whether to include section metadata and copy.
	 */
	private static function render_addon_settings_control( $definition, $key, $control, $value, $with_meta ) {
		$is_multiple = isset( $control['type'] ) && 'multiselect' === $control['type'];
		$is_checkbox = isset( $control['type'] ) && 'checkbox' === $control['type'];
		$help        = isset( $control['help'] ) ? (string) $control['help'] : '';
		$name        = PH_Template_Set_Addon_Settings::get_field_name( $definition['id'], $key, $is_multiple );
		$field_class = 'ph-template-editor-addon-' . sanitize_html_class( $definition['id'] . '-' . $key );

		if ( $is_checkbox ) {
			$field_class .= ' ph-template-editor-field-checkbox';
		}

		$meta_attrs  = '';

		if ( $with_meta ) {
			$meta_attrs .= ' data-ph-template-editor-addon-id="' . esc_attr( $definition['id'] ) . '"';
			$meta_attrs .= ' data-ph-template-editor-addon-scope="' . esc_attr( isset( $definition['scope'] ) ? $definition['scope'] : 'site_wide' ) . '"';
			$meta_attrs .= ' data-ph-template-editor-addon-advanced-url="' . esc_url( isset( $definition['advanced_url'] ) ? $definition['advanced_url'] : '' ) . '"';
			$meta_attrs .= ' data-ph-template-editor-addon-reload-after-save="' . ( ! empty( $definition['reload_after_save'] ) ? 'true' : 'false' ) . '"';
		}

		echo '<label class="ph-template-editor-field ' . esc_attr( $field_class ) . '"' . $meta_attrs . '>';
			if ( $is_checkbox ) {
				$checked_value   = isset( $control['checked_value'] ) ? (string) $control['checked_value'] : '1';
				$unchecked_value = isset( $control['unchecked_value'] ) ? (string) $control['unchecked_value'] : '';
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $unchecked_value ) . '">';
				echo '<input type="checkbox" name="' . esc_attr( $name ) . '" value="' . esc_attr( $checked_value ) . '"' . checked( $checked_value, $value, false ) . ' data-ph-template-editor-control data-ph-template-editor-addon-control="' . esc_attr( $definition['id'] ) . '">';
				echo '<span>' . esc_html( isset( $control['label'] ) ? $control['label'] : '' ) . '</span>';
			} else {
				echo '<span>' . esc_html( isset( $control['label'] ) ? $control['label'] : '' ) . '</span>';
			}

			if ( 'multiselect' === $control['type'] ) {
				$selected_values = array_map( 'strval', is_array( $value ) ? $value : array() );
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="">';
				echo '<select name="' . esc_attr( $name ) . '" multiple size="7" data-ph-template-editor-control data-ph-template-editor-addon-control="' . esc_attr( $definition['id'] ) . '">';
					foreach ( (array) $control['options'] as $option_value => $option_label ) {
						$is_selected = in_array( (string) $option_value, $selected_values, true );
						echo '<option value="' . esc_attr( $option_value ) . '"' . selected( $is_selected, true, false ) . '>' . esc_html( $option_label ) . '</option>';
					}
				echo '</select>';
			} elseif ( 'select' === $control['type'] ) {
				echo '<select name="' . esc_attr( $name ) . '" data-ph-template-editor-control data-ph-template-editor-addon-control="' . esc_attr( $definition['id'] ) . '">';
					foreach ( (array) $control['options'] as $option_value => $option_label ) {
						echo '<option value="' . esc_attr( $option_value ) . '"' . selected( $option_value, $value, false ) . '>' . esc_html( $option_label ) . '</option>';
					}
				echo '</select>';
			} elseif ( $is_checkbox ) {
				// The checkbox and label are rendered above as one horizontal toggle.
			} else {
				$placeholder = isset( $control['placeholder'] ) ? (string) $control['placeholder'] : '';
				echo '<input type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . ( $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' ) . ( isset( $control['maxlength'] ) ? ' maxlength="' . absint( $control['maxlength'] ) . '"' : '' ) . ' data-ph-template-editor-control data-ph-template-editor-addon-control="' . esc_attr( $definition['id'] ) . '">';
			}

			if ( $help && ! $is_checkbox ) {
				echo '<small class="ph-template-editor-field-help">' . esc_html( $help ) . '</small>';
			}

		echo '</label>';
	}

	/**
	 * Render only the controls declared by the current detail manifest.
	 *
	 * @param string $template Detail template slug.
	 */
	private static function render_detail_manifest_controls( $template ) {
		$manifest = PH_Template_Set_Catalog::get_detail_template_manifest( $template );
		$controls = PH_Template_Set_Catalog::get_available_detail_template_controls( $template );
		$groups   = self::get_detail_control_groups( $template );

		foreach ( $groups as $group ) {
			self::render_template_editor_section_start( $group['label'] );

			foreach ( $group['controls'] as $key ) {
				if ( ! isset( $controls[ $key ] ) ) {
					continue;
				}

				$control = $controls[ $key ];

				if ( array_key_exists( $key, (array) $manifest['locked'] ) ) {
					$value_label = isset( $control['options'][ $manifest['locked'][ $key ] ] ) ? $control['options'][ $manifest['locked'][ $key ] ] : $manifest['locked'][ $key ];
					echo '<div class="ph-template-editor-field ph-template-editor-field-locked" data-ph-template-editor-panel-control="' . esc_attr( $key ) . '">';
						echo '<span>' . esc_html( $control['label'] ) . '</span>';
						echo '<input type="text" value="' . esc_attr( $value_label ) . '" aria-label="' . esc_attr( $control['label'] ) . '" disabled>';
						echo '<small>' . esc_html__( "Set by this template's design", 'propertyhive' ) . '</small>';
						echo '</div>';
					continue;
				}

				$value = PH_Template_Set_Settings::get_for_template( $key, $template );

				if ( 'checkbox' === $control['type'] ) {
					self::render_template_editor_checkbox( $key, $control['label'], $value );
				} else {
					self::render_template_editor_select( $key, $control['label'], $control['options'], $value );
				}
			}

			self::render_template_editor_section_end();
		}
	}

	/**
	 * Save template editor settings.
	 */
	public static function ajax_save_template_editor() {
		if ( ! PH_Template_Set_Request_Context::can_manage_template_set() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to edit templates.', 'propertyhive' ) ), 403 );
		}

		check_ajax_referer( PH_Template_Set::EDITOR_NONCE_ACTION, 'security' );

		$editor_context = isset( $_POST['template_set_editor_context'] ) ? sanitize_key( wp_unslash( $_POST['template_set_editor_context'] ) ) : '';
		$addon_save     = PH_Template_Set_Addon_Settings::prepare_save( $_POST, $editor_context );

		if ( is_wp_error( $addon_save ) ) {
			wp_send_json_error( array( 'message' => $addon_save->get_error_message() ), 400 );
		}

		$current_settings = get_option( 'propertyhive_template_assistant', array() );
		$settings         = PH_Template_Set_Settings::sanitize_template_set_settings( $_POST, $current_settings, true );

		$addon_committed = PH_Template_Set_Addon_Settings::commit_save( $addon_save, $settings );

		if ( is_wp_error( $addon_committed ) ) {
			wp_send_json_error( array( 'message' => $addon_committed->get_error_message() ), 500 );
		}

		wp_send_json_success(
			array(
				'message'           => __( 'Template saved.', 'propertyhive' ),
				'settings'          => PH_Template_Set_Settings::get_public_settings( $settings ),
				'addon_settings'     => PH_Template_Set_Addon_Settings::get_public_settings( $editor_context ),
				'reloadRequired'     => ! empty( $addon_save['reload_after_save'] ),
				'reload_after_save'  => ! empty( $addon_save['reload_after_save'] ),
			)
		);
	}

	/**
	 * Front-end script data.
	 *
	 * @return array
	 */
	public static function get_script_data() {
		$settings  = PH_Template_Set_Settings::get_settings();
		$map_state = PH_Template_Set_Request_Context::get_map_search_state();
		$location_map_provider_configured = '' !== (string) get_option( 'propertyhive_maps_provider' );
		$location_map_is_real             = 'real-map' === PH_Template_Set_Request_Context::get_location_map();

		return array(
			'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
			'security'            => wp_create_nonce( PH_Template_Set::EDITOR_NONCE_ACTION ),
			'editorActive'        => PH_Template_Set_Request_Context::is_template_editor_active(),
			'editorMode'          => $settings['template_set_editor_mode'],
			'previewQueryArgs'    => PH_Template_Set_Request_Context::get_preview_query_args(),
			'mapSearchPreviewQueryArg' => PH_Template_Set::MAP_SEARCH_FORMAT_QUERY_ARG,
			'searchResultPreviewQueryArgs' => array(
				'defaultOrder' => PH_Template_Set::SEARCH_RESULT_DEFAULT_ORDER_QUERY_ARG,
				'fields'       => PH_Template_Set::SEARCH_RESULT_FIELDS_QUERY_ARG,
				'imageSize'    => PH_Template_Set::SEARCH_RESULT_IMAGE_SIZE_QUERY_ARG,
			),
			'settings'            => PH_Template_Set_Settings::get_public_settings( $settings ),
			'searchResultEditor'  => self::get_search_result_editor_data( $settings ),
			'addonSettings'       => PH_Template_Set_Addon_Settings::get_public_definitions( self::get_template_editor_context() ),
			'searchFormEditor'    => PH_Template_Set_Search_Form_Editor::get_script_data( self::get_template_editor_context() ),
			'editorSidebarLayout' => self::get_editor_sidebar_layout(),
			'mapSearchState'      => $map_state,
			'locationMapProviderConfigured' => $location_map_provider_configured,
			'requiresFullPreviewNavigation' => ( $map_state['available'] && $map_state['usable'] )
				|| ( class_exists( 'PH_Location_Autocomplete' ) && PH_Template_Set::is_add_on_usable( 'propertyhive-location-autocomplete' ) )
				|| ( class_exists( 'PH_Radial_Search' ) && PH_Template_Set::is_add_on_usable( 'propertyhive-radial-search' ) )
				|| ( function_exists( 'PHIS' ) && PH_Template_Set::is_add_on_usable( 'propertyhive-infinite-scroll' ) )
				|| ( class_exists( 'PH_Viewing_Request' ) && PH_Template_Set::is_add_on_usable( 'propertyhive-viewing-request' ) )
				|| ( $location_map_is_real && $location_map_provider_configured ),
			'labels'              => array(
				'ready'             => __( 'Ready', 'propertyhive' ),
				'changed'           => __( 'Unsaved changes', 'propertyhive' ),
				'loading'           => __( 'Loading...', 'propertyhive' ),
				'save'              => __( 'Save', 'propertyhive' ),
				'saving'            => __( 'Saving...', 'propertyhive' ),
				'saved'             => __( 'Saved', 'propertyhive' ),
				'error'             => __( 'Could not save', 'propertyhive' ),
				'unsavedNavigation' => __( 'You have unsaved changes. Leave this page without saving?', 'propertyhive' ),
				'resultsProgress'   =>
					/* translators: 1: number of currently displayed results, 2: total number of results */
					__( 'Showing %1$s of %2$s', 'propertyhive' ),
			),
		);
	}

	/**
	 * Public, sanitised data for the legacy search-result controls.
	 *
	 * @param array $settings Current template-assistant settings.
	 * @return array
	 */
	public static function get_search_result_editor_data( $settings = array() ) {
		$settings = is_array( $settings ) ? $settings : PH_Template_Set_Settings::get_settings();

		return array(
			'settings'        => PH_Template_Set_Settings::get_search_result_global_settings( $settings ),
			'orderOptions'    => PH_Template_Set_Settings::get_search_result_order_options(),
			'fieldOptions'    => PH_Template_Set_Settings::get_search_result_field_options( $settings ),
			'imageSizeOptions' => PH_Template_Set_Settings::get_search_result_image_size_options(),
		);
	}

	/**
	 * Editor sidebar layout data for front-end JavaScript.
	 *
	 * @return array
	 */
	public static function get_editor_sidebar_layout() {
		$detail_groups = array_merge(
			array(
				array(
					'id'       => 'template',
					'label'    => __( 'Template', 'propertyhive' ),
					'controls' => array( 'template_set_detail_template' ),
				),
			),
			self::get_detail_control_groups( PH_Template_Set_Request_Context::get_detail_template() )
		);
		$detail_groups = array_merge( $detail_groups, PH_Template_Set_Addon_Settings::get_sidebar_groups( 'detail' ) );
		$detail_active = isset( $detail_groups[1]['id'] ) ? $detail_groups[1]['id'] : 'template';
		$search_groups = array(
			array(
				'id'       => 'template',
				'label'    => __( 'Template', 'propertyhive' ),
				'controls' => array( 'template_set_search_template' ),
			),
			array(
				'id'       => 'search-form',
				'label'    => __( 'Search form', 'propertyhive' ),
				'controls' => array( 'ph_search_form_builder' ),
			),
			array(
				'id'       => 'layout',
				'label'    => __( 'Layout', 'propertyhive' ),
				'controls' => array( 'template_set_search_layout', 'template_set_search_grid_columns' ),
			),
			array(
				'id'       => 'card-appearance',
				'label'    => __( 'Card appearance', 'propertyhive' ),
				'controls' => array( 'template_set_search_card_size', 'template_set_image_style' ),
			),
			array(
				'id'       => 'details',
				'label'    => __( 'Details shown', 'propertyhive' ),
				'controls' => array( 'template_set_show_branch', 'template_set_show_badges' ),
			),
			array(
				'id'       => 'result-settings',
				'label'    => __( 'Result settings', 'propertyhive' ),
				'controls' => array( 'search_result_default_order', 'search_result_fields[]', 'search_result_image_size', 'search_result_css', 'search_result_css_all_pages' ),
			),
		);
		$search_groups = array_merge( $search_groups, PH_Template_Set_Addon_Settings::get_sidebar_groups( 'search' ) );
		$search_groups = array_merge( $search_groups, self::get_search_control_groups( PH_Template_Set_Request_Context::get_search_template() ) );

		return array(
			'active' => array(
				'search' => 'layout',
				'detail' => $detail_active,
			),
			'groups' => array(
				'search' => $search_groups,
				'detail' => $detail_groups,
			),
		);
	}

	/**
	 * Build grouped detail controls directly from the current manifest.
	 *
	 * @param string $template Detail template slug.
	 * @return array
	 */
	private static function get_detail_control_groups( $template ) {
		$controls = PH_Template_Set_Catalog::get_available_detail_template_controls( $template );
		$labels   = array(
			'media'       => __( 'Media', 'propertyhive' ),
			'enquiry'     => __( 'Enquiries', 'propertyhive' ),
			'modules'     => __( 'Modules', 'propertyhive' ),
			'purchase-calculators' => __( 'Purchase calculators', 'propertyhive' ),
			'shortlist'   => __( 'Shortlist', 'propertyhive' ),
			'send-to-friend' => __( 'Send to friend', 'propertyhive' ),
			'rooms'       => __( 'Rooms', 'propertyhive' ),
			'what3words'  => __( 'what3words', 'propertyhive' ),
			'recommended' => __( 'Related properties', 'propertyhive' ),
		);
		$groups = array();

		foreach ( $controls as $key => $control ) {
			$group = isset( $control['group'] ) ? sanitize_title( $control['group'] ) : 'details';

			if ( ! isset( $groups[ $group ] ) ) {
				$groups[ $group ] = array(
					'id'       => $group,
					'label'    => isset( $labels[ $group ] ) ? $labels[ $group ] : ucfirst( str_replace( '-', ' ', $group ) ),
					'controls' => array(),
				);
			}

			$groups[ $group ]['controls'][] = $key;
		}

		return array_values( $groups );
	}

	/**
	 * Build grouped search controls directly from the current manifest.
	 *
	 * @param string $template Search template slug.
	 * @return array
	 */
	private static function get_search_control_groups( $template ) {
		$controls = PH_Template_Set_Catalog::get_available_search_template_controls( $template );
		$labels   = array(
			'save-search' => __( 'Save search', 'propertyhive' ),
			'shortlist'  => __( 'Shortlist', 'propertyhive' ),
		);
		$groups = array();

		foreach ( $controls as $key => $control ) {
			$group = isset( $control['group'] ) ? sanitize_title( $control['group'] ) : 'details';

			if ( ! isset( $groups[ $group ] ) ) {
				$groups[ $group ] = array(
					'id'       => $group,
					'label'    => isset( $labels[ $group ] ) ? $labels[ $group ] : ucfirst( str_replace( '-', ' ', $group ) ),
					'controls' => array(),
				);
			}

			$groups[ $group ]['controls'][] = $key;
		}

		return array_values( $groups );
	}
}
