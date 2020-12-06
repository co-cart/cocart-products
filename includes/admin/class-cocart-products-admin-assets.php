<?php
/**
 * CoCart Products - Admin Assets.
 *
 * @author   Sébastien Dumont
 * @category Admin
 * @package  CoCart Products/Admin/Assets
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CoCart_Products_Admin_Assets' ) ) {

	class CoCart_Products_Admin_Assets {

		/**
		 * Constructor
		 *
		 * @access  public
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ), 10 );
		} // END __construct()

		/**
		 * Registers and enqueues Stylesheets.
		 *
		 * @access public
		 */
		public function admin_styles() {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';
			$suffix    = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

			// CoCart Page and Notices
			if ( ! CoCart_Products_Admin::is_cocart_installed() && in_array( $screen_id, CoCart_Products_Admin::cocart_get_admin_screens() ) ) {
				wp_register_style( COCART_PRODUCTS_SLUG . '_admin', COCART_PRODUCTS_URL_PATH . '/assets/css/admin/cocart' . $suffix . '.css' );
				wp_enqueue_style( COCART_PRODUCTS_SLUG . '_admin' );
			}

			// Modal
			if ( in_array( 'plugins', CoCart_Products_Admin::cocart_get_admin_screens() ) ) {
				wp_register_style( COCART_PRODUCTS_SLUG . '_modal', COCART_PRODUCTS_URL_PATH . '/assets/css/admin/modal' . $suffix . '.css' );
				wp_enqueue_style( COCART_PRODUCTS_SLUG . '_modal' );
			}
		} // END admin_styles()

	} // END class

} // END if class exists

return new CoCart_Products_Admin_Assets();
