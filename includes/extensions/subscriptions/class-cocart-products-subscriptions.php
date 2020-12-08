<?php
/**
 * CoCart Products - Subscription Products
 *
 * Extends the products endpoint by adding "Subscription Product data" for subscription products.
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
 * CoCart Products - Subscription Products class.
 */
class CoCart_Subscription_Products_Controller {

	/**
	 * Setup class.
	 *
	 * @access public
	 */
	public function __construct() {
		add_filter( 'cocart_prepare_product_object', array( $this, 'add_subscription_product_meta' ), 10, 2 );
	}

	/**
	 * Add Subscription data for subscription products.
	 * 
	 * @access public
	 * @param  WP_REST_Response $response The response object.
	 * @param  WC_Data          $object   Object data.
	 * @return $response
	 */
	public function add_subscription_product_meta( $response, $object ) {
		$id = $object->get_id();

		// Checks if the product is a subscription product. If not just return the `$response`.
		if ( ! WC_Subscriptions_Product::is_subscription( $id ) ) {
			return $response;
		}

		$price             = get_post_meta( $id, '_subscription_price', true );
		$period            = get_post_meta( $id, '_subscription_period', true );
		$period_interval   = get_post_meta( $id, '_subscription_period_interval', true );
		$length            = get_post_meta( $id, '_subscription_length', true );
		$trial_length      = get_post_meta( $id, '_subscription_trial_length', true );
		$trial_period      = get_post_meta( $id, '_subscription_trial_period', true );
		$sign_up_fee       = get_post_meta( $id, '_subscription_sign_up_fee', true );
		$one_time_shipping = get_post_meta( $id, '_subscription_one_time_shipping', true );
		$limit             = get_post_meta( $id, '_subscription_limit', true );

		// Return period as month if not set.
		if ( ! $period ) {
			$period = 'month';
		}

		$response->data['subscription'] = array(
			'price'             => html_entity_decode( strip_tags( wc_price( $price ) ) ),
			'period'            => $period,
			'period_interval'   => $period_interval,
			'length'            => $length,
			'trial_period'      => $trial_period,
			'trial_length'      => $trial_length,
			'sign_up_fee'       => html_entity_decode( strip_tags( wc_price( $sign_up_fee ) ) ),
			'one_time_shipping' => $one_time_shipping,
			'limit'             => $limit
		);

		// Variation data.
		if ( $object->get_type() == 'variation' ) {
			$var_regular_price = get_post_meta( $id, '_regular_price', true );

			$response->data['subscription']['regular_price'] = html_entity_decode( strip_tags( wc_price( $var_regular_price ) ) );
		}

		return $response;
	} // add_subscription_product_meta()

} // END class

return new CoCart_Subscription_Products_Controller();