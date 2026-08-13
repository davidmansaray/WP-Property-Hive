<?php
/**
 * Template Set Property Detail Modules.
 *
 * Override this template by copying it to yourtheme/propertyhive/template-set/detail/standard-sales-detail/modules.php
 *
 * Available variables: $property, $template, $facts, $location_label, $address, $location_map_available, $documents, $office, $has_floorplan.
 *
 * @author  PropertyHive
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<section class="ph-template-modules" aria-label="<?php esc_attr_e( 'Property information', 'propertyhive' ); ?>">
	<?php if ( ! empty( $facts ) ) : ?>
		<article class="ph-template-module-card ph-template-module-facts">
			<h4><?php esc_html_e( 'At a glance', 'propertyhive' ); ?></h4>
			<ul class="ph-template-area-list">
				<?php foreach ( $facts as $fact ) : ?>
					<li><span><?php echo esc_html( $fact ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		</article>
	<?php endif; ?>
	<?php if ( $has_floorplan ) : ?>
		<article class="ph-template-module-card ph-template-module-floorplan">
			<h4><?php esc_html_e( 'Floorplan', 'propertyhive' ); ?></h4>
			<p class="ph-template-module-foot"><?php esc_html_e( 'Floorplan available for this property.', 'propertyhive' ); ?></p>
		</article>
	<?php endif; ?>
	<?php if ( $location_label || $address ) : ?>
		<article class="ph-template-module-card ph-template-module-map">
			<h4><?php esc_html_e( 'Location', 'propertyhive' ); ?></h4>
			<?php if ( $location_map_available ) : ?>
				<div class="ph-template-module-map-surface ph-template-module-map-surface-live"><?php PH_Template_Set::render_detail_location_map( $property ); ?></div>
			<?php elseif ( $location_label || $address ) : ?>
				<div class="ph-template-module-map-surface" aria-hidden="true"><span class="ph-template-map-pin"></span><span class="ph-template-map-label"><?php echo esc_html( $location_label ? $location_label : $address ); ?></span></div>
			<?php endif; ?>
			<?php if ( $address ) : ?>
				<p class="ph-template-module-foot"><?php echo esc_html( $address ); ?></p>
			<?php endif; ?>
		</article>
	<?php endif; ?>
	<?php if ( ! empty( $documents ) ) : ?>
		<article class="ph-template-module-card ph-template-module-documents">
			<h4><?php esc_html_e( 'Documents and viewing', 'propertyhive' ); ?></h4>
			<div class="ph-template-doc-row">
				<?php foreach ( $documents as $document ) : ?>
					<?php $document_class = 'ph-template-doc-pill ph-template-doc-pill-' . sanitize_html_class( $document['type'] ); ?>
					<?php if ( ! empty( $document['url'] ) ) : ?>
						<a class="<?php echo esc_attr( $document_class ); ?>" href="<?php echo esc_url( $document['url'] ); ?>" target="_blank" rel="noopener noreferrer"<?php if ( ! empty( $document['attributes'] ) && is_array( $document['attributes'] ) ) : ?><?php foreach ( $document['attributes'] as $name => $value ) : ?> <?php echo esc_attr( $name ); ?>="<?php echo esc_attr( $value ); ?>"<?php endforeach; ?><?php endif; ?>><span class="ph-template-doc-icon" aria-hidden="true"></span><?php echo esc_html( $document['label'] ); ?></a>
					<?php else : ?>
						<span class="<?php echo esc_attr( $document_class ); ?>"><span class="ph-template-doc-icon" aria-hidden="true"></span><?php echo esc_html( $document['label'] ); ?></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<?php if ( $office ) : ?>
				<p class="ph-template-module-foot"><?php
					echo esc_html( sprintf(
						/* translators: %s: office name */
						__( 'Available from %s.', 'propertyhive' ),
						$office
					) );
				?></p>
			<?php endif; ?>
		</article>
	<?php endif; ?>
</section>
