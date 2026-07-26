<?php
/**
 * Search results toolbar shared by the prototype-led search templates.
 *
 * Override in yourtheme/propertyhive/template-set/search/results-toolbar.php.
 *
 * Available variables: $template, $total, $ordering_markup, $map_state,
 * $map_toggle_url, $save_search_button, $save_search_popup.
 *
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$map_label = ! empty( $map_state['requested_view'] ) && 'map' === $map_state['requested_view']
	? __( 'List view', 'propertyhive' )
	: __( 'Map view', 'propertyhive' );
?>
<div class="ph-template-results-toolbar">
	<p class="ph-template-results-count" role="status" aria-live="polite" aria-atomic="true">
		<strong><?php echo esc_html( number_format_i18n( absint( $total ) ) ); ?></strong>
		<?php esc_html_e( 'homes match your search', 'propertyhive' ); ?>
	</p>
	<div class="ph-template-results-toolbar-actions">
		<?php if ( '' !== $save_search_button ) : ?>
			<?php echo $save_search_button; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Existing Save Search add-on anchor markup. ?>
		<?php endif; ?>
		<?php if ( '' !== $map_toggle_url ) : ?>
			<a class="ph-template-map-toggle" href="<?php echo esc_url( $map_toggle_url ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3V6Zm6-3v15m6-12v15"></path>
				</svg>
				<?php echo esc_html( $map_label ); ?>
			</a>
		<?php endif; ?>
		<?php if ( '' !== $ordering_markup ) : ?>
			<label class="ph-template-ordering-label">
				<span><?php esc_html_e( 'Sort', 'propertyhive' ); ?></span>
				<?php echo $ordering_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core/theme ordering template markup. ?>
			</label>
		<?php endif; ?>
	</div>
</div>
<?php if ( '' !== $save_search_popup ) : ?>
	<?php echo $save_search_popup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Existing Save Search add-on popup markup. ?>
<?php endif; ?>
