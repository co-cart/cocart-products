<?php
/**
 * CoCart - Products controller
 *
 * Handles requests to the products endpoint.
 *
 * @author   Sébastien Dumont
 * @category API
 * @package  CoCart/API
 * @since    2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Product controller class.
 *
 * @package CoCart/API
 * @extends WP_REST_Controller
 */
class CoCart_Products_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'cocart/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'product';

	/**
	 * Register the routes for products.
	 *
	 * @access public
	 */
	public function register_routes() {
		// Get Products - cocart/v1/products (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_items' ),
				'args'     => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		// Get a single product - cocart/v1/products/32 (GET)
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			'args'   => array(
				'id' => array(
					'description' => __( 'Unique identifier for the product.', 'cocart-products' ),
					'type'        => 'integer',
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'args'                => array(
					'context' => $this->get_context_param( array(
						'default' => 'view',
					) ),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Get post types.
	 *
	 * @access protected
	 * @return array
	 */
	protected function get_post_types() {
		return array( 'product', 'product_variation' );
	} // END get_post_types()

	/**
	 * Get object.
	 *
	 * @access protected
	 * @param  int $id Object ID.
	 * @return WC_Data
	 */
	protected function get_object( $id ) {
		return wc_get_product( $id );
	} // END get_object()

	/**
	 * Get objects.
	 *
	 * @access protected
	 * @param  array $query_args Query args.
	 * @return array
	 */
	protected function get_objects( $query_args ) {
		$query  = new WP_Query();
		$result = $query->query( $query_args );

		$total_posts = $query->found_posts;

		if ( $total_posts < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );

			$count_query = new WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		return array(
			'objects' => array_map( array( $this, 'get_object' ), $result ),
			'total'   => (int) $total_posts,
			'pages'   => (int) ceil( $total_posts / (int) $query->query_vars['posts_per_page'] ),
		);
	} // END get_objects()

	/**
	 * Get a collection of posts.
	 *
	 * @access public
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$query_args    = $this->prepare_objects_query( $request );
		$query_results = $this->get_objects( $query_args );

		$objects = array();

		foreach ( $query_results['objects'] as $object ) {
			$data      = $this->prepare_object_for_response( $object, $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		$page      = (int) $query_args['paged'];
		$max_pages = $query_results['pages'];

		$response = rest_ensure_response( $objects );
		$response->header( 'X-WP-Total', $query_results['total'] );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base          = $this->rest_base;
		$attrib_prefix = '(?P<';

		if ( strpos( $base, $attrib_prefix ) !== false ) {
			$attrib_names = array();

			preg_match( '/\(\?P<[^>]+>.*\)/', $base, $attrib_names, PREG_OFFSET_CAPTURE );

			foreach ( $attrib_names as $attrib_name_match ) {
				$beginning_offset = strlen( $attrib_prefix );
				$attrib_name_end  = strpos( $attrib_name_match[0], '>', $attrib_name_match[1] );
				$attrib_name      = substr( $attrib_name_match[0], $beginning_offset, $attrib_name_end - $beginning_offset );

				if ( isset( $request[ $attrib_name ] ) ) {
					$base  = str_replace( "(?P<$attrib_name>[\d]+)", $request[ $attrib_name ], $base );
				}
			}
		}

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}

		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	} // END get_items()

	/**
	 * Prepare links for the request.
	 *
	 * @access protected
	 * @param  WC_Product      $product Product object.
	 * @param  WP_REST_Request $request Request object.
	 * @return array Links for the given product.
	 */
	protected function prepare_links( $product, $request ) {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $product->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		if ( $product->get_parent_id() ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $product->get_parent_id() ) ),
			);
		}

		return $links;
	} // END prepare_links()

	/**
	 * Prepare a single product output for response.
	 *
	 * @access public
	 * @param  WC_Data         $object  Object data.
	 * @param  WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {
		// Check what product type before returning product data.
		if ( $object->get_type() !== 'variation' ) {
			$data = $this->get_product_data( $object );
		} else {
			$data = $this->get_variation_product_data( $object );
		}

		// Add review data to products if requested.
		if ( $request['show_reviews'] ) {
			$data['reviews'] = $this->get_reviews( $object );
		}

		// Add variations to variable products. Returns just IDs by default.
		if ( $object->is_type( 'variable' ) && $object->has_child() ) {
			$variations = $object->get_children();

			foreach( $variations as $variation_product ) {
				$data['variations'][ $variation_product ] = array( 'id' => $variation_product );

				// If requested to return variations then fetch them.
				if ( $request['return_variations'] ) {
					$variation_object = new WC_Product_Variation( $variation_product );
					$data['variations'][ $variation_product ] = $this->get_variation_product_data( $variation_object );
				}
			}
		}

		// Add grouped products data.
		if ( $object->is_type( 'grouped' ) && $object->has_child() ) {
			$data['grouped_products'] = $object->get_children();
		}

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, 'view' );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Data          $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'cocart_prepare_product_object', $response, $object, $request );
	} // END prepare_object_for_response()

	/**
	 * Get a single item.
	 *
	 * @access public
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new WP_Error( 'cocart_' . $this->post_type . '_invalid_id', __( 'Invalid ID.', 'cocart-products' ), array( 'status' => 404 ) );
		}

		$data     = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		return $response;
	} // END get_item()

	/**
	 * Prepare objects query.
	 *
	 * @access protected
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args                        = array();
		$args['offset']              = $request['offset'];
		$args['order']               = $request['order'];
		$args['orderby']             = $request['orderby'];
		$args['paged']               = $request['page'];
		$args['post__in']            = $request['include'];
		$args['post__not_in']        = $request['exclude'];
		$args['posts_per_page']      = $request['per_page'];
		$args['name']                = $request['slug'];
		$args['post_parent__in']     = $request['parent'];
		$args['post_parent__not_in'] = $request['parent_exclude'];
	
		if ( 'date' === $args['orderby'] ) {
			$args['orderby'] = 'date ID';
		}

		$args['date_query'] = array();
		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}

		// Set after into date query. Date query must be specified as an array of an array.
		if ( isset( $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}

		// Set post_status.
		$args['post_status'] = 'publish';

		// Taxonomy query to filter products by type, category,
		// tag and attribute.
		$tax_query = array();

		// Map between taxonomy name and arg's key.
		$taxonomies = array(
			'product_cat' => 'category',
			'product_tag' => 'tag',
		);

		// Set tax_query for each passed arg.
		foreach ( $taxonomies as $taxonomy => $key ) {
			if ( ! empty( $request[ $key ] ) ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $request[ $key ],
				);
			}
		}

		// Filter product type by slug.
		if ( ! empty( $request['type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $request['type'],
			);
		}

		// Filter by attribute and term.
		if ( ! empty( $request['attribute'] ) && ! empty( $request['attribute_term'] ) ) {
			if ( in_array( $request['attribute'], wc_get_attribute_taxonomy_names(), true ) ) {
				$tax_query[] = array(
					'taxonomy' => $request['attribute'],
					'field'    => 'term_id',
					'terms'    => $request['attribute_term'],
				);
			}
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query; // WPCS: slow query ok.
		}

		// Filter featured.
		if ( is_bool( $request['featured'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'featured',
				'operator' => true === $request['featured'] ? 'IN' : 'NOT IN',
			);
		}

		// Filter by sku.
		if ( ! empty( $request['sku'] ) ) {
			$skus = explode( ',', $request['sku'] );

			// Include the current string as a SKU too.
			if ( 1 < count( $skus ) ) {
				$skus[] = $request['sku'];
			}

			$args['meta_query'] = $this->add_meta_query( // WPCS: slow query ok.
				$args, array(
					'key'     => '_sku',
					'value'   => $skus,
					'compare' => 'IN',
				)
			);
		}

		// Filter by tax class.
		if ( ! empty( $request['tax_class'] ) ) {
			$args['meta_query'] = $this->add_meta_query( // WPCS: slow query ok.
				$args, array(
					'key'   => '_tax_class',
					'value' => 'standard' !== $request['tax_class'] ? $request['tax_class'] : '',
				)
			);
		}

		// Price filter.
		if ( ! empty( $request['min_price'] ) || ! empty( $request['max_price'] ) ) {
			$args['meta_query'] = $this->add_meta_query( $args, wc_get_min_max_price_meta_query( $request ) );  // WPCS: slow query ok.
		}

		// Filter product in stock or out of stock.
		if ( is_bool( $request['in_stock'] ) ) {
			$args['meta_query'] = $this->add_meta_query( // WPCS: slow query ok.
				$args, array(
					'key'   => '_stock_status',
					'value' => true === $request['in_stock'] ? 'instock' : 'outofstock',
				)
			);
		}

		// Filter by on sale products.
		if ( is_bool( $request['on_sale'] ) ) {
			$on_sale_key = $request['on_sale'] ? 'post__in' : 'post__not_in';
			$on_sale_ids = wc_get_product_ids_on_sale();

			// Use 0 when there's no on sale products to avoid return all products.
			$on_sale_ids = empty( $on_sale_ids ) ? array( 0 ) : $on_sale_ids;

			$args[ $on_sale_key ] += $on_sale_ids;
		}

		// Force the post_type argument, since it's not a user input variable.
		if ( ! empty( $request['sku'] ) ) {
			$args['post_type'] = $this->get_post_types();
		} else {
			$args['post_type'] = $this->post_type;
		}

		return $args;
	} // END prepare_objects_query()

	/**
	 * Get taxonomy terms.
	 *
	 * @access protected
	 * @param  WC_Product $product  Product instance.
	 * @param  string     $taxonomy Taxonomy slug.
	 * @return array
	 */
	protected function get_taxonomy_terms( $product, $taxonomy = 'cat' ) {
		$terms = array();

		foreach ( wc_get_object_terms( $product->get_id(), 'product_' . $taxonomy ) as $term ) {
			$terms[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}

		return $terms;
	} // END get_taxonomy_terms()

	/**
	 * Get the images for a product or product variation.
	 *
	 * @access protected
	 * @param  WC_Product|WC_Product_Variation $product Product instance.
	 * @return array $images
	 */
	protected function get_images( $product ) {
		$images         = array();
		$attachment_ids = array();

		// Add featured image.
		if ( $product->get_image_id() ) {
			$attachment_ids[] = $product->get_image_id();
		}

		// Add gallery images.
		$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );

		// Build image data.
		foreach ( $attachment_ids as $position => $attachment_id ) {
			$attachment_post = get_post( $attachment_id );
			if ( is_null( $attachment_post ) ) {
				continue;
			}

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
			if ( ! is_array( $attachment ) ) {
				continue;
			}

			$images[] = array(
				'id'                => (int) $attachment_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, false ),
				'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
				'src'               => current( $attachment ),
				'name'              => get_the_title( $attachment_id ),
				'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'position'          => (int) $position,
			);
		}

		// Set a placeholder image if the product has no images set.
		if ( empty( $images ) ) {
			$images[] = array(
				'id'                => 0,
				'date_created'      => wc_rest_prepare_date_response( current_time( 'mysql' ), false ), // Default to now.
				'date_created_gmt'  => wc_rest_prepare_date_response( current_time( 'timestamp', true ) ), // Default to now.
				'date_modified'     => wc_rest_prepare_date_response( current_time( 'mysql' ), false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( current_time( 'timestamp', true ) ),
				'src'               => wc_placeholder_img_src(),
				'name'              => __( 'Placeholder', 'cocart-products' ),
				'alt'               => __( 'Placeholder', 'cocart-products' ),
				'position'          => 0,
			);
		}

		return $images;
	} // END get_images()

	/**
	 * Get the reviews for a product.
	 *
	 * @access protected
	 * @param  WC_Product|WC_Product_Variation $product Product instance.
	 * @return array $reviews
	 */
	protected function get_reviews( $product ) {
		$args = array(
			'post_id' => $product->get_id(),
			'comment_type' => 'review'
		);
		$comments_query = new WP_Comment_Query;
		$comments       = $comments_query->query( $args );

		$reviews = array();

		foreach( $comments as $key => $review ) {
			$reviews[ $key ] = array(
				'review_id'       => $review->comment_ID,
				'author_name'     => ucfirst( $review->comment_author ),
				'author_url'      => $review->comment_author_url,
				'review_comment'  => $review->comment_content,
				'review_date'     => $review->comment_date,
				'review_date_gmt' => $review->comment_date_gmt,
				'rating'          => get_comment_meta( $review->comment_ID, 'rating', true ),
				'verified'        => get_comment_meta( $review->comment_ID, 'verified', true ),
			);
		}

		return $reviews;
	} // END get_reviews()

	/**
	 * Get product attribute taxonomy name.
	 *
	 * @access protected
	 * @param  string     $slug    Taxonomy name.
	 * @param  WC_Product $product Product data.
	 * @return string
	 */
	protected function get_attribute_taxonomy_name( $slug, $product ) {
		$attributes = $product->get_attributes();

		if ( ! isset( $attributes[ $slug ] ) ) {
			return str_replace( 'pa_', '', $slug );
		}

		$attribute = $attributes[ $slug ];

		// Taxonomy attribute name.
		if ( $attribute->is_taxonomy() ) {
			$taxonomy = $attribute->get_taxonomy_object();
			return $taxonomy->attribute_label;
		}

		// Custom product attribute name.
		return $attribute->get_name();
	} // END get_attribute_taxonomy_name()

	/**
	 * Get default attributes.
	 *
	 * @access protected
	 * @param  WC_Product $product Product instance.
	 * @return array
	 */
	protected function get_default_attributes( $product ) {
		$default = array();

		if ( $product->is_type( 'variable' ) ) {
			foreach ( array_filter( (array) $product->get_default_attributes(), 'strlen' ) as $key => $value ) {
				if ( 0 === strpos( $key, 'pa_' ) ) {
					$default[ 'attribute_' . $key ] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $key ),
						'name'   => $this->get_attribute_taxonomy_name( $key, $product ),
						'option' => $value,
						//'type'   => $product->get_default_attributes()
					);
				} else {
					$default[ 'attribute_' . $key ] = array(
						'id'     => 0,
						'name'   => $this->get_attribute_taxonomy_name( $key, $product ),
						'option' => $value,
						//'type'   => $product->get_default_attributes()
					);
				}
			}
		}

		return $default;
	} // END get_default_attributes()

	/**
	 * Get attribute options.
	 *
	 * @access protected
	 * @param  int   $product_id Product ID.
	 * @param  array $attribute  Attribute data.
	 * @return array
	 */
	protected function get_attribute_options( $product_id, $attribute ) {
		if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
			return wc_get_product_terms(
				$product_id, $attribute['name'], array(
					'fields' => 'names',
				)
			);
		} elseif ( isset( $attribute['value'] ) ) {
			return array_map( 'trim', explode( '|', $attribute['value'] ) );
		}

		return array();
	} // END get_attribute_options()

	/**
	 * Get the attributes for a product or product variation.
	 *
	 * @access protected
	 * @param  WC_Product|WC_Product_Variation $product Product instance.
	 * @return array
	 */
	protected function get_attributes( $product ) {
		$attributes = array();

		if ( $product->is_type( 'variation' ) ) {
			$_product = wc_get_product( $product->get_parent_id() );
			foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
				$name = str_replace( 'attribute_', '', $attribute_name );

				if ( ! $attribute ) {
					continue;
				}

				// Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
				if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
					$option_term  = get_term_by( 'slug', $attribute, $name );

					$attributes[ 'attribute_pa_' . $name ] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $name ),
						'name'   => $this->get_attribute_taxonomy_name( $name, $_product ),
						'option' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
					);
				} else {
					$attributes[ 'attribute_' . $name ] = array(
						'id'     => 0,
						'name'   => $this->get_attribute_taxonomy_name( $name, $_product ),
						'option' => $attribute,
					);
				}
			}
		} else {
			foreach ( $product->get_attributes() as $attribute ) {
				$attribute_id = 'attribute_' . str_replace( ' ', '-', strtolower( $attribute['name'] ) );

				$attributes[ $attribute_id ] = array(
					'id'                   => $attribute['is_taxonomy'] ? wc_attribute_taxonomy_id_by_name( $attribute['name'] ) : 0,
					'name'                 => $this->get_attribute_taxonomy_name( $attribute['name'], $product ),
					'position'             => (int) $attribute['position'],
					'is_attribute_visible' => (bool) $attribute['is_visible'],
					'used_for_variation'   => (bool) $attribute['is_variation'],
					'options'              => $this->get_attribute_options( $product->get_id(), $attribute ),
				);
			}
		}

		return $attributes;
	} // END get_attributes()

	/**
	 * Get product data.
	 *
	 * @access protected
	 * @param  WC_Product $product Product instance.
	 * @return array
	 */
	protected function get_product_data( $product ) {
		$rating_count = $product->get_rating_count( 'view' );
		$review_count = $product->get_review_count( 'view' );
		$average      = $product->get_average_rating( 'view' );

		$data = array(
			'id'                    => $product->get_id(),
			'name'                  => $product->get_name( 'view' ),
			'slug'                  => $product->get_slug( 'view' ),
			'permalink'             => $product->get_permalink(),
			'date_created'          => wc_rest_prepare_date_response( $product->get_date_created( 'view' ), false ),
			'date_created_gmt'      => wc_rest_prepare_date_response( $product->get_date_created( 'view' ) ),
			'date_modified'         => wc_rest_prepare_date_response( $product->get_date_modified( 'view' ), false ),
			'date_modified_gmt'     => wc_rest_prepare_date_response( $product->get_date_modified( 'view' ) ),
			'type'                  => $product->get_type(),
			'featured'              => $product->is_featured(),
			'catalog_visibility'    => $product->get_catalog_visibility( 'view' ),
			'description'           => $product->get_description( 'view' ),
			'short_description'     => $product->get_short_description( 'view' ),
			'sku'                   => $product->get_sku( 'view' ),
			'price'                 => html_entity_decode( strip_tags( wc_price( $product->get_price( 'view' ) ) ) ),
			'regular_price'         => html_entity_decode( strip_tags( wc_price( $product->get_regular_price( 'view' ) ) ) ),
			'sale_price'            => $product->get_sale_price( 'view' ) ? html_entity_decode( strip_tags( wc_price( $product->get_sale_price( 'view' ) ) ) ) : '',
			'date_on_sale_from'     => wc_rest_prepare_date_response( $product->get_date_on_sale_from( 'view' ), false ),
			'date_on_sale_from_gmt' => wc_rest_prepare_date_response( $product->get_date_on_sale_from( 'view' ) ),
			'date_on_sale_to'       => wc_rest_prepare_date_response( $product->get_date_on_sale_to( 'view' ), false ),
			'date_on_sale_to_gmt'   => wc_rest_prepare_date_response( $product->get_date_on_sale_to( 'view' ) ),
			'on_sale'               => $product->is_on_sale( 'view' ),
			'purchasable'           => $product->is_purchasable(),
			'total_sales'           => $product->get_total_sales( 'view' ),
			'virtual'               => $product->is_virtual(),
			'downloadable'          => $product->is_downloadable(),
			'external_url'          => $product->is_type( 'external' ) ? $product->get_product_url( 'view' ) : '',
			'button_text'           => $product->is_type( 'external' ) ? $product->get_button_text( 'view' ) : '',
			'manage_stock'          => $product->managing_stock(),
			'stock_quantity'        => $product->get_stock_quantity( 'view' ),
			'in_stock'              => $product->is_in_stock(),
			'stock_status'          => $product->get_stock_status( 'view' ),
			'backorders'            => $product->get_backorders( 'view' ),
			'backorders_allowed'    => $product->backorders_allowed(),
			'backordered'           => $product->is_on_backorder(),
			'sold_individually'     => $product->is_sold_individually(),
			'weight'                => $product->get_weight( 'view' ),
			'dimensions'            => array(
				'length' => $product->get_length( 'view' ),
				'width'  => $product->get_width( 'view' ),
				'height' => $product->get_height( 'view' ),
			),
			'shipping_required'     => $product->needs_shipping(),
			'reviews_allowed'       => $product->get_reviews_allowed( 'view' ),
			'average_rating'        => $average,
			'rating_count'          => $rating_count,
			'review_count'          => $review_count,
			'rating_html'           => wc_get_rating_html( $average, $rating_count ),
			'reviews'               => array(),
			'related_ids'           => array_map( 'absint', array_values( wc_get_related_products( $product->get_id() ) ) ),
			'upsell_ids'            => array_map( 'absint', $product->get_upsell_ids( 'view' ) ),
			'cross_sell_ids'        => array_map( 'absint', $product->get_cross_sell_ids( 'view' ) ),
			'parent_id'             => $product->get_parent_id( 'view' ),
			'categories'            => $this->get_taxonomy_terms( $product ),
			'tags'                  => $this->get_taxonomy_terms( $product, 'tag' ),
			'images'                => $this->get_images( $product ),
			'attributes'            => $this->get_attributes( $product ),
			'default_attributes'    => $this->get_default_attributes( $product ),
			'variations'            => array(),
			'grouped_products'      => array(),
			'menu_order'            => $product->get_menu_order( 'view' ),
			'meta_data'             => $product->get_meta_data(),
		);

		return $data;
	} // END get_product_data()

	/**
	 * Get variation product data.
	 *
	 * @access protected
	 * @param  WC_Variation_Product $product Product instance.
	 * @return array
	 */
	protected function get_variation_product_data( $product ) {
		$data = array(
			'id'                    => $product->get_id(),
			'name'                  => $product->get_name( 'view' ),
			'slug'                  => $product->get_slug( 'view' ),
			'permalink'             => $product->get_permalink(),
			'date_created'          => wc_rest_prepare_date_response( $product->get_date_created( 'view' ), false ),
			'date_created_gmt'      => wc_rest_prepare_date_response( $product->get_date_created( 'view' ) ),
			'date_modified'         => wc_rest_prepare_date_response( $product->get_date_modified( 'view' ), false ),
			'date_modified_gmt'     => wc_rest_prepare_date_response( $product->get_date_modified( 'view' ) ),
			'description'           => $product->get_description( 'view' ),
			'sku'                   => $product->get_sku( 'view' ),
			'price'                 => html_entity_decode( strip_tags( wc_price( $product->get_price( 'view' ) ) ) ),
			'regular_price'         => html_entity_decode( strip_tags( wc_price( $product->get_regular_price( 'view' ) ) ) ),
			'sale_price'            => $product->get_sale_price( 'view' ) ? html_entity_decode( strip_tags( wc_price( $product->get_sale_price( 'view' ) ) ) ) : '',
			'date_on_sale_from'     => wc_rest_prepare_date_response( $product->get_date_on_sale_from( 'view' ), false ),
			'date_on_sale_from_gmt' => wc_rest_prepare_date_response( $product->get_date_on_sale_from( 'view' ) ),
			'date_on_sale_to'       => wc_rest_prepare_date_response( $product->get_date_on_sale_to( 'view' ), false ),
			'date_on_sale_to_gmt'   => wc_rest_prepare_date_response( $product->get_date_on_sale_to( 'view' ) ),
			'on_sale'               => $product->is_on_sale( 'view' ),
			'purchasable'           => $product->is_purchasable(),
			'total_sales'           => $product->get_total_sales( 'view' ),
			'virtual'               => $product->is_virtual(),
			'downloadable'          => $product->is_downloadable(),
			'manage_stock'          => $product->managing_stock(),
			'stock_quantity'        => $product->get_stock_quantity( 'view' ),
			'in_stock'              => $product->is_in_stock(),
			'stock_status'          => $product->get_stock_status( 'view' ),
			'backorders'            => $product->get_backorders( 'view' ),
			'backorders_allowed'    => $product->backorders_allowed(),
			'backordered'           => $product->is_on_backorder(),
			'weight'                => $product->get_weight( 'view' ),
			'dimensions'            => array(
				'length' => $product->get_length( 'view' ),
				'width'  => $product->get_width( 'view' ),
				'height' => $product->get_height( 'view' ),
			),
			'shipping_required'     => $product->needs_shipping(),
			'images'                => $this->get_images( $product ),
			'attributes'            => $this->get_attributes( $product ),
			'menu_order'            => $product->get_menu_order( 'view' ),
			'meta_data'             => $product->get_meta_data(),
		);

		return $data;
	} // END get_variation_product_data()

	/**
	 * Get the Product's schema, conforming to JSON Schema.
	 *
	 * @access public
	 * @return array
	 */
	public function get_item_schema() {
		$weight_unit    = get_option( 'woocommerce_weight_unit' );
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );

		$schema         = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id'                    => array(
					'description' => __( 'Unique identifier for the product.', 'cocart-products' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name'                  => array(
					'description' => __( 'Product name.', 'cocart-products' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'slug'                  => array(
					'description' => __( 'Product slug.', 'cocart-products' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'permalink'                  => array(
					'description' => __( 'Product permalink.', 'cocart-products' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'date_created'          => array(
					'description' => __( "The date the product was created, in the site's timezone.", 'cocart-products' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_created_gmt'      => array(
					'description' => __( 'The date the product was created, as GMT.', 'cocart-products' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_modified'         => array(
					'description' => __( "The date the product was last modified, in the site's timezone.", 'cocart-products' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'date_modified_gmt'     => array(
					'description' => __( 'The date the product was last modified, as GMT.', 'cocart-products' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'type'                  => array(
					'description' => __( 'Product type.', 'cocart-products' ),
					'type'        => 'string',
					'default'     => 'simple',
					'enum'        => array_keys( wc_get_product_types() ),
					'context'     => array( 'view' ),
				),
				'featured'              => array(
					'description' => __( 'Featured product.', 'cocart-products' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view' ),
				),
				'catalog_visibility'    => array(
					'description' => __( 'Catalog visibility.', 'cocart-products' ),
					'type'        => 'string',
					'default'     => 'visible',
					'enum'        => array( 'visible', 'catalog', 'search', 'hidden' ),
					'context'     => array( 'view' ),
				),
				'description'           => array(
					'description' => __( 'Product description.', 'cocart-products' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'short_description'     => array(
					'description' => __( 'Product short description.', 'cocart-products' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'sku'                   => array(
					'description' => __( 'Unique identifier.', 'cocart-products' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'price'                 => array(
					'description' => __( 'Current product price.', 'cocart-products' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'regular_price'         => array(
					'description' => __( 'Product regular price.', 'cocart-products' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'sale_price'            => array(
					'description' => __( 'Product sale price.', 'cocart-products' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'date_on_sale_from'     => array(
					'description' => __( "Start date of sale price, in the site's timezone.", 'cocart-products' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
				),
				'date_on_sale_from_gmt' => array(
					'description' => __( 'Start date of sale price, as GMT.', 'cocart-products' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
				),
				'date_on_sale_to'       => array(
					'description' => __( "End date of sale price, in the site's timezone.", 'cocart-products' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
				),
				'date_on_sale_to_gmt'   => array(
					'description' => __( 'End date of sale price, as GMT.', 'cocart-products' ),
					'type'        => 'date-time',
					'context'     => array( 'view' ),
				),
				'on_sale'               => array(
					'description' => __( 'Shows if the product is on sale.', 'cocart-products' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'purchasable'           => array(
					'description' => __( 'Shows if the product can be bought.', 'cocart-products' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'total_sales'           => array(
					'description' => __( 'Amount of sales.', 'cocart-products' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'virtual'               => array(
					'description' => __( 'If the product is virtual.', 'cocart-products' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view' ),
				),
				'downloadable'          => array(
					'description' => __( 'If the product is downloadable.', 'cocart-products' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view' ),
				),
				'external_url'          => array(
					'description' => __( 'Product external URL. Only for external products.', 'cocart-products' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view' ),
				),
				'button_text'           => array(
					'description' => __( 'Product external button text. Only for external products.', 'cocart-products' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'manage_stock'          => array(
					'description' => __( 'Stock management at product level.', 'cocart-products' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view' ),
				),
				'stock_quantity'        => array(
					'description' => __( 'Stock quantity.', 'cocart-products' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
				),
				'in_stock'              => array(
					'description' => __( 'Determines if product is listed as "in stock" or "out of stock".', 'cocart-products' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view' ),
				),
				'backorders'            => array(
					'description' => __( 'If managing stock, this controls if backorders are allowed.', 'cocart-products' ),
					'type'        => 'string',
					'default'     => 'no',
					'enum'        => array( 'no', 'notify', 'yes' ),
					'context'     => array( 'view' ),
				),
				'backorders_allowed'    => array(
					'description' => __( 'Are backorders allowed?', 'cocart-products' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'backordered'           => array(
					'description' => __( 'Shows if the product is on backordered.', 'cocart-products' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'sold_individually'     => array(
					'description' => __( 'Allow one of the item to be bought in a single order.', 'cocart-products' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view' ),
				),
				'weight'                => array(
					/* translators: %s: weight unit */
					'description' => sprintf( __( 'Product weight (%s).', 'cocart-products' ), $weight_unit ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'dimensions'            => array(
					'description' => __( 'Product dimensions.', 'cocart-products' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'properties'  => array(
						'length' => array(
							/* translators: %s: dimension unit */
							'description' => sprintf( __( 'Product length (%s).', 'cocart-products' ), $dimension_unit ),
							'type'        => 'string',
							'context'     => array( 'view' ),
						),
						'width'  => array(
							/* translators: %s: dimension unit */
							'description' => sprintf( __( 'Product width (%s).', 'cocart-products' ), $dimension_unit ),
							'type'        => 'string',
							'context'     => array( 'view' ),
						),
						'height' => array(
							/* translators: %s: dimension unit */
							'description' => sprintf( __( 'Product height (%s).', 'cocart-products' ), $dimension_unit ),
							'type'        => 'string',
							'context'     => array( 'view' ),
						),
					),
				),
				'shipping_required'     => array(
					'description' => __( 'Shows if the product need to be shipped.', 'cocart-products' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'reviews_allowed'       => array(
					'description' => __( 'Shows if reviews are allowed.', 'cocart-products' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view' ),
				),
				'reviews'               => array(
					'description' => __( 'Returns a list of product review IDs', 'cocart-products' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'average_rating'        => array(
					'description' => __( 'Reviews average rating.', 'cocart-products' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'rating_count'          => array(
					'description' => __( 'Amount of reviews that the product has.', 'cocart-products' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'review_count'          => array(
					'description' => __( 'Amount of reviews that the product have.', 'cocart-products' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'rating_html'           => array(
					'description' => __( 'Returns the rating of the product in html.', 'cocart-products' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'related_ids'           => array(
					'description' => __( 'List of related products IDs.', 'cocart-products' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'upsell_ids'            => array(
					'description' => __( 'List of up-sell products IDs.', 'cocart-products' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'context'     => array( 'view' ),
				),
				'cross_sell_ids'        => array(
					'description' => __( 'List of cross-sell products IDs.', 'cocart-products' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'context'     => array( 'view' ),
				),
				'parent_id'             => array(
					'description' => __( 'Product parent ID.', 'cocart-products' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
				),
				'categories'            => array(
					'description' => __( 'List of product categories.', 'cocart-products' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Category ID.', 'cocart-products' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'name' => array(
								'description' => __( 'Category name.', 'cocart-products' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Category slug.', 'cocart-products' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
						),
					),
				),
				'tags'                  => array(
					'description' => __( 'List of product tags.', 'cocart-products' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Tag ID.', 'cocart-products' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'name' => array(
								'description' => __( 'Tag name.', 'cocart-products' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Tag slug.', 'cocart-products' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
						),
					),
				),
				'images'                => array(
					'description' => __( 'List of product images.', 'cocart-products' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'              => array(
								'description' => __( 'Image ID.', 'cocart-products' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'date_created'    => array(
								'description' => __( "The date the image was created, in the site's timezone.", 'cocart-products' ),
								'type'        => 'date-time',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'date_created_gmt'  => array(
								'description' => __( 'The date the image was created, as GMT.', 'cocart-products' ),
								'type'        => 'date-time',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'date_modified'     => array(
								'description' => __( "The date the image was last modified, in the site's timezone.", 'cocart-products' ),
								'type'        => 'date-time',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'date_modified_gmt' => array(
								'description' => __( 'The date the image was last modified, as GMT.', 'cocart-products' ),
								'type'        => 'date-time',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'src'               => array(
								'description' => __( 'Image URL.', 'cocart-products' ),
								'type'        => 'string',
								'format'      => 'uri',
								'context'     => array( 'view' ),
							),
							'name'              => array(
								'description' => __( 'Image name.', 'cocart-products' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'alt'               => array(
								'description' => __( 'Image alternative text.', 'cocart-products' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'position'          => array(
								'description' => __( 'Image position. 0 means that the image is featured.', 'cocart-products' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
						),
					),
				),
				'attributes'            => array(
					'description' => __( 'List of attributes.', 'cocart-products' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'        => array(
								'description' => __( 'Attribute ID.', 'cocart-products' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'name'      => array(
								'description' => __( 'Attribute name.', 'cocart-products' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'position'  => array(
								'description' => __( 'Attribute position.', 'cocart-products' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'is_attribute_visible'   => array(
								'description' => __( "Is the attribute visible on the \"Additional information\" tab in the product's page.", 'cocart-products' ),
								'type'        => 'boolean',
								'default'     => false,
								'context'     => array( 'view' ),
							),
							'used_for_variation' => array(
								'description' => __( 'Can the attribute be used as variation?', 'cocart-products' ),
								'type'        => 'boolean',
								'default'     => false,
								'context'     => array( 'view' ),
							),
							'options'   => array(
								'description' => __( 'List of available term names of the attribute.', 'cocart-products' ),
								'type'        => 'array',
								'context'     => array( 'view' ),
								'items'       => array(
									'type' => 'string',
								),
							),
						),
					),
				),
				'default_attributes'    => array(
					'description' => __( 'Defaults variation attributes.', 'cocart-products' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'     => array(
								'description' => __( 'Attribute ID.', 'cocart-products' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'name'   => array(
								'description' => __( 'Attribute name.', 'cocart-products' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'option' => array(
								'description' => __( 'Selected attribute term name.', 'cocart-products' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
						),
					),
				),
				'variations'            => array(
					'description' => __( 'List of all variations and data.', 'cocart-products' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type' => 'object',
					),
					'readonly'    => true,
				),
				'grouped_products'      => array(
					'description' => __( 'List of grouped products ID.', 'cocart-products' ),
					'type'        => 'array',
					'items'       => array(
						'type' => 'integer',
					),
					'context'     => array( 'view' ),
				),
				'menu_order'            => array(
					'description' => __( 'Menu order, used to custom sort products.', 'cocart-products' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
				),
				'meta_data'             => array(
					'description' => __( 'Meta data.', 'cocart-products' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => __( 'Meta ID.', 'cocart-products' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => __( 'Meta key.', 'cocart-products' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'value' => array(
								'description' => __( 'Meta value.', 'cocart-products' ),
								'type'        => 'mixed',
								'context'     => array( 'view' ),
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	} // END get_item_schema()

	/**
	 * Add the schema from additional fields to an schema array.
	 *
	 * The type of object is inferred from the passed schema.
	 *
	 * @access protected
	 * @param  array $schema Schema array.
	 * @return array $schema
	 */
	protected function add_additional_fields_schema( $schema ) {
		if ( empty( $schema['title'] ) ) {
			return $schema;
		}

		/**
		 * Can't use $this->get_object_type otherwise we cause an inf loop.
		 */
		$object_type = $schema['title'];

		$additional_fields = $this->get_additional_fields( $object_type );

		foreach ( $additional_fields as $field_name => $field_options ) {
			if ( ! $field_options['schema'] ) {
				continue;
			}

			$schema['properties'][ $field_name ] = $field_options['schema'];
		}

		$schema['properties'] = apply_filters( 'cocart_' . $object_type . '_schema', $schema['properties'] );

		return $schema;
	} // END add_additional_fields_schema()

	/**
	 * Get the query params for collections of products.
	 *
	 * @access public
	 * @return array $params
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['slug'] = array(
			'description'       => __( 'Limit result set to products with a specific slug.', 'cocart-products' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['type'] = array(
			'description'       => __( 'Limit result set to products assigned a specific type.', 'cocart-products' ),
			'type'              => 'string',
			'enum'              => array_keys( wc_get_product_types() ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['sku'] = array(
			'description'       => __( 'Limit result set to products with specific SKU(s). Use commas to separate.', 'cocart-products' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['featured'] = array(
			'description'       => __( 'Limit result set to featured products.', 'cocart-products' ),
			'type'              => 'boolean',
			'sanitize_callback' => 'wc_string_to_bool',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['category'] = array(
			'description'       => __( 'Limit result set to products assigned a specific category slug.', 'cocart-products' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['tag'] = array(
			'description'       => __( 'Limit result set to products assigned a specific tag slug.', 'cocart-products' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['attribute'] = array(
			'description'       => __( 'Limit result set to products with a specific attribute. Use the taxonomy name/attribute slug.', 'cocart-products' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['attribute_term'] = array(
			'description'       => __( 'Limit result set to products with a specific attribute term ID (required an assigned attribute).', 'cocart-products' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['stock_status'] = array(
			'description'       => __( 'Limit result set to products with specified stock status.', 'cocart-products' ),
			'type'              => 'string',
			'enum'              => array_keys( wc_get_product_stock_status_options() ),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['on_sale'] = array(
			'description'       => __( 'Limit result set to products on sale.', 'cocart-products' ),
			'type'              => 'boolean',
			'sanitize_callback' => 'wc_string_to_bool',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['min_price'] = array(
			'description'       => __( 'Limit result set to products based on a minimum price.', 'cocart-products' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['max_price'] = array(
			'description'       => __( 'Limit result set to products based on a maximum price.', 'cocart-products' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby']['enum'] = array( 'price', 'popularity', 'rating' );

		return $params;
	} // END get_collection_params()

} // END class