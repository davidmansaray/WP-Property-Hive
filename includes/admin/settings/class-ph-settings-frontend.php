<?php
/**
 * PropertyHive Frontend Settings
 *
 * @author      PropertyHive
 * @category    Admin
 * @package     PropertyHive/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'PH_Settings_Frontend' ) ) :

/**
 * PH_Settings_Frontend.
 */
class PH_Settings_Frontend extends PH_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'frontend';
		$this->label = __( 'Frontend', 'propertyhive' );

        add_action( 'admin_init', array( $this, 'check_for_reset_search_form') );
        add_action( 'admin_init', array( $this, 'check_for_delete_search_form') );

		add_filter( 'propertyhive_settings_tabs_array', array( $this, 'add_settings_page' ), 16 );
		add_filter( 'propertyhive_default_settings_section_' . $this->id, array( $this, 'get_default_section' ) );
		add_filter( 'propertyhive_settings_page_title', array( $this, 'get_page_title' ), 10, 3 );
		add_filter( 'propertyhive_settings_page_description', array( $this, 'get_page_description' ), 10, 3 );
		add_action( 'propertyhive_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'propertyhive_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'propertyhive_settings_save_' . $this->id, array( $this, 'save' ) );

		add_action( 'propertyhive_admin_field_search_forms_table', array( $this, 'search_forms_table' ) );
        add_action( 'propertyhive_admin_field_search_form_fields', array( $this, 'search_form_fields' ) );
        add_action( 'propertyhive_admin_field_template_experience', array( $this, 'template_experience_field' ) );
        add_action( 'propertyhive_admin_field_template_advanced_start', array( $this, 'template_advanced_start' ) );
        add_action( 'propertyhive_admin_field_template_advanced_end', array( $this, 'template_advanced_end' ) );
	}

    public function check_for_reset_search_form()
    {
        if ( isset($_GET['action']) && $_GET['action'] == 'resetsearchform' && isset($_GET['id']) && $_GET['id'] != '' )
        {
            $current_id = ( !isset( $_GET['id'] ) ) ? '' : sanitize_title( $_GET['id'] );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have permission to reset search forms.', 'propertyhive' ) );
            }

            check_admin_referer( 'ph_reset_search_form_' . $current_id );

            $manager = new PH_Search_Form_Manager();
            $result  = $manager->reset_form( $current_id );

            if ( is_wp_error( $result ) ) {
                wp_die( esc_html( $result->get_error_message() ) );
            }

            wp_safe_redirect( admin_url( 'admin.php?page=ph-settings&tab=frontend&section=search-forms' ) );
            exit;
        }
    }

    public function check_for_delete_search_form()
    {
        if ( isset($_GET['action']) && $_GET['action'] == 'deletesearchform' && isset($_GET['id']) && $_GET['id'] != '' && $_GET['id'] != 'default' )
        {
            $current_id = ( !isset( $_GET['id'] ) ) ? '' : sanitize_title( $_GET['id'] );

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( esc_html__( 'You do not have permission to delete search forms.', 'propertyhive' ) );
            }

            check_admin_referer( 'ph_delete_search_form_' . $current_id );

            $manager = new PH_Search_Form_Manager();
            $result  = $manager->delete_form( $current_id );

            if ( is_wp_error( $result ) ) {
                wp_die( esc_html( $result->get_error_message() ) );
            }

            wp_safe_redirect( admin_url( 'admin.php?page=ph-settings&tab=frontend&section=search-forms' ) );
            exit;
        }
    }

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'template-set' => __( 'Property Templates', 'propertyhive' ),
			'search-results' => __( 'Search Results', 'propertyhive' ),
			'search-forms' => __( 'Search Forms', 'propertyhive' ),
			'flags' => __( 'Flags', 'propertyhive' ),
		);

		return apply_filters( 'propertyhive_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get the section shown when the main Frontend tab is opened.
	 *
	 * @param string $section Existing default section.
	 * @return string
	 */
	public function get_default_section( $section = '' ) {
		return '' === $section ? 'template-set' : $section;
	}

	/**
	 * Remove the redundant page heading beneath the Frontend section menu.
	 *
	 * @param string $title Current page title.
	 * @param string $tab Current settings tab.
	 * @param string $section Current settings section.
	 * @return string
	 */
	public function get_page_title( $title, $tab, $section ) {
		return $this->id === $tab ? '' : $title;
	}

	/**
	 * Remove the redundant page description beneath the Frontend section menu.
	 *
	 * @param string $description Current page description.
	 * @param string $tab Current settings tab.
	 * @param string $section Current settings section.
	 * @return string
	 */
	public function get_page_description( $description, $tab, $section ) {
		return $this->id === $tab ? '' : $description;
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {

		$current_settings = get_option( 'propertyhive_template_assistant', array() );

		$settings = array(

            array( 'title' => __( 'Search Results Page Layout', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'template_assistant_search_results_settings' )

        );

        $settings[] = array(
            'title' => __( 'Default Sort Order', 'propertyhive' ),
            'id'        => 'search_result_default_order',
            'type'      => 'select',
            'default'   => ( isset($current_settings['search_result_default_order']) ? $current_settings['search_result_default_order'] : ''),
            'options'   => array(
                '' => 'Price Descending (' . __( 'default', 'propertyhive') . ')',
                'price-asc' => 'Price Ascending',
                'date' => 'Date Added',
            )
        );

        $settings[] = array(
            'title' => __( 'Properties Per Row', 'propertyhive' ),
            'id'        => 'search_result_columns',
            'type'      => 'select',
            'default'   => ( isset($current_settings['search_result_columns']) ? $current_settings['search_result_columns'] : '1'),
            'options'   => array(
                '1' => '1 (' . __( 'default', 'propertyhive') . ')',
                '2' => '2',
                '3' => '3',
                '4' => '4',
            )
        );

        $settings[] = array(
            'title' => __( 'Result Layout', 'propertyhive' ),
            'id'        => 'search_result_layout',
            'type'      => 'select',
            'default'   => ( isset($current_settings['search_result_layout']) ? $current_settings['search_result_layout'] : '1'),
            'options'   => array(
                '1' => 'List Layout 1 (default)',
                '2' => 'List Layout 2 (card)',
            )
        );

        $search_result_fields = array( 'price', 'floor_area', 'summary', 'actions' );
        if ( isset($current_settings['search_result_fields']) && is_array($current_settings['search_result_fields']) )
        {
            if ( !empty($current_settings['search_result_fields']) )
            {
                $search_result_fields = $current_settings['search_result_fields'];
            }
            else
            {
                $search_result_fields = array();
            }
        }

        $fields = array(
            array( 'id' => 'price', 'label' => 'Price / Rent' ),
            array( 'id' => 'floor_area', 'label' => 'Floor Area (commercial only)' ),
            array( 'id' => 'summary', 'label' => 'Summary Description' ),
            array( 'id' => 'actions', 'label' => 'Actions (i.e. More Details Button)' ),
            array( 'id' => 'rooms', 'label' => 'Rooms Counts' ),
            array( 'id' => 'availability', 'label' => 'Availability' ),
            array( 'id' => 'property_type', 'label' => 'Property Type' ),
            array( 'id' => 'available_date', 'label' => 'Available Date (lettings only)' ),
        );
        $custom_field_selected = false;
        if ( isset($current_settings['custom_fields']) && is_array($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            $label = '<select name="search_result_fields_custom_field"><option value="">Custom Field...</option>';
            foreach ( $current_settings['custom_fields'] as $custom_field )
            {
                $label .= '<option value="custom_field' . $custom_field['field_name'] . '"';
                if ( in_array('custom_field' . $custom_field['field_name'], $search_result_fields) )
                {
                    $label .= ' selected';
                    $custom_field_selected = true;
                }
                $label .= '>' . $custom_field['field_label'] . '</option>';
            }
            $label .= '</select>';

            $fields[] = array( 'id' => 'custom_field', 'label' => $label );
        }

        // Need to sort order to match what's saved
        foreach ( $search_result_fields as $j => $search_result_field )
        {
            foreach ( $fields as $i => $field )
            {
                if ( $field['id'] == $search_result_field || ( $field['id'] == 'custom_field' && substr($search_result_field, 0, 12) == 'custom_field' ) )
                {
                    $fields[$i]['order'] = $j;
                }
            }
        }
        foreach ( $fields as $i => $field )
        {
            if ( !isset($field['order']) )
            {
                $fields[$i]['order'] = $i + 99;
            }
        }

        // order $fields by 'order' key
        $sorter = array();
        $ret = array();
        reset($fields);
        foreach ($fields as $ii => $va) 
        {
            $sorter[$ii] = $va['order'];
        }
        asort($sorter);
        foreach ($sorter as $ii => $va) 
        {
            $ret[$ii] = $fields[$ii];
        }
        $fields = $ret;

        $html = '<span class="form-field-options" id="sortable_options">';
        foreach ( $fields as $field )
        {
            $html .= '<span style="display:block; padding:3px 0;">
                <i class="fa fa-reorder" style="cursor:pointer; opacity:0.3"></i> &nbsp;
                <input type="checkbox" name="search_result_fields[]" value="' . $field['id'] . '"';
            if ( in_array($field['id'], $search_result_fields) || ( $field['id'] == 'custom_field' && $custom_field_selected ) )
            {
                $html .= ' checked';
            }
            $html .= '>
                ' . $field['label'] . '
            </span>';
        }
        $html .= '</span>

        <script>
            jQuery(document).ready(function($)
            {
                $( "#sortable_options" )
                .sortable({
                    axis: "y",
                    handle: "i",
                    stop: function( event, ui ) 
                    {
                        // IE doesn\'t register the blur when sorting
                        // so trigger focusout handlers to remove .ui-state-focus
                        //ui.item.children( "h3" ).triggerHandler( "focusout" );
             
                        // Refresh accordion to handle new order
                        //$( this ).accordion( "refresh" );
                    },
                    update: function( event, ui ) 
                    {
                        // Update hidden fields
                        var fields_order = $(this).sortable(\'toArray\');
                        
                        //$(\'#active_fields_order\').val( fields_order.join("|") );
                    }
                });
            });
        </script>';

        $settings[] = array(
            'title' => __( 'Fields Shown', 'propertyhive' ),
            'type'      => 'html',
            'html'      => $html
        );

        if ( get_option('propertyhive_images_stored_as', '') != 'urls' )
        {
            $image_sizes = get_intermediate_image_sizes();
            $image_size_options = array();
            foreach ( $image_sizes as $image_size )
            {
                $image_size_options[$image_size] = $image_size;
            }

            $settings[] = array(
                'title' => __( 'Image Size Used', 'propertyhive' ),
                'id'        => 'search_result_image_size',
                'type'      => 'select',
                'default'   => ( isset($current_settings['search_result_image_size']) ? $current_settings['search_result_image_size'] : 'medium'),
                'options'   => $image_size_options
            );
        }

        $columns_1_css = file_get_contents(PH()->plugin_path() . '/assets/css/search-results-layouts/columns-1.css');
        $columns_2_css = file_get_contents(PH()->plugin_path() . '/assets/css/search-results-layouts/columns-2.css');
        $columns_3_css = file_get_contents(PH()->plugin_path() . '/assets/css/search-results-layouts/columns-3.css');
        $columns_4_css = file_get_contents(PH()->plugin_path() . '/assets/css/search-results-layouts/columns-4.css');
        $layout_1_css = '';
        $layout_2_css = file_get_contents(PH()->plugin_path() . '/assets/css/search-results-layouts/content-property-2.css');

        $settings[] = array(
            'title' => __( 'Customise CSS', 'propertyhive' ),
            'id'        => 'search_result_css',
            'type'      => 'textarea',
            'default'   => ( isset($current_settings['search_result_css']) ? $current_settings['search_result_css'] : $columns_1_css . "\n\n" . $layout_1_css ),
            'css'       => 'height:200px;width:100%;',
        );

        if ( isset($current_settings['search_result_css']) && trim($current_settings['search_result_css']) != '' )
        {
            $settings[] = array(
                'type'      => 'html',
                'html'      => '<div id="change_warning" style="display:none; color:#900">
                    By changing the options above the CSS been regenerated. Please note that this will overwrite any customisations you\'ve previously made to the CSS.
                </div>'
            );
        }

        $settings[] = array(
            'title' => __( 'Apply CSS To All Pages', 'propertyhive' ),
            'id'        => 'search_result_css_all_pages',
            'type'      => 'checkbox',
            'default'   => isset($current_settings['search_result_css_all_pages']) && $current_settings['search_result_css_all_pages'] == 'yes' ? 'yes' : '',
        );

        $settings[] = array(
            'type'      => 'html',
            'html'      => '<script>

                jQuery(document).ready(function()
                {
                    jQuery(\'#search_result_columns\').change(function()
                    {
                        generate_search_results_css();
                    });
                    jQuery(\'#search_result_layout\').change(function()
                    {
                        generate_search_results_css();
                    });
                });

                function generate_search_results_css()
                {
                    jQuery(\'#search_result_css\').val(\'\');

                    jQuery(\'#change_warning\').slideDown();

                    var columns_css = \'\';
                    var layout_css = \'\';
                    switch ( jQuery(\'#search_result_columns\').val() )
                    {
                        case \'1\':
                        {
                            columns_css = "' . str_replace(array("\r\n", "\n"), '\n', $columns_1_css) . '";
                            break;
                        }
                        case \'2\':
                        {
                            columns_css = "' . str_replace(array("\r\n", "\n"), '\n', $columns_2_css) . '";
                            break;
                        }
                        case \'3\':
                        {
                            columns_css = "' . str_replace(array("\r\n", "\n"), '\n', $columns_3_css) . '";
                            break;
                        }
                        case \'4\':
                        {
                            columns_css = "' . str_replace(array("\r\n", "\n"), '\n', $columns_4_css) . '";
                            break;
                        }
                    }

                    switch ( jQuery(\'#search_result_layout\').val() )
                    {
                        case \'1\':
                        {
                            layout_css = "' . str_replace(array("\r\n", "\n"), '\n', $layout_1_css) . '";
                            break;
                        }
                        case \'2\':
                        {
                            layout_css = "' . str_replace(array("\r\n", "\n"), '\n', $layout_2_css) . '";
                            break;
                        }
                    }

                    jQuery(\'#search_result_css\').val( columns_css + "\n\n" + layout_css );
                }

            </script>'
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'template_assistant_search_results_settings');

		return apply_filters( 'propertyhive_get_settings_' . $this->id, $settings );
	}

	public function get_search_forms_settings()
    {
        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $settings = array(

            array( 'title' => __( 'Search Forms', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'template_assistant_search_forms_settings' )

        );

        $settings[] = array(
            'type' => 'search_forms_table',
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'template_assistant_search_forms_settings');

        return $settings;
    }

    public function get_search_form_settings()
    {
        global $current_section;

        wp_enqueue_script( 'jquery-ui-accordion' );
        wp_enqueue_script( 'jquery-ui-sortable' );

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( !isset($current_settings['search_forms']) )
        {
            $current_settings['search_forms'] = array();
        }
        if ( !isset($current_settings['search_forms']['default']) )
        {
            $current_settings['search_forms']['default'] = array();
        }

        $current_id = ( !isset( $_REQUEST['id'] ) ) ? '' : sanitize_title( $_REQUEST['id'] );

        $search_form_details = array();

        if ($current_id != '')
        {
            $search_forms = $current_settings['search_forms'];

            if (isset($search_forms[$current_id]))
            {
                $search_form_details = $search_forms[$current_id];
            }
            else
            {
                die('Trying to edit a search form which does not exist. Please go back and try again.');
            }
        }

        $settings = array(

            array( 'title' => __( ( $current_section == 'addsearchform' ? 'Add Search Form' : 'Edit Search Form' ), 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'searchforms' ),

        );

        $custom_attributes = array();
        if ($current_id == 'default' || $current_section == 'editsearchform')
        {
            $custom_attributes['disabled'] = 'disabled';
        }

        $settings[] = array(
            'title' => __( 'ID', 'propertyhive' ),
            'id'        => 'form_id',
            'default'   => ( (isset($current_id)) ? $current_id : ''),
            'type'      => 'text',
            'desc_tip'  =>  false,
            'custom_attributes' => $custom_attributes
        );

        $settings[] = array(
            'type' => 'search_form_fields',
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'searchforms');

        return $settings;
    }

    /**
     * Output list of search forms
     *
     * @access public
     * @return void
     */
    public function search_forms_table() {
        global $wpdb, $post;
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                &nbsp;
            </th>
            <td class="forminp forminp-button">
                <a href="<?php echo esc_url(admin_url( 'admin.php?page=ph-settings&tab=frontend&section=addsearchform' )); ?>" class="button alignright"><?php echo esc_html(__( 'Add New Search Form', 'propertyhive' )); ?></a>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php esc_html_e( 'Search Forms', 'propertyhive' ) ?></th>
            <td class="forminp">
                <table class="ph_portals widefat" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="id"><?php esc_html_e( 'ID', 'propertyhive' ); ?></th>
                            <th class="shortcode"><?php esc_html_e( 'Shortcode', 'propertyhive' ); ?></th>
                            <th class="settings">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php

                            $current_settings = get_option( 'propertyhive_template_assistant', array() );
                            $search_forms = array();
                            if ($current_settings !== FALSE)
                            {
                                if (isset($current_settings['search_forms']))
                                {
                                    $search_forms = $current_settings['search_forms'];
                                }
                            }

                            if ( !isset($search_forms['default']) )
                            {
                                $search_forms['default'] = array();
                            }

                            if (!empty($search_forms))
                            {
                                foreach ( $search_forms as $id => $search_form )
                                {
                                    $edit_url = admin_url( 'admin.php?page=ph-settings&tab=frontend&section=editsearchform&id=' . $id );

                                    $reset_url = wp_nonce_url(
                                        admin_url( 'admin.php?page=ph-settings&tab=frontend&section=search-forms&action=resetsearchform&id=' . $id ),
                                        'ph_reset_search_form_' . $id
                                    );

                                    echo '<tr>';
                                        echo '<td class="id">' . esc_html( $id ) . '</td>';
                                        echo '<td class="shortcode"><pre style="background:#EEE; padding:5px; display:inline">[property_search_form id="' . esc_attr( $id ) . '"]</pre></td>';
                                        echo '<td class="settings">
                                            <a class="button" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit Fields', 'propertyhive' ) . '</a>
                                            <a class="button" href="' . esc_url( $reset_url ) . '">' . esc_html__( 'Reset To Default Fields', 'propertyhive' ) . '</a>';

                                        if ( $id != 'default' )
                                        {
                                            $delete_url = wp_nonce_url(
                                                admin_url( 'admin.php?page=ph-settings&tab=frontend&section=search-forms&action=deletesearchform&id=' . $id ),
                                                'ph_delete_search_form_' . $id
                                            );

                                            echo '
                                                <a class="button" href="' . esc_url( $delete_url ) . '" onclick="return confirm(\'Are you sure you wish to delete this search form?\');">' . esc_html__( 'Delete', 'propertyhive' ) . '</a>
                                            ';
                                        }

                                        echo '</td>';
                                    echo '</tr>';
                                }
                            }
                            else
                            {
                                echo '<tr>';
                                    echo '<td align="center" colspan="3">' . esc_html(__( 'No search forms exist', 'propertyhive' )) . '</td>';
                                echo '</tr>';
                            }
                        ?>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                &nbsp;
            </th>
            <td class="forminp forminp-button">
                <a href="<?php echo esc_url(admin_url( 'admin.php?page=ph-settings&tab=frontend&section=addsearchform' )); ?>" class="button alignright"><?php echo esc_html(__( 'Add New Search Form', 'propertyhive' )); ?></a>
            </td>
        </tr>
        <?php
    }

    private function output_search_form_field( $id, $field )
    {
        echo '
        <div class="group" id="' . $id . '">
            <h3>' . trim( $id, '_' ) . '</h3>
            <div>';
        if ( $id == 'department' )
        {
            echo '<p><label for="type_'.$id.'">Type:</label> <select name="type[' . $id . ']" id="type_'.$id.'">
                <option value="radio"' . ( ( !isset($field['type']) || ( isset($field['type']) && $field['type'] == 'radio' ) ) ? ' selected' : '' ) . '>Radio Buttons</option>
                <option value="select"' . ( ( isset($field['type']) && $field['type'] == 'select' ) ? ' selected' : '' ) . '>Dropdown</option>
                ' . ( ( isset($field['type']) && $field['type'] != 'select' && $field['type'] != 'radio' ) ? '<option value="' . $field['type'] . '" selected>' . $field['type'] . '</option>' : '' ) . '
            </select></p>';
        }
        else
        {
            echo '<input type="hidden" name="type[' . $id . ']" id="type_'.$id.'" value="' . ( ( isset($field['type']) ) ? $field['type'] : '' ) . '">';
        }

        echo  ' <p><label for="show_label_'.$id.'">Show Label:</label> <input type="checkbox" name="show_label[' . $id . ']" id="show_label_'.$id.'" value="1"' . ( ( isset($field['show_label']) && $field['show_label'] === true ) ? ' checked' : '' ) . '></p>
                
                <p><label for="label_'.$id.'">Label:</label> <input type="text" name="label[' . $id . ']" id="label_'.$id.'" value="' . ( ( isset($field['label']) ) ? $field['label'] : '' ) . '"></p>
                
                <p><label for="before_'.$id.'">Before:</label> <input type="text" name="before[' . $id . ']" id="before_'.$id.'" value="' . ( ( isset($field['before']) ) ? htmlentities($field['before']) : '' ) . '"></p>
                
                <p><label for="after_'.$id.'">After:</label> <input type="text" name="after[' . $id . ']" id="after_'.$id.'" value="' . ( ( isset($field['after']) ) ? htmlentities($field['after']) : '' ) . '"></p>';

        if ( isset($field['type']) && in_array($field['type'], array('text', 'email', 'date', 'number', 'password')) )
        {
            echo '
            <p><label for="placeholder_'.$id.'">Placeholder:</label> <input type="text" name="placeholder[' . $id . ']" id="placeholder_'.$id.'" value="' . ( ( isset($field['placeholder']) ) ? htmlentities($field['placeholder']) : '' ) . '"></p>
            ';
        }

        if ( isset($field['type']) && in_array($field['type'], array('slider')) )
        {
            echo '
            <p><label for="min_'.$id.'">Min:</label> <input type="number" name="min[' . $id . ']" id="min_'.$id.'" value="' . ( ( isset($field['min']) ) ? htmlentities($field['min']) : '0' ) . '"></p>
            ';

            echo '
            <p><label for="max_'.$id.'">Max:</label> <input type="number" name="max[' . $id . ']" id="max_'.$id.'" value="' . ( ( isset($field['max']) ) ? htmlentities($field['max']) : '' ) . '"></p>
            ';

            echo '
            <p><label for="step_'.$id.'">Step:</label> <input type="number" name="step[' . $id . ']" id="step_'.$id.'" value="' . ( ( isset($field['step']) ) ? htmlentities($field['step']) : '1' ) . '"></p>
            ';
        }

        if ( isset($field['type']) && in_array($field['type'], array('office')) )
        {
            echo '
            <p><label for="blank_option_'.$id.'">Blank Option:</label> <input type="text" name="blank_option[' . $id . ']" id="blank_option_'.$id.'" value="' . ( ( isset($field['blank_option']) ) ? htmlentities($field['blank_option']) : __( 'No Preference', 'propertyhive' ) ) . '"></p>
            ';
        }

        if ( taxonomy_exists($id) || ( isset($field['custom_field']) && $field['custom_field'] === true && $field['type'] == 'select' ) )
        {
            echo '
            <p><label for="blank_option_'.$id.'">Blank Option:</label> <input type="text" name="blank_option[' . $id . ']" id="blank_option_'.$id.'" value="' . ( ( isset($field['blank_option']) ) ? htmlentities($field['blank_option']) : __( 'No Preference', 'propertyhive' ) ) . '"></p>
            ';

            if ( taxonomy_exists($id) && in_array( $id, apply_filters( 'propertyhive_template_assistant_multi_level_taxonomy_fields', array('property_type', 'commercial_property_type', 'location') ) ) )
            {
                echo '
                <p><label for="parent_terms_only_'.$id.'">Top-Level Terms Only:</label> <input type="checkbox" name="parent_terms_only[' . $id . ']" id="parent_terms_only_'.$id.'" value="yes"' . ( ( isset($field['parent_terms_only']) && $field['parent_terms_only'] === true ) ? ' checked' : '' ) . '></p>
                ';

                echo '
                <p><label for="hide_empty_'.$id.'">Hide Terms With No Properties Assigned:</label> <input type="checkbox" name="hide_empty[' . $id . ']" id="hide_empty_'.$id.'" value="yes"' . ( ( isset($field['hide_empty']) && $field['hide_empty'] === true ) ? ' checked' : '' ) . '></p>
                ';
            }

            if ( taxonomy_exists($id) && in_array( $id, apply_filters( 'propertyhive_template_assistant_dynamic_population_taxonomy_fields', array('location') ) ) )
            {
                echo '
                <p><label for="dynamic_population_'.$id.'">Dynamically Populate Cascading Dropdowns:</label> <input type="checkbox" name="dynamic_population[' . $id . ']" id="dynamic_population_'.$id.'" value="yes"' . ( ( isset($field['dynamic_population']) && $field['dynamic_population'] === true ) ? ' checked' : '' ) . '></p>
                ';
            }

            echo '
            <p><label for="multiselect_'.$id.'">Multi-Select:</label> <input type="checkbox" name="multiselect[' . $id . ']" id="multiselect_'.$id.'" value="yes"' . ( ( isset($field['multiselect']) && $field['multiselect'] === true ) ? ' checked' : '' ) . '></p>
            ';
        }

        if ( $id == 'office' )
        {
            echo '
            <p><label for="multiselect_'.$id.'">Multi-Select:</label> <input type="checkbox" name="multiselect[' . $id . ']" id="multiselect_'.$id.'" value="yes"' . ( ( isset($field['multiselect']) && $field['multiselect'] === true ) ? ' checked' : '' ) . '></p>
            ';
        }

        if ( isset($field['options']) && !taxonomy_exists($id) && ( !isset($field['custom_field']) || ( isset($field['custom_field']) && $field['custom_field'] === false ) ) )
        {
            echo '<p><label for="">Options: ';

            echo '<a href="" class="add-search-form-field-option" id="add_search_form_field_option_' . $id . '">Add Option</a>';

            echo '</label><br>';

            echo '<span class="form-field-options" id="sortable_options_' . $id . '">';
            $i = 0;
            foreach ( $field['options'] as $key => $value )
            {
                echo '<span style="display:block"><i class="fa fa-reorder" style="cursor:pointer; opacity:0.3"></i> ';
                echo '<input type="text" name="option_keys[' . $id . '][]" value="' . $key . '">';
                echo '<input type="text" name="options_values[' . $id . '][]" value="' . $value . '">';
                echo '</span>';

                ++$i;
            }
            echo '</span>';

            echo '</p>';
?>
<script>
            jQuery(document).ready(function($)
            {
                $( "#sortable_options_<?php echo $id; ?>" )
                .sortable({
                    axis: "y",
                    handle: "i",
                    stop: function( event, ui ) 
                    {
                        // IE doesn't register the blur when sorting
                        // so trigger focusout handlers to remove .ui-state-focus
                        //ui.item.children( "h3" ).triggerHandler( "focusout" );
             
                        // Refresh accordion to handle new order
                        //$( this ).accordion( "refresh" );
                    },
                    update: function( event, ui ) 
                    {
                        // Update hidden fields
                        var fields_order = $(this).sortable('toArray');
                        
                        //$('#active_fields_order').val( fields_order.join("|") );
                    }
                });
            });
        </script>
<?php
        }

        echo '</div>
        </div>';
    }

    /**
     * Output list of search form active/inactive fields
     *
     * @access public
     * @return void
     */
    public function search_form_fields() {
        global $wpdb, $post;

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        if ( !isset($current_settings['search_forms']) )
        {
            $current_settings['search_forms'] = array();
        }
        if ( !isset($current_settings['search_forms']['default']) )
        {
            $current_settings['search_forms']['default'] = array();
        }

        $current_id = ( !isset( $_REQUEST['id'] ) ) ? '' : sanitize_title( $_REQUEST['id'] );

        $search_form_details = array();

        if ($current_id != '')
        {
            $search_forms = $current_settings['search_forms'];

            if (isset($search_forms[$current_id]))
            {
                $search_form_details = $search_forms[$current_id];
            }
            else
            {
                die('Trying to edit search form which does not exist. Please go back and try again.');
            }
        }

        $all_fields = ph_get_search_form_fields();
        $all_fields['address_keyword'] = array(
            'type' => 'text',
            'label' => __( 'Location', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-address_keyword">'
        );
        if ( class_exists('PH_Radial_Search') )
        {
            $all_fields['radius'] = array(
                'type' => 'select',
                'label' => __( 'Radius', 'propertyhive' ),
                'show_label' => true,
                'before' => '<div class="control control-radius">',
                'options' => array(
                    '' => __( 'This Area Only', 'propertyhive' ),
                    '1' => __( 'Within 1 Mile', 'propertyhive' ),
                    '2' => __( 'Within 2 Miles', 'propertyhive' ),
                    '3' => __( 'Within 3 Miles', 'propertyhive' ),
                    '5' => __( 'Within 5 Miles', 'propertyhive' ),
                    '10' => __( 'Within 10 Miles', 'propertyhive' ),
                )
            );
        }
        $all_fields['location'] = array(
            'type' => 'location',
            'label' => __( 'Location', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-location">'
        );
        $all_fields['parking'] = array(
            'type' => 'parking',
            'label' => __( 'Parking', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-parking residential-only">'
        );
        $all_fields['outside_space'] = array(
            'type' => 'outside_space',
            'label' => __( 'Outside Space', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-outside_space residential-only">'
        );
        $all_fields['availability'] = array(
            'type' => 'availability',
            'label' => __( 'Status', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-availability">'
        );
        $all_fields['marketing_flag'] = array(
            'type' => 'marketing_flag',
            'label' => __( 'Marketing Flag', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-marketing_flag">'
        );
        $all_fields['tenure'] = array(
            'type' => 'tenure',
            'label' => __( 'Tenure', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-tenure residential-only">'
        );
        $all_fields['commercial_tenure'] = array(
            'type' => 'commercial_tenure',
            'label' => __( 'Commercial Tenure', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-commercial_tenure commercial-only">'
        );
        $all_fields['commercial_for_sale_to_rent'] = array(
            'type' => 'select',
            'label' => __( 'For Sale / To Rent', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-commercial_for_sale_to_rent commercial-only">',
            'options' => array(
                '' => __( 'No Preference', 'propertyhive' ),
                'for_sale' => __( 'For Sale', 'propertyhive' ),
                'to_rent' => __( 'To Rent', 'propertyhive' ),
            )
        );

        $prices = array(
            '' => __( 'No preference', 'propertyhive' ),
            '100000' => '£100,000',
            '200000' => '£200,000',
            '300000' => '£300,000',
            '400000' => '£400,000',
            '500000' => '£500,000',
            '750000' => '£750,000',
        );
        $all_fields['commercial_minimum_price'] = array(
            'type' => 'select',
            'label' => __( 'Minimum Price', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-commercial_minimum_price commercial-sales-only">',
            'options' => $prices
        );
        $all_fields['commercial_maximum_price'] = array(
            'type' => 'select',
            'label' => __( 'Maximum Price', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-commercial_maximum_price commercial-sales-only">',
            'options' => $prices
        );

        $prices = array(
            '' => __( 'No preference', 'propertyhive' ),
            '500' => '£500',
            '750' => '£750',
            '1000' => '£1,000',
            '1500' => '£1,500',
            '2000' => '£2,000',
            '3000' => '£3,000',
        );
        $all_fields['commercial_minimum_rent'] = array(
            'type' => 'select',
            'label' => __( 'Minimum Rent', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-commercial_minimum_rent commercial-lettings-only">',
            'options' => $prices
        );
        $all_fields['commercial_maximum_rent'] = array(
            'type' => 'select',
            'label' => __( 'Maximum Rent', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-commercial_maximum_rent commercial-lettings-only">',
            'options' => $prices
        );

        $all_fields['sale_by'] = array(
            'type' => 'sale_by',
            'label' => __( 'Sale By', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-sale_by">'
        );
        $all_fields['furnished'] = array(
            'type' => 'furnished',
            'label' => __( 'Furnished', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-furnished lettings-only">'
        );

        $price_ranges = array(
            '' => __( 'No preference', 'propertyhive' ),
            '100000-200000' => '£100,000 - £200,000',
            '200000-300000' => '£200,000 - £300,000',
            '300000-400000' => '£300,000 - £400,000',
            '400000-500000' => '£400,000 - £500,000',
            '500000-750000' => '£500,000 - £750,000',
            '750000-1000000' => '£750,000 - £1,000,000',
        );

        $all_fields['price_range'] = array(
            'type' => 'select',
            'label' => __( 'Price', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-price-range sales-only">',
            'options' => $price_ranges
        );

        $all_fields['price_slider'] = array(
            'type' => 'slider',
            'label' => __( 'Price', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-price-slider sales-only">',
            'min' => '0',
            'max' => '1000000',
            'step' => '10000',
        );

        $rent_ranges = array(
            '' => __( 'No preference', 'propertyhive' ),
            '100-200' => '£100 - £200 PCM',
            '200-300' => '£200 - £300 PCM',
            '300-400' => '£300 - £400 PCM',
            '400-500' => '£400 - £500 PCM',
            '500-750' => '£500 - £750 PCM',
            '750-1000' => '£750 - £1,000 PCM',
        );

        $all_fields['rent_range'] = array(
            'type' => 'select',
            'label' => __( 'Rent', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-rent-range lettings-only">',
            'options' => $rent_ranges
        );

        $all_fields['rent_slider'] = array(
            'type' => 'slider',
            'label' => __( 'Rent', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-rent-slider lettings-only">',
            'min' => '0',
            'max' => '1000',
            'step' => '100',
        );

        $bedrooms = array(
            '' => __( 'No preference', 'propertyhive' ),
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
        );

        $all_fields['bedrooms'] = array(
            'type' => 'select',
            'label' => __( 'Bedrooms', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-bedrooms residential-only">',
            'options' => $bedrooms
        );

        $all_fields['maximum_bedrooms'] = array(
            'type' => 'select',
            'label' => __( 'Max Beds', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-maximum_bedrooms residential-only">',
            'options' => $bedrooms
        );

        $bathrooms = array(
            '' => __( 'No preference', 'propertyhive' ),
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
        );

        $all_fields['minimum_bathrooms'] = array(
            'type' => 'select',
            'label' => __( 'Min Bathrooms', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-minimum_bathrooms residential-only">',
            'options' => $bathrooms
        );
        $all_fields['maximum_bathrooms'] = array(
            'type' => 'select',
            'label' => __( 'Max Bathrooms', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-maximum_bathrooms residential-only">',
            'options' => $bathrooms
        );

        $reception_rooms = array(
            '' => __( 'No preference', 'propertyhive' ),
            '1' => '1',
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
        );

        $all_fields['minimum_reception_rooms'] = array(
            'type' => 'select',
            'label' => __( 'Min Receptions', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-minimum_reception_rooms residential-only">',
            'options' => $reception_rooms
        );
        $all_fields['maximum_reception_rooms'] = array(
            'type' => 'select',
            'label' => __( 'Max Receptions', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-maximum_reception_rooms residential-only">',
            'options' => $reception_rooms
        );

        $all_fields['bedrooms_slider'] = array(
            'type' => 'slider',
            'label' => __( 'Bedrooms', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-bedrooms-slider residential-only">',
            'min' => '0',
            'max' => '10',
        );
        $all_fields['available_date_from'] = array(
            'type' => 'date',
            'label' => __( 'Available From', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-available_date_from lettings-only">'
        );

        $all_fields['office'] = array(
            'type' => 'office',
            'label' => __( 'Office', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-office">'
        );

        $all_fields['keyword'] = array(
            'type' => 'text',
            'label' => __( 'Keyword', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-keyword">'
        );

        if ( get_option('propertyhive_features_type') == 'checkbox' )
        {
            $all_fields['property_feature'] = array(
                'type' => 'property_feature',
                'label' => __( 'Property Features', 'propertyhive' ),
                'show_label' => true,
                'before' => '<div class="control control-property_feature">',
                'multiselect' => true,
            );
        }

        $all_fields = apply_filters( 'propertyhive_search_form_all_fields', $all_fields );

        $currencies = array(
            '' => '',
            'GBP' => 'GBP',
            'EUR' => 'EUR',
            'USD' => 'USD',
        );

        $all_fields['currency'] = array(
            'type' => 'select',
            'label' => __( 'Currency', 'propertyhive' ),
            'show_label' => true,
            'before' => '<div class="control control-currency">',
            'options' => $currencies
        );

        $date_added_days = array(
            '' => __( 'No preference', 'propertyhive' ),
            '1' => 'Last 24 Hours',
            '3' => 'Last 3 Days',
            '7' => 'Last 7 Days',
            '14' => 'Last 14 Days',
        );

        $all_fields['date_added'] = array(
            'type' => 'select',
            'show_label' => true,
            'label' => __( 'Date Added', 'propertyhive' ),
            'options' => $date_added_days
        );

        $form_controls = ph_get_search_form_fields();
        $active_fields = apply_filters( 'propertyhive_search_form_fields_' . $current_id, $form_controls );

        // Add any additional fields
        if ( isset($current_settings['custom_fields']) && !empty($current_settings['custom_fields']) )
        {
            foreach ( $current_settings['custom_fields'] as $id => $custom_field )
            {
                $field_type = 'text';
                if ( isset($custom_field['field_type']) )
                {
                    switch ( $custom_field['field_type'] )
                    {
                        case 'select':
                        case 'multiselect':
                        {
                            $field_type = 'select';
                            break;
                        }
                        case 'checkbox':
                        {
                            $field_type = 'checkbox';
                            break;
                        }
                    }
                    
                }

                $all_fields[$custom_field['field_name']] = array(
                    'type' => $field_type,
                    'label' => $custom_field['field_label'],
                    'show_label' => true,
                    'before' => '<div class="control control-' . trim( $custom_field['field_name'], '_' ) . '">',
                    'custom_field' => true,
                );

                if ( isset($active_fields[$custom_field['field_name']]) )
                {
                    $active_fields[$custom_field['field_name']]['custom_field'] = true;
                }
            }
        }

        $inactive_fields = array();
        foreach ( $all_fields as $id => $field )
        {
            if ( !isset($active_fields[$id]) )
            {
                if ( isset($search_form_details['inactive_fields'][$id]) && !empty($search_form_details['inactive_fields'][$id]) )
                {
                    $field = array_merge($field, $search_form_details['inactive_fields'][$id]);
                }
                $inactive_fields[$id] = $field;
            }
        }
?>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php esc_html_e( 'Active Fields', 'propertyhive' ) ?></th>
            <td class="forminp">
                <div id="sortable1" class="connectedSortable" style="min-height:30px;">
                <?php
                    foreach ( $active_fields as $id => $field )
                    {
                        if ( isset( $field['type'] ) && $field['type'] == 'hidden' ) { continue; }

                        $this->output_search_form_field( $id, $field );
                    }
                ?>
                </div>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc"><?php esc_html_e( 'Inactive Fields', 'propertyhive' ) ?></th>
            <td class="forminp">
                <div id="sortable2" class="connectedSortable" style="min-height:30px;">
                <?php
                    if ( !class_exists('PH_Radial_Search') )
                    {
                        // Show radial search with link to buy add on
                        echo '<div class="group" id="radius-placeholder">
                            <h3>radius</h3>
                            <div class="">This field requires the <a href="https://wp-property-hive.com/addons/radial-search/" target="_blank">Radial Search add on</a></div>
                        </div>';
                    }
                    foreach ( $inactive_fields as $id => $field )
                    {
                        if ( isset( $field['type'] ) && $field['type'] == 'hidden' ) { continue; }

                        $this->output_search_form_field( $id, $field );
                    }
                ?>
                </div>
            </td>
        </tr>

        <input type="hidden" name="active_fields_order" id="active_fields_order" value="<?php
            $field_ids = array();
            foreach ( $active_fields as $id => $field )
            {
                $field_ids[] = $id;
            }
            echo implode("|", $field_ids);
        ?>">
        <input type="hidden" name="inactive_fields_order" id="inactive_fields_order" value="<?php
            $field_ids = array();
            foreach ( $inactive_fields as $id => $field )
            {
                $field_ids[] = $id;
            }
            echo implode("|", $field_ids);
        ?>">

        <script>
            jQuery(document).ready(function($)
            {
                $( "#sortable1" )
                .accordion({
                    collapsible: true,
                    active: false,
                    header: "> div > h3",
                    heightStyle: "content"
                })
                .sortable({
                    axis: "y",
                    handle: "h3",
                    connectWith: ".connectedSortable",
                    stop: function( event, ui ) 
                    {
                        // IE doesn't register the blur when sorting
                        // so trigger focusout handlers to remove .ui-state-focus
                        ui.item.children( "h3" ).triggerHandler( "focusout" );
             
                        // Refresh accordion to handle new order
                        $( this ).accordion( "refresh" );
                    },
                    update: function( event, ui ) 
                    {
                        // Update hidden fields
                        var fields_order = $(this).sortable('toArray');

                        fields_order = jQuery.grep(fields_order, function(value) {
                            return value != 'radius-placeholder';
                        });
                        
                        $('#active_fields_order').val( fields_order.join("|") );
                    }
                });

                $( "#sortable2" )
                .accordion({
                    collapsible: true,
                    active: false,
                    header: "> div > h3",
                    heightStyle: "content"
                })
                .sortable({
                    axis: "y",
                    handle: "h3",
                    connectWith: ".connectedSortable",
                    stop: function( event, ui ) 
                    {
                        // IE doesn't register the blur when sorting
                        // so trigger focusout handlers to remove .ui-state-focus
                        ui.item.children( "h3" ).triggerHandler( "focusout" );
             
                        // Refresh accordion to handle new order
                        $( this ).accordion( "refresh" );
                    },
                    update: function( event, ui ) 
                    {
                        // Update hidden fields
                        var fields_order = $(this).sortable('toArray');

                        fields_order = jQuery.grep(fields_order, function(value) {
                            return value != 'radius-placeholder';
                        });
                        
                        $('#inactive_fields_order').val( fields_order.join("|") );
                    }
                });

                // Handle add/remove options
                $('body').on('click', '.add-search-form-field-option', function(e)
                {
                    e.preventDefault();

                    var this_id = $(this).attr('id').replace("add_search_form_field_option_", "");

                    var clone = $('#sortable_options_' + this_id).children('span').eq(0).clone();
                    clone.find('input').val('');

                    clone.appendTo( $('#sortable_options_' + this_id) );

                    add_remove_option_links();
                });

                $('body').on('click', '.remove-search-form-field-option', function(e)
                {
                    e.preventDefault();
                    
                    $(this).parent().remove();

                    add_remove_option_links();
                });

                add_remove_option_links();
            });

            function add_remove_option_links()
            {
                jQuery('.connectedSortable .group a.remove-search-form-field-option').remove();

                jQuery('.connectedSortable .group').each(function()
                {
                    if ( jQuery(this).find('.add-search-form-field-option').length > 0 )
                    {   
                        console.log(jQuery(this).find('.form-field-options span').length);
                        if ( jQuery(this).find('.form-field-options span').length > 1 )
                        {
                            jQuery(this).find('.form-field-options span').append(' <a href="" class="remove-search-form-field-option">X</a>');
                        }
                    }
                });
            }
        </script>
<?php
    }

    /**
     * Get flag settings
     *
     * @return array Array of settings
     */
    public function get_flags_settings() {

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

        $settings = array(

            array( 'title' => __( 'Flags', 'propertyhive' ), 'type' => 'title', 'desc' => '', 'id' => 'template_assistant_flags_settings' )

        );

        $settings[] = array(
            'title' => __( 'Show Flags On Search Results', 'propertyhive' ),
            'id'        => 'flags_active',
            'type'      => 'checkbox',
            'default'   => ( ( isset($current_settings['flags_active']) && $current_settings['flags_active'] == '1' ) ? 'yes' : ''),
            'desc'      => 'If checked flags will be shown in search results over the property thumbnail containing the property availability or marketing flag if one selected'
        );

        $settings[] = array(
            'title' => __( 'Show Flags On Property Details', 'propertyhive' ),
            'id'        => 'flags_active_single',
            'type'      => 'checkbox',
            'default'   => ( ( isset($current_settings['flags_active_single']) && $current_settings['flags_active_single'] == '1' ) ? 'yes' : ''),
            'desc'      => 'If checked flags will be shown over the main image slideshow on the full property details page'
        );

        $settings[] = array(
            'title' => __( 'Position Over Thumbnail', 'propertyhive' ),
            'id'        => 'flag_position',
            'type'      => 'select',
            'default'   => ( isset($current_settings['flag_position']) ? $current_settings['flag_position'] : ''),
            'options'   => array(
                'top:0; left:0;' => 'Top Left',
                'top:0; right:0;' => 'Top Right',
                'bottom:0; left:0;' => 'Bottom Left',
                'bottom:0; right:0;' => 'Bottom Right',
                'top:0; left:0; right:0;' => 'Across Top',
                'bottom:0; left:0; right:0;' => 'Across Bottom',
            )
        );

        $settings[] = array(
            'title' => __( 'Background Colour', 'propertyhive' ),
            'id'        => 'flag_bg_color',
            'type'      => 'color',
            'default'   => ( isset($current_settings['flag_bg_color']) ? $current_settings['flag_bg_color'] : '#000'),
        );

        $settings[] = array(
            'title' => __( 'Text Colour', 'propertyhive' ),
            'id'        => 'flag_text_color',
            'type'      => 'color',
            'default'   => ( isset($current_settings['flag_text_color']) ? $current_settings['flag_text_color'] : '#FFF'),
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'template_assistant_flags_settings');

        return $settings;
    }

    /**
     * Get template set settings.
     *
     * @return array Array of settings
     */
    public function get_template_set_settings() {

        $current_settings = get_option( 'propertyhive_template_assistant', array() );
        $template_set_settings = PH_Template_Set::get_settings();
        $settings         = array();
        $map_state        = PH_Template_Set_Request_Context::get_map_search_state();
        $map_messages     = array(
            'unavailable'  => __( 'Map Atlas remains selectable and will use its normal listing fallback until Map Search is installed and active.', 'propertyhive' ),
            'unusable'     => __( 'Map Search is installed but is not currently licensed for use. Map Atlas will use its normal listing fallback.', 'propertyhive' ),
            'unconfigured' => __( 'Map Search is available but its view switcher or split format is not configured. Map Atlas will use its normal listing fallback.', 'propertyhive' ),
            'list-state'   => __( 'Map Search is ready. Map Atlas can use the configured list/map view.', 'propertyhive' ),
        );
        if ( $map_state['usable'] && 'split' === $map_state['format'] ) {
            $map_status = __( 'Map Search is ready in split-map format.', 'propertyhive' );
        } elseif ( $map_state['usable'] && 'view' === $map_state['format'] ) {
            $map_status = __( 'Map Search is ready with its list/map view switcher.', 'propertyhive' );
        } else {
            $map_status = isset( $map_messages[ $map_state['fallback_reason'] ] ) ? $map_messages[ $map_state['fallback_reason'] ] : $map_messages['unavailable'];
        }
        $map_status_html = esc_html( $map_status );
        if ( $map_state['available'] ) {
            $map_status_html .= ' <a href="' . esc_url( admin_url( 'admin.php?page=ph-settings&tab=mapsearch' ) ) . '">' . esc_html__( 'Map Search settings', 'propertyhive' ) . '</a>';
        }
        // Deliberately omit the licence gate: lapsed owners still need this admin-only guidance.
        if ( function_exists( 'PHIS' ) ) {
            $map_status_html .= ' ' . esc_html__( 'Infinite Scroll is replaced by pagination in split and map-only layouts.', 'propertyhive' );
        }
        $catalog_html     = '<div class="ph-template-admin-catalog"><p>' . esc_html__( 'Keep Portal-Style Search Results for the existing presentation, choose Portal Grid for an image-led listing, or use Map Atlas for a location-led experience. All three keep Property Hive search controls and theme templates in charge.', 'propertyhive' ) . '</p><ul>';

        foreach ( PH_Template_Set::get_template_catalog() as $slug => $template ) {
            $catalog_html .= '<li><strong>' . esc_html( $template['label'] ) . '</strong> <span>' . esc_html( $template['group'] ) . '</span> <a href="' . esc_url( PH_Template_Set::get_template_preview_url( $slug ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Preview', 'propertyhive' ) . '</a></li>';
            if ( 'map-led-search-results' === $slug ) {
                $catalog_html .= '<li class="description">' . wp_kses_post( $map_status_html ) . '</li>';
            }
        }

        $catalog_html .= '</ul></div>';

        // Editing method summary and chooser (renders above the settings table).
        $settings[] = array(
            'type' => 'template_experience',
        );

        // Remaining template styling controls live in a collapsed "advanced"
        // card, only shown when Developer Mode is selected. The
        // enable flag and editor mode are owned by the chooser above.
        $settings[] = array(
            'type' => 'template_advanced_start',
        );

        $settings[] = array(
            'title' => '',
            'type'  => 'title',
            'desc'  => __( 'Fine-tune the templates used for property detail pages and search results.', 'propertyhive' ),
            'id'    => 'template_set_settings',
        );

        $settings[] = array(
            'title'   => __( 'Property Detail Template', 'propertyhive' ),
            'id'      => 'template_set_detail_template',
            'type'    => 'select',
            'default' => isset( $current_settings['template_set_detail_template'] ) ? $current_settings['template_set_detail_template'] : 'conversion-first-sales-detail',
            'options' => PH_Template_Set::get_detail_templates(),
        );

        $settings[] = array(
            'title'   => __( 'Search Results Template', 'propertyhive' ),
            'id'      => 'template_set_search_template',
            'type'    => 'select',
            'default' => isset( $current_settings['template_set_search_template'] ) ? $current_settings['template_set_search_template'] : 'portal-style-search-results',
            'options' => PH_Template_Set::get_search_templates(),
        );

        $settings[] = array(
            'title'   => __( 'Search Listing Layout', 'propertyhive' ),
            'id'      => 'template_set_search_layout',
            'type'    => 'select',
            'default' => $template_set_settings['template_set_search_layout'],
            'options' => PH_Template_Set::get_search_card_layouts(),
        );

        $settings[] = array(
            'title'   => __( 'Search Card Size', 'propertyhive' ),
            'id'      => 'template_set_search_card_size',
            'type'    => 'select',
            'default' => isset( $current_settings['template_set_search_card_size'] ) ? $current_settings['template_set_search_card_size'] : 'standard',
            'options' => PH_Template_Set::get_search_card_sizes(),
        );

        $settings[] = array(
            'title'   => __( 'Grid Properties Per Row', 'propertyhive' ),
            'id'      => 'template_set_search_grid_columns',
            'type'    => 'select',
            'default' => isset( $current_settings['template_set_search_grid_columns'] ) ? $current_settings['template_set_search_grid_columns'] : 3,
            'options' => PH_Template_Set::get_search_grid_column_options(),
        );

        $settings[] = array(
            'title'   => __( 'Gallery Layout', 'propertyhive' ),
            'id'      => 'template_set_gallery_layout',
            'type'    => 'select',
            'default' => isset( $current_settings['template_set_gallery_layout'] ) ? $current_settings['template_set_gallery_layout'] : 'showcase',
            'options' => PH_Template_Set::get_gallery_layouts(),
        );

        $settings[] = array(
            'title' => __( 'Template Catalogue', 'propertyhive' ),
            'type'  => 'html',
            'html'  => $catalog_html,
        );

        $settings[] = array(
            'title'   => __( 'Brand Colour', 'propertyhive' ),
            'id'      => 'template_set_brand_colour',
            'type'    => 'color',
            'default' => isset( $current_settings['template_set_brand_colour'] ) ? $current_settings['template_set_brand_colour'] : '#155e63',
        );

        $settings[] = array(
            'title'   => __( 'Accent Colour', 'propertyhive' ),
            'id'      => 'template_set_accent_colour',
            'type'    => 'color',
            'default' => isset( $current_settings['template_set_accent_colour'] ) ? $current_settings['template_set_accent_colour'] : '#b7791f',
        );

        $settings[] = array(
            'title'   => __( 'Button Style', 'propertyhive' ),
            'id'      => 'template_set_button_style',
            'type'    => 'select',
            'default' => isset( $current_settings['template_set_button_style'] ) ? $current_settings['template_set_button_style'] : 'filled',
            'options' => array(
                'filled'  => __( 'Filled', 'propertyhive' ),
                'outline' => __( 'Outline', 'propertyhive' ),
                'soft'    => __( 'Soft', 'propertyhive' ),
            ),
        );

        $settings[] = array(
            'title'   => __( 'Image Style', 'propertyhive' ),
            'id'      => 'template_set_image_style',
            'type'    => 'select',
            'default' => isset( $current_settings['template_set_image_style'] ) ? $current_settings['template_set_image_style'] : 'soft',
            'options' => array(
                'square'  => __( 'Square', 'propertyhive' ),
                'soft'    => __( 'Soft corners', 'propertyhive' ),
                'rounded' => __( 'Rounded corners', 'propertyhive' ),
            ),
        );

        $settings[] = array(
            'title'   => __( 'Contact Card Style', 'propertyhive' ),
            'id'      => 'template_set_contact_card_style',
            'type'    => 'select',
            'default' => isset( $current_settings['template_set_contact_card_style'] ) ? $current_settings['template_set_contact_card_style'] : 'classic',
            'options' => PH_Template_Set::get_contact_card_styles(),
        );

        $settings[] = array(
            'title'         => __( 'Display Options', 'propertyhive' ),
            'id'            => 'template_set_show_branch',
            'type'          => 'checkbox',
            'checkboxgroup' => 'start',
            'default'       => ( ! isset( $current_settings['template_set_show_branch'] ) || 'yes' === $current_settings['template_set_show_branch'] ) ? 'yes' : '',
            'desc'          => __( 'Show branch contact details on property cards.', 'propertyhive' ),
        );

        $settings[] = array(
            'id'            => 'template_set_show_badges',
            'type'          => 'checkbox',
            'checkboxgroup' => '',
            'default'       => ( ! isset( $current_settings['template_set_show_badges'] ) || 'yes' === $current_settings['template_set_show_badges'] ) ? 'yes' : '',
            'desc'          => __( 'Show badges on property cards.', 'propertyhive' ),
        );

        $settings[] = array(
            'id'            => 'template_set_show_mobile_cta',
            'type'          => 'checkbox',
            'checkboxgroup' => '',
            'default'       => ( ! isset( $current_settings['template_set_show_mobile_cta'] ) || 'yes' === $current_settings['template_set_show_mobile_cta'] ) ? 'yes' : '',
            'desc'          => __( 'Show the mobile enquiry bar on property detail pages.', 'propertyhive' ),
        );

        $settings[] = array(
            'id'            => 'template_set_show_floorplans',
            'type'          => 'checkbox',
            'checkboxgroup' => '',
            'default'       => ( ! isset( $current_settings['template_set_show_floorplans'] ) || 'yes' === $current_settings['template_set_show_floorplans'] ) ? 'yes' : '',
            'desc'          => __( 'Show floorplans on property detail pages when available.', 'propertyhive' ),
        );

        $settings[] = array(
            'id'            => 'template_set_show_virtual_tours',
            'type'          => 'checkbox',
            'checkboxgroup' => '',
            'default'       => ( isset( $current_settings['template_set_show_virtual_tours'] ) && 'yes' === $current_settings['template_set_show_virtual_tours'] ) ? 'yes' : '',
            'desc'          => __( 'Show virtual tours on property detail pages when available.', 'propertyhive' ),
        );

        $settings[] = array(
            'id'            => 'template_set_show_recommended',
            'type'          => 'checkbox',
            'checkboxgroup' => 'end',
            'default'       => ( ! isset( $current_settings['template_set_show_recommended'] ) || 'yes' === $current_settings['template_set_show_recommended'] ) ? 'yes' : '',
            'desc'          => __( 'Show recommended homes on property detail pages.', 'propertyhive' ),
        );

        $settings[] = array(
            'title'   => __( 'Recommended Homes Count', 'propertyhive' ),
            'id'      => 'template_set_recommended_count',
            'type'    => 'select',
            'default' => isset( $current_settings['template_set_recommended_count'] ) ? $current_settings['template_set_recommended_count'] : 3,
            'options' => PH_Template_Set::get_recommended_property_counts(),
        );

        $settings[] = array(
            'title'   => __( 'Recommended Homes Layout', 'propertyhive' ),
            'id'      => 'template_set_recommended_layout',
            'type'    => 'select',
            'default' => isset( $current_settings['template_set_recommended_layout'] ) ? $current_settings['template_set_recommended_layout'] : 'grid',
            'options' => PH_Template_Set::get_recommended_property_layouts(),
        );

        $settings[] = array(
            'title'   => __( 'Recommended Homes Images', 'propertyhive' ),
            'id'      => 'template_set_recommended_image_size',
            'type'    => 'select',
            'default' => isset( $current_settings['template_set_recommended_image_size'] ) ? $current_settings['template_set_recommended_image_size'] : 'standard',
            'options' => PH_Template_Set::get_recommended_property_image_sizes(),
        );

        $settings[] = array(
            'title' => __( 'Shortcode', 'propertyhive' ),
            'type'  => 'html',
            'html'  => '<p><code>[propertyhive_featured_template title="Featured properties" per_page="3" columns="3"]</code></p>',
        );

        $settings[] = array( 'type' => 'sectionend', 'id' => 'template_set_settings');

        $settings[] = array(
            'type' => 'template_advanced_end',
        );

        return $settings;
    }

    /**
     * Open the collapsed advanced-settings card wrapping the template set table.
     *
     * @access public
     * @return void
     */
    public function template_advanced_start() {
        // Keep these controls exclusive to Developer Mode. Visual Editor and
        // Page Builder each provide their own editing method.
        $show_advanced = PH_Template_Set::EDITOR_MODE_DEVELOPER === PH_Template_Set::get_editor_mode();

        echo '<details class="ph-tx-advanced"' . ( $show_advanced ? '' : ' style="display:none"' ) . '><summary>' . esc_html__( 'Advanced template settings', 'propertyhive' ) . '<span class="ph-tx-advanced-chevron" aria-hidden="true"></span></summary><div class="ph-tx-advanced-body">';
    }

    /**
     * Close the advanced-settings card.
     *
     * @access public
     * @return void
     */
    public function template_advanced_end() {
        echo '</div></details>';
    }

    /**
     * Render the editing method summary, chooser, and method-specific workspace.
     *
     * @access public
     * @return void
     */
    public function template_experience_field() {

        $settings = PH_Template_Set::get_settings();
        $mode     = isset( $settings['template_set_editor_mode'] ) ? $settings['template_set_editor_mode'] : PH_Template_Set::EDITOR_MODE_VISUAL;

        // The chooser only knows three editing methods. Legacy/unknown sites preview
        // the Visual Editor without claiming it has already been selected.
        $card_modes = array(
            PH_Template_Set::EDITOR_MODE_VISUAL,
            PH_Template_Set::EDITOR_MODE_PAGE_BUILDER,
            PH_Template_Set::EDITOR_MODE_DEVELOPER,
        );
        $visual_editor_enabled   = isset( $settings[ PH_Template_Set::OPTION_ENABLED ] ) && 'yes' === $settings[ PH_Template_Set::OPTION_ENABLED ];
        $has_selected_experience = in_array( $mode, $card_modes, true ) &&
            ( PH_Template_Set::EDITOR_MODE_VISUAL !== $mode || $visual_editor_enabled );
        $selected                = $has_selected_experience ? $mode : PH_Template_Set::EDITOR_MODE_VISUAL;

        $nonce = wp_create_nonce( PH_Template_Set::EXPERIENCE_NONCE_ACTION );

        $cards = array(
            PH_Template_Set::EDITOR_MODE_VISUAL => array(
                'label'       => __( 'Visual Editor', 'propertyhive' ),
                'badge'       => __( 'Recommended', 'propertyhive' ),
                'description' => __( 'Choose from professionally designed templates and customise them with live previews.', 'propertyhive' ),
                'icon'        => $this->template_experience_icon( 'visual' ),
                'checks'      => array(
                    __( 'Live preview as you customise', 'propertyhive' ),
                    __( 'No coding required', 'propertyhive' ),
                    __( 'Pre-built templates', 'propertyhive' ),
                    __( 'Responsive on all devices', 'propertyhive' ),
                ),
            ),
            PH_Template_Set::EDITOR_MODE_PAGE_BUILDER => array(
                'label'       => __( 'Page Builder', 'propertyhive' ),
                'badge'       => '',
                'description' => __( 'Use Elementor, Divi or other supported page builders to design your templates.', 'propertyhive' ),
                'icon'        => $this->template_experience_icon( 'builder' ),
                'checks'      => array(
                    __( 'Works with Elementor, Divi & more', 'propertyhive' ),
                    __( 'Full design flexibility', 'propertyhive' ),
                    __( 'Access to builder ecosystem', 'propertyhive' ),
                    __( 'Advanced styling options', 'propertyhive' ),
                ),
            ),
            PH_Template_Set::EDITOR_MODE_DEVELOPER => array(
                'label'       => __( 'Developer Mode', 'propertyhive' ),
                'badge'       => '',
                'description' => __( 'Edit templates using code, CSS, and PHP. Perfect for developers who want full control.', 'propertyhive' ),
                'icon'        => $this->template_experience_icon( 'developer' ),
                'checks'      => array(
                    __( 'Full code control', 'propertyhive' ),
                    __( 'CSS & PHP customisation', 'propertyhive' ),
                    __( 'Override templates in your theme', 'propertyhive' ),
                    __( 'Version control friendly', 'propertyhive' ),
                ),
            ),
        );
        $current_card = $cards[ $selected ];
        ?>
        <div class="ph-template-experience<?php echo $has_selected_experience ? ' has-persisted-experience' : ''; ?>" data-selected="<?php echo esc_attr( $selected ); ?>" data-persisted-mode="<?php echo esc_attr( $has_selected_experience ? $mode : '' ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">

            <div class="ph-tx-current-method" tabindex="-1"<?php echo $has_selected_experience ? '' : ' hidden'; ?>>
                <span class="ph-tx-current-icon" aria-hidden="true"><?php echo $current_card['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <span class="ph-tx-current-copy">
                    <span class="ph-tx-current-kicker"><?php esc_html_e( 'Editing method', 'propertyhive' ); ?></span>
                    <strong class="ph-tx-current-label"><?php echo esc_html( $current_card['label'] ); ?></strong>
                    <span class="ph-tx-current-description"><?php echo esc_html( $current_card['description'] ); ?></span>
                </span>
                <button type="button" class="button ph-tx-change-method" aria-expanded="false" aria-controls="ph-template-editing-method-chooser">
                    <?php esc_html_e( 'Change editing method', 'propertyhive' ); ?>
                </button>
            </div>

            <div id="ph-template-editing-method-chooser" class="ph-tx-chooser<?php echo $has_selected_experience ? '' : ' is-open'; ?>" aria-hidden="<?php echo $has_selected_experience ? 'true' : 'false'; ?>"<?php echo $has_selected_experience ? ' inert' : ''; ?>>
                <div class="ph-tx-chooser-clip">
                    <div class="ph-tx-shell">

                    <div class="ph-tx-intro">
                        <div>
                            <h2 id="ph-template-editing-method-title" tabindex="-1">
                                <span class="ph-tx-first-choice"><?php esc_html_e( 'How would you like to edit your property templates?', 'propertyhive' ); ?></span>
                                <span class="ph-tx-change-choice"><?php esc_html_e( 'Change editing method', 'propertyhive' ); ?></span>
                            </h2>
                            <p>
                                <span class="ph-tx-first-choice"><?php esc_html_e( 'Choose the approach that best matches how you work.', 'propertyhive' ); ?></span>
                                <span class="ph-tx-change-choice"><?php esc_html_e( 'Your existing template settings are kept when you switch methods.', 'propertyhive' ); ?></span>
                            </p>
                        </div>
                        <button type="button" class="button-link ph-tx-chooser-cancel"<?php echo $has_selected_experience ? '' : ' hidden'; ?>><?php esc_html_e( 'Cancel', 'propertyhive' ); ?></button>
                    </div>

                    <div class="ph-tx-cards">
                        <?php foreach ( $cards as $card_mode => $card ) :
                            $is_selected = $has_selected_experience && ( $card_mode === $selected );
                            ?>
                            <div class="ph-tx-card<?php echo $is_selected ? ' is-selected' : ''; ?>" data-mode="<?php echo esc_attr( $card_mode ); ?>" data-label="<?php echo esc_attr( $card['label'] ); ?>" data-description="<?php echo esc_attr( $card['description'] ); ?>">
                                <div class="ph-tx-card-head">
                                    <span class="ph-tx-card-icon"><?php echo $card['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                    <h3>
                                        <?php echo esc_html( $card['label'] ); ?>
                                        <?php if ( ! empty( $card['badge'] ) ) : ?>
                                            <span class="ph-tx-badge"><?php echo esc_html( $card['badge'] ); ?></span>
                                        <?php endif; ?>
                                    </h3>
                                    <p><?php echo esc_html( $card['description'] ); ?></p>
                                </div>
                                <ul class="ph-tx-checks">
                                    <?php foreach ( $card['checks'] as $check ) : ?>
                                        <li><?php echo esc_html( $check ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="button ph-tx-select" data-mode="<?php echo esc_attr( $card_mode ); ?>">
                                    <span class="ph-tx-select-choose"><?php
                                        /* translators: %s: editing method name */
                                        echo esc_html( sprintf( __( 'Choose %s', 'propertyhive' ), $card['label'] ) );
                                    ?></span>
                                    <span class="ph-tx-select-selected"><?php esc_html_e( 'Selected', 'propertyhive' ); ?></span>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="ph-tx-hint">
                        <span class="ph-tx-hint-icon" aria-hidden="true"><?php echo $this->template_experience_icon( 'bulb' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <?php
                        printf(
                            /* translators: %s: "Learn more" link */
                            esc_html__( 'Not sure which option to choose? %s about each editing method.', 'propertyhive' ),
                            '<a href="' . esc_url( 'https://docs.wp-property-hive.com/article/282-an-introduction-to-integrating-property-hive-to-your-website' ) . '" target="_blank" rel="noopener">' . esc_html__( 'Learn more', 'propertyhive' ) . '</a>'
                        );
                        ?>
                    </div>

                    </div><!-- .ph-tx-shell -->
                </div><!-- .ph-tx-chooser-clip -->
            </div><!-- .ph-tx-chooser -->

            <div class="ph-tx-panels">
                <?php
                $this->template_experience_visual_panel( $settings );
                $this->template_experience_page_builder_panel();
                $this->template_experience_developer_panel();
                ?>
            </div>

            <p class="ph-tx-status" role="status" aria-live="polite"></p>
        </div>

        <script>
        jQuery( function( $ ) {
            var $wrap = $( '.ph-template-experience' );
            if ( ! $wrap.length ) { return; }
            var $chooser = $wrap.find( '.ph-tx-chooser' );
            var $current = $wrap.find( '.ph-tx-current-method' );
            var $change  = $wrap.find( '.ph-tx-change-method' );
            var $cancel  = $wrap.find( '.ph-tx-chooser-cancel' );
            var $title   = $wrap.find( '#ph-template-editing-method-title' );

            function showPanel( mode, markSelected ) {
                $wrap.find( '.ph-tx-card' ).removeClass( 'is-selected' );
                if ( markSelected ) {
                    $wrap.find( '.ph-tx-card[data-mode="' + mode + '"]' ).addClass( 'is-selected' );
                }
                $wrap.find( '.ph-tx-panel' ).removeClass( 'is-active' );
                if ( markSelected ) {
                    $wrap.find( '.ph-tx-panel[data-panel="' + mode + '"]' ).addClass( 'is-active' );
                }
                $wrap.attr( 'data-selected', mode );

                // Advanced settings are available only when Developer Mode is selected.
                $( '.ph-tx-advanced' ).toggle( markSelected && mode === '<?php echo esc_js( PH_Template_Set::EDITOR_MODE_DEVELOPER ); ?>' );
            }

            function updateCurrentMethod( mode ) {
                var $card = $wrap.find( '.ph-tx-card[data-mode="' + mode + '"]' );
                $current.find( '.ph-tx-current-icon' ).html( $card.find( '.ph-tx-card-icon' ).html() );
                $current.find( '.ph-tx-current-label' ).text( $card.data( 'label' ) );
                $current.find( '.ph-tx-current-description' ).text( $card.data( 'description' ) );
            }

            function setChooserOpen( open, restoreFocus ) {
                $chooser.toggleClass( 'is-open', open ).attr( 'aria-hidden', open ? 'false' : 'true' );
                $change.attr( 'aria-expanded', open ? 'true' : 'false' );

                if ( open ) {
                    $chooser.removeAttr( 'inert' );
                    window.setTimeout( function() { $title.trigger( 'focus' ); }, 180 );
                } else {
                    $chooser.attr( 'inert', '' );
                    if ( restoreFocus ) {
                        $change.trigger( 'focus' );
                    }
                }
            }

            showPanel(
                $wrap.attr( 'data-selected' ),
                $wrap.attr( 'data-persisted-mode' ) === $wrap.attr( 'data-selected' )
            );

            $wrap.on( 'click', '.ph-tx-change-method', function( e ) {
                e.preventDefault();
                setChooserOpen( true, false );
            } );

            $wrap.on( 'click', '.ph-tx-chooser-cancel', function( e ) {
                e.preventDefault();
                setChooserOpen( false, true );
            } );

            $( document ).on( 'keydown.phTemplateEditingMethod', function( e ) {
                if ( 'Escape' === e.key && $chooser.hasClass( 'is-open' ) && $wrap.attr( 'data-persisted-mode' ) ) {
                    e.preventDefault();
                    setChooserOpen( false, true );
                }
            } );

            $wrap.on( 'click', '.ph-tx-select', function( e ) {
                e.preventDefault();

                var mode  = $( this ).data( 'mode' );
                var $btns = $wrap.find( '.ph-tx-select' );

                if ( mode === $wrap.attr( 'data-persisted-mode' ) ) {
                    setChooserOpen( false, true );
                    return;
                }

                $btns.prop( 'disabled', true );
                $wrap.find( '.ph-tx-status' ).text( '' );

                $.post( ajaxurl, {
                    action: 'propertyhive_set_template_experience',
                    mode:   mode,
                    nonce:  $wrap.data( 'nonce' )
                } ).done( function( response ) {
                    if ( response && response.success ) {
                        $wrap.attr( 'data-persisted-mode', mode );
                        $wrap.addClass( 'has-persisted-experience' );
                        $current.removeAttr( 'hidden' );
                        $cancel.removeAttr( 'hidden' );
                        updateCurrentMethod( mode );
                        showPanel( mode, true );
                        setChooserOpen( false, false );
                        $current.trigger( 'focus' );
                    } else {
                        var msg = ( response && response.data && response.data.message ) ? response.data.message : '<?php echo esc_js( __( 'Could not save. Please try again.', 'propertyhive' ) ); ?>';
                        $wrap.find( '.ph-tx-status' ).text( msg );
                    }
                } ).fail( function() {
                    $wrap.find( '.ph-tx-status' ).text( '<?php echo esc_js( __( 'Could not save. Please try again.', 'propertyhive' ) ); ?>' );
                } ).always( function() {
                    $btns.prop( 'disabled', false );
                } );
            } );
        } );
        </script>
        <?php
    }

    /**
     * Contextual panel for the Visual Editor experience.
     *
     * @param array $settings Template set settings.
     * @return void
     */
    private function template_experience_visual_panel( $settings ) {
        $search_slug = isset( $settings['template_set_search_template'] ) ? $settings['template_set_search_template'] : '';
        $detail_slug = isset( $settings['template_set_detail_template'] ) ? $settings['template_set_detail_template'] : '';

        $edit_search_url = add_query_arg( PH_Template_Set::EDIT_QUERY_ARG, '1', PH_Template_Set::get_template_preview_url( $search_slug ) );
        $edit_detail_url = add_query_arg( PH_Template_Set::EDIT_QUERY_ARG, '1', PH_Template_Set::get_template_preview_url( $detail_slug ) );
        ?>
        <div class="ph-tx-panel ph-tx-panel-row ph-tx-panel-row--visual" data-panel="<?php echo esc_attr( PH_Template_Set::EDITOR_MODE_VISUAL ); ?>">
            <div class="ph-tx-panel-card">
                <h3><?php esc_html_e( 'Visual Editor', 'propertyhive' ); ?> <span class="ph-tx-badge ph-tx-badge--new"><?php esc_html_e( 'NEW', 'propertyhive' ); ?></span></h3>
                <div class="ph-tx-media">
                    <span class="ph-tx-panel-icon ph-tx-panel-icon--brand"><?php echo $this->template_experience_icon( 'brush' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                    <div>
                        <p><?php esc_html_e( 'The Visual Editor is our new and improved way to customise your property templates. It provides a live preview of your changes and makes it easy to create beautiful, responsive layouts without any coding knowledge.', 'propertyhive' ); ?></p>
                        <p class="ph-tx-actions">
                            <a class="button button-primary" href="<?php echo esc_url( $edit_search_url ); ?>"><?php esc_html_e( 'Edit Search Template', 'propertyhive' ); ?> <span class="ph-tx-btn-glyph" aria-hidden="true">&rsaquo;</span></a>
                            <a class="button button-primary" href="<?php echo esc_url( $edit_detail_url ); ?>"><?php esc_html_e( 'Edit Details Template', 'propertyhive' ); ?> <span class="ph-tx-btn-glyph" aria-hidden="true">&rsaquo;</span></a>
                        </p>
                    </div>
                </div>
            </div>
            <div class="ph-tx-panel-card">
                <h3><?php esc_html_e( 'Need help getting started?', 'propertyhive' ); ?></h3>
                <p><?php esc_html_e( 'Browse the Property Hive documentation or visit our official video channel for help and tutorials.', 'propertyhive' ); ?></p>
                <p class="ph-tx-actions">
                    <a class="button" href="<?php echo esc_url( 'https://docs.wp-property-hive.com/category/325-template-assistant' ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View Documentation', 'propertyhive' ); ?> <span class="ph-tx-btn-glyph" aria-hidden="true">&#8599;</span></a>
                    <a class="button" href="<?php echo esc_url( 'https://www.youtube.com/channel/UCbxvikUbwOUzntjY5WYI-dQ' ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Visit Video Channel', 'propertyhive' ); ?> <span class="ph-tx-btn-glyph" aria-hidden="true">&#9654;</span></a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Contextual panel for the Page Builder experience. Shows a detected state
     * when a supported builder is active, otherwise a "get started" state.
     *
     * @return void
     */
    private function template_experience_page_builder_panel() {
        $builder            = PH_Template_Set::detect_page_builder();
        $supported_builders = PH_Template_Set_Page_Builders::get_supported();
        $builder_resources  = ! empty( $builder['builder'] ) && isset( $supported_builders[ $builder['builder'] ] )
            ? $supported_builders[ $builder['builder'] ]
            : array();
        $builder_docs_url   = isset( $builder_resources['docs_url'] )
            ? $builder_resources['docs_url']
            : 'https://docs.wp-property-hive.com/article/282-an-introduction-to-integrating-property-hive-to-your-website';
        $builder_video_url  = isset( $builder_resources['video_url'] ) ? $builder_resources['video_url'] : '';
        $builder_ready      = ! empty( $builder['ready'] );
        ?>
        <div class="ph-tx-panel" data-panel="<?php echo esc_attr( PH_Template_Set::EDITOR_MODE_PAGE_BUILDER ); ?>">
            <?php if ( ! empty( $builder['active'] ) ) :
                $theme_builder_url = '';
                if ( 'elementor' === $builder['builder'] && ! empty( $builder['theme_builder'] ) ) {
                    $theme_builder_url = admin_url( 'admin.php?page=elementor-app#/site-editor' );
                } elseif ( 'divi' === $builder['builder'] ) {
                    $theme_builder_url = admin_url( 'admin.php?page=et_theme_builder' );
                }
                ?>
                <div class="ph-tx-panel-card ph-tx-panel-card--columns">
                    <div class="ph-tx-panel-col">
                        <?php if ( 'elementor' === $builder['builder'] ) : ?>
                            <span class="ph-tx-panel-icon ph-tx-panel-icon--logo"><?php echo $this->template_experience_icon( 'elementor' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <?php else : ?>
                            <span class="ph-tx-panel-icon ph-tx-panel-icon--brand"><?php echo $this->template_experience_icon( 'builder' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <?php endif; ?>
                        <h3>
                            <?php
                            /* translators: %s: page builder name */
                            echo esc_html( sprintf( __( '%s is active', 'propertyhive' ), $builder['name'] ) );
                            ?>
                            <span class="ph-tx-badge <?php echo esc_attr( $builder_ready ? 'ph-tx-badge--detected' : 'ph-tx-badge--setup' ); ?>">
                                <?php echo $builder_ready ? esc_html__( 'Ready', 'propertyhive' ) : esc_html__( 'Setup required', 'propertyhive' ); ?>
                            </span>
                        </h3>
                        <?php if ( $builder_ready ) : ?>
                            <p><?php
                                /* translators: %s: page builder name */
                                echo esc_html( sprintf( __( 'You can use %s Theme Builder to design Property Detail templates with full visual control.', 'propertyhive' ), $builder['name'] ) );
                            ?></p>
                        <?php else : ?>
                            <p><?php
                                /* translators: %s: page builder name */
                                echo esc_html( sprintf( __( '%s is active, but its Theme Builder is not available. Elementor Pro is required before you can create Property Hive templates.', 'propertyhive' ), $builder['name'] ) );
                            ?></p>
                        <?php endif; ?>
                        <div class="ph-tx-supported">
                            <ul class="ph-tx-checks ph-tx-checks--stacked">
                                <?php if ( ! empty( $builder['version'] ) ) : ?>
                                    <li><?php echo esc_html( $builder['name'] . ' v' . $builder['version'] ); ?></li>
                                <?php else : ?>
                                    <li><?php echo esc_html( sprintf( __( '%s active', 'propertyhive' ), $builder['name'] ) ); ?></li>
                                <?php endif; ?>
                                <?php if ( ! empty( $builder['theme_builder'] ) ) : ?>
                                    <li><?php esc_html_e( 'Theme Builder active', 'propertyhive' ); ?></li>
                                <?php else : ?>
                                    <li class="ph-tx-check-warning"><?php esc_html_e( 'Elementor Pro Theme Builder required', 'propertyhive' ); ?></li>
                                <?php endif; ?>
                            </ul>
                            <?php if ( ! empty( $theme_builder_url ) ) : ?>
                                <p class="ph-tx-actions">
                                    <a class="button button-primary" href="<?php echo esc_url( $theme_builder_url ); ?>"><?php esc_html_e( 'Open Theme Builder', 'propertyhive' ); ?> <span class="ph-tx-btn-glyph" aria-hidden="true">&#8599;</span></a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ph-tx-panel-col">
                        <h3><?php esc_html_e( 'Getting started', 'propertyhive' ); ?></h3>
                        <ol class="ph-tx-steps">
                            <?php if ( $builder_ready ) : ?>
                                <li><strong><?php esc_html_e( 'Open Theme Builder', 'propertyhive' ); ?></strong><br><?php echo esc_html( sprintf( __( 'Open the Theme Builder provided by %s.', 'propertyhive' ), $builder['name'] ) ); ?></li>
                                <li><strong><?php esc_html_e( 'Create a Property Detail template', 'propertyhive' ); ?></strong><br><?php esc_html_e( 'Choose the single-property display conditions described in the integration guide.', 'propertyhive' ); ?></li>
                                <li><strong><?php esc_html_e( 'Publish your changes', 'propertyhive' ); ?></strong><br><?php esc_html_e( 'Your detail-template changes will be applied to matching properties.', 'propertyhive' ); ?></li>
                            <?php else : ?>
                                <li><strong><?php esc_html_e( 'Activate Elementor Pro', 'propertyhive' ); ?></strong><br><?php esc_html_e( 'Property Hive template integration requires Elementor Pro Theme Builder.', 'propertyhive' ); ?></li>
                                <li><strong><?php esc_html_e( 'Open Theme Builder', 'propertyhive' ); ?></strong><br><?php esc_html_e( 'Create a new Single template for Property Hive properties.', 'propertyhive' ); ?></li>
                                <li><strong><?php esc_html_e( 'Follow the integration guide', 'propertyhive' ); ?></strong><br><?php esc_html_e( 'Apply the correct display conditions before publishing.', 'propertyhive' ); ?></li>
                            <?php endif; ?>
                        </ol>
                    </div>
                    <div class="ph-tx-panel-col">
                        <h3><?php esc_html_e( 'Helpful resources', 'propertyhive' ); ?></h3>
                        <div class="ph-tx-resources">
                            <a class="ph-tx-resource" href="<?php echo esc_url( $builder_docs_url ); ?>" target="_blank" rel="noopener">
                                <span class="ph-tx-resource-text">
                                    <strong><?php echo esc_html( sprintf( __( 'How to use %s with Property Hive', 'propertyhive' ), $builder['name'] ) ); ?></strong>
                                    <span><?php esc_html_e( 'Step-by-step guide to getting started.', 'propertyhive' ); ?></span>
                                </span>
                                <span class="ph-tx-btn-glyph" aria-hidden="true">&#8599;</span>
                            </a>
                            <a class="ph-tx-resource" href="<?php echo esc_url( $builder_docs_url ); ?>" target="_blank" rel="noopener">
                                <span class="ph-tx-resource-text">
                                    <strong><?php esc_html_e( 'Which templates can I edit?', 'propertyhive' ); ?></strong>
                                    <span><?php echo esc_html( sprintf( __( 'See how %s integrates with Property Detail templates.', 'propertyhive' ), $builder['name'] ) ); ?></span>
                                </span>
                                <span class="ph-tx-btn-glyph" aria-hidden="true">&#8599;</span>
                            </a>
                            <?php if ( ! empty( $builder_video_url ) ) : ?>
                                <a class="ph-tx-resource" href="<?php echo esc_url( $builder_video_url ); ?>" target="_blank" rel="noopener">
                                    <span class="ph-tx-resource-text">
                                        <strong><?php esc_html_e( 'Watch video tutorial', 'propertyhive' ); ?></strong>
                                        <span><?php echo esc_html( sprintf( __( 'Learn how to customise templates with %s.', 'propertyhive' ), $builder['name'] ) ); ?></span>
                                    </span>
                                    <span class="ph-tx-btn-glyph" aria-hidden="true">&#9654;</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <div class="ph-tx-panel-card ph-tx-panel-card--columns">
                    <div class="ph-tx-panel-col">
                        <span class="ph-tx-panel-icon"><?php echo $this->template_experience_icon( 'puzzle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <h3><?php esc_html_e( 'No page builder detected', 'propertyhive' ); ?></h3>
                        <p><?php esc_html_e( "We didn't detect any supported page builders on your site. Page builders let you visually design your property templates with drag and drop tools.", 'propertyhive' ); ?></p>
                        <div class="ph-tx-supported">
                            <strong><?php esc_html_e( 'Supported page builders:', 'propertyhive' ); ?></strong>
                            <ul class="ph-tx-checks ph-tx-checks--stacked">
                                <?php foreach ( PH_Template_Set_Page_Builders::get_supported() as $supported ) : ?>
                                    <li><?php echo esc_html( $supported['name'] ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="ph-tx-panel-col">
                        <h3><?php esc_html_e( 'What is a page builder?', 'propertyhive' ); ?></h3>
                        <p><?php esc_html_e( 'Page builders are plugins that provide a visual interface for creating and designing page layouts.', 'propertyhive' ); ?></p>
                        <ul class="ph-tx-checks ph-tx-checks--stacked">
                            <li><?php esc_html_e( 'Drag and drop interface', 'propertyhive' ); ?></li>
                            <li><?php esc_html_e( 'No coding required', 'propertyhive' ); ?></li>
                            <li><?php esc_html_e( 'Full design flexibility', 'propertyhive' ); ?></li>
                        </ul>
                    </div>
                    <div class="ph-tx-panel-col">
                        <h3><?php esc_html_e( 'Get started', 'propertyhive' ); ?></h3>
                        <p><?php esc_html_e( 'Install and activate a supported page builder to unlock visual template building.', 'propertyhive' ); ?></p>
                        <p class="ph-tx-actions ph-tx-actions--stacked">
                            <a class="button button-primary" href="<?php echo esc_url( 'https://docs.wp-property-hive.com/article/282-an-introduction-to-integrating-property-hive-to-your-website' ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View our documentation', 'propertyhive' ); ?> <span class="ph-tx-btn-glyph" aria-hidden="true">&#8599;</span></a>
                            <?php foreach ( $supported_builders as $supported ) : ?>
                                <a class="button" href="<?php echo esc_url( $supported['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( sprintf( __( 'Explore %s', 'propertyhive' ), $supported['name'] ) ); ?></a>
                            <?php endforeach; ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Contextual panel for the Developer experience.
     *
     * @return void
     */
    private function template_experience_developer_panel() {
        $links = array(
            array(
                'title' => __( 'Template Overrides', 'propertyhive' ),
                'desc'  => __( 'Learn how to override any template file.', 'propertyhive' ),
                'icon'  => 'file',
                'url'   => 'https://docs.wp-property-hive.com/article/322-overriding-templates',
            ),
            array(
                'title' => __( 'Hooks & Filters', 'propertyhive' ),
                'desc'  => __( 'Learn how to extend Property Hive with hooks and filters.', 'propertyhive' ),
                'icon'  => 'code_box',
                'url'   => 'https://docs.wp-property-hive.com/article/260-using-hooks',
            ),
            array(
                'title' => __( 'Developer Guide', 'propertyhive' ),
                'desc'  => __( 'Full guide to extending Property Hive.', 'propertyhive' ),
                'icon'  => 'folder',
                'url'   => 'https://docs.wp-property-hive.com/category/32-developer-information',
            ),
            array(
                'title' => __( 'Sample Add-on', 'propertyhive' ),
                'desc'  => __( 'Example of a custom add-on plugin.', 'propertyhive' ),
                'icon'  => 'github',
                'url'   => 'https://github.com/propertyhive/WP-Property-Hive-Skeleton-Add-On',
            ),
        );
        ?>
        <div class="ph-tx-panel" data-panel="<?php echo esc_attr( PH_Template_Set::EDITOR_MODE_DEVELOPER ); ?>">
            <div class="ph-tx-panel-card ph-tx-panel-card--columns ph-tx-panel-card--two">
            <div class="ph-tx-panel-col">
                <span class="ph-tx-panel-icon"><?php echo $this->template_experience_icon( 'developer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                <h3><?php esc_html_e( 'Build without limits', 'propertyhive' ); ?></h3>
                <p><?php esc_html_e( 'Developer Mode allows you to override any Property Hive template and hook into the plugin using actions and filters. Perfect for developers who need full control.', 'propertyhive' ); ?></p>
                <ul class="ph-tx-checks ph-tx-checks--stacked">
                    <li><?php esc_html_e( 'Override any template', 'propertyhive' ); ?></li>
                    <li><?php esc_html_e( 'Extend with hooks & filters', 'propertyhive' ); ?></li>
                    <li><?php esc_html_e( 'Safe, upgrade-friendly approach', 'propertyhive' ); ?></li>
                </ul>
            </div>
            <div class="ph-tx-panel-col">
                <h3><?php esc_html_e( 'Quick links', 'propertyhive' ); ?></h3>
                <div class="ph-tx-quicklinks">
                    <?php foreach ( $links as $link ) : ?>
                        <a class="ph-tx-quicklink" href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener">
                            <span class="ph-tx-quicklink-icon"><?php echo $this->template_experience_icon( $link['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <strong><?php echo esc_html( $link['title'] ); ?></strong>
                            <span><?php echo esc_html( $link['desc'] ); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <p class="ph-tx-note"><?php esc_html_e( 'All overrides are upgrade safe. Your changes will not be lost when the plugin updates.', 'propertyhive' ); ?></p>
            </div>
            </div>
        </div>
        <?php
    }

    /**
     * Inline SVG icons for the experience chooser. Kept as simple line icons to
     * match the design mockups.
     *
     * @param string $name Icon key.
     * @return string SVG markup.
     */
    private function template_experience_icon( $name ) {
        $icons = array(
            // Bold layout: rounded square, top bar, left column (per design card).
            'visual'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="3.5" width="17" height="17" rx="1.5"/><path d="M3.5 8.5h17M9.5 20.5v-12"/></svg>',
            // Elementor-style bars glyph (per design card): solid vertical bar + three horizontal bars.
            'builder'   => '<svg viewBox="0 0 24 24" fill="none"><rect x="4.5" y="4.5" width="3.4" height="15" rx="0.7" fill="currentColor"/><rect x="10.4" y="4.5" width="9.1" height="3.4" rx="0.7" fill="currentColor"/><rect x="10.4" y="10.3" width="9.1" height="3.4" rx="0.7" fill="currentColor"/><rect x="10.4" y="16.1" width="9.1" height="3.4" rx="0.7" fill="currentColor"/></svg>',
            // Bold </> mark (per design card and developer panel).
            'developer' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 8.2 4.7 12l3.8 3.8M15.5 8.2l3.8 3.8-3.8 3.8M13.6 5.4l-3.2 13.2"/></svg>',
            // Solid paintbrush at 45° (per design "Visual Editor NEW" panel).
            'brush'     => '<svg viewBox="0 0 24 24" fill="none"><path d="M10.9 12.9 17.3 5a2.6 2.6 0 0 1 4 3.3l-7.2 7.2" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 13.5c-1.9 0-3.4 1.5-3.4 3.4 0 1.5-1.2 2.2-2 2.6 1.1.9 2.5 1.6 4 1.6a4 4 0 0 0 4-4c0-1.9-1-3.6-2.6-3.6Z" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linejoin="round"/></svg>',
            // Puzzle piece: nub on the right edge, notch in the bottom edge (per design).
            'puzzle'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5.2 10.5V6a.8.8 0 0 1 .8-.8h12a.8.8 0 0 1 .8.8v4h.6a2.1 2.1 0 1 1 0 4.2h-.6v4a.8.8 0 0 1-.8.8h-4.3v-.5a2.1 2.1 0 1 0-4.2 0v.5H6a.8.8 0 0 1-.8-.8v-4.4"/></svg>',
            // Document with folded top-right corner and text lines (per design).
            'file'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 3.5H6.8a1 1 0 0 0-1 1v15a1 1 0 0 0 1 1h10.4a1 1 0 0 0 1-1V7.2l-3.7-3.7z"/><path d="M14.5 3.5v3.7h3.7"/><path d="M8.5 12h7M8.5 15h7M8.5 18h4.5"/></svg>',
            // </> inside a rounded box (per design "Hooks & Filters" tile).
            'code_box'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="4.5" width="17" height="15" rx="2.5"/><path d="m9.2 9.5-2.4 2.5 2.4 2.5M14.8 9.5l2.4 2.5-2.4 2.5M13 8.2l-2 7.6"/></svg>',
            'folder'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 18.5v-12a1 1 0 0 1 1-1h4.3l2 2.3h8.7a1 1 0 0 1 1 1v9.7a1 1 0 0 1-1 1h-15a1 1 0 0 1-1-1z"/></svg>',
            // GitHub octocat mark (per design "Sample Add-on" tile).
            'github'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-4 1.5-5-2-5-2m10 5v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 18 4.77 5.07 5.07 0 0 0 17.91 1S16.73.65 14 2.48a13.38 13.38 0 0 0-7 0C4.27.65 3.09 1 3.09 1A5.07 5.07 0 0 0 3 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 7 18.13V22"/></svg>',
            // Elementor brand roundel (used when Elementor is the detected builder).
            'elementor' => '<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10.5" fill="#92003B"/><rect x="7.4" y="7.3" width="2.1" height="9.4" fill="#fff"/><rect x="11.1" y="7.3" width="5.5" height="2.1" fill="#fff"/><rect x="11.1" y="10.95" width="5.5" height="2.1" fill="#fff"/><rect x="11.1" y="14.6" width="5.5" height="2.1" fill="#fff"/></svg>',
            // Yellow lightbulb (hint bar).
            'bulb'      => '<svg viewBox="0 0 24 24" fill="none" stroke="#f5c518" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0-4 10.47c.63.55 1 1.34 1 2.18V16a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-.35c0-.84.37-1.63 1-2.18A6 6 0 0 0 12 3z"/><path d="M10 20h4"/></svg>',
        );

        return isset( $icons[ $name ] ) ? $icons[ $name ] : '';
    }

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section, $hide_save_button;

		if ( $current_section ) 
        {
        	switch ($current_section)
            {
                case "search-results": { $settings = $this->get_settings(); break; }
            	case "search-forms": { $hide_save_button = true; $settings = $this->get_search_forms_settings(); break; }
                case "addsearchform": { $settings = $this->get_search_form_settings(); break; }
                case "editsearchform": { $settings = $this->get_search_form_settings(); break; }
            	case "flags": { $settings = $this->get_flags_settings();  break; }
                case "template-set": { $settings = $this->get_template_set_settings(); break; }
                default: { die("Unknown setting section"); }
            }
        }
        else
        {
            $settings = $this->get_template_set_settings();
        }

		PH_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() 
	{
		global $current_section;

        $current_settings = get_option( 'propertyhive_template_assistant', array() );

		// Property Templates is the Frontend default. Search Results now has an
		// explicit section ID but continues to use its established save path.
		if ( '' === $current_section ) {
			$current_section = 'template-set';
		} elseif ( 'search-results' === $current_section ) {
			$current_section = '';
		}

		if ( $current_section != '' ) 
        {
        	switch ($current_section)
        	{
        		case "flags": 
                {
                    $propertyhive_template_assistant = array(
                        'flags_active' => ( ( isset($_POST['flags_active']) ) ? sanitize_text_field($_POST['flags_active']) : '' ),
                        'flags_active_single' => ( ( isset($_POST['flags_active_single']) ) ? sanitize_text_field($_POST['flags_active_single']) : '' ),
                        'flag_position' => sanitize_text_field($_POST['flag_position']),
                        'flag_bg_color' => sanitize_text_field($_POST['flag_bg_color']),
                        'flag_text_color' => sanitize_text_field($_POST['flag_text_color']),
                    );

                    $propertyhive_template_assistant = array_merge($current_settings, $propertyhive_template_assistant);

                    update_option( 'propertyhive_template_assistant', $propertyhive_template_assistant );
                    break; 
                }
                case "template-set":
                {
                    $propertyhive_template_assistant = PH_Template_Set::sanitize_template_set_settings( $_POST, $current_settings, false );

                    update_option( 'propertyhive_template_assistant', $propertyhive_template_assistant );
                    break;
                }
        		case "addsearchform": 
                case "editsearchform": 
                {
                    $current_id = ( !isset( $_REQUEST['id'] ) ) ? '' : sanitize_title( $_REQUEST['id'] );

                    $existing_search_forms = ( (isset($current_settings['search_forms'])) ? $current_settings['search_forms'] : array() );

                    if ( $current_section == 'editsearchform' && $current_id != 'default' && !isset($existing_search_forms[$current_id]) )
                    {
                        die("Trying to edit a non-existant search form. Please go back and try again");
                    }

                    if ( isset($existing_search_forms[$current_id]) )
                    {
                        unset($existing_search_forms[$current_id]);
                    }

                    $current_id = ( ( isset($_POST['form_id']) && $_POST['form_id'] != '' ) ? str_replace("-", "_", sanitize_title($_POST['form_id'])) : $current_id );
                    if ($current_section == 'addsearchform' && trim($current_id) == '' )
                    {
                        $current_id = 'custom';
                    }

                    $active_fields = array();
                    $inactive_fields = array();

                    if ( isset($_POST['active_fields_order']) && $_POST['active_fields_order'] != '' )
                    {
                        $field_ids = explode("|", sanitize_text_field($_POST['active_fields_order']));
                        if ( !empty($field_ids) )
                        {
                            foreach ( $field_ids as $field_id )
                            {
                                $active_fields[$field_id] = array(
                                    'show_label' => ( ( isset($_POST['show_label'][$field_id]) && $_POST['show_label'][$field_id] == '1' ) ? true : false ),
                                    'label' => ( isset($_POST['label'][$field_id]) ? stripslashes($_POST['label'][$field_id]) : '' ),
                                );

                                if ( isset($_POST['type'][$field_id]) && $_POST['type'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['type'] = stripslashes($_POST['type'][$field_id]);
                                }
                                if ( isset($_POST['before'][$field_id]) && $_POST['before'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['before'] = stripslashes($_POST['before'][$field_id]);
                                }
                                if ( isset($_POST['after'][$field_id]) && $_POST['after'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['after'] = stripslashes($_POST['after'][$field_id]);
                                }
                                if ( isset($_POST['placeholder'][$field_id]) && $_POST['placeholder'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['placeholder'] = stripslashes($_POST['placeholder'][$field_id]);
                                }
                                if ( isset($_POST['min'][$field_id]) && $_POST['min'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['min'] = stripslashes($_POST['min'][$field_id]);
                                }
                                if ( isset($_POST['max'][$field_id]) && $_POST['max'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['max'] = stripslashes($_POST['max'][$field_id]);
                                }
                                if ( isset($_POST['step'][$field_id]) && $_POST['step'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['step'] = stripslashes($_POST['step'][$field_id]);
                                }
                                if ( isset($_POST['blank_option'][$field_id]) && $_POST['blank_option'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['blank_option'] = stripslashes($_POST['blank_option'][$field_id]);
                                }
                                if ( isset($_POST['parent_terms_only'][$field_id]) && $_POST['parent_terms_only'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['parent_terms_only'] = true;
                                }
                                if ( isset($_POST['dynamic_population'][$field_id]) && $_POST['dynamic_population'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['dynamic_population'] = true;
                                }
                                if ( isset($_POST['hide_empty'][$field_id]) && $_POST['hide_empty'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['hide_empty'] = true;
                                }
                                if ( isset($_POST['multiselect'][$field_id]) && $_POST['multiselect'][$field_id] != '' )
                                {
                                    $active_fields[$field_id]['multiselect'] = true;
                                }

                                if ( isset($_POST['option_keys'][$field_id]) && is_array($_POST['option_keys'][$field_id]) && !empty($_POST['option_keys'][$field_id]) )
                                {
                                    $options = array();
                                    foreach ( $_POST['option_keys'][$field_id] as  $i => $key )
                                    {
                                        $options[$key] = $_POST['options_values'][$field_id][$i];
                                    }
                                    $active_fields[$field_id]['options'] = $options;
                                }
                            }
                        }
                    }

                    if ( isset($_POST['inactive_fields_order']) && $_POST['inactive_fields_order'] != '' )
                    {
                        $field_ids = explode("|", sanitize_text_field($_POST['inactive_fields_order']));
                        if ( !empty($field_ids) )
                        {
                            foreach ( $field_ids as $field_id )
                            {
                                $inactive_fields[$field_id] = array(
                                    'show_label' => ( ( isset($_POST['show_label'][$field_id]) && $_POST['show_label'][$field_id] == '1' ) ? true : false ),
                                    'label' => ( isset($_POST['label'][$field_id]) ? stripslashes($_POST['label'][$field_id]) : '' ),
                                );

                                if ( isset($_POST['type'][$field_id]) && $_POST['type'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['type'] = stripslashes($_POST['type'][$field_id]);
                                }
                                if ( isset($_POST['before'][$field_id]) && $_POST['before'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['before'] = stripslashes($_POST['before'][$field_id]);
                                }
                                if ( isset($_POST['after'][$field_id]) && $_POST['after'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['after'] = stripslashes($_POST['after'][$field_id]);
                                }
                                if ( isset($_POST['placeholder'][$field_id]) && $_POST['placeholder'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['placeholder'] = stripslashes($_POST['placeholder'][$field_id]);
                                }
                                if ( isset($_POST['blank_option'][$field_id]) && $_POST['blank_option'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['blank_option'] = stripslashes($_POST['blank_option'][$field_id]);
                                }
                                if ( isset($_POST['parent_terms_only'][$field_id]) && $_POST['parent_terms_only'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['parent_terms_only'] = true;
                                }
                                if ( isset($_POST['dynamic_population'][$field_id]) && $_POST['dynamic_population'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['dynamic_population'] = true;
                                }
                                if ( isset($_POST['hide_empty'][$field_id]) && $_POST['hide_empty'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['hide_empty'] = true;
                                }
                                if ( isset($_POST['multiselect'][$field_id]) && $_POST['multiselect'][$field_id] != '' )
                                {
                                    $inactive_fields[$field_id]['multiselect'] = true;
                                }

                                if ( isset($_POST['option_keys'][$field_id]) && is_array($_POST['option_keys'][$field_id]) && !empty($_POST['option_keys'][$field_id]) )
                                {
                                    $options = array();
                                    foreach ( $_POST['option_keys'][$field_id] as  $i => $key )
                                    {
                                        $options[$key] = $_POST['options_values'][$field_id][$i];
                                    }
                                    $inactive_fields[$field_id]['options'] = $options;
                                }
                            }
                        }
                    }

                    $existing_search_forms[$current_id] = array(
                        'active_fields' => $active_fields,
                        'inactive_fields' => $inactive_fields,
                    );

                    $current_settings['search_forms'] = $existing_search_forms;

                    update_option( 'propertyhive_template_assistant', $current_settings );

                    break; 
                }
				default: { die("Unknown setting section"); }
			}
		}
		else
		{
			$search_results_fields = array();
            if ( isset($_POST['search_result_fields']) && is_array($_POST['search_result_fields']) )
            {
                $search_results_fields = $_POST['search_result_fields'];

                $new_search_results_fields = array();
                foreach ( $search_results_fields as $search_results_field )
                {
                    if ( $search_results_field == 'custom_field' )
                    {  
                        if ( isset($_POST['search_result_fields_custom_field']) && $_POST['search_result_fields_custom_field'] != '' )
                        {
                            $new_search_results_fields[] = ph_clean($_POST['search_result_fields_custom_field']);
                        }
                    }
                    else
                    {
                        $new_search_results_fields[] = $search_results_field;
                    }
                }

                $search_results_fields = $new_search_results_fields;
            }

            $propertyhive_template_assistant = array(
                'search_result_default_order' => ph_clean($_POST['search_result_default_order']),
                'search_result_columns' => (int)$_POST['search_result_columns'],
                'search_result_layout' => (int)$_POST['search_result_layout'],
                'search_result_fields' => $search_results_fields,
                'search_result_image_size' => ( isset($_POST['search_result_image_size']) ? ph_clean($_POST['search_result_image_size']) : 'medium' ),
                'search_result_css' => wp_unslash($_POST['search_result_css']),
                'search_result_css_all_pages' => isset($_POST['search_result_css_all_pages']) ? 'yes' : '',
            );

            $propertyhive_template_assistant = array_merge($current_settings, $propertyhive_template_assistant);

            update_option( 'propertyhive_template_assistant', $propertyhive_template_assistant );
		}
	}
}

endif;

return new PH_Settings_Frontend();
