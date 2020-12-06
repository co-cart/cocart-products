<?php
/*
 * Plugin Name: CoCart - Products
 * Plugin URI:  https://cocart.xyz
 * Description: Access products without the requirement of authenticating. Get each variation for a variable product in one request and more.
 * Author:      Sébastien Dumont
 * Author URI:  https://sebastiendumont.com
 * Version:     1.0.0-beta.2
 * Text Domain: cocart-products
 * Domain Path: /languages/
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 3.9.3
 *
 * Copyright: © 2020 Sébastien Dumont, (mailme@sebastiendumont.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! class_exists( 'CoCart_Products' ) ) {
	class CoCart_Products {

		/**
		 * @var CoCart_Products - the single instance of the class.
		 *
		 * @access protected
		 * @static
		 */
		protected static $_instance = null;

		/**
		 * Plugin Version
		 *
		 * @access public
		 * @static
		 */
		public static $version = '1.0.0-beta.2';

		/**
		 * Required CoCart Version
		 *
		 * @access public
		 * @static
		 */
		public static $required_cocart = '2.0.0';

		/**
		 * Main CoCart Products Instance.
		 *
		 * Ensures only one instance of CoCart Products is loaded or can be loaded.
		 *
		 * @access  public
		 * @static
		 * @see     CoCart_Products()
		 * @return  CoCart_Products - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @access public
		 * @return void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'Cloning this object is forbidden.', 'cocart-products' ), self::$version );
		} // END __clone()

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @access public
		 * @return void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'cocart-products' ), self::$version );
		} // END __wakeup()

		/**
		 * Load the plugin.
		 *
		 * @access public
		 */
		public function __construct() {
			// Setup Constants.
			$this->setup_constants();

			// Include admin classes to handle all back-end functions.
			$this->admin_includes();

			// Include required files.
			add_action( 'init', array( $this, 'includes' ) );

			// Load translation files.
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
		} // END __construct()

		/**
		 * Setup Constants
		 *
		 * @access public
		 */
		public function setup_constants() {
			$this->define('COCART_PRODUCTS_VERSION', self::$version);
			$this->define('COCART_PRODUCTS_FILE', __FILE__);
			$this->define('COCART_PRODUCTS_SLUG', 'cocart-products');

			$this->define('COCART_PRODUCTS_URL_PATH', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
			$this->define('COCART_PRODUCTS_FILE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

			$this->define('COCART_STORE_URL', 'https://cocart.xyz/');
			$this->define('COCART_PRODUCTS_REVIEW_URL', 'https://cocart.xyz/submit-review/');
			$this->define('COCART_PRODUCTS_DOCUMENTATION_URL', 'https://docs.cocart.xyz/products.html');
		} // END setup_constants()

		/**
		 * Define constant if not already set.
		 *
		 * @access private
		 * @param  string $name
		 * @param  string|bool $value
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		} // END define()

		/**
		 * Includes REST-API Controllers.
		 *
		 * @access public
		 * @return void
		 */
		public function includes() {
			include_once( COCART_PRODUCTS_FILE_PATH . '/includes/class-cocart-products-autoloader.php' );
			include_once( COCART_PRODUCTS_FILE_PATH . '/includes/class-cocart-products-init.php' );
		} // END includes()

		/**
		 * Include admin class to handle all back-end functions.
		 *
		 * @access public
		 * @return void
		 */
		public function admin_includes() {
			if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
				include_once( COCART_PRODUCTS_FILE_PATH . '/includes/admin/class-cocart-products-admin.php' );
				require_once( COCART_PRODUCTS_FILE_PATH . '/includes/class-cocart-products-install.php' ); // Install CoCart Products.
			}
		} // END admin_includes()

		/**
		 * Make the plugin translation ready.
		 *
		 * Translations should be added in the WordPress language directory:
		 *      - WP_LANG_DIR/plugins/cocart-products-LOCALE.mo
		 *
		 * @access public
		 * @return void
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'cocart-products', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		} // END load_plugin_textdomain()

	} // END class

} // END if class exists

/**
 * Returns the main instance of CoCart Products.
 *
 * @return CoCart Products
 */
function CoCart_Products() {
	return CoCart_Products::instance();
}

// Run CoCart Products
CoCart_Products();