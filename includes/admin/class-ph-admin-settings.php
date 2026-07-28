<?php
/**
 * PropertyHive Admin Settings Class.
 *
 * @author 		PropertyHive
 * @category 	Admin
 * @package 	PropertyHive/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'PH_Admin_Settings' ) ) :

/**
 * PH_Admin_Settings
 */
class PH_Admin_Settings {

	private static $settings = array();
	private static $errors   = array();
	private static $messages = array();

	/**
	 * Include the settings page classes
	 */
	public static function get_settings_pages() {
		if ( empty( self::$settings ) ) {
			$settings = array();

			include_once( 'settings/class-ph-settings-page.php' );

			$settings[] = include( 'settings/class-ph-settings-general.php' );
            $settings[] = include( 'settings/class-ph-settings-offices.php' );
            $settings[] = include( 'settings/class-ph-settings-custom-fields.php' );
            $propertyhive_template_assistant_auto_deactivated = get_option('propertyhive_template_assistant_auto_deactivated', '');
            if ( !empty($propertyhive_template_assistant_auto_deactivated) )
            {
            	// Only show if they had the TA active and we deactived it. Don't want it showing for new users
	            $settings[] = include( 'settings/class-ph-settings-template-assistant.php' ); // Maybe temporary after migrating TA code into core. Remove in future version
	        }
	        $settings[] = include( 'settings/class-ph-settings-frontend.php' );
            $settings[] = include( 'settings/class-ph-settings-emails.php' );
            $settings[] = include( 'settings/class-ph-settings-features.php' );
            $settings[] = include( 'settings/class-ph-settings-licenses.php' );

			self::$settings = apply_filters( 'propertyhive_get_settings_pages', $settings );
		}
		return self::$settings;
	}

