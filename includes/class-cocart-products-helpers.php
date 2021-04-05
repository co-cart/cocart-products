<?php
/**
 * CoCart Products REST API helpers.
 *
 * Provides functions that provide helpful data for the plugin.
 *
 * @author   Sébastien Dumont
 * @category API
 * @package  CoCart/Helpers
 * @license  GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CoCart Products REST API helper class.
 *
 * @package CoCart Products REST API/Helpers
 */
class CoCart_Products_Helpers {

	/**
	 * Cache 'gte' comparison results.
	 *
	 * @var array
	 */
	private static $is_wc_version_gte = array();

	/**
	 * Cache 'gt' comparison results.
	 *
	 * @var array
	 */
	private static $is_wc_version_gt = array();

	/**
	 * Cache 'gt' comparison results for WP version.
	 *
	 * @var array
	 */
	private static $is_wp_version_gt = array();

	/**
	 * Cache 'gte' comparison results for WP version.
	 *
	 * @var array
	 */
	private static $is_wp_version_gte = array();

	/**
	 * Helper method to get the version of the currently installed WooCommerce.
	 *
	 * @access private
	 * @return string
	 */
	private static function get_wc_version() {
		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	} // END get_wc_version()

	/**
	 * Returns true if the installed version of WooCommerce is 3.6 or greater.
	 *
	 * @access public
	 * @return boolean
	 */
	public static function is_wc_version_gte_3_6() {
		return self::is_wc_version_gte( '3.6' );
	} // END is_wc_version_gte()

	/**
	 * Returns true if the installed version of WooCommerce is 4.0 or greater.
	 *
	 * @access public
	 * @return boolean
	 */
	public static function is_wc_version_gte_4_0() {
		return self::is_wc_version_gte( '4.0' );
	} // END is_wc_version_gte()

	/**
	 * Returns true if the installed version of WooCommerce is greater than or equal to $version.
	 *
	 * @access public
	 * @param  string $version the version to compare
	 * @return boolean true if the installed version of WooCommerce is > $version
	 */
	public static function is_wc_version_gte( $version ) {
		if ( ! isset( self::$is_wc_version_gte[ $version ] ) ) {
			self::$is_wc_version_gte[ $version ] = self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>=' );
		}
		return self::$is_wc_version_gte[ $version ];
	} // END is_wc_version_gte()

	/**
	 * Returns true if the installed version of WooCommerce is greater than $version.
	 *
	 * @access public
	 * @param  string $version the version to compare
	 * @return boolean true if the installed version of WooCommerce is > $version
	 */
	public static function is_wc_version_gt( $version ) {
		if ( ! isset( self::$is_wc_version_gt[ $version ] ) ) {
			self::$is_wc_version_gt[ $version ] = self::get_wc_version() && version_compare( self::get_wc_version(), $version, '>' );
		}

		return self::$is_wc_version_gt[ $version ];
	} // END is_wc_version_gt()

	/**
	 * Returns true if the WooCommerce version does not meet CoCart Products requirements.
	 *
	 * @access public
	 * @static
	 * @return boolean
	 */
	public static function is_not_wc_version_required() {
		if ( version_compare( self::get_wc_version(), CoCart_Pro::$required_woo, '<' ) ) {
			return true;
		}

		return false;
	} // END is_note_wc_version_required()

	/**
	 * Returns true if the installed version of WordPress is greater than $version.
	 *
	 * @access public
	 * @param  string $version
	 * @return boolean
	 */
	public static function is_wp_version_gt( $version ) {
		if ( ! isset( self::$is_wp_version_gt[ $version ] ) ) {
			global $wp_version;

			self::$is_wp_version_gt[ $version ] = $wp_version && version_compare( $wp_version, $version, '>' );
		}

		return self::$is_wp_version_gt[ $version ];
	} // END is_wp_version_gt()

	/**
	 * Returns true if the installed version of WordPress is greater than or equal to $version.
	 *
	 * @access public
	 * @param  string $version
	 * @return boolean
	 */
	public static function is_wp_version_gte( $version ) {
		if ( ! isset( self::$is_wp_version_gte[ $version ] ) ) {
			global $wp_version;

			self::$is_wp_version_gte[ $version ] = $wp_version && version_compare( $wp_version, $version, '>=' );
		}

		return self::$is_wp_version_gte[ $version ];
	} // END is_wp_version_gte()

