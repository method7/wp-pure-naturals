<?php

class WPF_EC_Ontraport {

	/**
	 * Lets pluggable functions know which features are supported by the CRM
	 */

	public $supports;

	/**
	 * Get things started
	 *
	 * @access  public
	 * @since   1.0
	 */

	public function init() {

		$this->supports = array( 'products', 'refunds' );

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 20, 2 );

		if ( get_option( 'wpf_ontraport_products' ) == false ) {
			$this->sync_products();
		}

		add_action( 'wpf_sync', array( $this, 'sync_products' ) );

	}

	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['ec_op_header'] = array(
			'title'   => __( 'Ontraport Enhanced Ecommerce', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'ecommerce',
			'desc'    => 'For more information on WP Fusion\'s ecommerce integration with Ontraport <a href="https://wpfusion.com/documentation/ecommerce-tracking/ontraport-ecommerce/">see our documentation</a>.'
		);

		$settings['ec_op_prices'] = array(
			'title'   => __( 'Ontraport Pricing', 'wp-fusion' ),
			'type'    => 'radio',
			'section' => 'ecommerce',
			'choices' => array(
				'op'       => __( 'Use product prices as set in Ontraport', 'wp-fusion' ),
				'checkout' => __( 'Use product prices as paid at checkout' ),
			),
			'std'     => 'op',
		);

		$product_options = array(
			'create'      => '- ' . __( 'Create as needed', 'wp-fusion' ) . ' -',
			'no_shipping' => '- ' . __( 'Don\'t track shipping', 'wp-fusion' ) . ' -',
		);

		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		if ( ! empty( $available_products ) ) {
			$product_options = $product_options + $available_products;
		}

		$settings['ec_op_shipping'] = array(
			'title'   => __( 'Shipping Product', 'wp-fusion' ),
			'type'    => 'select',
			'section' => 'ecommerce',
			'choices' => $product_options,
			'desc'    => __( 'WP Fusion can use a pseudo-product in Ontraport to track shipping charges. You can select your shipping product here, or leave blank to have one created automatically at the next checkout.', 'wp-fusion' ),
		);

		return $settings;

	}

	/**
	 * Syncs available products
	 *
	 * @since 1.3
	 * @return void
	 */

	public function sync_products() {

		if ( ! wp_fusion()->crm->params ) {
			wp_fusion()->crm->get_params();
		}

		$products = array();
		$offset   = 0;
		$proceed  = true;

		while ( $proceed == true ) {

			$request  = 'https://api.ontraport.com/1/Products&range=50&start=' . $offset . '';
			$response = wp_remote_get( $request, wp_fusion()->crm->params );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body_json = json_decode( $response['body'], true );

			foreach ( (array) $body_json['data'] as $product ) {
				$products[ $product['id'] ] = $product['name'];
			}

			$offset = $offset + 50;

			if ( count( $body_json['data'] ) < 50 ) {
				$proceed = false;
			}
		}

		update_option( 'wpf_ontraport_products', $products, false );
	}

	/**
	 * Register a product in Ontraport
	 *
	 * @access  public
	 * @return  int Product ID
	 */

	public function add_product( $product ) {

		$ontraport_products = get_option( 'wpf_ontraport_products', array() );

		$search = array_search( $product['name'], $ontraport_products );

		if ( ! empty( $search ) ) {
			return $search;
		}

		$query    = '[{ "field":{"field":"name"}, "op":"=", "value":{"value":"' . $product['name'] . '"} }]';
		$request  = 'https://api.ontraport.com/1/Products?condition=' . urlencode( $query );
		$response = wp_remote_get( $request, wp_fusion()->crm->params );
		$body     = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['data'] ) && $body['data'][0]['price'] == $product['price'] ) {

			// If matching product name found
			$product_id = $body['data'][0]['id'];

		} else {

			// Logger
			wpf_log(
				'info', 0, 'Registering new product <a href="' . admin_url( 'post.php?post=' . $product['id'] . '&action=edit' ) . '" target="_blank">' . $product['name'] . '</a> in Ontraport:', array(
					'meta_array_nofilter' => array(
						'Name'  => $product['name'],
						'Price' => $product['price'],
					),
					'source'              => 'wpf-ecommerce',
				)
			);

			// Add new product
			$nparams         = wp_fusion()->crm->params;
			$nparams['body'] = json_encode(
				array(
					'name'  => $product['name'],
					'price' => $product['price'],
				)
			);

			$response = wp_remote_post( 'https://api.ontraport.com/1/Products', $nparams );

			if ( is_wp_error( $response ) ) {

				return $response;

			} else {

				$body       = json_decode( wp_remote_retrieve_body( $response ), true );
				$product_id = $body['data']['id'];

			}
		}

		if ( ! empty( $product['id'] ) ) {

			// Save the ID to the product
			update_post_meta( $product['id'], 'ontraport_product_id', $product_id );

		}

		// Update the global products list
		$ontraport_products                = get_option( 'wpf_ontraport_products', array() );
		$ontraport_products[ $product_id ] = $product['name'];
		update_option( 'wpf_ontraport_products', $ontraport_products, false );

		return $product_id;

	}


	/**
	 * Add an order
	 *
	 * @access  public
	 * @return  mixed Invoice ID or
	 */

	public function add_order( $order_id, $contact_id, $order_args ) {

		if ( empty( $order_args['order_date'] ) ) {
			$order_date = current_time( 'timestamp' );
		} else {
			$order_date = $order_args['order_date'];
		}

		// Convert date to GMT
		$offset      = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		$order_date -= $offset;

		if ( ! wp_fusion()->crm->params ) {
			wp_fusion()->crm->get_params();
		}

		$params = wp_fusion()->crm->params;

		$order_data = array(
			'objectID'          => 0,
			'contact_id'        => $contact_id,
			'chargeNow'         => 'chargeLog',
			'trans_date'        => (int) $order_date * 1000,
			'invoice_template'  => 0,
			'offer'             => array( 'products' => array() ),
			'delay'             => 0,
			'external_order_id' => $order_id,
		);

		// Referral handling (The OPRID is a combination between a promo tool id and the affiliate id. )
		if ( isset( $_COOKIE['oprid'] ) ) {

			wpf_log( 'info', 0, 'Recording referral for partner ID ' . $_COOKIE['oprid'], array( 'source' => 'wpf-ecommerce' ) );

			$order_data['oprid'] = $_COOKIE['oprid'];

		}

		$calc_totals = 0;

		// Get product IDs for each product
		foreach ( $order_args['products'] as $product ) {

			if ( empty( $product['crm_product_id'] ) ) {

				$product['crm_product_id'] = $this->add_product( $product );

				// Error handling for adding products
				if ( is_wp_error( $product['crm_product_id'] ) ) {
					return $product['crm_product_id'];
				}
			}

			$product_data = array(
				'name'     => $product['name'],
				'id'       => $product['crm_product_id'],
				'quantity' => $product['qty'],
				'sku'      => $product['sku'],
				// 'price'       => array(array('price' => $product['price'], 'payment_count' => 1, 'unit' => 'day')) (removed for customer Michael Bernstein using Brazilian currency payments)
			);

			if ( wp_fusion()->settings->get( 'ec_op_prices' ) == 'checkout' ) {
				$product_data['price'] = array(
					array(
						'price'         => $product['price'],
						'payment_count' => 1,
						'unit'          => 'day',
					),
				);
			}

			$order_data['offer']['products'][] = $product_data;

		}

		foreach ( $order_args['line_items'] as $line_item ) {

			if ( $line_item['type'] == 'shipping' ) {

				// Shipping doesn't work properly with the current API so we get around that by creating a pseudo-product for shipping fees

				$product_id = wp_fusion()->settings->get( 'ec_op_shipping' );

				if ( $product_id == 'no_shipping' ) {
					continue;
				}

				if ( $product_id == 'create' || empty( $product_id ) ) {

					// Create the product to handle shipping

					$product_data = array(
						'name'  => 'Shipping',
						'price' => 0,
						'id'    => 0,
					);

					$product_id = $this->add_product( $product_data );

					if ( is_wp_error( $product_id ) ) {
						return $product_id;
					}

					wp_fusion()->settings->set( 'ec_op_shipping', $product_id );

				}

				$product_data = array(
					'name'     => 'Shipping',
					'quantity' => 1,
					'id'       => $product_id,
					'price'    => array(
						array(
							'price'         => $line_item['price'],
							'payment_count' => 1,
							'unit'          => 'day'
						),
					),
				);

				$order_data['offer']['products'][] = $product_data;

			} elseif ( $line_item['type'] == 'tax' ) {

				// Taxes don't work yet because a tax object must first be registered in OP

			} elseif ( $line_item['type'] == 'discount' ) {

				// Discounts don't work properly with the current API, but we can adjust the prices of the other order items to reflect the discount and at least get a proper total

				if ( wp_fusion()->settings->get( 'ec_op_prices' ) == 'checkout' ) {

					$total_products = count( $order_data['offer']['products'] );

					$discount_per_product = $line_item['price'] / $total_products;

					foreach ( $order_data['offer']['products'] as $i => $product ) {

						$order_data['offer']['products'][ $i ]['price'][0]['price'] = round( $order_data['offer']['products'][ $i ]['price'][0]['price'] + $discount_per_product, 2 );

					}
				}
			} else {

				// Addons and other meta

				$product = array(
					'id'    => $line_item['id'],
					'name'  => $line_item['title'],
					'price' => 0
				);

				$product['crm_product_id'] = $this->add_product( $product );

				$product_data = array(
					'name'     => $product['name'],
					'id'       => $product['crm_product_id'],
					'quantity' => 1
				);

				$order_data['offer']['products'][] = $product_data;

			}
		}

		wpf_log(
			'info', $order_args['user_id'], 'Adding <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:', array(
				'meta_array_nofilter' => $order_data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params['body'] = json_encode( $order_data );

		$response = wp_remote_post( 'https://api.ontraport.com/1/transaction/processManual', $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body       = json_decode( wp_remote_retrieve_body( $response ), true );
		$invoice_id = $body['data']['invoice_id'];

		return $invoice_id;

	}


	/**
	 * Mark a previously added order as refunded
	 *
	 * @access  public
	 * @return  mixed Bool or WP_Error
	 */

	public function refund_order( $transaction_id, $refund_amount ) {

		if ( ! wp_fusion()->crm->params ) {
			wp_fusion()->crm->get_params();
		}

		$params = wp_fusion()->crm->params;

		$refund_data = array(
			'ids' => array( $transaction_id ),
		);

		$params['body']   = json_encode( $refund_data );
		$params['method'] = 'PUT';

		$response = wp_remote_request( 'https://api.ontraport.com/1/transaction/refund', $params );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;

	}

}
