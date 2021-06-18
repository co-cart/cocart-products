<?php
/**
 * Admin View: Required CoCart Notice.
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
<div class="notice notice-info cocart-notice">
	<div class="cocart-notice-inner">
		<div class="cocart-notice-icon">
			<img src="<?php echo COCART_PRODUCTS_URL_PATH . '/assets/images/logo.jpg'; ?>" alt="<?php echo esc_attr__( 'CoCart, a WooCommerce REST-API extension', 'cocart-products' ); ?>" />
		</div>

		<div class="cocart-notice-content">
			<h3><?php echo esc_html__( 'Update Required!', 'cocart-products' ); ?></h3>
			<p><?php echo sprintf( __( '%1$s Products requires at least %1$s v%2$s or higher.', 'cocart-products' ), 'CoCart', CoCart_Products::$required_cocart ); ?></p>
		</div>

		<?php if ( current_user_can( 'update_plugins' ) ) { ?>
		<div class="cocart-action">
			<?php $upgrade_url = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=cart-rest-api-for-woocommerce' ), 'upgrade-plugin_cart-rest-api-for-woocommerce' ); ?>

			<p><a href="<?php echo esc_url( $upgrade_url ); ?>" class="button button-primary cocart-button" aria-label="<?php echo sprintf( esc_html__( 'Update %s', 'cocart-products' ), 'CoCart' ); ?>"><?php echo sprintf( esc_html__( 'Update %s', 'cocart-products' ), 'CoCart' ); ?></a></p>
		</div>
		<?php } ?>
	</div>
</div>
