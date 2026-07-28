<?php
/**
 * Portal Grid search introduction.
 *
 * Override in yourtheme/propertyhive/template-set/search/portal-grid-search-results/intro.php.
 *
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="ph-template-search-intro ph-template-search-intro-portal-grid-search-results">
	<span class="ph-template-search-kicker"><?php echo esc_html( $content['kicker'] ); ?></span>
	<div class="ph-template-search-intro-copy">
		<h1><?php echo esc_html( $content['title'] ); ?> <span><?php esc_html_e( 'your area', 'propertyhive' ); ?></span></h1>
		<p><?php echo esc_html( $content['body'] ); ?></p>
	</div>
</section>
