<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Shared template set property data helpers.
 */
trait PH_Template_Set_Property_Data {

	/**
	 * Should card extras render?
	 *
	 * @return bool
	 */
	private static function should_render_card_extras() {
		return self::$rendering_module || self::is_search_card_rendering();
	}

	/**
	 * Get the first character from a string.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function get_string_initial( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		return self::get_string_slice( $value, 0, 1 );
	}

	/**
	 * Slice strings with mbstring support when available.
	 *
	 * @param string   $value Value.
	 * @param int      $start Start offset.
	 * @param int|null $length Length.
	 * @return string
	 */
	private static function get_string_slice( $value, $start, $length = null ) {
		if ( function_exists( 'mb_substr' ) ) {
			return null === $length ? mb_substr( $value, $start ) : mb_substr( $value, $start, $length );
		}

		return null === $length ? substr( $value, $start ) : substr( $value, $start, $length );
	}

	/**
	 * Get photo count.
	 *
	 * @param PH_Property $property Property object.
	 * @return int
	 */
	private static function get_photo_count( $property ) {
		if ( get_option( 'propertyhive_images_stored_as', '' ) === 'urls' ) {
			$photos = $property->_photo_urls;
			return is_array( $photos ) ? count( array_filter( $photos ) ) : 0;
		}

		return count( $property->get_gallery_attachment_ids() );
	}

	/**
	 * Build reusable property fact items.
	 *
	 * @param PH_Property $property Property object.
	 * @param int         $limit Maximum facts.
	 * @return array
	 */
	private static function get_fact_items( $property, $limit = 5 ) {
		$facts = array();

		if ( $property->bedrooms > 0 ) {
			$facts[] = array(
				'label' => _n( 'Bed', 'Beds', absint( $property->bedrooms ), 'propertyhive' ),
				'value' => $property->bedrooms,
				'quantity' => true,
			);
		}

		if ( $property->bathrooms > 0 ) {
			$facts[] = array(
				'label' => _n( 'Bath', 'Baths', absint( $property->bathrooms ), 'propertyhive' ),
				'value' => $property->bathrooms,
				'quantity' => true,
			);
		}

		if ( $property->reception_rooms > 0 ) {
			$facts[] = array(
				'label' => _n( 'Reception', 'Receptions', absint( $property->reception_rooms ), 'propertyhive' ),
				'value' => $property->reception_rooms,
				'quantity' => true,
			);
		}

		if ( $property->property_type ) {
			$facts[] = array(
				'label' => __( 'Type', 'propertyhive' ),
				'value' => $property->property_type,
			);
		}

		if ( $property->tenure ) {
			$facts[] = array(
				'label' => __( 'Tenure', 'propertyhive' ),
				'value' => $property->tenure,
			);
		}

		if ( $property->furnished ) {
			$facts[] = array(
				'label' => __( 'Furnished', 'propertyhive' ),
				'value' => $property->furnished,
			);
		}

		if ( $property->available_date ) {
			$facts[] = array(
				'label' => __( 'Available', 'propertyhive' ),
				'value' => $property->get_available_date(),
			);
		}

		if ( $property->floor_area ) {
			$facts[] = array(
				'label' => __( 'Floor area', 'propertyhive' ),
				'value' => $property->get_formatted_floor_area(),
			);
		}

		$facts = apply_filters( 'propertyhive_template_set_search_facts', array_filter( $facts ), $property, self::get_search_template(), $limit );

		return array_slice( is_array( $facts ) ? array_filter( $facts ) : array(), 0, absint( $limit ) );
	}
}
