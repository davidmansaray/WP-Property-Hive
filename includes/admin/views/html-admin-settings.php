<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Buffer the tab content first: tabs may set $hide_save_button / button text
// globals while rendering, and the header band (rendered above the content)
// needs to know about them. Sections are buffered separately so the sub-nav
// can sit between the tab bar and the page header, as per the design.
ob_start();
do_action( 'propertyhive_sections_' . $current_tab );
$ph_sections_html = ob_get_clean();

ob_start();
do_action( 'propertyhive_settings_' . $current_tab );
do_action( 'propertyhive_settings_tabs_' . $current_tab ); // @deprecated hook
$ph_settings_content = ob_get_clean();

$ph_tab_meta     = PH_Admin_Settings::get_tab_meta();
$ph_current_meta = isset( $ph_tab_meta[ $current_tab ] ) ? $ph_tab_meta[ $current_tab ] : array();
$ph_page_title   = isset( $tabs[ $current_tab ] ) ? $tabs[ $current_tab ] : '';
$ph_page_desc    = isset( $ph_current_meta['description'] ) ? $ph_current_meta['description'] : ( isset( $ph_current_meta['subtitle'] ) ? $ph_current_meta['subtitle'] : '' );
$ph_show_save    = ! isset( $GLOBALS['hide_save_button'] );
$ph_button_text  = __( 'Save changes', 'propertyhive' );
$ph_default_button_text = $ph_button_text;

if ( isset( $GLOBALS['save_button_text'] ) && ! empty( $GLOBALS['save_button_text'] ) )
{
	$ph_button_text = $GLOBALS['save_button_text'];
}

$ph_always_show_save = $ph_button_text !== $ph_default_button_text;
?>

