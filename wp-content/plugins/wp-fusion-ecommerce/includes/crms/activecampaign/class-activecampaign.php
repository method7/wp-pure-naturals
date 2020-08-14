<?php

class WPF_EC_ActiveCampaign {

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

		$this->supports = array( 'refunds' );

		add_filter( 'wpf_compatibility_notices', array( $this, 'compatibility_notices' ) );

		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );
		add_filter( 'validate_field_deals_enabled', array( $this, 'validate_deals_enabled' ), 10, 2 );

		add_action( 'wpf_sync', array( $this, 'sync' ) );

		// Export functions
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_action( 'wpf_batch_activecampaign_customers_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_activecampaign_customers', array( $this, 'batch_step' ) );

		// Sync data on first run
		$pipelines_stages = wp_fusion()->settings->get( 'ac_pipelines_stages' );

		if ( $pipelines_stages != null && ! is_array( $pipelines_stages ) ) {
			$this->sync();
		}

	}

	/**
	 * Compatibility checks
	 *
	 * @access public
	 * @return array Notices
	 */

	public function compatibility_notices( $notices ) {

		if ( is_plugin_active( 'activecampaign-for-woocommerce/activecampaign-for-woocommerce.php' ) ) {

			$notices['ac-woo-plugin'] = 'The <strong>ActiveCampaign for WooCommerce</strong> plugin is active. You may get duplicate orders in ActiveCampaign if you\'re using WP Fusion\'s Enhanced Ecommerce addon and ActiveCampaign for WooCommerce at the same time.';

		}

		return $notices;

	}


	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		if ( ! isset( $options['deals_enabled'] ) ) {
			$options['deals_enabled'] = false;
		}

		$settings['ecommerce_header'] = array(
			'title'   => __( 'ActiveCampaign Deep Data Integration', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		$desc = false;

		if ( true == wp_fusion()->settings->get( 'deep_data_enabled' ) ) {

			$connection_id = wp_fusion()->crm->get_connection_id();

			if ( ! empty( $connection_id ) ) {

				$desc = '<span class="label label-success">' . sprintf( __( 'Connected with ID %s', 'wp-fusion' ), $connection_id ) . '</span>';

			} elseif ( false == $connection_id ) {

				$desc = '<span class="label label-danger">Upgrade your ActiveCampaign account to enable Deep Data</span>';

			}
		} elseif ( true == wp_fusion()->settings->get( 'connection_configured' ) ) {

			// Don't delete the connection when the settings are being set

			$desc = '<span class="label label-default">Disconnected</span>';

			$connection_id = get_option( 'wpf_ac_connection_id' );

			if ( ! empty( $connection_id ) ) {
				wp_fusion()->crm->delete_connection( $connection_id );
			}
		}

		$settings['deep_data_enabled'] = array(
			'title'   => __( 'Deep Data', 'wp-fusion' ),
			'desc'    => __( 'Use WP Fusion\'s deep data integration with ActiveCampaign for ecommerce data. ', 'wp-fusion' ) . $desc,
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'ecommerce',
		);

		$settings['deals_header'] = array(
			'title'   => __( 'ActiveCampaign Deals', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		if ( ! empty( $options['ac_pipelines_stages'] ) ) {

			$settings['deals_enabled'] = array(
				'title'   => __( 'Deals', 'wp-fusion' ),
				'desc'    => __( 'Add individual sales as Deals in ActiveCampaign.', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'checkbox',
				'section' => 'ecommerce',
				'unlock'  => array( 'deals_pipeline_stage', 'deals_owner' ),
			);

			$settings['deals_pipeline_stage'] = array(
				'title'       => __( 'Pipeline / Stage', 'wp-fusion' ),
				'type'        => 'select',
				'section'     => 'ecommerce',
				'placeholder' => 'Select a Pipeline / Stage',
				'choices'     => $options['ac_pipelines_stages'],
				'disabled'    => ( $options['deals_enabled'] == 0 ? true : false ),
			);

			if ( ! isset( $options['ac_owners'] ) ) {
				$options['ac_owners'] = array();
			}

			$settings['deals_owner'] = array(
				'title'       => __( 'Default Owner', 'wp-fusion' ),
				'type'        => 'select',
				'section'     => 'ecommerce',
				'placeholder' => 'Select an owner',
				'choices'     => $options['ac_owners'],
				'disabled'    => ( $options['deals_enabled'] == 0 ? true : false ),
				'desc'        => __( 'Select a default owner for deals.' ),
			);

		} else {

			$settings['deals_header']['desc'] = __( 'No pipelines or stages were detected in ActiveCampaign, so deal creation is disabled. To create deals with WP Fusion, first create a pipeline in ActiveCampaign and then click Resynchronize Available Tags & Fields from the Setup tab to load them.', 'wp-fusion' );

		}

		return $settings;

	}

	/**
	 * Validate deals/pipelines/stages to make sure it's not empty
	 *
	 * @access public
	 * @return string Input
	 */

	public function validate_deals_enabled( $input, $setting ) {

		if ( $input == true && empty( $_POST['wpf_options']['deals_pipeline_stage'] ) ) {
			return new WP_Error( 'error', 'You must specify an initial Pipeline and Stage to send Deals to ActiveCampaign' );
		} else {
			return $input;
		}

	}


	/**
	 * Syncs deals and pipelines on plugin install or when Resynchronize is clicked
	 *
	 * @since 1.0
	 * @return void
	 */

	public function sync() {

		$result = wp_fusion()->crm->connect();

		if ( is_wp_error( $result ) ) {
			wpf_log( $result->get_error_code(), 0, 'Error initializing sync: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return false;
		}

		$url = wp_fusion()->settings->get( 'ac_url' );
		$key = wp_fusion()->settings->get( 'ac_key' );

		$pipelines = array();

		$continue = true;
		$page     = 1;

		while ( $continue ) {

			$count = 0;

			$request = $url . '/admin/api.php?api_action=deal_pipeline_list&api_output=json&api_key=' . $key . '&page=' . $page;

			$response = wp_remote_get( $request );
			$response = json_decode( wp_remote_retrieve_body( $response ) );

			foreach ( $response as $result ) {

				if ( is_object( $result ) ) {

					$pipelines[ $result->id ] = $result->title;

					$count++;

				}
			}

			if ( $count < 20 ) {
				$continue = false;
			} else {
				$page++;
			}
		}

		$choices = array();

		$continue = true;
		$page     = 1;

		while ( $continue ) {

			$count = 0;

			$request = $url . '/admin/api.php?api_action=deal_stage_list&api_output=json&api_key=' . $key . '&page=' . $page;

			$response = wp_remote_get( $request );
			$response = json_decode( wp_remote_retrieve_body( $response ) );

			foreach ( $response as $result ) {

				if ( is_object( $result ) ) {

					$choices[ $result->pipeline . ',' . $result->id ] = $pipelines[ $result->pipeline ] . ' &raquo; ' . $result->title;

					$count++;

				}
			}

			if ( $count < 20 ) {
				$continue = false;
			} else {
				$page++;
			}
		}

		wp_fusion()->settings->set( 'ac_pipelines_stages', $choices );

		// Get deal owners
		$owners = array();

		$results = wp_fusion()->crm->app->api( 'user/list?ids=all' );

		foreach ( $results as $result ) {

			if ( is_object( $result ) ) {

				$owners[ $result->id ] = $result->first_name . ' ' . $result->last_name;

			}
		}

		wp_fusion()->settings->set( 'ac_owners', $owners );

	}


	/**
	 * Add an order
	 *
	 * @access  public
	 * @return  mixed Invoice ID or WP Error
	 */

	public function add_order( $order_id, $contact_id, $order_args ) {

		if ( empty( $order_args['order_date'] ) ) {
			$order_date = current_time( 'timestamp' );
		} else {
			$order_date = $order_args['order_date'];
		}

		// Convert date to GMT
		$offset      = get_option( 'gmt_offset' );
		$order_date -= $offset * 60 * 60;

		$order_date = new DateTime( date( 'c', $order_date ) );

		// DateTimeZone throws an error with 0 as the timezone
		if ( $offset >= 0 ) {
			$offset = '+' . $offset;
		}

		$order_date->setTimezone( new DateTimeZone( $offset ) );

		$order_args['order_date'] = $order_date;

		$result = null;

		// Sync deals
		if ( wp_fusion()->settings->get( 'deals_enabled' ) ) {
			$result = $this->add_deal( $order_id, $contact_id, $order_args );
		}

		// Sync Deep Data
		if ( wp_fusion()->settings->get( 'deep_data_enabled' ) ) {
			$result = $this->add_deep_data( $order_id, $contact_id, $order_args );
		}

		return $result;

	}


	/**
	 * Sync deal
	 *
	 * @access  public
	 * @return  mixed Invoice ID or WP Error
	 */

	public function add_deal( $order_id, $contact_id, $order_args ) {

		$pipeline_stage = wp_fusion()->settings->get( 'deals_pipeline_stage' );

		if ( empty( $pipeline_stage ) ) {
			return;
		}

		$pipeline_stage = explode( ',', $pipeline_stage );

		$pipeline = $pipeline_stage[0];
		$stage    = $pipeline_stage[1];

		$data = array(
			'title'     => $order_args['order_label'],
			'value'     => $order_args['total'],
			'currency'  => strtolower( $order_args['currency'] ),
			'pipeline'  => $pipeline,
			'stage'     => $stage,
			'contactid' => $contact_id,
		);

		$owner = wp_fusion()->settings->get( 'deals_owner' );

		if ( ! empty( $owner ) ) {
			$data['owner'] = $owner;
		}

		$result = wp_fusion()->crm->connect();

		if ( is_wp_error( $result ) ) {
			wpf_log( $result->get_error_code(), 0, 'Error adding deal: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return false;
		}

		$data = apply_filters( 'wpf_ecommerce_activecampaign_add_deal', $data, $order_id );

		wpf_log(
			'info', $order_args['user_id'], 'Syncing Deal for <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:', array(
				'meta_array_nofilter' => $data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$result = wp_fusion()->crm->app->api( 'deal/add', $data );

		// Add note
		if ( 1 == $result->success ) {

			$note = 'Product(s) purchased:' . PHP_EOL;

			foreach ( $order_args['products'] as $product ) {
				$note .= $product['name'] . ' - ' . $order_args['currency'] . ' ' . number_format( $product['price'], 2, '.', '' ) . ( $product['qty'] > 1 ? ' (x' . $product['qty'] . ')' : '' ) . PHP_EOL;
			}

			$note .= PHP_EOL . $order_args['order_edit_link'];

			$note_result = wp_fusion()->crm->app->api(
				'deal/note_add', array(
					'dealid' => $result->id,
					'note'   => $note,
					'owner'  => $result->owner,
				)
			);

			update_post_meta( $order_id, 'wpf_ac_deal', $result->id );

			do_action( 'wpf_ecommerce_activecampaign_deal_added', $result->id, $data );

			if ( wp_fusion()->settings->get( 'deep_data_enabled' ) != true ) {
				return $result->id;
			}
		} else {

			return new WP_Error( 'error', $result->message );

		}
	}

	/**
	 * Syncs Deep Data
	 *
	 * @access  public
	 * @return  mixed Invoice ID or WP Error
	 */

	public function add_deep_data( $order_id, $contact_id, $order_args ) {

		$connection_id = wp_fusion()->crm->get_connection_id();
		$customer_id   = wp_fusion()->crm->get_customer_id( $contact_id, $connection_id );

		if ( false == $customer_id ) {

			wpf_log( 'info', $order_args['user_id'], 'Unable to sync order <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>, couldn\'t find or create Customer in ActiveCampaign.', array( 'source' => 'wpf-ecommerce' ) );
			return;

		}

		$product_objects = array();

		foreach ( $order_args['products'] as $product ) {

			$product_data = array(
				'externalid' => $product['id'],
				'name'       => $product['name'],
				'price'      => $product['price'] * 100,
				'quantity'   => $product['qty'],
				'productUrl' => get_permalink( $product['id'] ),
			);

			if ( ! empty( $product['image'] ) ) {
				$product_data['imageUrl'] = $product['image'];
			}

			if ( ! empty( $product['sku'] ) ) {
				$product_data['sku'] = $product['sku'];
			}

			$product_objects[] = (object) $product_data;

		}

		// Line items with prices
		foreach ( $order_args['line_items'] as $line_item ) {

			// Only sync addons (if enabled). Taxes / shipping / discounts have their own key in the order payload
			if ( $line_item['type'] == 'addon' ) {

				$product_data = array(
					'externalid' => $line_item['id'],
					'name'       => $line_item['title'],
					'price'      => $line_item['price'] * 100,
					'quantity'   => 1,
				);

				$product_objects[] = (object) $product_data;

			}
		}

		$body = array(
			'ecomOrder' => array(
				'externalid'    => $order_id,
				'source'        => 1,
				'email'         => $order_args['user_email'],
				'orderNumber'   => $order_args['order_number'],
				'orderProducts' => $product_objects,
				'orderUrl'      => $order_args['order_edit_link'],
				'orderDate'     => $order_args['order_date']->format( 'c' ),
				'totalPrice'    => ( $order_args['total'] * 100 ),
				'currency'      => strtoupper( $order_args['currency'] ),
				'connectionid'  => $connection_id,
				'customerid'    => $customer_id,
			),
		);

		// Get shipping and discounts

		foreach ( $order_args['line_items'] as $line_item ) {

			if ( isset( $line_item['type'] ) && $line_item['type'] == 'shipping' ) {

				$body['ecomOrder']['shippingMethod'] = $line_item['description'];
				$body['ecomOrder']['shippingAmount'] = ( $line_item['price'] * 100 );

			} elseif ( isset( $line_item['type'] ) && $line_item['type'] == 'discount' ) {

				$body['ecomOrder']['discountAmount'] = abs( $line_item['price'] * 100 );

			} elseif ( isset( $line_item['type'] ) && $line_item['type'] == 'tax' ) {

				$body['ecomOrder']['taxAmount'] = ( $line_item['price'] * 100 );

			}
		}

		// Logging

		wpf_log(
			'info', $order_args['user_id'], 'Syncing Deep Data for <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:', array(
				'meta_array_nofilter' => $body,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params         = wp_fusion()->crm->get_params();
		$params['body'] = json_encode( $body );

		$transient = get_transient( 'wpf_abandoned_cart_' . $contact_id );

		if ( ! empty( $transient ) && ! empty( $transient['order_id'] ) ) {

			// Maybe update an existing order that was sent as a cart
			$params['method'] = 'PUT';

			$response = wp_remote_request( wp_fusion()->crm->api_url . '/api/3/ecomOrders/' . $transient['order_id'], $params );

			delete_transient( 'wpf_abandoned_cart_' . $contact_id );

		} else {

			// Log a completely new order
			$response = wp_remote_post( wp_fusion()->crm->api_url . '/api/3/ecomOrders', $params );

		}

		if ( is_wp_error( $response ) && 'The related ecomCustomer does not exist.' == $response->get_error_message() && ! empty( $order_args['user_id'] ) ) {

			// Maybe try to clear out the ecom customer ID and try again if it's a registered user

			$customer_id = get_user_meta( $order_args['user_id'], 'wpf_ac_customer_id', true );

			if ( ! empty( $customer_id ) ) {

				wpf_log(
					'notice', $order_args['user_id'], 'Error adding order, "The related ecomCustomer does not exist", for ecomCustomer #' . $customer_id . '. Clearing cached customer ID and trying again...', array(
						'source' => 'wpf-ecommerce',
					)
				);

				delete_user_meta( $order_args['user_id'], 'wpf_ac_customer_id' );

				$result = $this->add_deep_data( $order_id, $contact_id, $order_args );

				return $result;

			} else {

				return $response;

			}
		} elseif ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( is_array( $body ) && isset( $body['ecomOrder']['id'] ) ) {

			do_action( 'wpf_ecommerce_activecampaign_order_added', $body['ecomOrder']['id'], $body );

			return $body['ecomOrder']['id'];

		} else {

			return new WP_Error( 'error', wp_remote_retrieve_body( $response ) );

		}

	}


	/**
	 * Refund an order
	 *
	 * @access  public
	 * @return  mixed Bool or WP_Error
	 */

	public function refund_order( $transaction_id, $refund_amount ) {

		$body = array(
			'ecomOrder' => array(
				'totalPrice' => 0,
			),
		);

		$args = array(
			'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'    => json_encode( $body ),
			'method'  => 'PUT',
		);

		$api_url = wp_fusion()->settings->get( 'ac_url' );
		$api_key = wp_fusion()->settings->get( 'ac_key' );

		$response = wp_remote_request( $api_url . '/api/3/ecomOrders/' . $transaction_id . '?api_key=' . $api_key, $args );
		$body     = json_decode( wp_remote_retrieve_body( $response ) );

		if ( is_object( $body ) && isset( $body->errors ) ) {

			return new WP_Error( 'error', $body->errors[0]->title );

		}

		return true;

	}


	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds option to batch tools list
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['activecampaign_customers'] = array(
			'label'   => __( 'ActiveCampaign Deep Data customer IDs', 'wp-fusion' ),
			'title'   => __( 'Customers', 'wp-fusion' ),
			'tooltip' => __( 'Look up the ActiveCampaign customer IDs for each user based on email address.', 'wp-fusion' ),
		);

		return $options;

	}

	/**
	 * Counts total number of users to be processed
	 *
	 * @access public
	 * @return int Count
	 */

	public function batch_init() {

		$connection_id = get_option( 'wpf_ac_connection_id' );

		if ( empty( $connection_id ) ) {
			return array();
		}

		$args = array( 'fields' => 'ID' );

		$users = get_users( $args );

		wpf_log( 'info', 0, 'Beginning <strong>ActiveCampaign Deep Data customers</strong> batch operation on ' . count( $users ) . ' users', array( 'source' => 'batch-process' ) );

		return $users;

	}

	/**
	 * Processes users in steps
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $user_id ) {

		$customer_id = false;

		$api_url       = wp_fusion()->settings->get( 'ac_url' );
		$api_key       = wp_fusion()->settings->get( 'ac_key' );
		$connection_id = get_option( 'wpf_ac_connection_id' );

		$user = get_userdata( $user_id );

		$response = wp_remote_get( $api_url . '/api/3/ecomCustomers?api_key=' . $api_key . '&filters[email]=' . $user->user_email . '&filters[connectionid]=' . $connection_id );

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! empty( $body->ecomCustomers ) ) {
			$customer_id = $body->ecomCustomers[0]->id;
		}

		$current_customer_id = get_user_meta( $user_id, 'wpf_ac_customer_id', true );

		if ( false !== $customer_id || ! empty( $current_customer_id ) ) {
			update_user_meta( $user_id, 'wpf_ac_customer_id', $customer_id );
		}

	}

}
