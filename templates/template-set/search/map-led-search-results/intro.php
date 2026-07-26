<?php
/**
 * Map Atlas search introduction.
 *
 * Override in yourtheme/propertyhive/template-set/search/map-led-search-results/intro.php.
 *
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="ph-template-search-intro ph-template-search-intro-map-led-search-results">
	<span class="ph-template-search-kicker"><?php echo esc_html( $content['kicker'] ); ?></span>
	<div class="ph-template-search-intro-copy">
		<h1><?php echo esc_html( number_format_i18n( absint( $total ) ) ); ?> <?php echo esc_html( $content['title'] ); ?></h1>
	</div>
</section>
