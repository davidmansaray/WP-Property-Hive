<?php
/**
 * Portal Grid search-card footer.
 *
 * Override in yourtheme/propertyhive/template-set/search/portal-grid-search-results/card-footer.php.
 *
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="ph-template-card-footer">
	<?php if ( ! empty( $facts ) ) : ?>
		<ul class="ph-template-facts">
			<?php foreach ( array_slice( $facts, 0, 3 ) as $fact ) : ?>
				<li><?php if ( ! empty( $fact['quantity'] ) ) : ?><?php echo esc_html( $fact['value'] ); ?> <span><?php echo esc_html( $fact['label'] ); ?></span><?php else : ?><span><?php echo esc_html( $fact['label'] ); ?></span> <?php echo esc_html( $fact['value'] ); ?><?php endif; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<div class="ph-template-card-meta">
		<span><?php echo esc_html( get_the_date() ); ?></span>
		<a href="<?php echo esc_url( get_permalink( $property->id ) ); ?>">
			<?php esc_html_e( 'View home', 'propertyhive' ); ?> <span aria-hidden="true">→</span>
		</a>
	</div>
	<?php if ( $show_branch && ( $office || $phone ) ) : ?>
		<div class="ph-template-card-branch">
			<?php if ( $office ) : ?><span><?php echo esc_html( $office ); ?></span><?php endif; ?>
			<?php if ( $phone ) : ?><a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
		</div>
	<?php endif; ?>
	<?php if ( ! empty( $shortlist_button ) ) : ?>
		<div class="ph-template-card-actions"><?php echo wp_kses_post( $shortlist_button ); ?></div>
	<?php endif; ?>
</div>
