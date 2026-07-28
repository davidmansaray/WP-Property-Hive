<?php
/**
 * Property search form
 *
 * @author      PropertyHive
 * @package     PropertyHive/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<?php do_action( 'propertyhive_before_search_form', $id ); ?>

<form name="ph_property_search" class="property-search-form property-search-form-<?php echo esc_attr($id); ?> clear" action="<?php echo apply_filters( 'propertyhive_search_form_action', get_post_type_archive_link( 'property' ) ); ?>" method="get" role="form">

    <?php do_action( 'propertyhive_before_search_form_controls', $id, $form_controls ); ?>

    <?php
    $is_prototype_search = class_exists( 'PH_Template_Set_Request_Context' )
        && PH_Template_Set_Request_Context::is_search_results_request()
        && in_array( PH_Template_Set_Request_Context::get_search_template(), array( 'portal-grid-search-results', 'map-led-search-results' ), true );
    $primary_controls = array( 'address_keyword', 'maximum_price', 'minimum_bedrooms', 'property_type' );

    if ( $is_prototype_search ) :
        foreach ( $form_controls as $key => $field ) :
            if ( in_array( $key, $primary_controls, true ) ) {
                ph_form_field( $key, $field );
            }
        endforeach;
        ?>
        <details class="ph-template-search-advanced-filters">
            <summary>
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false">
                    <path d="M2 4.5h14M2 9h14M2 13.5h14"></path>
                    <circle cx="6" cy="4.5" r="1"></circle>
                    <circle cx="12" cy="9" r="1"></circle>
                    <circle cx="8" cy="13.5" r="1"></circle>
                </svg>
                <?php esc_html_e( 'More filters', 'propertyhive' ); ?>
            </summary>
            <div class="ph-template-search-advanced-filters-panel">
                <?php foreach ( $form_controls as $key => $field ) : ?>
                    <?php if ( ! in_array( $key, $primary_controls, true ) ) : ?>
                        <?php ph_form_field( $key, $field ); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </details>
    <?php else : ?>
        <?php foreach ( $form_controls as $key => $field ) : ?>
            <?php ph_form_field( $key, $field ); ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php do_action( 'propertyhive_after_search_form_controls', $id, $form_controls ); ?>

    <input type="submit" value="<?php echo esc_attr__( 'Search', 'propertyhive' ); ?>">

    <?php do_action( 'propertyhive_after_search_form_submit', $id, $form_controls ); ?>

</form>

<?php do_action( 'propertyhive_after_search_form', $id ); ?>
