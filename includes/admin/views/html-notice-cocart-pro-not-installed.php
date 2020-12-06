<?php
/**
 * Admin View: CoCart Pro not installed or activated notice.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  CoCart Products/Admin/Views
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="notice notice-warning cocart-notice">
	<div class="cocart-notice-inner">
		<div class="cocart-notice-icon">
			<img src="<?php echo COCART_PRODUCTS_URL_PATH . '/assets/images/logo.jpg'; ?>" alt="<?php echo esc_attr__( 'CoCart, a WooCommerce REST-API extension', 'cocart-products' ); ?>" />
		</div>

		<div class="cocart-notice-content">
			<h3><?php echo sprintf( __( '%1$s Products requires %2$s to be installed and activated.', 'cocart-products' ), 'CoCart', 'CoCart Pro' ); ?></h3>

			<p>
			<?php
			if ( ! is_plugin_active( 'cocart-pro/cocart-pro.php' ) && file_exists( WP_PLUGIN_DIR . '/cocart-pro/cocart-pro.php' ) ) :

				if ( current_user_can( 'activate_plugin', 'cocart-pro/cocart-pro.php' ) ) :

					echo sprintf( '<a href="%1$s" class="button button-primary" aria-label="%2$s">%2$s</a>', esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=cocart-pro/cocart-pro.php&plugin_status=active' ), 'activate-plugin_cocart-pro/cocart-pro.php' ) ), sprintf( esc_html__( 'Activate %s', 'cocart-products' ), 'CoCart Pro' ) );

				else :

					echo spritnf( esc_html__( 'As you do not have permission to activate a plugin. Please ask a site administrator to activate %s for you.', 'cocart-products' ), 'CoCart Pro' );

				endif;

			else:

				echo '<a href="' . esc_url( 'https://cocart.xyz/pricing/' ) . '" class="button button-primary" aria-label="' . sprintf( esc_html__( 'Purchase %s', 'cocart-products' ), 'CoCart Pro' ) . '">' . sprintf( esc_html__( 'Purchase %s', 'cocart-products' ), 'CoCart Pro' ) . '</a>';

			endif;

			if ( current_user_can( 'deactivate_plugin', 'cocart-products/cocart-products.php' ) ) :

				echo sprintf( 
					' <a href="%1$s" class="button button-secondary" aria-label="%2$s">%2$s</a>', 
					esc_url( wp_nonce_url( 'plugins.php?action=deactivate&plugin=cocart-pro/cocart-pro.php&plugin_status=inactive', 'deactivate-plugin_cocart-pro/cocart-pro.php' ) ),
					sprintf( esc_html__( 'Turn off the %s Products plugin', 'cocart-products' ), 'CoCart' )
				);

			endif;
			?>
			</p>
		</div>
	</div>
</div>