<?php
/**
 * CoCart Products - Installation related functions and actions.
 *
 * @author   Sébastien Dumont
 * @category Classes
 * @package  CoCart Products/Classes/Install
 * @license  GPL-2.0+
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CoCart_Products_Install' ) ) {

	class CoCart_Products_Install {

		/**
		 * Constructor.
		 *
		 * @access public
		 */
		public function __construct() {
			// Checks version of CoCart Products and install/update if needed.
			add_action( 'init', array( $this, 'check_version' ), 5 );
		} // END __construct()

		/**
		 * Check plugin version and run the updater if necessary.
		 *
		 * This check is done on all requests and runs if the versions do not match.
		 *
		 * @access public
		 * @static
		 */
		public static function check_version() {
			if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'cocart_products_version' ), COCART_PRODUCTS_VERSION, '<' ) && current_user_can( 'install_plugins' ) ) {
				self::install();
				do_action( 'cocart_products_updated' );
			}
		} // END check_version()

		/**
		 * Install CoCart.
		 *
		 * @access public
		 * @static
		 */
		public static function install() {
			if ( ! is_blog_installed() ) {
				return;
			}

			// Check if we are not already running this routine.
			if ( 'yes' === get_transient( 'cocart_products_installing' ) ) {
				return;
			}

			// If we made it till here nothing is running yet, lets set the transient now for five minutes.
			set_transient( 'cocart_products_installing', 'yes', MINUTE_IN_SECONDS * 5 );
			if ( ! defined( 'COCART_PRODUCTS_INSTALLING' ) ) {
				define( 'COCART_PRODUCTS_INSTALLING', true );
			}

			// Set activation date.
			self::set_install_date();

			// Update plugin version.
			self::update_version();

			delete_transient( 'cocart_products_installing' );

			do_action( 'cocart_products_installed' );
		} // END install()

		/**
		 * Update plugin version to current.
		 *
		 * @access private
		 * @static
		 */
		private static function update_version() {
			update_option( 'cocart_products_version', COCART_PRODUCTS_VERSION );
		} // END update_version()

		/**
		 * Set the time the plugin was installed.
		 *
		 * @access public
		 * @static
		 */
		public static function set_install_date() {
			add_option( 'cocart_products_install_date', time() );
		} // END set_install_date()

	} // END class.

} // END if class exists.

return new CoCart_Products_Install();
