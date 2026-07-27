<?php
/**
 * Template Set property gallery.
 *
 * Override this template by copying it to yourtheme/propertyhive/template-set/detail/gallery.php
 *
 * The classic propertyhive_product_thumbnails action is intentionally replaced
 * by this gallery. Gallery item content can be changed with
 * propertyhive_template_set_gallery_item_html; interactive button wrappers are
 * deliberately not filterable so the gallery JavaScript contract remains intact.
 *
 * @author  PropertyHive
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$is_editorial        = ( 'premium-editorial-detail' === $template );
$use_cinema_controls = ( 'immersive-cinema-detail' === $template ) || ( 'cinema' === $gallery_layout );
$show_floor_panel    = $is_preview && $has_floor_map;
$show_tour_panel     = $is_preview && $has_virtual_tour;
$show_map_panel      = ! $use_cinema_controls && $location;
$floorplan_url       = ( $has_floor_map && ! $is_preview ) ? $floorplan_url : '';
$public_template     = PH_Template_Set_Catalog::get_detail_template_public_slug( $template );

if ( empty( $images ) ) :
	?>
	<div class="images ph-template-gallery ph-template-gallery-empty" data-ph-template-gallery>
		<?php do_action( 'propertyhive_before_single_property_images' ); ?>
		<div class="ph-template-gallery-empty-state" role="status"><?php esc_html_e( 'Property photos are not available.', 'propertyhive' ); ?></div>
		<?php do_action( 'propertyhive_after_single_property_images' ); ?>
	</div>
	<?php
	return;
endif;

$hero  = reset( $images );
$rail  = $use_cinema_controls ? $images : array_slice( $images, 0, 5 );
$count = count( $images );

$hero_content = '<img src="' . esc_url( $hero['src'] ) . '" alt="' . esc_attr( $hero['alt'] ) . '" loading="lazy" data-ph-gallery-hero-image>';
$hero_content .= '<span class="ph-template-gallery-expand-label" aria-hidden="true">' . esc_html__( 'View larger', 'propertyhive' ) . '</span>';
$hero_content = apply_filters( 'propertyhive_template_set_gallery_item_html', $hero_content, absint( $hero['attachment_id'] ), 'hero', $hero );
?>
<div class="images ph-template-gallery ph-template-gallery-<?php echo esc_attr( sanitize_html_class( $template ) ); ?> ph-template-gallery-<?php echo esc_attr( sanitize_html_class( $public_template ) ); ?> ph-gallery-variant-<?php echo esc_attr( sanitize_html_class( $gallery_layout ) ); ?>" data-ph-template-gallery data-ph-gallery-current-variant="<?php echo esc_attr( $gallery_layout ); ?>">
	<?php do_action( 'propertyhive_before_single_property_images' ); ?>
	<figure class="ph-template-gallery-hero">
		<button type="button" class="ph-template-gallery-photo-trigger" data-ph-gallery-open aria-label="<?php echo esc_attr( sprintf( __( 'Open larger photo: %s', 'propertyhive' ), $hero['caption'] ) ); ?>"><?php echo $hero_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered gallery item content. ?></button>

		<?php if ( $show_floor_panel ) : ?>
			<div class="ph-template-gallery-panel ph-template-gallery-panel-floorplan" hidden data-ph-gallery-panel="floorplan" aria-label="<?php esc_attr_e( 'Floor map preview', 'propertyhive' ); ?>">
				<div class="ph-template-floorplan" aria-hidden="true"><span class="ph-template-floorplan-room ph-template-room-reception"><?php esc_html_e( 'Reception', 'propertyhive' ); ?></span><span class="ph-template-floorplan-room ph-template-room-kitchen"><?php esc_html_e( 'Kitchen', 'propertyhive' ); ?></span><span class="ph-template-floorplan-room ph-template-room-bed-one"><?php esc_html_e( 'Bed 1', 'propertyhive' ); ?></span><span class="ph-template-floorplan-room ph-template-room-bed-two"><?php esc_html_e( 'Bed 2', 'propertyhive' ); ?></span><span class="ph-template-floorplan-room ph-template-room-bath"><?php esc_html_e( 'Bath', 'propertyhive' ); ?></span></div>
			</div>
		<?php endif; ?>
		<?php if ( $show_tour_panel ) : ?>
			<div class="ph-template-gallery-panel ph-template-gallery-panel-virtual-tour" hidden data-ph-gallery-panel="virtual-tour" aria-label="<?php esc_attr_e( 'Virtual tour preview', 'propertyhive' ); ?>">
				<div class="ph-template-virtual-tour-preview" aria-hidden="true"><span class="ph-template-virtual-tour-scene ph-template-virtual-tour-scene-living"><?php esc_html_e( 'Living room', 'propertyhive' ); ?></span><span class="ph-template-virtual-tour-scene ph-template-virtual-tour-scene-kitchen"><?php esc_html_e( 'Kitchen', 'propertyhive' ); ?></span><span class="ph-template-virtual-tour-scene ph-template-virtual-tour-scene-bedroom"><?php esc_html_e( 'Bedroom', 'propertyhive' ); ?></span><span class="ph-template-virtual-tour-hotspot ph-template-virtual-tour-hotspot-one"></span><span class="ph-template-virtual-tour-hotspot ph-template-virtual-tour-hotspot-two"></span><span class="ph-template-virtual-tour-label"><?php esc_html_e( '360 virtual tour', 'propertyhive' ); ?></span></div>
			</div>
		<?php endif; ?>
		<?php if ( $show_map_panel ) : ?>
			<div class="ph-template-gallery-panel ph-template-gallery-panel-map" hidden data-ph-gallery-panel="map" aria-label="<?php esc_attr_e( 'Location preview', 'propertyhive' ); ?>"><span class="ph-template-map-pin"></span><span class="ph-template-map-label"><?php echo esc_html( $location ); ?></span></div>
		<?php endif; ?>

		<?php if ( $is_editorial ) : ?>
			<figcaption data-ph-gallery-caption><?php echo esc_html( $hero['caption'] ); ?></figcaption>
		<?php elseif ( $use_cinema_controls ) : ?>
			<div class="ph-template-cinema-controls" role="toolbar" aria-label="<?php esc_attr_e( 'Gallery controls', 'propertyhive' ); ?>">
				<button type="button" data-ph-gallery-prev aria-label="<?php esc_attr_e( 'Previous photo', 'propertyhive' ); ?>"><span aria-hidden="true">&#9664;</span></button><button type="button" data-ph-gallery-next aria-label="<?php esc_attr_e( 'Next photo', 'propertyhive' ); ?>"><span aria-hidden="true">&#9654;</span></button><span class="ph-template-cinema-counter" data-ph-gallery-counter aria-live="polite"><?php echo esc_html( '1 / ' . (int) $count ); ?></span>
				<?php if ( $has_floor_map && $show_floor_panel ) : ?><button type="button" class="ph-template-cinema-control-floorplan" data-ph-gallery-tab="floorplan" aria-selected="false"><?php esc_html_e( 'Floorplan', 'propertyhive' ); ?></button><?php elseif ( $has_floor_map && $floorplan_url ) : ?><a class="ph-template-cinema-control-link ph-template-cinema-control-floorplan" href="<?php echo esc_url( $floorplan_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Floorplan', 'propertyhive' ); ?></a><?php endif; ?>
				<?php if ( $has_virtual_tour && ! empty( $virtual_tours ) ) : foreach ( $virtual_tours as $index => $tour ) : $tour_label = ! empty( $tour['label'] ) ? trim( wp_strip_all_tags( $tour['label'] ) ) : sprintf( __( 'Virtual tour %d', 'propertyhive' ), (int) $index + 1 ); $tour_url = ( ! $is_preview && ! empty( $tour['url'] ) ) ? esc_url_raw( $tour['url'] ) : ''; ?><?php if ( $tour_url ) : ?><a class="ph-template-cinema-control-link ph-template-cinema-control-virtual-tour" href="<?php echo esc_url( $tour_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $tour_label ); ?></a><?php elseif ( $show_tour_panel ) : ?><button type="button" class="ph-template-cinema-control-virtual-tour" data-ph-gallery-tab="virtual-tour" aria-selected="false"><?php echo esc_html( $tour_label ); ?></button><?php endif; ?><?php endforeach; elseif ( $has_virtual_tour && $show_tour_panel ) : ?><button type="button" class="ph-template-cinema-control-virtual-tour" data-ph-gallery-tab="virtual-tour" aria-selected="false"><?php esc_html_e( 'Virtual tour', 'propertyhive' ); ?></button><?php endif; ?>
			</div>
		<?php else : ?>
			<span class="ph-template-gallery-count"><span class="ph-template-gallery-count-icon" aria-hidden="true"></span><?php echo esc_html( sprintf( _n( '%d photo', '%d photos', $count, 'propertyhive' ), (int) $count ) ); ?></span>
			<div class="ph-template-gallery-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Gallery views', 'propertyhive' ); ?>"><button type="button" class="is-active" data-ph-gallery-tab="photos" aria-selected="true"><?php esc_html_e( 'Photos', 'propertyhive' ); ?></button><?php if ( $show_floor_panel ) : ?><button type="button" data-ph-gallery-tab="floorplan" aria-selected="false"><?php esc_html_e( 'Floor map', 'propertyhive' ); ?></button><?php endif; ?><?php if ( $show_tour_panel ) : ?><button type="button" data-ph-gallery-tab="virtual-tour" aria-selected="false"><?php esc_html_e( 'Virtual tour', 'propertyhive' ); ?></button><?php endif; ?><?php if ( $show_map_panel ) : ?><button type="button" data-ph-gallery-tab="map" aria-selected="false"><?php esc_html_e( 'Location', 'propertyhive' ); ?></button><?php endif; ?></div>
		<?php endif; ?>

		<?php if ( 'immersive-cinema-detail' === $template ) : ?><?php PH_Template_Set::render_detail_contact_panel(); ?><?php endif; ?>
	</figure>

	<?php if ( ! empty( $rail ) ) : ?>
		<div class="ph-template-gallery-rail">
			<?php foreach ( $rail as $index => $image ) : $is_active = ( 0 === $index ); $item_content = '<img src="' . esc_url( $image['thumb'] ) . '" alt="' . esc_attr( $image['alt'] ) . '" loading="lazy">' . ( $is_editorial ? '<span>' . esc_html( $image['caption'] ) . '</span>' : '' ); $item_content = apply_filters( 'propertyhive_template_set_gallery_item_html', $item_content, absint( $image['attachment_id'] ), 'rail', $image ); ?>
				<button type="button" class="ph-template-gallery-thumb<?php echo $is_active ? ' is-active' : ''; ?>" data-ph-gallery-thumb data-src="<?php echo esc_url( $image['src'] ); ?>" data-alt="<?php echo esc_attr( $image['alt'] ); ?>" data-caption="<?php echo esc_attr( $image['caption'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Show %s', 'propertyhive' ), $image['caption'] ) ); ?>"<?php echo $is_active ? ' aria-current="true"' : ''; ?>><?php echo $item_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Filtered gallery item content. ?></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<?php do_action( 'propertyhive_after_single_property_images' ); ?>
</div>
