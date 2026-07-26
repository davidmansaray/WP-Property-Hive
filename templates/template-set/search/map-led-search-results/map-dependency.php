<?php
/**
 * Privileged Map Atlas dependency status.
 *
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$reason = isset( $state['fallback_reason'] ) ? $state['fallback_reason'] : 'unavailable';
$messages = array(
	'unavailable'  => __( 'Map Atlas is showing its normal property-list fallback because Map Search is not installed or active.', 'propertyhive' ),
	'unusable'     => __( 'Map Atlas is showing its normal property-list fallback because Map Search is not currently licensed for use.', 'propertyhive' ),
	'unconfigured' => __( 'Map Atlas is ready, but Map Search must use its view switcher or split format before a live map can appear.', 'propertyhive' ),
	'list-state'   => __( 'Map Atlas is showing the list view. Use the Map Search controls to return to the live map.', 'propertyhive' ),
);
?>
<aside class="ph-template-map-dependency" role="status">
	<strong><?php esc_html_e( 'Map Atlas preview', 'propertyhive' ); ?></strong>
	<p><?php echo esc_html( isset( $messages[ $reason ] ) ? $messages[ $reason ] : $messages['unavailable'] ); ?></p>
</aside>
