<?php
/**
 * CoCart Products - Admin Action Links.
 *
 * Adds links to CoCart Products on the plugins page.
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

if ( ! class_exists( 'CoCart_Products_Admin_Action_Links' ) ) {

	class CoCart_Products_Admin_Action_Links {

		/**
		 * Constructor
		 *
		 * @access public
		 */
		public function __construct() {
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 3 );
		} // END __construct()

		/**
		 * Plugin row meta links
		 *
		 * @access public
		 * @param  array  $metadata An array of the plugin's metadata.
		 * @param  string $file     Path to the plugin file.
		 * @param  array  $data     Plugin Information
		 * @return array  $metadata
		 */
		public function plugin_row_meta( $metadata, $file, $data ) {
			if ( $file == plugin_basename( COCART_PRODUCTS_FILE ) ) {
				$metadata[1] = sprintf( __( 'Developed By %s', 'cocart-products' ), '<a href="' . $data['AuthorURI'] . '" aria-label="' . esc_attr__( 'View the developers site', 'cocart-products' ) . '">' . $data['Author'] . '</a>' );

				$row_meta = array(
					'docs'      => '<a href="' . esc_url( COCART_PRODUCTS_DOCUMENTATION_URL ) . '" aria-label="' . sprintf( esc_attr__( 'View %s Products documentation', 'cocart-products' ), 'CoCart' ) . '" target="_blank">' . esc_attr__( 'Documentation', 'cocart-products' ) . '</a>',
					'translate' => '<a href="' . esc_url( COCART_PRODUCTS_TRANSLATION_URL ) . '" aria-label="' . sprintf( esc_attr__( 'Translate %s', 'cocart-products' ), 'CoCart' ) . '" target="_blank">' . esc_attr__( 'Translate', 'cocart-products' ) . '</a>',
					'review'    => '<a href="' . esc_url( COCART_PRODUCTS_REVIEW_URL ) . '" aria-label="' . sprintf( esc_attr__( 'Review %s on CoCart.xyz', 'cocart-products' ), 'CoCart' ) . '" target="_blank">' . esc_attr__( 'Leave a Review', 'cocart-products' ) . '</a>',
				);

				$metadata = array_merge( $metadata, $row_meta );
			}

			return $metadata;
		} // END plugin_row_meta()

	} // END class

} // END if class exists

return new CoCart_Products_Admin_Action_Links();
