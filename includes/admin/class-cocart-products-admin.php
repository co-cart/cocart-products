<?php
/**
 * CoCart Products - Admin.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  CoCart Products/Admin
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CoCart_Products_Admin' ) ) {

	class CoCart_Products_Admin {

		/**
		 * Constructor
		 *
		 * @access public
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'includes' ) );
			add_action( 'current_screen', array( $this, 'conditional_includes' ) );
		} // END __construct()

		/**
		 * Include any classes we need within admin.
		 *
		 * @access public
		 */
		public function includes() {
			include COCART_PRODUCTS_ABSPATH . '/includes/admin/class-cocart-products-admin-assets.php';  // Admin Assets
			include COCART_PRODUCTS_ABSPATH . '/includes/admin/class-cocart-products-admin-notices.php'; // Plugin Notices
			// include COCART_PRODUCTS_ABSPATH . '/includes/admin/class-cocart-products-admin-updater.php'; // Plugin Updater
		} // END includes()

		/**
		 * Include admin files conditionally.
		 *
		 * @access public
		 */
		public function conditional_includes() {
			$screen = get_current_screen();

			if ( ! $screen ) {
				return;
			}

			switch ( $screen->id ) {
				case 'plugins':
					include_once COCART_PRODUCTS_ABSPATH . 'includes/admin/class-cocart-products-admin-action-links.php';  // Action Links
					// include_once COCART_PRODUCTS_ABSPATH . 'includes/admin/class-cocart-products-admin-plugin-screen.php'; // Plugin Screen
					break;
			}
		} // END conditional_includes()

	} // END class

} // END if class exists

return new CoCart_Products_Admin();
