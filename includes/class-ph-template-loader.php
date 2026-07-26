<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Template Loader
 *
 * @class 		PH_Template_Loader
 * @version		1.0.0
 * @package		PropertyHive/Classes
 * @category	Class
 * @author 		PropertyHive
 */
class PH_Template_Loader {

	/**
	 * Cached search-results ownership decision for this request.
	 *
	 * @var array|null
	 */
	private static $search_results_ownership = null;

	/**
	 * Whether Property Hive templates are enabled for this request.
	 *
	 * @var bool|null
	 */
	private static $templates_enabled = null;

	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'init' ) );
	}

	public function init()
	{
		$use_propertyhive_templates = self::templates_enabled();
		if ( $use_propertyhive_templates === false )
		{
			return;
		}

		$priority = 10;
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		if ( is_plugin_active( 'oxygen/functions.php' ) )
        {
        	$priority = 99;
        }
        add_filter( 'template_include', array( $this, 'template_loader' ), $priority );
	}

	/**
	 * Does Property Hive own the property search-results template?
	 *
	 * @return bool
	 */
	public static function owns_search_results_template() {
		$ownership = self::get_search_results_template_ownership();

		return ! empty( $ownership['owns'] );
	}

	/**
	 * Resolve the property search-results template owner once per request.
	 *
	 * Theme overrides are still Property Hive-owned because they use the
	 * documented Property Hive archive contract. Builder/external takeovers
	 * stand down.
	 *
	 * @return array
	 */
	public static function get_search_results_template_ownership() {
		if ( null !== self::$search_results_ownership ) {
			return self::$search_results_ownership;
		}

		$ownership = array(
			'owns'              => false,
			'owner'             => 'none',
			'resolved_template' => '',
		);

		if ( ! self::templates_enabled() ) {
			$ownership['owner'] = 'disabled';
		} elseif ( self::has_bricks_property_archive_template() ) {
			$ownership['owner'] = 'bricks';
		} else {
			$find = array_unique( array(
				'propertyhive.php',
				'archive-property.php',
				PH_TEMPLATE_PATH . 'archive-property.php',
			) );
			$theme_template = locate_template( $find );

			$ownership['owns']              = true;
			$ownership['owner']             = $theme_template ? 'theme' : 'plugin';
			$ownership['resolved_template'] = $theme_template ? $theme_template : PH()->plugin_path() . '/templates/archive-property.php';
		}

		$ownership = apply_filters( 'propertyhive_search_results_template_ownership', $ownership );
		$ownership = is_array( $ownership ) ? wp_parse_args( $ownership, array(
			'owns'              => false,
			'owner'             => 'none',
			'resolved_template' => '',
		) ) : array(
			'owns'              => false,
			'owner'             => 'none',
			'resolved_template' => '',
		);
		$ownership['owns'] = (bool) $ownership['owns'];

		self::$search_results_ownership = $ownership;

		return self::$search_results_ownership;
	}

	/**
	 * Apply the global template enable filter once.
	 *
	 * @return bool
	 */
	private static function templates_enabled() {
		if ( null === self::$templates_enabled ) {
			self::$templates_enabled = apply_filters( 'propertyhive_use_propertyhive_templates', true ) !== false;
		}

		return self::$templates_enabled;
	}

	/**
	 * Does Bricks own the property post-type archive?
	 *
	 * @return bool
	 */
	private static function has_bricks_property_archive_template() {
		if ( ! defined( 'BRICKS_DB_TEMPLATE_SLUG' ) ) {
			return false;
		}

		$template_query = new WP_Query(
			array(
				'post_type'              => BRICKS_DB_TEMPLATE_SLUG,
				'post_status'            => 'publish',
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'     => '_bricks_template_type',
						'compare' => 'archive',
					),
				),
			)
		);
		$matched = false;

		if ( $template_query->have_posts() ) {
			while ( $template_query->have_posts() ) {
				$template_query->the_post();
				$template_settings = get_post_meta( get_the_ID(), '_bricks_template_settings', true );

				if ( empty( $template_settings['templateConditions'] ) || ! is_array( $template_settings['templateConditions'] ) ) {
					continue;
				}

				foreach ( $template_settings['templateConditions'] as $condition ) {
					if (
						isset( $condition['main'], $condition['archiveType'], $condition['archivePostTypes'] )
						&& 'archiveType' === $condition['main']
						&& is_array( $condition['archiveType'] )
						&& in_array( 'postType', $condition['archiveType'], true )
						&& is_array( $condition['archivePostTypes'] )
						&& in_array( 'property', $condition['archivePostTypes'], true )
					) {
						$matched = true;
						break 2;
					}
				}
			}
		}

		wp_reset_postdata();

		return $matched;
	}

	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. propertyhive looks for theme
	 * overrides in /theme/propertyhive/ by default
	 *
	 * For beginners, it also looks for a propertyhive.php template first. If the user adds
	 * this to the theme (containing a propertyhive() inside) this will be used for all
	 * propertyhive templates.
	 *
	 * @param mixed $template
	 * @return string
	 */
	public function template_loader( $template ) {
	    
		$find = array( 'propertyhive.php' );
		$file = '';
        
		if ( is_single() && get_post_type() == 'property' ) 
		{	
			$use_property_hive_template = true;

			// Check for single Divi property page template
			if ( class_exists( 'ET_Theme_Builder_Request' ) )
			{
				$template_query = new WP_Query(
					array(
						'post_type'              => ET_THEME_BUILDER_TEMPLATE_POST_TYPE,
						'post_status'            => 'publish',
						'posts_per_page'         => 1,
						'fields'                 => 'ids',
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'meta_query'             => array(
							array(
								'key'     => '_et_enabled',
								'value'   => '1',
								'compare' => '=',
							),
							array(
								'key'     => '_et_body_layout_id',
								'value'   => '0',
								'compare' => '!=',
							),
							array(
								'key'     => "_et_use_on",
								'value'   => 'singular:post_type:property:all',
								'compare' => '=',
							),
							array(
								'key'     => '_et_theme_builder_marked_as_unused',
								'compare' => 'NOT EXISTS',
							),
						),
					)
				);

				if ( $template_query->have_posts() ) 
				{
					$use_property_hive_template = false;
				}
				wp_reset_postdata();
			}

			// Check for single Bricks Builder property page template
			if ( defined('BRICKS_DB_TEMPLATE_SLUG') )
			{
				$template_query = new WP_Query(
					array(
						'post_type'              => BRICKS_DB_TEMPLATE_SLUG,
						'post_status'            => 'publish',
						'fields'                 => 'ids',
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'meta_query'             => array(
							array(
								'key'     => '_bricks_template_type',
								'compare' => 'content',
							),
						),
					)
				);

				if ( $template_query->have_posts() ) 
				{
					while ( $template_query->have_posts() )
					{
						$template_query->the_post();

						$template_settings = get_post_meta( get_the_ID(), '_bricks_template_settings', TRUE );

						if ( 
							isset($template_settings['templateConditions']) && 
							is_array($template_settings['templateConditions']) &&
							!empty($template_settings['templateConditions'])
						)
						{
							foreach ( $template_settings['templateConditions'] as $templateCondition )
							{
								if ( 
									isset($templateCondition['main']) && 
									$templateCondition['main'] == 'postType' &&
									isset($templateCondition['postType']) && 
									is_array($templateCondition['postType']) &&
									in_array('property', $templateCondition['postType'])
								)
								{
									$use_property_hive_template = false;
								}
							}
						}
					}
				}
				wp_reset_postdata();
			}

			// Check for single Salient property page template
			if ( defined('NECTAR_THEME_NAME') && NECTAR_THEME_NAME == 'salient' && class_exists( 'Salient_Core' ) )
			{
				$template_query = new WP_Query(
					array(
						'post_type'              => 'salient_g_sections',
						'post_status'            => 'publish',
						'fields'                 => 'ids',
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
					)
				);

				if ( $template_query->have_posts() ) 
				{
					while ( $template_query->have_posts() )
					{
						$template_query->the_post();

						$conditions = get_post_meta( get_the_ID(), 'nectar_g_section_conditions', TRUE );

						if ( is_array($conditions) )
						{
							foreach ( $conditions as $condition )
							{
								$condition = (array)$condition;
								if ( !isset($condition['options']) || !is_array($condition['options']) ) 
								{
			                        continue;
			                    }

			                    $include_met = false;
			                    $condition_met = false;
			                    foreach ( $condition['options'] as $option )
			                    {
			                    	$option = (array)$option;

			                    	if ( 
			                    		isset($option['type']) && $option['type'] === 'include' &&
			                    		isset($option['value']) && $option['value'] === 'include'
			                    	)
			                    	{
			                    		$include_met = true;
			                    	}
			                    	if ( 
			                    		isset($option['type']) && $option['type'] === 'condition' &&
			                    		isset($option['value']) && $option['value'] === 'post_type__property'
			                    	)
			                    	{
			                    		$condition_met = true;
			                    	}
			                    }

			                    if ( $include_met && $condition_met )
			                    {
			                    	$use_property_hive_template = false;
			                    }
							}
						}
					}
				}
			}

			if ( $use_property_hive_template )
			{
				$file 	= 'single-property.php';
				$find[] = $file;
				$find[] = PH_TEMPLATE_PATH . $file;
			}
		} 
		elseif ( is_post_type_archive( 'property' ) || is_page( ph_get_page_id( 'search_results' ) ) ) 
		{
			$ownership = self::get_search_results_template_ownership();

			if ( $ownership['owns'] )
			{
				$file 	= 'archive-property.php';
				$find[] = $file;
				$find[] = PH_TEMPLATE_PATH . $file;
			}

		}

		if ( $file ) 
		{
			$template = locate_template( array_unique($find) );
			if ( ! $template )
			{
				$template = PH()->plugin_path() . '/templates/' . $file;
			}
		}

		$template = apply_filters( 'propertyhive_loaded_template', $template );

		if ( is_post_type_archive( 'property' ) || is_page( ph_get_page_id( 'search_results' ) ) ) {
			$ownership = self::get_search_results_template_ownership();
			if ( $ownership['owns'] ) {
				$ownership['resolved_template'] = $template;
				$resolved = apply_filters( 'propertyhive_resolved_search_results_template_ownership', $ownership, $template );
				if ( is_array( $resolved ) ) {
					$ownership = wp_parse_args( $resolved, $ownership );
				}
				$ownership['owns'] = (bool) $ownership['owns'];
				self::$search_results_ownership = $ownership;
			}
		}

		return $template;
	}

}

new PH_Template_Loader();
