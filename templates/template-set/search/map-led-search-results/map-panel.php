<?php
/**
 * Decorative Map Atlas fallback surface.
 *
 * Override in yourtheme/propertyhive/template-set/search/map-led-search-results/map-panel.php.
 *
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$default_area_labels = array(
	__( 'Local area', 'propertyhive' ),
	__( 'Nearby homes', 'propertyhive' ),
	__( 'Neighbourhood', 'propertyhive' ),
);
?>
<section class="ph-template-map-panel" aria-label="<?php esc_attr_e( 'Property map preview', 'propertyhive' ); ?>">
	<span class="ph-template-map-road ph-template-map-road-one" aria-hidden="true"></span>
	<span class="ph-template-map-road ph-template-map-road-two" aria-hidden="true"></span>
	<span class="ph-template-map-road ph-template-map-road-three" aria-hidden="true"></span>
	<span class="ph-template-map-water" aria-hidden="true"></span>
	<?php foreach ( array( 'one', 'two', 'three' ) as $index => $position ) : ?>
		<span class="ph-template-map-label ph-template-map-label-<?php echo esc_attr( $position ); ?>" aria-hidden="true"><?php echo esc_html( ! empty( $area_labels[ $index ] ) ? $area_labels[ $index ] : $default_area_labels[ $index ] ); ?></span>
	<?php endforeach; ?>
	<?php foreach ( $properties as $property ) : ?>
		<button class="ph-template-map-pin" type="button" data-pin="<?php echo esc_attr( $property['id'] ); ?>" style="--ph-map-x: <?php echo esc_attr( $property['position'][0] ); ?>%; --ph-map-y: <?php echo esc_attr( $property['position'][1] ); ?>%;" aria-label="<?php echo esc_attr( sprintf( __( 'Show %s', 'propertyhive' ), $property['title'] ) ); ?>">
			<span><?php echo esc_html( $property['price'] ); ?></span>
		</button>
	<?php endforeach; ?>
	<p class="ph-template-map-preview-label"><?php esc_html_e( 'Map preview', 'propertyhive' ); ?></p>
	<div class="ph-template-map-zoom" aria-hidden="true">
		<span>+</span>
		<span>−</span>
	</div>
</section>
