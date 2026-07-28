<?php
/**
 * Private Office contact letter.
 *
 * Override this template by copying it to yourtheme/propertyhive/template-set/detail/premium-editorial-detail/contact-panel.php
 *
 * Available variables: $property, $post_id, $template, $button, $hint, $is_demo, $phone, $email, $office, $office_alt, $address, $agent, $agent_role, $agent_initials, $portrait, $media_links, $shortlist_button, $has_brochure, $brochure_url.
 *
 * @author  PropertyHive
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<section class="ph-template-detail-contact-card ph-template-contact-panel ph-template-contact-panel-private-office ph-template-editorial-letter" aria-label="<?php esc_attr_e( 'Private viewing', 'propertyhive' ); ?>">
	<div>
		<span><?php esc_html_e( 'By appointment', 'propertyhive' ); ?></span>
		<h2><?php esc_html_e( 'Viewings are private, and unhurried', 'propertyhive' ); ?></h2>
		<?php // Letter shows a portrait only when a real negotiator photo exists — an initials circle reads like a label here. ?>
		<?php if ( $portrait ) : ?>
			<span class="ph-template-contact-portrait ph-template-contact-portrait-image"><?php echo wp_kses_post( $portrait ); ?></span>
		<?php endif; ?>
		<?php if ( $hint ) : ?>
			<p><?php echo esc_html( $hint ); ?></p>
		<?php endif; ?>
		<?php if ( $agent ) : ?>
			<p class="ph-template-letter-signature"><?php echo esc_html( $agent ); ?></p>
		<?php endif; ?>
		<?php if ( $agent_role ) : ?>
			<small><?php echo esc_html( $agent_role ); ?></small>
		<?php endif; ?>
		<div class="ph-template-contact-actions">
			<button type="button" class="ph-template-button ph-template-button-primary" data-fancybox data-src="#makeEnquiry<?php echo absint( $post_id ); ?>" aria-haspopup="dialog" aria-controls="makeEnquiry<?php echo absint( $post_id ); ?>"><?php echo esc_html( $button ); ?></button>
			<?php if ( $brochure_url ) : ?>
				<a class="ph-template-button ph-template-button-primary" href="<?php echo esc_url( $brochure_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Download the brochure', 'propertyhive' ); ?></a>
			<?php else : ?>
				<button type="button" class="ph-template-button ph-template-button-primary" data-fancybox data-src="#makeEnquiry<?php echo absint( $post_id ); ?>" aria-haspopup="dialog" aria-controls="makeEnquiry<?php echo absint( $post_id ); ?>"><?php esc_html_e( 'Request the brochure', 'propertyhive' ); ?></button>
			<?php endif; ?>
		</div>
		<p class="ph-template-letter-quiet">
			<?php esc_html_e( 'Brochure:', 'propertyhive' ); ?>
			<?php if ( $brochure_url ) : ?>
				<a href="<?php echo esc_url( $brochure_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Available', 'propertyhive' ); ?></a>
			<?php else : ?>
				<?php esc_html_e( 'Ask agent', 'propertyhive' ); ?>
			<?php endif; ?>
		</p>
		<?php if ( $phone ) : ?>
			<p class="ph-template-letter-quiet">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s: linked agent telephone number */
						__( 'Or telephone %s', 'propertyhive' ),
						'<a href="' . esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>'
					)
				);
				?>
			</p>
		<?php endif; ?>
	</div>
</section>
