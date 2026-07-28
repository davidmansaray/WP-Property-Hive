<?php
/**
 * Template Set detail add-on actions.
 *
 * Override this template by copying it to yourtheme/propertyhive/template-set/detail/actions.php
 *
 * Available variables: $actions, $start, $list_start, $list_end, $end, $extracted_modals, $filter_output, $list_outside_markup.
 *
 * @author  PropertyHive
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$has_list_content = ! empty( $actions ) || '' !== trim( $list_start . $list_end );
?>
<?php echo $start; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- captured classic hook output. ?>
<?php if ( $has_list_content ) : ?>
	<ul class="ph-template-addon-actions">
		<?php echo $list_start; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- captured classic hook output. ?>
		<?php foreach ( $actions as $action ) : ?>
			<?php
			if ( ! is_array( $action ) ) {
				continue;
			}

			$action_class      = isset( $action['class'] ) ? (string) $action['class'] : '';
			$href              = isset( $action['href'] ) ? (string) $action['href'] : '';
			$label             = isset( $action['label'] ) ? (string) $action['label'] : '';
			$parent_attributes = ! empty( $action['parent_attributes'] ) && is_array( $action['parent_attributes'] ) ? $action['parent_attributes'] : array();
			$attributes        = ! empty( $action['attributes'] ) && is_array( $action['attributes'] ) ? $action['attributes'] : array();
			?>
			<li class="<?php echo esc_attr( $action_class ); ?>"<?php foreach ( $parent_attributes as $name => $value ) : ?> <?php echo esc_attr( $name ); ?>="<?php echo esc_attr( $value ); ?>"<?php endforeach; ?>>
				<a class="ph-template-button ph-template-button-secondary" href="<?php echo esc_url( $href ); ?>"<?php foreach ( $attributes as $name => $value ) : ?> <?php echo esc_attr( $name ); ?>="<?php echo esc_attr( $value ); ?>"<?php endforeach; ?>><?php echo esc_html( $label ); ?></a>
			</li>
		<?php endforeach; ?>
		<?php echo $list_end; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- captured classic hook output. ?>
	</ul>
<?php endif; ?>
<?php echo $list_outside_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- captured classic hook output. ?>
<?php echo $end; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- captured classic hook output. ?>
<?php echo $extracted_modals; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- extracted add-on lightboxes. ?>
<?php echo $filter_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- deduplicated add-on filter output. ?>
