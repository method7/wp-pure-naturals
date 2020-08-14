<?php

class WPF_EC_Drip {

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

		$this->supports = array( 'status_changes' );

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

	}


	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		if ( ! isset( $options['conversions_enabled'] ) ) {
			$options['conversions_enabled'] = true;
		}

		$settings['ecommerce_header'] = array(
			'title'   => __( 'Drip Ecommerce Tracking', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		$settings['orders_enabled'] = array(
			'title'   => __( 'Orders', 'wp-fusion' ),
			'desc'    => __( 'Create an <a href="https://help.drip.com/hc/en-us/articles/360002603911-Orders" target="_blank">order</a> in Drip for each sale.', 'wp-fusion' ),
			'std'     => 1,
			'type'    => 'checkbox',
			'section' => 'ecommerce',
			'unlock'  => array( 'orders_api_version' ),
		);

		$settings['orders_api_version'] = array(
			'title'   => __( 'API Version', 'wp-fusion' ),
			'desc'    => __( 'The Shopper Activity API is Drip\'s newer ecommerce API. <a href="https://wpfusion.com/documentation/ecommerce-tracking/drip-ecommerce/" target="_blank">See the documentation</a> for more info.', 'wp-fusion' ),
			'std'     => 'v3',
			'type'    => 'radio',
			'section' => 'ecommerce',
			'choices' => array(
				'v3' => __( 'Shopper Activity API' ),
				'v2' => __( 'Orders API' ),
			),
		);

		$settings['conversions_enabled'] = array(
			'title'   => __( 'Events (Legacy Feature)', 'wp-fusion' ),
			'desc'    => __( 'Record an <a href="https://help.drip.com/hc/en-us/articles/115003757391-Events" target="_blank">event</a> in Drip for each sale, in addition to the order data.', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'ecommerce',
			'tooltip' => __( 'This is a old feature from before Drip had APIs for ecommerce data. It\'s recommended to leave this disabled as it will make your checkout slower.', 'wp-fusion' ),
		);

		return $settings;

	}


	/**
	 * Add an order
	 *
	 * @access  public
	 * @return  bool
	 */

	public function add_order( $order_id, $contact_id, $order_args ) {

		if ( empty( $order_args['order_date'] ) ) {
			$order_date = current_time( 'timestamp' );
		} else {
			$order_date = $order_args['order_date'];
		}

		$result = true;

		// Convert date
		$offset      = get_option( 'gmt_offset' );
		$order_date -= $offset * 60 * 60;

		$order_date = new DateTime( date( 'c', $order_date ) );

		// DateTimeZone throws an error with 0 as the timezone
		if ( $offset >= 0 ) {
			$offset = '+' . $offset;
		}

		$order_date->setTimezone( new DateTimeZone( $offset ) );

		$items    = array();
		$discount = 0;
		$shipping = 0;
		$tax      = 0;

		if ( wp_fusion()->settings->get( 'orders_enabled', true ) == true && wp_fusion()->settings->get( 'orders_api_version' ) == 'v3' ) {

			// v3 API
			// Build up items array
			foreach ( $order_args['products'] as $product ) {

				// For Woo Dynamic Pricing addon. Drip throws an error if a price is less than 0
				if ( floatval( $product['price'] ) < 0 ) {

					$discount += abs( $product['price'] );
					continue;
				}

				$item_data = array(
					'product_id'  => (string) $product['id'],
					'sku'         => $product['sku'],
					'price'       => round( $product['price'], 2 ),
					'name'        => substr( $product['name'], 0, 255 ),
					'quantity'    => intval( $product['qty'] ),
					'total'       => round( ( $product['price'] * $product['qty'] ), 2 ),
					'product_url' => get_permalink( $product['id'] ),
					'image_url'   => $product['image'],
					'categories'  => $product['categories'],
				);

				// Clean up possible empty values
				foreach ( $item_data as $key => $value ) {

					if ( empty( $value ) ) {
						unset( $item_data[ $key ] );
					}
				}

				if ( ! isset( $item_data['price'] ) ) {
					$item_data['price'] = 0;
				}

				$items[] = (object) $item_data;

			}

			foreach ( $order_args['line_items'] as $line_item ) {

				if ( $line_item['type'] == 'shipping' ) {

					// Shipping
					$items[] = (object) array(
						'product_id' => 'SHIPPING',
						'price'      => round( $line_item['price'], 2 ),
						'total'      => round( $line_item['price'], 2 ),
						'name'       => substr( $line_item['title'] . ' - ' . $line_item['description'], 0, 255 ),
						'quantity'   => 1,
					);

					$shipping += abs( $line_item['price'] );

				} elseif ( $line_item['type'] == 'tax' ) {

					// Tax
					$tax += abs( $line_item['price'] );

				} elseif ( $line_item['type'] == 'discount' ) {

					// Discounts
					$discount += abs( $line_item['price'] );

				} else {

					// Addons & variations
					$item_data = array(
						'product_id' => (string) $line_item['id'],
						'sku'        => $line_item['sku'],
						'price'      => round( $line_item['price'], 2 ),
						'name'       => substr( $line_item['title'], 0, 255 ),
						'quantity'   => intval( $line_item['qty'] ),
						'total'      => round( ( $line_item['price'] * $line_item['qty'] ), 2 ),
					);

					// Clean up possible empty values
					foreach ( $item_data as $key => $value ) {

						if ( empty( $value ) ) {
							unset( $item_data[ $key ] );
						}
					}

					if ( ! isset( $item_data['price'] ) ) {
						$item_data['price'] = 0;
					}

					$items[] = (object) $item_data;

				}
			}

			if ( isset( $order_args['action'] ) ) {
				$action = $order_args['action'];
			} else {
				$action = 'placed';
			}

			$order = (object) array(
				'provider'        => $order_args['provider'],
				'person_id'       => $contact_id,
				'action'          => $action,
				'occurred_at'     => $order_date->format( 'c' ),
				'order_id'        => (string) $order_id,
				'order_public_id' => $order_args['order_label'],
				'grand_total'     => round( $order_args['total'], 2 ),
				'total_discounts' => round( $discount, 2 ),
				'total_taxes'     => round( $tax, 2 ),
				'total_shipping'  => round( $shipping, 2 ),
				'currency'        => $order_args['currency'],
				'order_url'       => $order_args['order_edit_link'],
				'items'           => array_reverse( $items ),
			);

			if ( ! empty( $order_args['refund_amount'] ) ) {
				$order->refund_amount = $order_args['refund_amount'];
			}

			wpf_log(
				'info', $order_args['user_id'], 'Adding <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:', array(
					'meta_array_nofilter' => $order,
					'source'              => 'wpf-ecommerce',
				)
			);

			$api_token  = wp_fusion()->settings->get( 'drip_token' );
			$account_id = wp_fusion()->settings->get( 'drip_account' );

			$params = array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $api_token ),
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode( $order ),
				'timeout' => 30,
			);

			$request  = 'https://api.getdrip.com/v3/' . $account_id . '/shopper_activity/order';
			$response = wp_remote_post( $request, $params );

			if ( is_wp_error( $response ) ) {

				wpf_log( $response->get_error_code(), $order_args['user_id'], 'Error adding order: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
				return $response;

			} else {

				$body   = json_decode( wp_remote_retrieve_body( $response ) );
				$result = $body->request_id;

			}
		} elseif ( wp_fusion()->settings->get( 'orders_enabled', true ) == true ) {

			// v2 API
			foreach ( $order_args['products'] as $product ) {

				if ( ! isset( $product['price'] ) ) {
					$product['price'] = 0;
				}

				$items[] = (object) array(
					'product_id' => $product['id'],
					'sku'        => $product['sku'],
					'amount'     => floor( floatval( $product['price'] ) * 100 * $product['qty'] ),
					'price'      => floor( floatval( $product['price'] ) * 100 ),
					'name'       => $product['name'],
					'quantity'   => intval( $product['qty'] ),
				);

			}

			foreach ( $order_args['line_items'] as $line_item ) {

				if ( $line_item['type'] == 'shipping' ) {

					// Shipping
					$items[] = (object) array(
						'amount'   => floor( floatval( $line_item['price'] ) * 100 ),
						'price'    => floor( floatval( $line_item['price'] ) * 100 ),
						'name'     => $line_item['title'],
						'quantity' => 1,
					);

				} elseif ( $line_item['type'] == 'tax' ) {

					// Tax
					$tax += abs( $line_item['price'] );

				} elseif ( $line_item['type'] == 'discount' ) {

					// Discounts
					$discount += abs( $line_item['price'] );

				} else {

					// Addons & variations
					$items[] = (object) array(
						'product_id' => $line_item['id'],
						'sku'        => $line_item['sku'],
						'amount'     => floor( floatval( $line_item['price'] ) * 100 * $line_item['qty'] ),
						'price'      => floor( floatval( $line_item['price'] ) * 100 ),
						'name'       => $line_item['name'],
						'quantity'   => intval( $line_item['qty'] ),
					);

				}
			}

			$order = array(
				'id'          => $contact_id,
				'provider'    => $order_args['order_label'],
				'amount'      => floor( floatval( $order_args['total'] ) * 100 ),
				'permalink'   => $order_args['order_edit_link'],
				'occurred_at' => $order_date->format( 'c' ),
				'discount'    => floatval( $discount ) * 100,
				'tax'         => floatval( $tax ) * 100,
				'items'       => array_reverse( $items ),
			);

			$orders = (object) array( 'orders' => array( (object) $order ) );

			wpf_log(
				'info', $order_args['user_id'], 'Adding <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:', array(
					'meta_array_nofilter' => $orders,
					'source'              => 'wpf-ecommerce',
				)
			);

			$api_token  = wp_fusion()->settings->get( 'drip_token' );
			$account_id = wp_fusion()->settings->get( 'drip_account' );

			$request = 'https://api.getdrip.com/v2/' . $account_id . '/orders';

			$params = array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $api_token ),
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode( $orders ),
				'timeout' => 30,
			);

			$response = wp_remote_post( $request, $params );

			if ( is_wp_error( $response ) ) {

				wpf_log( $response->get_error_code(), $order_args['user_id'], 'Error adding order: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
				return $response;

			}
		} else {
			return null;
		}

		if ( wp_fusion()->settings->get( 'conversions_enabled' ) == true && isset( $order->action ) && $order->action == 'placed' ) {

			// API request for events (when purchase is made)
			$events = array(
				'events' => array(
					(object) array(
						'email'       => wp_fusion()->crm->get_email_from_cid( $contact_id ),
						'occurred_at' => $order_date->format( 'c' ),
						'action'      => 'Conversion',
						'properties'  => array(
							'value' => intval( round( floatval( $order_args['total'] ), 2 ) * 100 ),
						),
					),
				),
			);

			$request        = 'https://api.getdrip.com/v2/' . $account_id . '/events/';
			$params['body'] = json_encode( $events );

			$response = wp_remote_post( $request, $params );

			if ( is_wp_error( $response ) ) {

				wpf_log( $response->get_error_code(), $order_args['user_id'], 'Error adding Event: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
				return $response;

			}
		}

		return $result;

	}

	/**
	 * Updated order statuses in Drip when statuses are changed in Woo
	 *
	 * @access  public
	 * @return  mixed Bool or WP_Error
	 */

	public function order_status_changed( $order_id, $contact_id, $status, $args ) {

		switch ( $status ) {

			case 'completed':
				$args['action'] = 'fulfilled';
				break;

			case 'cancelled':
				$args['action'] = 'canceled';
				break;

			case 'refunded':
				$args['action'] = 'refunded';
				break;

			default:
				return false;
		}

		if ( wp_fusion()->settings->get( 'orders_enabled', true ) == true && wp_fusion()->settings->get( 'orders_api_version' ) == 'v3' ) {

			$response = $this->add_order( $order_id, $contact_id, $args );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			return true;

		}

		return false;

	}

}
