<?php
/**
 * Shared Template Set search-card badges.
 *
 * Override in yourtheme/propertyhive/template-set/search/card-badges.php.
 *
 * @package PropertyHive/Templates/TemplateSet
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<span class="ph-template-badges">
	<?php foreach ( $badges as $badge ) : ?>
		<span class="ph-template-badge"><?php echo esc_html( $badge ); ?></span>
	<?php endforeach; ?>
</span>
