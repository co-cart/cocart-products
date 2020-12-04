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
			register_activation_hook( COCART_PRODUCTS_FILE, array( $this, 'activated' ) );
			register_deactivation_hook( COCART_PRODUCTS_FILE, array( $this, 'deactivated' ) );

			// Include classes.
			self::includes();
		} // END __construct()

		/**
		 * Include any classes we need within admin.
		 *
		 * @access public
		 */
		public function includes() {
			include COCART_PRODUCTS_FILE_PATH . '/includes/admin/class-cocart-products-admin-action-links.php'; // Action Links
			include COCART_PRODUCTS_FILE_PATH . '/includes/admin/class-cocart-products-admin-assets.php';  // Admin Assets
			include COCART_PRODUCTS_FILE_PATH . '/includes/admin/class-cocart-products-admin-notices.php'; // Plugin Notices
			include COCART_PRODUCTS_FILE_PATH . '/includes/admin/class-cocart-products-admin-updater.php'; // Plugin Updater
		} // END includes()

		/**
		 * Checks if CoCart is installed.
		 *
		 * @access public
		 * @static
		 */
		public static function is_cocart_installed() {
			$active_plugins = (array) get_option( 'active_plugins', array() );

			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}

			return in_array( 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php', $active_plugins ) || array_key_exists( 'cart-rest-api-for-woocommerce/cart-rest-api-for-woocommerce.php', $active_plugins );
		} // END is_cocart_installed()

		/**
		 * These are the only screens CoCart will focus
		 * on displaying notices or enqueue scripts/styles.
		 *
		 * @access public
		 * @static
		 * @return array
		 */
		public static function cocart_get_admin_screens() {
			return array(
				'dashboard',
				'plugins',
				'toplevel_page_cocart',
			);
		} // END cocart_get_admin_screens()

		/**
		 * Returns true if CoCart Products is a beta/pre-release.
		 *
		 * @access public
		 * @static
		 * @return boolean
		 */
		public static function is_cocart_products_beta() {
			if ( 
				strpos( COCART_PRODUCTS_VERSION, 'beta' ) ||
				strpos( COCART_PRODUCTS_VERSION, 'rc' )
			) {
				return true;
			}

			return false;
		} // END is_cocart_products_beta()

		/**
		 * Seconds to words.
		 *
		 * Forked from: https://github.com/thatplugincompany/login-designer/blob/master/includes/admin/class-login-designer-feedback.php
		 *
		 * @access public
		 * @static
		 * @param  string $seconds Seconds in time.
		 * @return string
		 */
		public static function cocart_seconds_to_words( $seconds ) {
			// Get the years.
			$years = ( intval( $seconds ) / YEAR_IN_SECONDS ) % 100;
			if ( $years > 1 ) {
				/* translators: Number of years */
				return sprintf( __( '%s years', 'cocart-products' ), $years );
			} elseif ( $years > 0 ) {
				return __( 'a year', 'cocart-products' );
			}

			// Get the months.
			$months = ( intval( $seconds ) / MONTH_IN_SECONDS ) % 52;
			if ( $months > 1 ) {
				return sprintf( __( '%s months ago', 'cocart-products' ), $months );
			} elseif ( $months > 0 ) {
				return __( '1 month ago', 'cocart-products' );
			}

			// Get the weeks.
			$weeks = ( intval( $seconds ) / WEEK_IN_SECONDS ) % 52;
			if ( $weeks > 1 ) {
				/* translators: Number of weeks */
				return sprintf( __( '%s weeks', 'cocart-products' ), $weeks );
			} elseif ( $weeks > 0 ) {
				return __( 'a week', 'cocart-products' );
			}

			// Get the days.
			$days = ( intval( $seconds ) / DAY_IN_SECONDS ) % 7;
			if ( $days > 1 ) {
				/* translators: Number of days */
				return sprintf( __( '%s days', 'cocart-products' ), $days );
			} elseif ( $days > 0 ) {
				return __( 'a day', 'cocart-products' );
			}

			// Get the hours.
			$hours = ( intval( $seconds ) / HOUR_IN_SECONDS ) % 24;
			if ( $hours > 1 ) {
				/* translators: Number of hours */
				return sprintf( __( '%s hours', 'cocart-products' ), $hours );
			} elseif ( $hours > 0 ) {
				return __( 'an hour', 'cocart-products' );
			}

			// Get the minutes.
			$minutes = ( intval( $seconds ) / MINUTE_IN_SECONDS ) % 60;
			if ( $minutes > 1 ) {
				/* translators: Number of minutes */
				return sprintf( __( '%s minutes', 'cocart-products' ), $minutes );
			} elseif ( $minutes > 0 ) {
				return __( 'a minute', 'cocart-products' );
			}

			// Get the seconds.
			$seconds = intval( $seconds ) % 60;
			if ( $seconds > 1 ) {
				/* translators: Number of seconds */
				return sprintf( __( '%s seconds', 'cocart-products' ), $seconds );
			} elseif ( $seconds > 0 ) {
				return __( 'a second', 'cocart-products' );
			}
		} // END cocart_seconds_to_words()

		/**
		 * Runs when the plugin is activated.
		 *
		 * Adds plugin to list of installed CoCart add-ons.
		 *
		 * @access public
		 */
		public function activated() {
			$addons_installed = get_site_option( 'cocart_addons_installed', array() );

			$plugin = plugin_basename( COCART_PRODUCTS_FILE );

			// Check if plugin is already added to list of installed add-ons.
			if ( ! in_array( $plugin, $addons_installed, true ) ) {
				array_push( $addons_installed, $plugin );
				update_site_option( 'cocart_addons_installed', $addons_installed );
			}
		} // END activated()

		/**
		 * Runs when the plugin is deactivated.
		 *
		 * Removes plugin from list of installed CoCart add-ons.
		 *
		 * @access public
		 */
		public function deactivated() {
			$addons_installed = get_site_option( 'cocart_addons_installed', array() );

			$plugin = plugin_basename( COCART_PRODUCTS_FILE );

			// Remove plugin from list of installed add-ons.
			if ( in_array( $plugin, $addons_installed, true ) ) {
				$addons_installed = array_diff( $addons_installed, array( $plugin ) );
				update_site_option( 'cocart_addons_installed', $addons_installed );
			}
		} // END deactivated()

	} // END class

} // END if class exists

return new CoCart_Products_Admin();
