<?php
/**
 * CoCart Products REST API
 *
 * Handles endpoints requests for Products.
 *
 * @author   Sébastien Dumont
 * @category API
 * @package  CoCart Products/API
 * @license  GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CoCart Products REST API class.
 */
class CoCart_Products_Rest_API {

	/**
	 * Setup class.
	 *
	 * @access public
	 */
	public function __construct() {
		// CoCart Products REST API.
		$this->cocart_products_rest_api_init();
	} // END __construct()

	/**
	 * Init CoCart Products REST API.
	 *
	 * @access private
	 */
	private function cocart_products_rest_api_init() {
		// REST API was included starting WordPress 4.4.
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			return;
		}

		// If WooCommerce does not exists then do nothing!
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// If CoCart does not exists then do nothing!
		if ( ! class_exists( 'CoCart' ) ) {
			return;
		}

		// Include REST API Controllers.
		add_action( 'rest_api_init', array( $this, 'rest_api_includes' ), 10 );

		// Supports WooCommerce extensions by filtering the products to apply additional data.
		add_action( 'rest_api_init', array( $this, 'support_extensions' ), 12 );

		// Register CoCart Products REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_products_routes' ), 20 );
	} // cart_rest_api_init()

	/**
	 * Include CoCart Products REST API controllers.
	 *
	 * @access public
	 */
	public function rest_api_includes() {
		include_once( dirname( __FILE__ ) . '/api/class-cocart-abstract-terms-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-cocart-product-attribute-terms-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-cocart-product-attributes-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-cocart-product-categories-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-cocart-product-reviews-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-cocart-product-tags-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-cocart-products-controller.php' );
		include_once( dirname( __FILE__ ) . '/api/class-cocart-product-variations-controller.php' );
	} // rest_api_includes()

	/**
	 * Includes filtered data for WooCommerce Extensions.
	 *
	 * @access public
	 */
	public function support_extensions() {
		// WooCommerce Subscriptions
		if ( class_exists( 'WC_Subscriptions' ) ) {
			include_once( dirname( __FILE__ ) . '/extensions/subscriptions/class-cocart-products-subscriptions.php' );
		}
	} // support_extensions()

	/**
	 * Register CoCart Products REST API routes.
	 *
	 * @access public
	 */
	public function register_products_routes() {
		$controllers = array(
			'CoCart_Product_Attribute_Terms_Controller',
			'CoCart_Product_Attributes_Controller',
			'CoCart_Product_Categories_Controller',
			'CoCart_Product_Reviews_Controller',
			'CoCart_Product_Tags_Controller',
			'CoCart_Products_Controller',
			'CoCart_Product_Variations_Controller'
		);

		sort( $controllers );

		foreach ( $controllers as $controller ) {
			if ( class_exists( $controller ) ) {
				$this->$controller = new $controller();
				$this->$controller->register_routes();
			}
		}
	} // END register_products_routes()

} // END class

return new CoCart_Products_Rest_API();