<div class="wrap propertyhive ph-settings-redesign">
	<form method="post" id="mainform" action="" enctype="multipart/form-data">
		<div class="icon32 icon32-propertyhive-settings" id="icon-propertyhive"><br /></div>
		<?php
			// Split tabs into core (known meta) and add-on (registered dynamically).
			$ph_core_tabs  = array();
			$ph_addon_tabs = array();
			foreach ( $tabs as $name => $label )
			{
				if ( isset( $ph_tab_meta[ $name ] ) )
				{
					$ph_core_tabs[ $name ] = $label;
				}
				else
				{
					$ph_addon_tabs[ $name ] = $label;
				}
			}

			$ph_active_is_addon = isset( $ph_addon_tabs[ $current_tab ] );

			// Render one tab link. $context: 'row' (nav row) or 'menu' (dropdown item).
			$ph_render_tab = function( $name, $label, $context = 'row' ) use ( $ph_tab_meta, $current_tab )
			{
				$ph_meta     = isset( $ph_tab_meta[ $name ] ) ? $ph_tab_meta[ $name ] : array();
				$ph_icon     = PH_Admin_Settings::get_tab_icon_svg( isset( $ph_meta['icon'] ) ? $ph_meta['icon'] : 'default' );
				$ph_subtitle = isset( $ph_meta['subtitle'] ) ? $ph_meta['subtitle'] : '';
				$ph_is_core  = isset( $ph_tab_meta[ $name ] );

				$classes = 'menu' === $context
					? 'ph-nav-menu-item' . ( $current_tab == $name ? ' is-active' : '' )
					: 'nav-tab ph-nav-tab ' . ( $ph_is_core ? 'ph-nav-core' : 'ph-nav-addon' ) . ' nav-tab-' . sanitize_title( $name ) . ( $current_tab == $name ? ' nav-tab-active' : '' );

				echo '<a href="' . esc_url( admin_url( 'admin.php?page=ph-settings&tab=' . $name ) ) . '" class="' . esc_attr( $classes ) . '"' . ( 'row' === $context ? ' title="' . esc_attr( $label ) . '"' : '' ) . '>';
					echo '<span class="ph-nav-icon">' . $ph_icon . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<span class="ph-nav-text">';
						echo '<span class="ph-nav-label">' . esc_html( $label ) . '</span>';
						if ( '' !== $ph_subtitle )
						{
							echo '<span class="ph-nav-subtitle">' . esc_html( $ph_subtitle ) . '</span>';
						}
					echo '</span>';
				echo '</a>';
			};
		?>
		<div class="ph-settings-nav-wrap">
			<nav class="nav-tab-wrapper ph-settings-nav">
				<?php
					foreach ( $ph_core_tabs as $name => $label )
					{
						$ph_render_tab( $name, $label, 'row' );
					}

					if ( ! empty( $ph_addon_tabs ) )
					{
						// The active add-on tab renders inline so the current
						// location is always visible; the rest live in the menu.
						if ( $ph_active_is_addon )
						{
							$ph_render_tab( $current_tab, $ph_addon_tabs[ $current_tab ], 'row' );
						}

						echo '<span class="ph-nav-addons">';
							// Compact toggle (icon + count) when an add-on tab is
							// already visible inline, to keep the nav to one row.
							echo '<button type="button" class="ph-nav-tab ph-nav-addons-toggle' . ( $ph_active_is_addon ? ' is-compact' : '' ) . '" aria-expanded="false" aria-haspopup="true" title="' . esc_attr__( 'Add-ons', 'propertyhive' ) . '">';
								echo '<span class="ph-nav-icon">' . PH_Admin_Settings::get_tab_icon_svg( 'default' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo '<span class="ph-nav-text">';
									echo '<span class="ph-nav-label">' . esc_html__( 'Add-ons', 'propertyhive' ) . ' <span class="ph-nav-count">' . count( $ph_addon_tabs ) . '</span></span>';
									echo '<span class="ph-nav-subtitle">' . esc_html__( 'Extension settings', 'propertyhive' ) . '</span>';
								echo '</span>';
								echo '<span class="ph-nav-chevron" aria-hidden="true"></span>';
							echo '</button>';
							echo '<div class="ph-nav-addons-menu">';
								foreach ( $ph_addon_tabs as $name => $label )
								{
									$ph_render_tab( $name, $label, 'menu' );
								}
							echo '</div>';
						echo '</span>';
					}

					do_action( 'propertyhive_settings_tabs' );
				?>
			</nav>
		</div>

		<script>
		jQuery( function( $ ) {
			var $wrap = $( '.ph-settings-nav-wrap' );
			var $addonsToggle = $wrap.find( '.ph-nav-addons-toggle' );

			function closeAddonsMenu( restoreFocus ) {
				$wrap.removeClass( 'is-open' );
				$addonsToggle.attr( 'aria-expanded', 'false' );

				if ( restoreFocus ) {
					$addonsToggle.trigger( 'focus' );
				}
			}

			$wrap.on( 'click', '.ph-nav-addons-toggle', function( e ) {
				e.preventDefault();
				$wrap.toggleClass( 'is-open' );
				$( this ).attr( 'aria-expanded', $wrap.hasClass( 'is-open' ) ? 'true' : 'false' );
			} );

			$( document ).on( 'click', function( e ) {
				if ( $wrap.hasClass( 'is-open' ) && ! $( e.target ).closest( '.ph-nav-addons' ).length ) {
					closeAddonsMenu( false );
				}
			} );

			$( document ).on( 'keydown', function( e ) {
				if ( 'Escape' === e.key && $wrap.hasClass( 'is-open' ) ) {
					e.preventDefault();
					closeAddonsMenu( true );
				}
			} );
		} );
		</script>

		<hr class="wp-header-end" style="display:none">

		<?php echo $ph_sections_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="ph-settings-header">
			<div class="ph-settings-header-text">
				<?php if ( '' !== $ph_page_title ) : ?>
					<h1><?php echo esc_html( $ph_page_title ); ?></h1>
				<?php endif; ?>
				<?php if ( '' !== $ph_page_desc ) : ?>
					<p><?php echo esc_html( $ph_page_desc ); ?></p>
				<?php endif; ?>
			</div>
			<div class="ph-settings-header-actions">
				<?php
				if ( isset( $GLOBALS['show_cancel_button'] ) && $GLOBALS['show_cancel_button'] === TRUE )
				{
					$cancel_href = 'javascript:history.go(-1);';
					if ( isset( $GLOBALS['cancel_button_href'] ) && ! empty( $GLOBALS['cancel_button_href'] ) )
					{
						$cancel_href = $GLOBALS['cancel_button_href'];
					}
					?>
					<a href="<?php echo esc_url( $cancel_href ); ?>" class="button ph-cancel-button"><?php echo esc_html( __( 'Cancel', 'propertyhive' ) ); ?></a>
					<?php
				}
				?>
				<a class="ph-help-button" href="https://wp-property-hive.com/documentation/" target="_blank" rel="noopener" title="<?php echo esc_attr( __( 'Help & documentation', 'propertyhive' ) ); ?>">?</a>
			</div>
		</div>

		<?php echo $ph_settings_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <p class="submit ph-settings-footer-submit">
        	<input type="hidden" name="subtab" id="last_tab" />
            <input type="hidden" name="redirect" value="<?php echo esc_url( !empty($redirect_after_save) ? $redirect_after_save : '' ); ?>">
        	<?php wp_nonce_field( 'propertyhive-settings' ); ?>
        </p>

		<?php if ( $ph_show_save ) : ?>
			<div class="ph-save-tray" data-ph-save-tray data-ph-save-always-active="<?php echo $ph_always_show_save ? 'true' : 'false'; ?>" aria-hidden="true" inert>
				<div class="ph-save-tray-status">
					<span class="ph-save-tray-orb" aria-hidden="true"></span>
					<span class="ph-save-tray-copy">
						<strong data-ph-save-state><?php echo esc_html__( 'Unsaved changes', 'propertyhive' ); ?></strong>
						<small><?php echo esc_html__( 'Review your changes before leaving this page.', 'propertyhive' ); ?></small>
					</span>
				</div>
				<div class="ph-save-tray-actions">
					<button class="ph-save-discard" type="button" data-ph-save-discard><?php echo esc_html__( 'Discard', 'propertyhive' ); ?></button>
					<button name="save" class="button-primary ph-save-pill" type="submit" value="<?php echo esc_attr( $ph_button_text ); ?>" data-ph-save-button disabled>
						<span class="ph-save-check" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg></span>
						<span data-ph-save-button-label><?php echo esc_html( $ph_button_text ); ?></span>
					</button>
				</div>
			</div>
			<div class="ph-save-toast" data-ph-save-toast role="status" aria-live="polite" aria-hidden="true">
				<span class="ph-save-toast-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg></span>
				<span><?php echo esc_html__( 'Settings saved', 'propertyhive' ); ?></span>
			</div>
			<noscript>
				<p class="submit">
					<button name="save" class="button button-primary" type="submit" value="<?php echo esc_attr( $ph_button_text ); ?>"><?php echo esc_html( $ph_button_text ); ?></button>
				</p>
			</noscript>
		<?php endif; ?>
	</form>
</div>
