<?php
/**
 * PropertyHive Settings Page/Tab
 *
 * @author 		PropertyHive
 * @category 	Admin
 * @package 	PropertyHive/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'PH_Settings_Page' ) ) :

/**
 * PH_Settings_Page
 */
class PH_Settings_Page {

	protected $id    = '';
	protected $label = '';

	/**
	 * Add this page to settings
	 */
	public function add_settings_page( $pages ) {
		$pages[ $this->id ] = $this->label;

		return $pages;
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		return array();
	}

	/**
	 * Get sections
	 *
	 * @return array
	 */
	public function get_sections() {
		return array();
	}

	/**
	 * Output sections
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) ) {
			return;
		}

		/* translators: %s: Settings tab label. */
		echo '<nav class="ph-settings-sections-nav" aria-label="' . esc_attr( sprintf( __( '%s sections', 'propertyhive' ), $this->label ) ) . '">';
		echo '<ul class="subsubsub ph-settings-sections">';

		foreach ( $sections as $id => $label ) {
			$section_id = sanitize_title( (string) $id );
			$url_args = array(
				'page' => 'ph-settings',
				'tab'  => $this->id,
			);

			if ( '' !== $section_id ) {
				$url_args['section'] = $section_id;
			}

			$is_current = $current_section === $section_id;

			echo '<li><a href="' . esc_url( add_query_arg( $url_args, admin_url( 'admin.php' ) ) ) . '" class="' . ( $is_current ? 'current' : '' ) . '"' . ( $is_current ? ' aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a></li>';
		}

		echo '</ul></nav>';
	}

	/**
	 * Output the settings
	 */
	public function output() {
		$settings = $this->get_settings();

		PH_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings();
		PH_Admin_Settings::save_fields( $settings );

		 if ( $current_section )
	    	do_action( 'propertyhive_update_options_' . $this->id . '_' . $current_section );
	}
}

endif;
