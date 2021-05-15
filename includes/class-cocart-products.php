<?php
/**
 * CoCart Products core setup.
 *
 * @author   Sébastien Dumont
 * @category Package
 * @license  GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CoCart_Products {

	/**
	 * Plugin Version
	 *
	 * @access public
	 * @static
	 */
	public static $version = '1.0.0-beta.11';

	/**
	 * Required WordPress Version
	 *
	 * @access public
	 * @static
	 */
	public static $required_wp = '5.4';

	/**
	 * Required WooCommerce Version
	 *
	 * @access public
	 * @static
	 */
	public static $required_woo = '4.3';

	/**
	 * Required PHP Version
	 *
	 * @access public
	 * @static
	 */
	public static $required_php = '7.0';

	/**
	 * Required CoCart Version
	 *
	 * @access public
	 * @static
	 */
	public static $required_cocart = '2.0.0';

	/**
	 * Initiate CoCart Products.
	 *
	 * @access public
	 * @static
	 */
	public static function init() {
		self::setup_constants();
		self::includes();

		// Install CoCart Products upon activation.
		register_activation_hook( COCART_PRODUCTS_FILE, array( __CLASS__, 'install_cocart_products' ) );

		// Update CoCart add-on counter upon deactivation.
		register_deactivation_hook( COCART_PRODUCTS_FILE, array( __CLASS__, 'deactivate_addon' ) );

		// Load translation files.
		add_action( 'init', array( __CLASS__, 'load_plugin_textdomain' ), 0 );

		// Load REST API.
		add_action( 'init', array( __CLASS__, 'load_rest_api' ) );
	} // END init()

	/**
	 * Return the name of the package.
	 *
	 * @access public
	 * @static
	 * @return string
	 */
	public static function get_name() {
		return 'CoCart Products';
	}

	/**
	 * Return the version of the package.
	 *
	 * @access public
	 * @static
	 * @return string
	 */
	public static function get_version() {
		return self::$version;
	}

	/**
	 * Return the path to the package.
	 *
	 * @access public
	 * @static
	 * @return string
	 */
	public static function get_path() {
		return dirname( __DIR__ );
	}

	/**
	 * Setup Constants
	 *
	 * @access public
	 * @static
	 */
	public static function setup_constants() {
		self::define( 'COCART_PRODUCTS_ABSPATH', dirname( COCART_PRODUCTS_FILE ) . '/' );
		self::define( 'COCART_PRODUCTS_PLUGIN_BASENAME', plugin_basename( COCART_PRODUCTS_FILE ) );
		self::define( 'COCART_PRODUCTS_VERSION', self::$version );
		self::define( 'COCART_PRODUCTS_SLUG', 'cocart-products' );
		self::define( 'COCART_PRODUCTS_URL_PATH', untrailingslashit( plugins_url( '/', COCART_PRODUCTS_FILE ) ) );
		self::define( 'COCART_PRODUCTS_FILE_PATH', untrailingslashit( plugin_dir_path( COCART_PRODUCTS_FILE ) ) );
		self::define( 'COCART_PRODUCTS_PLUGIN_URL', 'https://cocart.xyz/add-ons/products/' );
		self::define( 'COCART_PRODUCTS_REVIEW_URL', 'https://cocart.xyz/submit-review/?wpf15410_12=CoCart%20Products' );
		self::define( 'COCART_PRODUCTS_DOCUMENTATION_URL', 'https://docs.cocart.xyz/products.html' );
		self::define( 'COCART_PRODUCTS_TRANSLATION_URL', 'https://translate.cocart.xyz/projects/cocart-products/' );
	} // END setup_constants()

	/**
	 * Define constant if not already set.
	 *
	 * @access private
	 * @static
	 * @param  string      $name
	 * @param  string|bool $value
	 */
	private static function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	} // END define()

	/**
	 * Includes CoCart Products REST-API.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function includes() {
		include_once COCART_PRODUCTS_ABSPATH . '/includes/class-cocart-products-autoloader.php';
		include_once COCART_PRODUCTS_ABSPATH . '/includes/class-cocart-products-helpers.php';
		require_once COCART_PRODUCTS_ABSPATH . '/includes/class-cocart-products-install.php';
	} // END includes()

	/**
	 * Install CoCart Products upon activation.
	 *
	 * @access public
	 * @static
	 */
	public static function install_cocart_products() {
		self::activation_check();

		CoCart_Products_Install::install();

		self::activate_addon();
	} // END install_cocart_products()

	/**
	 * Checks the server environment and other factors and deactivates the plugin if necessary.
	 *
	 * @access public
	 * @static
	 */
	public static function activation_check() {
		if ( ! CoCart_Products_Helpers::is_environment_compatible() ) {
			self::deactivate_plugin();
			wp_die( sprintf( __( '%1$s could not be activated. %2$s', 'cocart-products' ), 'CoCart Products', CoCart_Products_Helpers::get_environment_message() ) );
		}
	} // END activation_check()

	/**
	 * Deactivates the plugin if the environment is not ready.
	 *
	 * @access public
	 * @static
	 */
	public static function deactivate_plugin() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		deactivate_plugins( plugin_basename( COCART_PRODUCTS_FILE ) );

		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	} // END deactivate_plugin()

	/**
	 * Load Products.
	 *
	 * @access public
	 * @static
	 */
	public static function load_rest_api() {
		include_once COCART_PRODUCTS_ABSPATH . '/includes/class-cocart-products-init.php';
	} // END load_rest_api()

	/**
	 * Runs when the plugin is activated.
	 *
	 * Adds plugin to list of installed CoCart add-ons.
	 *
	 * @access public
	 */
	public static function activate_addon() {
		$addons_installed = get_option( 'cocart_addons_installed', array() );

		$plugin = plugin_basename( COCART_PRODUCTS_FILE );

		// Check if plugin is already added to list of installed add-ons.
		if ( ! in_array( $plugin, $addons_installed, true ) ) {
			array_push( $addons_installed, $plugin );
			update_option( 'cocart_addons_installed', $addons_installed );
		}
	} // END activate_addon()

	/**
	 * Runs when the plugin is deactivated.
	 *
	 * Removes plugin from list of installed CoCart add-ons.
	 *
	 * @access public
	 */
	public static function deactivate_addon() {
		$addons_installed = get_option( 'cocart_addons_installed', array() );

		$plugin = plugin_basename( COCART_PRODUCTS_FILE );

		// Remove plugin from list of installed add-ons.
		if ( in_array( $plugin, $addons_installed, true ) ) {
			$addons_installed = array_diff( $addons_installed, array( $plugin ) );
			update_option( 'cocart_addons_installed', $addons_installed );
		}
	} // END deactivate_addon()

	/**
	 * Load the plugin translations if any ready.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/cocart-products/cocart-products-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/cocart-products-LOCALE.mo
	 *
	 * @access public
	 * @static
	 */
	public static function load_plugin_textdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'cocart-products' );

		unload_textdomain( 'cocart-products' );
		load_textdomain( 'cocart-products', WP_LANG_DIR . '/cocart-products/cocart-products-' . $locale . '.mo' );
		load_plugin_textdomain( 'cocart-products', false, plugin_basename( dirname( COCART_PRODUCTS_FILE ) ) . '/languages' );
	} // END load_plugin_textdomain()

} // END class