	/**
	 * Helper method to get the version of the currently installed CoCart Products.
	 *
	 * @access private
	 * @static
	 * @return string
	 */
	public static function get_cocart_products_version() {
		return defined( 'COCART_PRODUCTS_VERSION' ) && COCART_PRODUCTS_VERSION ? COCART_PRODUCTS_VERSION : null;
	} // END get_cocart_products_version()

	/**
	 * Returns true if CoCart Products is a pre-release.
	 *
	 * @access public
	 * @static
	 * @return boolean
	 */
	public static function is_cocart_products_pre_release() {
		$version = self::get_cocart_products_version();

		if ( strpos( $version, 'beta' ) || strpos( $version, 'rc' ) ) {
			return true;
		}

		return false;
	} // END is_cocart_products_pre_release()

	/**
	 * Returns true if CoCart Products is a Beta release.
	 *
	 * @access public
	 * @static
	 * @return boolean
	 */
	public static function is_cocart_products_beta() {
		$version = self::get_cocart_products_version();

		if ( strpos( $version, 'beta' ) ) {
			return true;
		}

		return false;
	} // END is_cocart_products_beta()

	/**
	 * Returns true if CoCart Products is a Release Candidate.
	 *
	 * @access public
	 * @static
	 * @return boolean
	 */
	public static function is_cocart_products_rc() {
		$version = self::get_cocart_products_version();

		if ( strpos( $version, 'rc' ) ) {
			return true;
		}

		return false;
	} // END is_cocart_products_rc()

	/**
	 * Returns true if we are making a REST API request for CoCart.
	 *
	 * @access public
	 * @static
	 * @return bool
	 */
	public static function is_rest_api_request() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix         = trailingslashit( rest_get_url_prefix() );
		$is_rest_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix . 'cocart/' ) );

		return $is_rest_api_request;
	} // END is_rest_api_request()

	/**
	 * Checks if CoCart is installed.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function is_cocart_installed() {
		if ( ! class_exists( 'CoCart' ) ) {
			return false;
		}

		return true;
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
	 * Returns true|false if the user is on a CoCart page.
	 *
	 * @access public
	 * @static
	 * @return bool
	 */
	public static function is_cocart_admin_page() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( ! in_array( $screen_id, self::cocart_get_admin_screens() ) ) {
			return false;
		}

		return true;
	} // END is_cocart_admin_page()

	/**
	 * Checks if the current user has the capabilities to install a plugin.
	 *
	 * @access public
	 * @static
	 * @return bool
	 */
	public static function user_has_capabilities() {
		if ( current_user_can( apply_filters( 'cocart_install_capability', 'install_plugins' ) ) ) {
			return true;
		}

		// If the current user can not install plugins then return nothing!
		return false;
	} // END user_has_capabilities()

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
			return sprintf( __( '%s months', 'cocart-products' ), $months );
		} elseif ( $months > 0 ) {
			return __( '1 month', 'cocart-products' );
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
	 * Check how long CoCart Products has been active for.
	 *
	 * @access public
	 * @static
	 * @param  int $seconds - Time in seconds to check.
	 * @return bool Whether or not WooCommerce admin has been active for $seconds.
	 */
	public static function cocart_products_active_for( $seconds = '' ) {
		if ( empty( $seconds ) ) {
			return true;
		}

		// Getting install timestamp.
		$cocart_products_installed = get_option( 'cocart_products_install_date', false );

		if ( false === $cocart_products_installed ) {
			return false;
		}

		return ( ( time() - $cocart_products_installed ) >= $seconds );
	} // END cocart_products_active_for()

	/**
	 * Determines if the server environment is compatible with this plugin.
	 *
	 * @access public
	 * @static
	 * @return bool
	 */
	public static function is_environment_compatible() {
		return version_compare( PHP_VERSION, CoCart_Products::$required_php, '>=' );
	} // END is_environment_compatible()

	/**
	 * Gets the message for display when the environment is incompatible with this plugin.
	 *
	 * @access public
	 * @static
	 * @return string
	 */
	public static function get_environment_message() {
		/* translators: 1: CoCart Products, 2: Required PHP version */
		return sprintf( __( 'The minimum PHP version required for %1$s is %2$s. You are running %3$s.', 'cocart-products' ), 'CoCart Products', CoCart_Products::$required_php, self::get_php_version() );
	} // END get_environment_message()

	/**
	 * Gets the current site's PHP version, without the extra info.
	 *
	 * @access private
	 * @static
	 * @return string The PHP version.
	 */
	private static function get_php_version() {
		$version = explode( '.', PHP_VERSION );

		return (int) $version[0] . '.' . (int) $version[1];
	} // END get_php_version()

} // END class

return new CoCart_Products_Helpers();