	/**
	 * Save the settings
	 */
	public static function save() {
		global $current_section, $current_tab;

		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'propertyhive-settings' ) )
	    		die( esc_html(__( 'Action failed. Please refresh the page and retry.', 'propertyhive' )) );

	    // Trigger actions
	   	do_action( 'propertyhive_settings_save_' . $current_tab );
	    do_action( 'propertyhive_update_options_' . $current_tab );
	    do_action( 'propertyhive_update_options' );

		self::add_message( __( 'Your settings have been saved.', 'propertyhive' ) );

		update_option( 'propertyhive_queue_flush_rewrite_rules', 'yes' );

		do_action( 'propertyhive_settings_saved' );
	}

	/**
	 * Add a message
	 * @param string $text
	 */
	public static function add_message( $text ) {
		self::$messages[] = $text;
	}

	/**
	 * Add an error
	 * @param string $text
	 */
	public static function add_error( $text ) {
		self::$errors[] = $text;
	}

	/**
	 * Output messages + errors
	 */
	public static function show_messages() {
		if ( sizeof( self::$errors ) > 0 ) {
			foreach ( self::$errors as $error )
			{
				$allowed_tags = array(
				    'a'      => array(
				        'href' => array(),
				    ),
				);

				$error = wp_kses($error, $allowed_tags);

				echo '<div id="message" class="error fade"><p><strong>' . $error . '</strong></p></div>';
			}
		} elseif ( sizeof( self::$messages ) > 0 ) {
			foreach ( self::$messages as $message )
			{
				$allowed_tags = array(
				    'a'      => array(
				        'href' => array(),
				    ),
				);

				$message = wp_kses($message, $allowed_tags);

				echo '<div id="message" class="updated fade"><p><strong>' . $message . '</strong></p></div>';
			}
		}
	}

	/**
	 * Settings page.
	 *
	 * Handles the display of the main propertyhive settings page in admin.
	 *
	 * @access public
	 * @return void
	 */
	public static function output() {
	    global $current_section, $current_tab, $redirect_after_save;

	    do_action( 'propertyhive_settings_start' );

	    //wp_enqueue_script( 'propertyhive_settings', PH()->plugin_url() . '/assets/js/admin/settings.min.js', array( 'jquery'/*, 'jquery-ui-datepicker', 'jquery-ui-sortable', 'iris', 'chosen'*/ ), PH()->version, true );

		/*wp_localize_script( 'propertyhive_settings', 'propertyhive_settings_params', array(
			'i18n_nav_warning' => __( 'The changes you made will be lost if you navigate away from this page.', 'propertyhive' )
		) );*/

		// Include settings pages
		self::get_settings_pages();

		// Get current tab/section
		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( $_GET['tab'] );
		$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( $_REQUEST['section'] );
		if ( '' === $current_section ) {
			$current_section = sanitize_title(
				apply_filters( 'propertyhive_default_settings_section_' . $current_tab, '' )
			);
		}

	    // Save settings if data has been posted
	    //if ( ! empty( $_POST ) )
	    //	self::save();

	    // Add any posted messages
	    if ( ! empty( $_GET['ph_error'] ) )
	    	self::add_error( stripslashes( $_GET['ph_error'] ) );

	     if ( ! empty( $_GET['ph_message'] ) )
	    	self::add_message( stripslashes( $_GET['ph_message'] ) );

	    self::show_messages();

	    // Get tabs for the settings page
	    $tabs = apply_filters( 'propertyhive_settings_tabs_array', array() );

	    include 'views/admin-settings.php';
	}

	/**
	 * Icon + subtitle metadata for the top settings tab bar.
	 *
	 * Keyed by tab id. Third-party tabs without an entry fall back to a generic
	 * icon and no subtitle.
	 *
	 * @return array
	 */
	public static function get_tab_meta() {
		$meta = array(
			'general'      => array( 'icon' => 'gear',     'subtitle' => __( 'Core settings', 'propertyhive' ), 'description' => __( 'Configure the core Property Hive settings for your site.', 'propertyhive' ) ),
			'offices'      => array( 'icon' => 'building',  'subtitle' => __( 'Manage offices', 'propertyhive' ), 'description' => __( 'Manage your branches and office contact details.', 'propertyhive' ) ),
			'customfields' => array( 'icon' => 'sliders',   'subtitle' => __( 'Customise fields', 'propertyhive' ), 'description' => __( 'Customise the fields available throughout Property Hive.', 'propertyhive' ) ),
			'frontend'     => array( 'icon' => 'layout',    'subtitle' => __( 'Design templates', 'propertyhive' ), 'description' => __( 'Choose how you want to build and customise your property pages.', 'propertyhive' ) ),
			'email'        => array( 'icon' => 'mail',      'subtitle' => __( 'Email templates', 'propertyhive' ), 'description' => __( 'Configure outgoing email settings and templates.', 'propertyhive' ) ),
			'features'     => array( 'icon' => 'star',      'subtitle' => __( 'Extra features', 'propertyhive' ), 'description' => __( 'Enable and manage optional Property Hive features.', 'propertyhive' ) ),
			'licensekey'   => array( 'icon' => 'key',       'subtitle' => __( 'Your license', 'propertyhive' ), 'description' => __( 'Manage your Property Hive license.', 'propertyhive' ) ),
			'demo_data'    => array( 'icon' => 'database',  'subtitle' => __( 'Import sample data', 'propertyhive' ), 'description' => __( 'Fill Property Hive with sample data to explore how it works.', 'propertyhive' ) ),
		);

		return apply_filters( 'propertyhive_settings_tab_meta', $meta );
	}

	/**
	 * Inline SVG for a settings tab icon, with a generic fallback.
	 *
	 * @param string $name Icon key.
	 * @return string SVG markup.
	 */
	public static function get_tab_icon_svg( $name ) {
		$icons = array(
			// Scalloped six-lobe cog with centre circle (per design).
			'gear'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>',
			// Tall office block (left, with door) beside a shorter block, window dots.
			'building' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 21h17"/><path d="M6 21V4.8a.8.8 0 0 1 .8-.8h5.4a.8.8 0 0 1 .8.8V21"/><path d="M13 11h4.2a.8.8 0 0 1 .8.8V21"/><path d="M8.4 7.2h.01M10.6 7.2h.01M8.4 10.2h.01M10.6 10.2h.01M8.4 13.2h.01M10.6 13.2h.01M15.2 14h.01M15.2 17h.01M8.5 21v-2.6h2V21"/></svg>',
			// Three horizontal slider rows with round knobs at staggered positions.
			'sliders'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h11.2M19.4 6H20M4 12h.6M8.8 12H20M4 18h5.2M13.4 18H20"/><circle cx="17.3" cy="6" r="1.9"/><circle cx="6.7" cy="12" r="1.9"/><circle cx="11.3" cy="18" r="1.9"/></svg>',
			// Browser window: rounded square with top bar.
			'layout'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="4.5" width="17" height="15" rx="2.5"/><path d="M3.5 9h17"/></svg>',
			'mail'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="14" rx="1.5"/><path d="m4.5 6.5 7.5 5.8 7.5-5.8"/></svg>',
			'star'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3.6 2.47 5.15 5.63.7-4.15 3.9 1.09 5.6L12 16.2l-5.04 2.75 1.09-5.6-4.15-3.9 5.63-.7L12 3.6z"/></svg>',
			// Diagonal spanner (per design).
			'key'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
			// Stack of three layers (per design).
			'database' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M11.17 2.44a2 2 0 0 1 1.66 0l7.57 3.44a1 1 0 0 1 0 1.82l-7.57 3.44a2 2 0 0 1-1.66 0L3.6 7.7a1 1 0 0 1 0-1.82l7.57-3.44Z"/><path d="m21 12.1-8.17 3.72a2 2 0 0 1-1.66 0L3 12.1"/><path d="m21 16.6-8.17 3.72a2 2 0 0 1-1.66 0L3 16.6"/></svg>',
			// Puzzle piece fallback for add-on tabs.
			'default'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5.2 10.5V6a.8.8 0 0 1 .8-.8h12a.8.8 0 0 1 .8.8v4h.6a2.1 2.1 0 1 1 0 4.2h-.6v4a.8.8 0 0 1-.8.8h-4.3v-.5a2.1 2.1 0 1 0-4.2 0v.5H6a.8.8 0 0 1-.8-.8v-4.4"/></svg>',
		);

		return isset( $icons[ $name ] ) ? $icons[ $name ] : $icons['default'];
	}

	/**
	 * Get a setting from the settings API.
	 *
	 * @param mixed $option
	 * @return string
	 */
	public static function get_option( $option_name, $default = '' ) {
		// Array value
		if ( strstr( $option_name, '[' ) ) {

			parse_str( $option_name, $option_array );

			// Option name is first key
			$option_name = current( array_keys( $option_array ) );

			// Get value
			$option_values = get_option( $option_name, '' );

			$key = key( $option_array[ $option_name ] );

			if ( isset( $option_values[ $key ] ) )
				$option_value = $option_values[ $key ];
			else
				$option_value = null;

		// Single value
		} else {
			$option_value = get_option( $option_name, null );
		}

		if ( is_array( $option_value ) )
			$option_value = array_map( 'stripslashes', $option_value );
		elseif ( ! is_null( $option_value ) )
			$option_value = stripslashes( $option_value );

		return $option_value === null ? $default : $option_value;
	}

	/**
	 * Output admin fields.
	 *
	 * Loops though the propertyhive options array and outputs each field.
	 *
	 * @access public
	 * @param array $options Opens array to output
	 */
	public static function output_fields( $options ) {
	    foreach ( $options as $value ) {
	    	if ( ! isset( $value['type'] ) ) continue;
	    	if ( ! isset( $value['id'] ) ) $value['id'] = '';
	    	if ( ! isset( $value['title'] ) ) $value['title'] = isset( $value['name'] ) ? $value['name'] : '';
	    	if ( ! isset( $value['class'] ) ) $value['class'] = '';
	    	if ( ! isset( $value['css'] ) ) $value['css'] = '';
	    	if ( ! isset( $value['default'] ) ) $value['default'] = '';
	    	if ( ! isset( $value['desc'] ) ) $value['desc'] = '';
	    	if ( ! isset( $value['desc_tip'] ) ) $value['desc_tip'] = false;

	    	// Custom attribute handling
			$custom_attributes = array();

			if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) )
				foreach ( $value['custom_attributes'] as $attribute => $attribute_value )
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

			// Description handling
			if ( $value['desc_tip'] === true ) {
				$description = '';
				$tip = $value['desc'];
			} elseif ( ! empty( $value['desc_tip'] ) ) {
				$description = $value['desc'];
				$tip = $value['desc_tip'];
			} elseif ( ! empty( $value['desc'] ) ) {
				$description = $value['desc'];
				$tip = '';
			} else {
				$description = $tip = '';
			}

			if ( $description && in_array( $value['type'], array( 'textarea', 'radio' ) ) ) {
				$description = '<p style="margin-top:0">' . wp_kses_post( $description ) . '</p>';
			} elseif ( $description && in_array( $value['type'], array( 'checkbox' ) ) ) {
				$description =  wp_kses_post( $description );
			} elseif ( $description ) {
				$description = '<span class="description">' . wp_kses_post( $description ) . '</span>';
			}

			if ( $tip && in_array( $value['type'], array( 'checkbox' ) ) ) {

				$tip = '<p class="description">' . $tip . '</p>';

			} elseif ( $tip ) {

				$tip = '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . PH()->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

			}

			// Switch based on type
	        switch( $value['type'] ) {

	        	// Section Titles
	            case 'title':
	            	if ( ! empty( $value['title'] ) ) {
	            		echo '<h3>' . esc_html( $value['title'] ) . '</h3>';
	            	}
	            	if ( ! empty( $value['desc'] ) ) {
	            		echo wpautop( wptexturize( wp_kses_post( $value['desc'] ) ) );
	            	}
	            	echo '<table class="form-table">'. "\n\n";
	            	if ( ! empty( $value['id'] ) ) {
	            		do_action( 'propertyhive_settings_' . sanitize_title( $value['id'] ) );
	            	}
	            break;

	            // Section Ends
	            case 'sectionend':
	            	if ( ! empty( $value['id'] ) ) {
	            		do_action( 'propertyhive_settings_' . sanitize_title( $value['id'] ) . '_end' );
	            	}
	            	echo '</table>';
	            	if ( ! empty( $value['id'] ) ) {
	            		do_action( 'propertyhive_settings_' . sanitize_title( $value['id'] ) . '_after' );
	            	}
	            break;
                
                case 'html':
                	$full_width = ( isset($value['full_width']) && is_bool($value['full_width']) ) ? $value['full_width'] : false;
                ?>
                <tr valign="top" id="row_<?php echo esc_attr( $value['id'] ); ?>">
                		<?php if ( $full_width !== true ) { ?>
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
                            <?php echo $tip; ?>
                        </th>
                    	<?php } ?>
                        <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
                            <?php echo $value['html']; ?>
                        </td>
                    </tr>
                <?php
                break;

	            // Standard text inputs and subtypes like 'number'
	            case 'text':
	            case 'email':
	            case 'number':
	            case 'color' :
	            case 'password' :

	            	$type 			= $value['type'];
	            	$class 			= '';
	            	$option_value 	= self::get_option( $value['id'], $value['default'] );

	            	if ( $value['type'] == 'color' ) {
	            		$type = 'text';
	            		$value['class'] .= 'colorpick';
		            	$description .= '<div id="colorPickerDiv_' . esc_attr( $value['id'] ) . '" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>';
	            	}

	            	?><tr valign="top" id="row_<?php echo esc_attr( $value['id'] ); ?>">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
	                    <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
	                    	<input
	                    		name="<?php echo esc_attr( $value['id'] ); ?>"
	                    		id="<?php echo esc_attr( $value['id'] ); ?>"
	                    		type="<?php echo esc_attr( $type ); ?>"
	                    		style="<?php echo esc_attr( $value['css'] ); ?>"
	                    		value="<?php echo esc_attr( $option_value ); ?>"
	                    		class="<?php echo esc_attr( $value['class'] ); ?>"
	                    		<?php echo implode( ' ', $custom_attributes ); ?>
	                    		/> <?php echo $description; ?>
	                    </td>
	                </tr><?php
	            break;
                
                // Hidden
                case 'hidden':
                    
                    $option_value   = self::get_option( $value['id'], $value['default'] );
                    
                    ?><input type="hidden" 
                        name="<?php echo esc_attr( $value['id'] ); ?>" 
                        value="<?php echo esc_attr( $option_value ); ?>"
                        /><?php
                    
                break;
                    
	            // Textarea
	            case 'textarea':

	            	$option_value 	= self::get_option( $value['id'], $value['default'] );

	            	?><tr valign="top" id="row_<?php echo esc_attr( $value['id'] ); ?>">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
	                    <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
	                    	<?php echo $description; ?>

	                        <textarea
	                        	name="<?php echo esc_attr( $value['id'] ); ?>"
	                        	id="<?php echo esc_attr( $value['id'] ); ?>"
	                        	style="<?php echo esc_attr( $value['css'] ); ?>"
	                        	class="<?php echo esc_attr( $value['class'] ); ?>"
	                        	<?php echo implode( ' ', $custom_attributes ); ?>
	                        	><?php echo esc_textarea( $option_value );  ?></textarea>
	                    </td>
	                </tr><?php
	            break;

	            // WYSIWYG
	            case 'wysiwyg':

	            	$option_value 	= self::get_option( $value['id'], $value['default'] );

	            	?><tr valign="top" id="row_<?php echo esc_attr( $value['id'] ); ?>">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
	                    <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
	                    	
	                    	<?php wp_editor( $option_value, esc_attr( $value['id'] ), array( 'media_buttons' => false, 'textarea_rows' => 3, 'teeny' => true ) ); ?>

	                    	<?php echo '<br>' . $description; ?>

	                        <?php /*<textarea
	                        	name="<?php echo esc_attr( $value['id'] ); ?>"
	                        	id="<?php echo esc_attr( $value['id'] ); ?>"
	                        	<?php echo implode( ' ', $custom_attributes ); ?>
	                        	><?php echo esc_textarea( $option_value );  ?></textarea>*/ ?>
	                    </td>
	                </tr><?php
	            break;

	            // Select boxes
	            case 'select' :
	            case 'multiselect' :

	            	$option_value 	= self::get_option( $value['id'], $value['default'] );

	            	?><tr valign="top" id="row_<?php echo esc_attr( $value['id'] ); ?>">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
	                    <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
	                    	<select
	                    		name="<?php echo esc_attr( $value['id'] ); ?><?php if ( $value['type'] == 'multiselect' ) echo '[]'; ?>"
	                    		id="<?php echo esc_attr( $value['id'] ); ?>"
	                    		style="<?php echo esc_attr( $value['css'] ); ?>"
	                    		class="<?php echo esc_attr( $value['class'] ); ?>"
	                    		<?php echo implode( ' ', $custom_attributes ); ?>
	                    		<?php if ( $value['type'] == 'multiselect' ) echo 'multiple="multiple"'; ?>
	                    		>
		                    	<?php
			                        foreach ( $value['options'] as $key => $val ) {
			                        	?>
			                        	<option value="<?php echo esc_attr( $key ); ?>" <?php

				                        	if ( is_array( $option_value ) )
				                        		selected( in_array( $key, $option_value ), true );
				                        	else
				                        		selected( $option_value, $key );

			                        	?>><?php echo $val ?></option>
			                        	<?php
			                        }
			                    ?>
	                       </select> <?php echo $description; ?>
	                    </td>
	                </tr><?php
	            break;

	            // Radio inputs
	            case 'radio' :

	            	$option_value 	= self::get_option( $value['id'], $value['default'] );

	            	?><tr valign="top" id="row_<?php echo esc_attr( $value['id'] ); ?>">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
	                    <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
	                    	<fieldset>
	                    		<?php echo $description; ?>
	                    		<ul>
	                    		<?php
	                    			foreach ( $value['options'] as $key => $val ) {
			                        	?>
			                        	<li>
			                        		<label><input
				                        		name="<?php echo esc_attr( $value['id'] ); ?>"
				                        		value="<?php echo $key; ?>"
				                        		type="radio"
					                    		style="<?php echo esc_attr( $value['css'] ); ?>"
					                    		class="<?php echo esc_attr( $value['class'] ); ?>"
					                    		<?php echo implode( ' ', $custom_attributes ); ?>
					                    		<?php checked( $key, $option_value ); ?>
				                        		/> <?php echo $val ?></label>
			                        	</li>
			                        	<?php
			                        }
	                    		?>
	                    		</ul>
	                    	</fieldset>
	                    </td>
	                </tr><?php
	            break;

	            // Checkbox input
	            case 'checkbox' :

	            	$name  = isset($value['name']) && $value['name'] != '' ? ph_clean($value['name']) : $value['id'];
					$option_value = isset($value['value']) ? ph_clean($value['value']) : self::get_option( $value['id'], $value['default'] );
					$fieldset_css = isset($value['fieldset_css']) ? ph_clean($value['fieldset_css']) : '';

					$visbility_class = array();

	            	if ( ! isset( $value['hide_if_checked'] ) ) {
	            		$value['hide_if_checked'] = false;
	            	}
	            	if ( ! isset( $value['show_if_checked'] ) ) {
	            		$value['show_if_checked'] = false;
	            	}
	            	if ( $value['hide_if_checked'] == 'yes' || $value['show_if_checked'] == 'yes' ) {
	            		$visbility_class[] = 'hidden_option';
	            	}
	            	if ( $value['hide_if_checked'] == 'option' ) {
	            		$visbility_class[] = 'hide_options_if_checked';
	            	}
	            	if ( $value['show_if_checked'] == 'option' ) {
	            		$visbility_class[] = 'show_options_if_checked';
	            	}

	            	if ( ! isset( $value['checkboxgroup'] ) || 'start' == $value['checkboxgroup'] ) {
	            		?>
		            		<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visbility_class ) ); ?>" id="row_<?php echo esc_attr( $value['id'] ); ?>">
								<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?></th>
								<td class="forminp forminp-checkbox">
									<fieldset style="<?php echo $fieldset_css; ?>">
						<?php
	            	} else { 
	            		?>
		            		<fieldset style="<?php echo $fieldset_css; ?>" class="<?php echo esc_attr( implode( ' ', $visbility_class ) ); ?>">
	            		<?php
	            	}

	            	if ( ! empty( $value['title'] ) ) {
	            		?>
	            			<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ) ?></span></legend>
	            		<?php
	            	}

	            	?>
						<label for="<?php echo $value['id'] ?>">
							<input
								name="<?php echo esc_attr( $name ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="checkbox"
								value="1"
								<?php checked( $option_value, 'yes'); ?>
								<?php echo implode( ' ', $custom_attributes ); ?>
							/> <?php echo $description ?>
						</label> <?php echo $tip; ?>
					<?php

					if ( ! isset( $value['checkboxgroup'] ) || 'end' == $value['checkboxgroup'] ) {
									?>
									</fieldset>
								</td>
							</tr>
						<?php
					} else {
						?>
							</fieldset>
						<?php
					}
	            break;
                
	            // Image width settings
	            case 'image_width' :

	            	$width 	= self::get_option( $value['id'] . '[width]', $value['default']['width'] );
	            	$height = self::get_option( $value['id'] . '[height]', $value['default']['height'] );
	            	$crop 	= checked( 1, self::get_option( $value['id'] . '[crop]', $value['default']['crop'] ), false );

	            	?><tr valign="top" id="row_<?php echo esc_attr( $value['id'] ); ?>">
						<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?> <?php echo $tip; ?></th>
	                    <td class="forminp image_width_settings">

	                    	<input name="<?php echo esc_attr( $value['id'] ); ?>[width]" id="<?php echo esc_attr( $value['id'] ); ?>-width" type="text" size="3" value="<?php echo $width; ?>" /> &times; <input name="<?php echo esc_attr( $value['id'] ); ?>[height]" id="<?php echo esc_attr( $value['id'] ); ?>-height" type="text" size="3" value="<?php echo $height; ?>" />px

	                    	<label><input name="<?php echo esc_attr( $value['id'] ); ?>[crop]" id="<?php echo esc_attr( $value['id'] ); ?>-crop" type="checkbox" <?php echo $crop; ?> /> <?php _e( 'Hard Crop?', 'propertyhive' ); ?></label>

	                    	</td>
	                </tr><?php
	            break;

	            // Image
	            case 'image' :

	            	$option_value = self::get_option( $value['id'], $value['default'] );

	            	?>
	            	<tr valign="top" id="row_<?php echo esc_attr( $value['id'] ); ?>_uploaded" <?php if ( $option_value == '' ) { echo ' style="display:none"'; } ?>>
						<th scope="row" class="titledesc"><?php echo esc_html( __( 'Uploaded', 'propertyhive' ) . ' ' . $value['title'] ); ?></th>
	                    <td class="forminp image_settings">
	                    <?php
	                    	$image = wp_get_attachment_image_src( $option_value, 'thumbnail' );
							if ($image !== FALSE)
							{
								echo '<img src="' . $image[0] . '" width="150" alt="">';
							}
							else
							{
								echo 'Image doesn\'t exist';
							}
	                    ?>
	                    </td>
	                </tr>
	            	<tr valign="top" id="row_<?php echo esc_attr( $value['id'] ); ?>">
						<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?> <?php echo $tip; ?></th>
	                    <td class="forminp image_settings">

	                    	<a href="" class="button button-primary ph_upload_photo_button<?php echo esc_attr( $value['id'] ); ?>">Select Image</a>
	                    	<input name="<?php echo esc_attr( $value['id'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" type="hidden" value="<?php echo $option_value; ?>" />

	                    </td>
	                </tr><?php
	                echo '<script>

		var file_frame' . $value['id'] . ';

		jQuery(document).ready(function()
        {
        	jQuery(\'body\').on(\'click\', \'.ph_upload_photo_button' . $value['id'] . '\', function( event ){
                 
	            event.preventDefault();
	         
	            // If the media frame already exists, reopen it.
	            if ( file_frame' . $value['id'] . ' ) {
	              file_frame' . $value['id'] . '.open();
	              return;
	            }
	         
	            // Create the media frame.
	            file_frame' . $value['id'] . ' = wp.media.frames.file_frame' . $value['id'] . ' = wp.media({
	              title: jQuery( this ).data( \'uploader_title\' ),
	              button: {
	                text: jQuery( this ).data( \'uploader_button_text\' ),
	              },
	              multiple: false  // Set to true to allow multiple files to be selected
	            });
	         
	            // When an image is selected, run a callback.
	            file_frame' . $value['id'] . '.on( \'select\', function() {
	                var selection = file_frame' . $value['id'] . '.state().get(\'selection\');

	                selection.map( function( attachment ) {
	             
	                    attachment = attachment.toJSON();
	             
	                    // Do something with attachment.id and/or attachment.url here
	                    console.log(attachment.url);
	                    
	                    // Add selected image to page
	                    //add_photo_attachment_to_grid(attachment);

	                    jQuery(\'#row_' . esc_attr( $value['id'] ) . '_uploaded\').show();
	                    jQuery(\'#row_' . esc_attr( $value['id'] ) . '_uploaded td\').html(\'<img src="\' + attachment.url + \'" width="150" alt="">\');
	                    jQuery(\'#' . esc_attr( $value['id'] ) . '\').val(attachment.id);
	                });
	            });
	         
	            // Finally, open the modal
	            file_frame' . $value['id'] . '.open();
	        });
		});

	</script>';
	            break;

	            // Single page selects
	            case 'single_select_page' :

	            	$args = array( 'name'				=> $value['id'],
	            				   'id'					=> $value['id'],
	            				   'sort_column' 		=> 'menu_order',
	            				   'sort_order'			=> 'ASC',
	            				   'show_option_none' 	=> ' ',
	            				   'class'				=> $value['class'],
	            				   'echo' 				=> false,
	            				   'selected'			=> absint( self::get_option( $value['id'] ) )
	            				   );

	            	if( isset( $value['args'] ) )
	            		$args = wp_parse_args( $value['args'], $args );

	            	?><tr valign="top" class="single_select_page" id="row_<?php echo esc_attr( $value['id'] ); ?>">
	                    <th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ) ?> <?php echo $tip; ?></th>
	                    <td class="forminp">
				        	<?php echo str_replace(' id=', " data-placeholder='" . __( 'Select a page&hellip;', 'propertyhive' ) .  "' style='" . $value['css'] . "' class='" . $value['class'] . "' id=", wp_dropdown_pages( $args ) ); ?> <?php echo $description; ?>
				        </td>
	               	</tr><?php
	            break;

	            // Single country selects
	            case 'single_select_country' :
					$country_setting = (string) self::get_option( $value['id'] );
					$countries       = PH()->countries->countries;

	            	if ( strstr( $country_setting, ':' ) ) {
						$country_setting = explode( ':', $country_setting );
						$country         = current( $country_setting );
	            	} else {
						$country = $country_setting;
	            	}
	            	?><tr valign="top" id="row_<?php echo esc_attr( $value['id'] ); ?>">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
	                    <td class="forminp">
		                    <select name="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>">
					        	<?php PH()->countries->country_dropdown_options( $country ); ?>
					        </select>
					        <?php echo $description; ?>
	               		</td>
	               	</tr><?php
	            break;

	            // Country multiselects
	            case 'multi_select_countries' :

	            	$selections = (array) self::get_option( $value['id'] );

	            	if ( ! empty( $value['options'] ) )
	            		$countries = $value['options'];
	            	else
	            		$countries = PH()->countries->countries;

	            	asort( $countries );
	            	?><tr valign="top" id="row_<?php echo esc_attr( $value['id'] ); ?>">
						<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo $tip; ?>
						</th>
	                    <td class="forminp">
		                    <select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="<?php echo esc_attr( $value['css'] ); ?>">
					        	<?php
					        		if ( $countries )
					        			foreach ( $countries as $key => $val )
		                    				echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selections ), true, false ).'>' . $val['name'] . '</option>';
		                    	?>
					        </select> <?php if ( $description ) echo $description; ?>
	               		</td>
	               	</tr><?php
	            break;

	            // Default: run an action
	            default:
	            	do_action( 'propertyhive_admin_field_' . $value['type'], $value );
	            break;
	    	}
		}
	}

	/**
	 * Save admin fields.
	 *
	 * Loops though the propertyhive options array and outputs each field.
	 *
	 * @access public
	 * @param array $options Opens array to output
	 * @return bool
	 */
	public static function save_fields( $options ) {
	    if ( empty( $_POST ) )
	    	return false;

	    // Options to update will be stored here
	    $update_options = array();

	    // Loop options and get values to save
	    foreach ( $options as $value ) {

	    	if ( ! isset( $value['id'] ) )
	    		continue;

	    	$type = isset( $value['type'] ) ? sanitize_title( $value['type'] ) : '';

	    	// Get the option name
	    	$option_value = null;

	    	switch ( $type ) {

		    	// Standard types
		    	case "checkbox" :

		    		if ( isset( $_POST[ $value['id'] ] ) ) {
		    			$option_value = 'yes';
		            } else {
		            	$option_value = 'no';
		            }

		    	break;

		    	case "textarea" :
		    	case "wysiwyg" :

			    	if ( isset( $_POST[$value['id']] ) ) {
			    		$option_value = wp_kses_post( trim( stripslashes( $_POST[ $value['id'] ] ) ) );
		            } else {
		                $option_value = '';
		            }

		    	break;

		    	case "text" :
		    	case 'email':
	            case 'number':
		    	case "select" :
		    	case "color" :
	            case 'password' :
		    	case "single_select_page" :
		    	case "single_select_country" :
		    	case 'radio' :

			       if ( isset( $_POST[$value['id']] ) ) {
		            	$option_value = sanitize_text_field( stripslashes( $_POST[ $value['id'] ] ) );
		            } else {
		                $option_value = '';
		            }

		    	break;

		    	// Special types
		    	case "multiselect" :
		    	case "multi_select_countries" :

		    		// Get countries array
					if ( isset( $_POST[ $value['id'] ] ) )
						$selected_countries = array_map( 'ph_clean', array_map( 'stripslashes', (array) $_POST[ $value['id'] ] ) );
					else
						$selected_countries = array();

					$option_value = $selected_countries;

		    	break;

		    	case "image_width" :

			    	if ( isset( $_POST[$value['id'] ]['width'] ) ) {

		              	$update_options[ $value['id'] ]['width']  = ph_clean( stripslashes( $_POST[ $value['id'] ]['width'] ) );
		              	$update_options[ $value['id'] ]['height'] = ph_clean( stripslashes( $_POST[ $value['id'] ]['height'] ) );

						if ( isset( $_POST[ $value['id'] ]['crop'] ) )
							$update_options[ $value['id'] ]['crop'] = 1;
						else
							$update_options[ $value['id'] ]['crop'] = 0;

		            } else {
		            	$update_options[ $value['id'] ]['width'] 	= $value['default']['width'];
		            	$update_options[ $value['id'] ]['height'] 	= $value['default']['height'];
		            	$update_options[ $value['id'] ]['crop'] 	= $value['default']['crop'];
		            }

		    	break;

		    	// Custom handling
		    	default :

		    		do_action( 'propertyhive_update_option_' . $type, $value );

		    	break;

	    	}

	    	if ( ! is_null( $option_value ) ) {
		    	// Check if option is an array
				if ( strstr( $value['id'], '[' ) ) {

					parse_str( $value['id'], $option_array );

		    		// Option name is first key
		    		$option_name = current( array_keys( $option_array ) );

		    		// Get old option value
		    		if ( ! isset( $update_options[ $option_name ] ) )
		    			 $update_options[ $option_name ] = get_option( $option_name, array() );

		    		if ( ! is_array( $update_options[ $option_name ] ) )
		    			$update_options[ $option_name ] = array();

		    		// Set keys and value
		    		$key = key( $option_array[ $option_name ] );

		    		$update_options[ $option_name ][ $key ] = $option_value;

				// Single value
				} else {
					$update_options[ $value['id'] ] = $option_value;
				}
			}

	    	// Custom handling
	    	do_action( 'propertyhive_update_option', $value );
	    }

	    // Now save the options
	    foreach( $update_options as $name => $value )
	    	update_option( $name, $value );

	    return true;
	}
}

endif;
