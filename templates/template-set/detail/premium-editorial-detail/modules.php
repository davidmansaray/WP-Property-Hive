<?php
/**
 * Private Office property modules.
 *
 * Override this template by copying it to yourtheme/propertyhive/template-set/detail/premium-editorial-detail/modules.php
 *
 * Available variables: $property, $template, $facts, $rooms, $material, $features, $description, $overview, $location_label, $address, $location_map_available, $documents, $office, $has_floorplan, $duet_images, $calculator_price, $costs_shortcodes, $show_purchase_costs.
 *
 * @author  PropertyHive
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<section class="ph-template-modules ph-template-property-information ph-template-property-information-private-office ph-template-modules-editorial" aria-label="<?php esc_attr_e( 'Property information', 'propertyhive' ); ?>">
	<?php if ( $overview || $description || $rooms ) : ?>
		<section class="ph-template-module ph-template-editorial-chapter">
			<span><?php esc_html_e( 'The property', 'propertyhive' ); ?></span>
			<h2><?php esc_html_e( 'Rooms that keep their own company', 'propertyhive' ); ?></h2>
			<?php if ( $overview ) : ?>
				<p class="lede"><?php echo esc_html( $overview ); ?></p>
			<?php elseif ( $description ) : ?>
				<?php echo wp_kses_post( $description ); ?>
			<?php endif; ?>
		</section>
	<?php endif; ?>
	<?php if ( $rooms ) : ?>
		<section class="ph-template-module ph-template-editorial-rooms" aria-label="<?php esc_attr_e( 'Room by room', 'propertyhive' ); ?>">
			<?php foreach ( $rooms as $room ) : ?>
				<p class="room">
					<?php if ( $room['name'] ) : ?><strong class="name"><?php echo esc_html( $room['name'] ); ?></strong><?php endif; ?>
					<?php if ( $room['dimensions'] ) : ?><span class="dimension"><?php echo esc_html( $room['dimensions'] ); ?></span><?php endif; ?>
					<?php if ( $room['description'] ) : ?><span class="description"><?php echo esc_html( $room['description'] ); ?></span><?php endif; ?>
				</p>
			<?php endforeach; ?>
		</section>
	<?php endif; ?>
	<?php if ( ! empty( $duet_images ) ) : ?>
		<div class="ph-template-editorial-duet">
			<?php foreach ( $duet_images as $duet_image ) : ?>
				<img src="<?php echo esc_url( $duet_image['src'] ); ?>" alt="<?php echo esc_attr( $duet_image['alt'] ); ?>" loading="lazy">
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<?php if ( $facts || $features ) : ?>
		<section class="ph-template-module ph-template-editorial-particulars">
			<span><?php esc_html_e( 'Particulars', 'propertyhive' ); ?></span>
			<?php if ( $facts ) : ?>
				<dl>
					<?php foreach ( $facts as $fact ) : ?>
						<div><dt><?php echo esc_html( $fact['label'] ); ?></dt><i aria-hidden="true"></i><dd><?php echo esc_html( $fact['value'] ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<?php if ( $features ) : ?>
				<ul>
					<?php foreach ( $features as $feature ) : ?>
						<li><?php echo esc_html( $feature ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	<?php endif; ?>
	<?php if ( $material ) : ?>
		<section class="ph-template-module ph-template-editorial-material">
			<span><?php esc_html_e( 'Material information', 'propertyhive' ); ?></span>
			<dl>
				<?php foreach ( $material as $item ) : ?>
					<div><dt><?php echo esc_html( $item['label'] ); ?></dt><dd><?php echo esc_html( $item['value'] ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
		</section>
	<?php endif; ?>
	<?php if ( $show_purchase_costs ) : ?>
		<section class="ph-template-module ph-template-module-costs ph-template-editorial-costs">
			<span><?php esc_html_e( 'Purchase costs', 'propertyhive' ); ?></span>
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
		</section>
	<?php endif; ?>
	<?php if ( $documents || $location_label || $address ) : ?>
		<section class="ph-template-module ph-template-editorial-appendix">
			<span><?php esc_html_e( 'Appendix', 'propertyhive' ); ?></span>
			<?php if ( $documents ) : ?>
				<div class="ph-template-doc-row">
					<?php foreach ( $documents as $document ) : ?>
						<?php
						$doc_type  = ! empty( $document['type'] ) ? sanitize_html_class( $document['type'] ) : '';
						$doc_class = 'ph-template-doc-pill' . ( $doc_type ? ' ph-template-doc-pill-' . $doc_type : '' );
						?>
						<?php if ( ! empty( $document['url'] ) ) : ?>
							<a class="<?php echo esc_attr( $doc_class ); ?>" href="<?php echo esc_url( $document['url'] ); ?>"<?php if ( ! empty( $document['attributes'] ) && is_array( $document['attributes'] ) ) : ?><?php foreach ( $document['attributes'] as $name => $value ) : ?> <?php echo esc_attr( $name ); ?>="<?php echo esc_attr( $value ); ?>"<?php endforeach; ?><?php endif; ?>><?php echo esc_html( $document['label'] ); ?></a>
						<?php else : ?>
							<span class="<?php echo esc_attr( $doc_class ); ?>"><?php echo esc_html( $document['label'] ); ?></span>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( $location_label || $address ) : ?>
				<div class="ph-template-module-map-surface<?php echo $location_map_available ? ' ph-template-module-map-surface-live' : ''; ?>">
					<?php if ( $location_map_available ) : ?>
						<?php PH_Template_Set::render_detail_location_map( $property ); ?>
					<?php else : ?>
					<span class="ph-template-map-pin" aria-hidden="true"></span>
					<span class="ph-template-map-label"><?php echo esc_html( sprintf( __( '%s — precise location shared on enquiry', 'propertyhive' ), $location_label ? $location_label : $address ) ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</section>
