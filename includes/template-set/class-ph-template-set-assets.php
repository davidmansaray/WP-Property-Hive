<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Template set front-end assets and style variables.
 */
class PH_Template_Set_Assets {

	/**
	 * Version preview assets by modified time so local design changes are not cached.
	 *
	 * @param string $relative_path Asset path relative to the plugin root.
	 * @return string
	 */
	public static function asset_version( $relative_path ) {
		$path = PH()->plugin_path() . '/' . ltrim( $relative_path, '/' );

		return file_exists( $path ) ? (string) filemtime( $path ) : PH_VERSION;
	}

	/**
	 * Register styles.
	 *
	 * @param array $styles Existing styles.
	 * @return array
	 */
	public static function enqueue_styles( $styles ) {
		if ( ! self::should_enqueue_frontend_assets( true ) ) {
			return $styles;
		}

		$base_url       = str_replace( array( 'http:', 'https:' ), '', PH()->plugin_url() ) . '/assets/css/';
		$search_request = PH_Template_Set_Request_Context::is_search_results_request();
		$search_template = $search_request ? PH_Template_Set_Request_Context::get_search_template() : '';
		$legacy_search   = $search_request && 'portal-style-search-results' === $search_template;

		if ( ! $search_request || $legacy_search || PH_Template_Set_Request_Context::is_template_editor_active() ) {
			$styles['propertyhive-template-set'] = array(
				'src'     => $base_url . 'template-set.css',
				'deps'    => array( 'propertyhive-general' ),
				'version' => self::asset_version( 'assets/css/template-set.css' ),
				'media'   => 'all',
			);
		}

		if ( $search_request && ! $legacy_search ) {
			$structure_dependencies = array( 'propertyhive-general' );
			if ( PH_Template_Set_Request_Context::is_template_editor_active() ) {
				$structure_dependencies[] = 'propertyhive-template-set';
			}

			$styles['propertyhive-template-set-search-structure'] = array(
				'src'     => $base_url . 'template-set-search-structure.css',
				'deps'    => $structure_dependencies,
				'version' => self::asset_version( 'assets/css/template-set-search-structure.css' ),
				'media'   => 'all',
			);

			if ( apply_filters( 'propertyhive_template_set_enqueue_search_fallbacks', true ) ) {
				$fallbacks = array(
					'propertyhive-template-set-search-fallbacks' => array(
						'src'     => $base_url . 'template-set-search-fallbacks.css',
						'deps'    => array( 'propertyhive-template-set-search-structure' ),
						'version' => self::asset_version( 'assets/css/template-set-search-fallbacks.css' ),
						'media'   => 'all',
					),
				);
				$fallbacks = apply_filters( 'propertyhive_template_set_search_styles', $fallbacks );
				if ( is_array( $fallbacks ) ) {
					$styles = array_merge( $styles, $fallbacks );
				}
			}
		}

		return $styles;
	}

	/**
	 * Register template-set scripts.
	 */
	public static function enqueue_scripts() {
		if ( ! self::should_enqueue_frontend_assets() ) {
			return;
		}

		$script_base_url = str_replace( array( 'http:', 'https:' ), '', PH()->plugin_url() ) . '/assets/js/frontend/';
		$search_form_builder_dependencies = array( 'propertyhive-template-set-editor-sidebar' );

		/*
		 * Search-form previews are replaced after the page has loaded. A slider
		 * added in the editor therefore cannot rely on the renderer enqueueing
		 * its dependencies during the later AJAX request: those enqueue calls
		 * happen too late to print assets into the original page. Load the
		 * WordPress slider dependencies up front for authorized editor sessions
		 * so the returned inline initializer is always safe to run.
		 */
		if (
			PH_Template_Set_Request_Context::is_search_results_request()
			&& PH_Template_Set_Request_Context::is_template_editor_active()
			&& PH_Template_Set_Search_Form_Editor::can_manage()
		) {
			if ( ! wp_script_is( 'jquery-ui-touch-punch', 'registered' ) ) {
				wp_register_script(
					'jquery-ui-touch-punch',
					PH()->plugin_url() . '/assets/js/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js',
					array( 'jquery', 'jquery-ui-slider' ),
					'0.2.3',
					true
				);
			}

			wp_enqueue_style( 'jquery-ui-style', PH()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.css', array(), PH_VERSION );

			$search_form_builder_dependencies[] = 'jquery-ui-slider';
			$search_form_builder_dependencies[] = 'jquery-ui-touch-punch';
		}

		$module_scripts  = array(
			'propertyhive-template-set-gallery'             => array(
				'path' => 'assets/js/frontend/template-set/gallery.js',
				'deps' => array(),
			),
			'propertyhive-template-set-editor-preview'      => array(
				'path' => 'assets/js/frontend/template-set/editor-preview.js',
				'deps' => array( 'propertyhive-template-set-gallery' ),
			),
			'propertyhive-template-set-editor-sidebar'      => array(
				'path' => 'assets/js/frontend/template-set/editor-sidebar.js',
				'deps' => array(),
			),
			'propertyhive-template-set-search-form-builder' => array(
				'path' => 'assets/js/frontend/template-set/search-form-builder.js',
				'deps' => $search_form_builder_dependencies,
			),
			'propertyhive-template-set-search-map-rail'     => array(
				'path' => 'assets/js/frontend/template-set/search-map-rail.js',
				'deps' => array(),
			),
		);

		foreach ( $module_scripts as $handle => $script ) {
			wp_enqueue_script(
				$handle,
				$script_base_url . str_replace( 'assets/js/frontend/', '', $script['path'] ),
				$script['deps'],
				self::asset_version( $script['path'] ),
				true
			);
		}

		wp_enqueue_script(
			'propertyhive-template-set',
			$script_base_url . 'template-set.js',
			array_keys( $module_scripts ),
			self::asset_version( 'assets/js/frontend/template-set.js' ),
			true
		);

		wp_localize_script( 'propertyhive-template-set', 'phTemplateSet', PH_Template_Set_Editor_Controller::get_script_data() );
	}

	/**
	 * Should Template Set frontend assets be loaded for this request?
	 *
	 * @param bool $include_modules Whether module shortcode pages should match.
	 * @return bool
	 */
	private static function should_enqueue_frontend_assets( $include_modules = false ) {
		if ( is_property() || PH_Template_Set_Request_Context::is_search_results_request() ) {
			return PH_Template_Set_Request_Context::is_enabled() || PH_Template_Set_Request_Context::can_show_template_switcher();
		}

		if ( $include_modules && is_singular() ) {
			$post = get_post();

			if ( $post && has_shortcode( $post->post_content, 'propertyhive_featured_template' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Print CSS variables from safe style controls.
	 */
	public static function print_style_variables( $rendering_module = false ) {
		if ( ! PH_Template_Set_Request_Context::is_enabled() && ! $rendering_module ) {
			return;
		}

		$settings = PH_Template_Set_Settings::get_settings();
		$brand    = sanitize_hex_color( $settings['template_set_brand_colour'] );
		$accent   = sanitize_hex_color( $settings['template_set_accent_colour'] );

		if ( empty( $brand ) ) {
			$brand = '#155e63';
		}

		if ( empty( $accent ) ) {
			$accent = '#b7791f';
		}

		echo '<style id="propertyhive-template-set-vars">.ph-template-set{--ph-template-brand:' . esc_html( $brand ) . ';--ph-template-accent:' . esc_html( $accent ) . ';}</style>' . "\n";
	}
}
