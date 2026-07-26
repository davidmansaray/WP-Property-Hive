<?php
/**
 * Shared linked search-card price.
 *
 * Override in yourtheme/propertyhive/template-set/search/linked-card-price.php.
 *
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="price">
	<a href="<?php echo esc_url( $permalink ); ?>">
		<?php echo wp_kses_post( $price ); ?>
		<?php if ( '' !== $price_qualifier ) : ?><span class="price-qualifier"><?php echo esc_html( $price_qualifier ); ?></span><?php endif; ?>
	</a>
	<?php if ( '' !== $fees ) : ?>
		<span class="lettings-fees"><a data-fancybox data-src="#propertyhive_lettings_fees_popup" href="javascript:;"><?php esc_html_e( 'Tenancy Info', 'propertyhive' ); ?></a></span>
		<div id="propertyhive_lettings_fees_popup" style="display:none; max-width:500px;"><h3><?php esc_html_e( 'Tenancy Info', 'propertyhive' ); ?></h3><?php echo wp_kses_post( $fees ); ?></div>
	<?php endif; ?>
</div>
