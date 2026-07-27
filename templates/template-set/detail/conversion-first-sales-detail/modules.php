<?php
/**
 * Portal Split property modules.
 *
 * Override this template by copying it to yourtheme/propertyhive/template-set/detail/conversion-first-sales-detail/modules.php
 *
 * Available variables: $property, $template, $facts, $rooms, $material, $features, $description, $overview, $location_label, $address, $location_map_available, $documents, $office, $has_floorplan, $calculator_price, $costs_shortcodes, $show_purchase_costs.
 *
 * @author  PropertyHive
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<section class="ph-template-modules ph-template-property-information ph-template-property-information-portal-split ph-template-modules-portal" aria-label="<?php esc_attr_e( 'Property information', 'propertyhive' ); ?>">
	<?php if ( ! empty( $features ) ) : ?>
		<article class="ph-template-module ph-template-module-block ph-template-module-features">
			<h2><?php esc_html_e( 'Key features', 'propertyhive' ); ?></h2>
			<ul>
				<?php foreach ( $features as $feature ) : ?>
					<li><?php echo esc_html( $feature ); ?></li>
				<?php endforeach; ?>
			</ul>
		</article>
	<?php endif; ?>

	<?php if ( $overview ) : ?>
		<article class="ph-template-module ph-template-module-block ph-template-module-overview">
			<h2><?php esc_html_e( 'Overview', 'propertyhive' ); ?></h2>
			<p><?php echo esc_html( $overview ); ?></p>
		</article>
	<?php endif; ?>

	<?php if ( ! empty( $rooms ) || $description ) : ?>
		<article class="ph-template-module ph-template-module-block ph-template-module-rooms">
			<h2><?php esc_html_e( 'Full details', 'propertyhive' ); ?></h2>
			<?php if ( ! empty( $rooms ) ) : ?>
				<?php foreach ( $rooms as $room ) : ?>
					<p class="room">
						<?php if ( $room['name'] ) : ?><strong class="name"><?php echo esc_html( $room['name'] ); ?></strong><?php endif; ?>
						<?php if ( $room['dimensions'] ) : ?><span class="dimension"><?php echo esc_html( $room['dimensions'] ); ?></span><?php endif; ?>
						<?php if ( $room['description'] ) : ?><span class="description"><?php echo esc_html( $room['description'] ); ?></span><?php endif; ?>
					</p>
				<?php endforeach; ?>
			<?php else : ?>
				<?php echo wp_kses_post( $description ); ?>
			<?php endif; ?>
		</article>
	<?php endif; ?>

	<?php if ( $show_purchase_costs ) : ?>
		<article class="ph-template-module ph-template-module-block ph-template-module-costs">
			<h2><?php esc_html_e( 'Purchase costs', 'propertyhive' ); ?></h2>
			<div class="ph-template-costs-calculators">
				<?php foreach ( $costs_shortcodes as $costs_shortcode ) : ?>
					<?php
					if ( ! is_string( $costs_shortcode ) || ! shortcode_exists( $costs_shortcode ) ) {
						continue;
					}

					$calculator_price_attribute = '';
					if ( '' !== $calculator_price && ( 0 === strpos( $costs_shortcode, 'stamp_duty_calculator' ) || 'mortgage_calculator' === $costs_shortcode ) ) {
						$calculator_price_attribute = ' price="' . esc_attr( $calculator_price ) . '"';
					}
					?>
					<div class="ph-template-costs-calc ph-template-costs-calc-<?php echo esc_attr( sanitize_html_class( $costs_shortcode ) ); ?>">
						<?php echo do_shortcode( '[' . $costs_shortcode . $calculator_price_attribute . ']' ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</article>
	<?php endif; ?>

	<?php if ( ! empty( $documents ) ) : ?>
		<article class="ph-template-module ph-template-module-block ph-template-module-documents">
			<h2><?php esc_html_e( 'Documents & media', 'propertyhive' ); ?></h2>
			<div class="ph-template-doc-row">
				<?php foreach ( $documents as $document ) : ?>
					<?php $document_class = 'ph-template-doc-pill ph-template-doc-pill-' . sanitize_html_class( $document['type'] ); ?>
					<?php if ( ! empty( $document['url'] ) ) : ?>
						<a class="<?php echo esc_attr( $document_class ); ?>" href="<?php echo esc_url( $document['url'] ); ?>"<?php if ( ! empty( $document['attributes'] ) && is_array( $document['attributes'] ) ) : ?><?php foreach ( $document['attributes'] as $name => $value ) : ?> <?php echo esc_attr( $name ); ?>="<?php echo esc_attr( $value ); ?>"<?php endforeach; ?><?php endif; ?>><?php echo esc_html( $document['label'] ); ?></a>
					<?php else : ?>
						<span class="<?php echo esc_attr( $document_class ); ?>"><?php echo esc_html( $document['label'] ); ?></span>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</article>
	<?php endif; ?>

	<?php if ( ! empty( $material ) ) : ?>
		<article class="ph-template-module ph-template-module-block ph-template-module-material">
			<h2><?php esc_html_e( 'Material information', 'propertyhive' ); ?></h2>
			<dl>
				<?php foreach ( $material as $item ) : ?>
					<div><dt><?php echo esc_html( $item['label'] ); ?></dt><dd><?php echo esc_html( $item['value'] ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
		</article>
	<?php endif; ?>

	<?php if ( $location_label || $address ) : ?>
		<article class="ph-template-module ph-template-module-block ph-template-module-location">
			<h2><?php esc_html_e( 'Location', 'propertyhive' ); ?></h2>
			<div class="ph-template-module-map-surface<?php echo $location_map_available ? ' ph-template-module-map-surface-live' : ''; ?>">
				<?php if ( $location_map_available ) : ?>
					<?php PH_Template_Set::render_detail_location_map( $property ); ?>
				<?php else : ?>
				<span class="ph-template-map-pin" aria-hidden="true"></span>
				<span class="ph-template-map-label"><?php echo esc_html( $location_label ? $location_label : $address ); ?></span>
				<?php endif; ?>
			</div>
			<?php if ( $address ) : ?>
				<p><?php echo esc_html( $address ); ?></p>
			<?php endif; ?>
		</article>
	<?php endif; ?>
</section>
