<?php
/**
 * Property Portal card attribution.
 *
 * Override in yourtheme/propertyhive/template-set/search/card-attribution.php.
 *
 * Available variables: $property, $attribution.
 *
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attribution = isset( $attribution ) && is_array( $attribution ) ? $attribution : array();

if ( ! empty( $attribution ) ) :
	?>
	<div class="ph-template-card-attribution">
		<?php if ( ! empty( $attribution['logo_id'] ) ) : ?>
			<?php echo wp_get_attachment_image( absint( $attribution['logo_id'] ), 'thumbnail', false, array( 'class' => 'ph-template-card-attribution-logo' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress image helper returns escaped image markup. ?>
		<?php endif; ?>
		<?php if ( ! empty( $attribution['agent_name'] ) ) : ?>
			<span class="ph-template-card-attribution-agent"><?php echo esc_html( $attribution['agent_name'] ); ?></span>
		<?php endif; ?>
		<?php if ( ! empty( $attribution['branch_name'] ) ) : ?>
			<span class="ph-template-card-attribution-branch"><?php echo esc_html( $attribution['branch_name'] ); ?></span>
		<?php endif; ?>
	</div>
	<?php
endif;

do_action( 'propertyhive_template_set_card_meta', $property );
