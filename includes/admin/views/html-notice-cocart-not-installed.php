<?php
/**
 * Admin View: CoCart not installed or activated notice.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  CoCart Products\Admin\Views
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
			<h3><?php echo sprintf( __( '%1$s requires %2$s to be installed and activated.', 'cocart-products' ), 'CoCart Products', 'CoCart' ); ?></h3>

			<p>
			<?php
			if ( ! is_plugin_active( 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php' ) && file_exists( WP_PLUGIN_DIR . '/cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php' ) ) :

				if ( current_user_can( 'activate_plugin', 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php' ) ) :

					echo sprintf( '<a href="%1$s" class="button button-primary" aria-label="%2$s">%2$s</a>', esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php&plugin_status=active' ), 'activate-plugin_cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php' ) ), esc_html__( 'Activate CoCart', 'cocart-products' ) );

				else :

					echo esc_html__( 'As you do not have permission to activate a plugin. Please ask a site administrator to activate CoCart for you.', 'cocart-products' );

				endif;

			else :

				if ( current_user_can( 'install_plugins' ) ) {
					$url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=cart-rest-api-for-woocommerce' ), 'install-plugin_cart-rest-api-for-woocommerce' );
				} else {
					$url = 'https://wordpress.org/plugins/cart-rest-api-for-woocommerce/';
				}

				echo '<a href="' . esc_url( $url ) . '" class="button button-primary" aria-label="' . esc_html__( 'Install CoCart', 'cocart-products' ) . '">' . esc_html__( 'Install CoCart', 'cocart-products' ) . '</a>';

			endif;

			if ( current_user_can( 'deactivate_plugin', plugin_basename( COCART_PRODUCTS_FILE ) ) ) :

				echo sprintf(
					' <a href="%1$s" class="button button-secondary" aria-label="%2$s">%2$s</a>',
					esc_url( wp_nonce_url( 'plugins.php?action=deactivate&plugin=' . plugin_basename( COCART_PRODUCTS_FILE ) . '&plugin_status=inactive', 'deactivate-plugin_' . plugin_basename( COCART_PRODUCTS_FILE ) ) ),
					sprintf( esc_html__( 'Turn off the %s Products plugin', 'cocart-products' ), 'CoCart' )
				);

			endif;
			?>
			</p>
		</div>
	</div>
</div>
