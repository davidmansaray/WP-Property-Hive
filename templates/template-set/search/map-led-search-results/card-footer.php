<?php
/**
 * Map Atlas search-card facts and optional controls.
 *
 * Override in yourtheme/propertyhive/template-set/search/map-led-search-results/card-footer.php.
 *
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ph-template-card-footer">
	<span hidden data-home="<?php echo esc_attr( $property->id ); ?>"></span>
	<?php if ( ! empty( $facts ) ) : ?>
		<ul class="ph-template-facts">
			<?php foreach ( array_slice( $facts, 0, 2 ) as $fact ) : ?>
				<li><?php if ( ! empty( $fact['quantity'] ) ) : ?><?php echo esc_html( $fact['value'] ); ?> <span><?php echo esc_html( $fact['label'] ); ?></span><?php else : ?><span><?php echo esc_html( $fact['label'] ); ?></span> <?php echo esc_html( $fact['value'] ); ?><?php endif; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( ! empty( $shortlist_button ) ) : ?>
		<div class="ph-template-card-actions">
			<?php echo wp_kses_post( $shortlist_button ); ?>
			<svg class="ph-template-shortlist-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.7-7.5 1.1-1.1a5.5 5.5 0 0 0 0-7.8Z"></path>
			</svg>
		</div>
	<?php endif; ?>
</div>
