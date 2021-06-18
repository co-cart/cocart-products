<?php
/*
 * Plugin Name: CoCart - Products
 * Plugin URI:  https://cocart.xyz
 * Description: Provides access to non-sensitive product information, categories, tags, attributes and even reviews from your store without the need to authenticate.
 * Author:      Sébastien Dumont
 * Author URI:  https://sebastiendumont.com
 * Version:     1.0.0-beta.11
 * Text Domain: cocart-products
 * Domain Path: /languages/
 *
 * Requires at least: 5.4
 * Requires PHP: 7.0
 * WC requires at least: 4.3
 * WC tested up to: 5.4
 *
 * Copyright: © 2020 Sébastien Dumont, (mailme@sebastiendumont.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'COCART_PRODUCTS_FILE' ) ) {
	define( 'COCART_PRODUCTS_FILE', __FILE__ );
}

// Include the main CoCart Product class.
if ( ! class_exists( 'CoCart_Products', false ) ) {
	include_once untrailingslashit( plugin_dir_path( COCART_PRODUCTS_FILE ) ) . '/includes/class-cocart-products.php';
}

/**
 * Returns the main instance of CoCart Products and only runs if it does not already exists.
 *
 * @return CoCart_Products
 */
if ( ! function_exists( 'CoCart_Products' ) ) {
	function CoCart_Products() {
		return CoCart_Products::init();
	}

	CoCart_Products();

	/**
	 * Load backend features only if COCART_WHITE_LABEL constant is
	 * NOT set or IS set to false in user's wp-config.php file.
	 */
	if (
		! defined( 'COCART_WHITE_LABEL' ) || false === COCART_WHITE_LABEL &&
		is_admin() || ( defined( 'WP_CLI' ) && WP_CLI )
	) {
		include_once untrailingslashit( plugin_dir_path( COCART_PRODUCTS_FILE ) ) . '/includes/admin/class-cocart-products-admin.php';
	}
}
