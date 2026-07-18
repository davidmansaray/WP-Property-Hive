<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Detect supported page builders for the Frontend "Page Builder" experience.
 */
class PH_Template_Set_Page_Builders {

	/**
	 * Supported builders and their marketing links.
	 *
	 * @return array
	 */
	public static function get_supported() {
		return array(
			'elementor' => array(
				'name'      => __( 'Elementor', 'propertyhive' ),
				'url'       => 'https://elementor.com/',
				'docs_url'  => 'https://docs.wp-property-hive.com/article/281-elementor-integration',
				'video_url' => 'https://www.youtube.com/watch?v=EgEwr_ynvq8',
			),
			'divi' => array(
				'name'      => __( 'Divi', 'propertyhive' ),
				'url'       => 'https://www.elegantthemes.com/gallery/divi/',
				'docs_url'  => 'https://docs.wp-property-hive.com/article/488-divi-integration',
				'video_url' => '',
			),
		);
	}

	/**
	 * Detect the active supported builder, if any.
	 *
	 * @return array {
	 *     @type bool   $active        Whether a supported builder is active.
	 *     @type string $builder       Builder key (elementor|divi|'').
	 *     @type string $name          Human readable name.
	 *     @type string $version       Detected version, if available.
	 *     @type bool   $theme_builder Whether theme/template building is available.
	 * }
	 */
	public static function detect() {
		$elementor = self::detect_elementor();
		if ( $elementor['active'] ) {
			return $elementor;
		}

		$divi = self::detect_divi();
		if ( $divi['active'] ) {
			return $divi;
		}

		return array(
			'active'        => false,
			'builder'       => '',
			'name'          => '',
			'version'       => '',
			'theme_builder' => false,
		);
	}

	/**
	 * @return array
	 */
	private static function detect_elementor() {
		$active = defined( 'ELEMENTOR_VERSION' ) || did_action( 'elementor/loaded' );

		return array(
			'active'        => (bool) $active,
			'builder'       => 'elementor',
			'name'          => __( 'Elementor', 'propertyhive' ),
			'version'       => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '',
			// Elementor's Theme Builder ships with Elementor Pro.
			'theme_builder' => defined( 'ELEMENTOR_PRO_VERSION' ),
		);
	}

	/**
	 * @return array
	 */
	private static function detect_divi() {
		$active = defined( 'ET_BUILDER_VERSION' ) || class_exists( 'ET_Builder_Module' ) || function_exists( 'et_setup_theme' );

		$version = '';
		if ( defined( 'ET_BUILDER_VERSION' ) ) {
			$version = ET_BUILDER_VERSION;
		} elseif ( defined( 'ET_CORE_VERSION' ) ) {
			$version = ET_CORE_VERSION;
		}

		return array(
			'active'        => (bool) $active,
			'builder'       => 'divi',
			'name'          => __( 'Divi', 'propertyhive' ),
			'version'       => $version,
			// Divi's Theme Builder is part of Divi core.
			'theme_builder' => (bool) $active,
		);
	}
}
