<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_EC_Woocommerce extends WPF_EC_Integrations_Base {

	/**
	 * Get things started
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		$this->slug = 'woocommerce';

		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 25, 2 );

		add_action( 'wpf_woocommerce_payment_complete', array( $this, 'send_order_data' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 4 );

		// Product panels
		add_action( 'wpf_woocommerce_panel', array( $this, 'product_panel' ), 20 );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ) );

		// Variable product panels
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'product_variation_panel' ), 16, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variable_fields' ), 10, 2 );

		// Super secret admin / debugging tools
		add_action( 'wpf_settings_page_init', array( $this, 'settings_page_init' ) );

		// Export functions
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_action( 'wpf_batch_woocommerce_ecom_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_woocommerce_ecom', array( $this, 'batch_step' ) );

	}

	/**
	 * Adds Addons tab if not already present
	 *
	 * @access public
	 * @return void
	 */

	public function configure_sections( $page, $options ) {

		if ( ! isset( $page['sections']['ecommerce'] ) ) {
			$page['sections'] = wp_fusion()->settings->insert_setting_before( 'import', $page['sections'], array( 'ecommerce' => __( 'Enhanced Ecommerce', 'wp-fusion' ) ) );
		}

		return $page;

	}


	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['ec_woo_header'] = array(
			'title'   => __( 'Misc. Ecommerce Settings', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		$settings['ec_woo_attributes'] = array(
			'title'   => __( 'Sync Attributes', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => 0,
			'section' => 'ecommerce',
			'desc'    => __( 'Sync selected product attributes and meta data as separate line items.', 'wp-fusion' ),
			'tooltip' => __( 'This is useful when using addon plugins for WooCommerce that attach metadata to ordered products, for example WooCommerce Product Addons or FooEvents', 'wp-fusion' ),
		);

		if ( isset( wp_fusion_ecommerce()->crm->supports ) && in_array( 'deal_stages', wp_fusion_ecommerce()->crm->supports ) ) {

			$settings['ec_woo_header_stages'] = array(
				'title'   => __( 'WooCommerce Order Status Stages', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'heading',
				'section' => 'ecommerce',
				'desc'    => sprintf( __( 'For each order status in WooCommerce, select a corresponding pipeline and stage in %s. When the order status is updated the deal\'s stage will also be changed.', 'wp-fusion' ), wp_fusion()->crm->name ),
			);

			if ( ! isset( $options[ wp_fusion()->crm->slug . '_pipelines' ] ) ) {
				$options[ wp_fusion()->crm->slug . '_pipelines' ] = array();
			}

			$statuses = wc_get_order_statuses();

			foreach ( $statuses as $key => $label ) {

				$settings[ 'ec_woo_status_' . $key ] = array(
					'title'       => $label,
					'type'        => 'select',
					'section'     => 'ecommerce',
					'placeholder' => 'Select a Pipeline / Stage',
					'choices'     => $options[ wp_fusion()->crm->slug . '_pipelines' ],
				);

			}
		}

		return $settings;

	}

	/**
	 * Shows configured CRM product corresponding to Woo product (simple products)
	 *
	 * @access  public
	 * @return  mixed
	 */

	public function product_panel() {

		if ( ! in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {
			return;
		}

		global $post;

		$product_id         = get_post_meta( $post->ID, wp_fusion()->crm->slug . '_product_id', true );
		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		echo '<p class="form-field hide_if_variable"><label><strong>Enhanced Ecommerce</strong></label></p>';

		echo '<p class="form-field hide_if_variable"><label for="wpf-ec-product">' . wp_fusion()->crm->name . ' Product</label>';

		echo '<select id="wpf-ec-product" class="select4-search" data-placeholder="None" name="' . wp_fusion()->crm->slug . '_product_id">';

			echo '<option></option>';

		foreach ( $available_products as $id => $name ) {

			echo '<option value="' . $id . '"' . selected( $id, $product_id, false ) . '>' . $name . ' (#' . $id . ')</option>';
		}

		echo '</select>';

		echo '</p>';

		echo '<p class="form-field show_if_variable"><label></label>' . wp_fusion()->crm->name . ' product assignment for variations can be configured within the Variations tab.</p>';

	}

	/**
	 * Adds product select to variable fields
	 *
	 * @access public
	 * @return mixed
	 */

	public function product_variation_panel( $loop, $variation_data, $variation ) {

		if ( ! in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {
			return;
		}

		$product_id         = get_post_meta( $variation->ID, wp_fusion()->crm->slug . '_product_id', true );
		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		echo '<div><p class="form-row form-row-full">';

			echo '<label for="wpf-ec-product-variation-' . $variation->ID . '">' . wp_fusion()->crm->name . ' product:</label>';

			echo '<select id="wpf-ec-product-variation-' . $variation->ID . '" class="select4-search" data-placeholder="None" name="' . wp_fusion()->crm->slug . '_product_id[' . $variation->ID . ']">';

				echo '<option></option>';

		foreach ( $available_products as $id => $name ) {
			echo '<option value="' . $id . '"' . selected( $id, $product_id, false ) . '>' . $name . '</option>';
		}

			echo '</select>';

		echo '</p></div>';

	}

	/**
	 * Saves variable field data to product
	 *
	 * @access public
	 * @return void
	 */


	public function save_variable_fields( $variation_id, $i ) {

		if ( isset( $_POST[ wp_fusion()->crm->slug . '_product_id' ] ) ) {

			update_post_meta( $variation_id, wp_fusion()->crm->slug . '_product_id', $_POST[ wp_fusion()->crm->slug . '_product_id' ][ $variation_id ] );

		}

	}

	/**
	 * Saves CRM product ID selected in dropdown
	 *
	 * @access public
	 * @return mixed
	 */

	public function save_meta_box_data( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_meta_box_woo_nonce'] ) || ! isset( $_POST[ wp_fusion()->crm->slug . '_product_id' ] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpf_meta_box_woo_nonce'], 'wpf_meta_box_woo' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Don't update on revisions
		if ( $_POST['post_type'] == 'revision' ) {
			return;
		}

		update_post_meta( $post_id, wp_fusion()->crm->slug . '_product_id', $_POST[ wp_fusion()->crm->slug . '_product_id' ] );

	}

	/**
	 * Gets order args for creating / updating orders
	 *
	 * @access  public
	 * @return  array Order Args
	 */

	public function get_order_args( $order ) {

		// Build array of products / subscriptions
		$products = array();

		// Array of line items
		$line_items = array();

		foreach ( $order->get_items() as $item_id => $item ) {

			$product_id = $item->get_product_id();

			// Deal with deleted products
			if ( empty( $product_id ) ) {
				$product_id = $item_id;
			}

			$crm_product_id = get_post_meta( $product_id, wp_fusion()->crm->slug . '_product_id', true );

			$name = str_replace( ' &ndash;', ': ', $item->get_name() );
			$name = wp_strip_all_tags( $name );

			$product_data = array(
				'id'             => $product_id,
				'crm_product_id' => $crm_product_id,
				'name'           => $name,
				'qty'            => $item->get_quantity(),
				'subtotal'       => round( $item->get_subtotal(), 2 ),
				'sku'            => get_post_meta( $item->get_product_id(), '_sku', true ),
				'image'          => get_the_post_thumbnail_url( $product_id, 'medium' ),
				'categories'     => array(),
			);

			// Add categories
			$categories = get_the_terms( $item->get_product_id(), 'product_cat' );

			if ( ! empty( $categories ) ) {

				foreach ( $categories as $category ) {

					$product_data['categories'][] = $category->name;

				}
			}

			$product_data['price'] = $product_data['subtotal'] / (int) $product_data['qty'];

			if ( wc_prices_include_tax() ) {

				$total_tax              = $item->get_total_tax();
				$product_data['price'] += (float) $total_tax / (int) $product_data['qty'];

			}

			// Treat variations as separate products
			if ( $item->get_variation_id() != 0 ) {
				$product_data['id']             = $item->get_variation_id();
				$product_data['sku']            = get_post_meta( $item->get_variation_id(), '_sku', true );
				$product_data['crm_product_id'] = get_post_meta( $item->get_variation_id(), wp_fusion()->crm->slug . '_product_id', true );

				$image = get_the_post_thumbnail_url( $item->get_variation_id(), 'medium' );

				if ( ! empty( $image ) ) {
					$product_data['image'] = $image;
				}
			}

			// Make sure CRM product ID still exists
			$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

			if ( ! empty( $product_data['crm_product_id'] ) && ! isset( $available_products[ $product_data['crm_product_id'] ] ) ) {
				$product_data['crm_product_id'] = false;
			}

			// If it's not set try and find a match
			if ( empty( $product_data['crm_product_id'] ) && is_array( $available_products ) ) {
				$product_data['crm_product_id'] = array_search( $product_data['name'], $available_products );
			}

			$products[] = $product_data;

			if ( wp_fusion()->settings->get( 'ec_woo_attributes', true ) == true ) {

				// Add meta (for WC Addons)
				$item_meta = $item->get_meta_data();

				if ( ! empty( $item_meta ) ) {

					foreach ( $item_meta as $meta ) {

						if ( is_a( $meta, 'WC_Meta_Data' ) ) {

							$data = $meta->get_data();

							if ( empty( $data['id'] ) || empty( $data['key'] ) ) {
								continue;
							}

							// Ignore hidden fields
							if ( substr( $data['key'], 0, 1 ) === '_' || is_array( $data['key'] ) || is_array( $data['value'] ) ) {
								continue;
							}

							$data['key'] = str_replace( '&#36;', '$', $data['key'] );

							$product_data = array(
								'id'       => $data['id'],
								'title'    => $data['key'] . ': ' . $data['value'],
								'qty'      => 1,
								'subtotal' => 0,
								'sku'      => '',
								'type'     => 'addon',
								'price'    => null,
							);

							$line_items[] = $product_data;

						}
					}
				}
			}
		}

		// Add line item for taxes
		if ( $order->get_total_tax() > 0.0 ) {
			$line_items[] = array(
				'type'        => 'tax',
				'price'       => $order->get_total_tax(),
				'title'       => 'Tax',
				'description' => 'Tax',
			);
		}

		// Add line item for shipping
		$shipping_total = $order->get_shipping_total();

		if ( $shipping_total > 0.0 ) {
			$line_items[] = array(
				'type'        => 'shipping',
				'price'       => $shipping_total,
				'title'       => 'Shipping',
				'description' => $order->get_shipping_method(),
			);
		}

		// Add coupons
		if ( $order->get_total_discount() > 0.00 ) {
			$line_items[] = array(
				'type'        => 'discount',
				'price'       => -$order->get_total_discount(),
				'title'       => 'Discount',
				'description' => 'Woocommerce Shop Coupon Code',
			);
		}

		// Backwards compatibility

		$payment_method_title = $order->get_payment_method_title();
		$currency             = $order->get_currency();
		$user_id              = $order->get_user_id();

		// Get fees

		foreach ( $order->get_items( 'fee' ) as $item_id => $item ) {

			if ( empty( $item->get_total() ) ) {
				continue;
			}

			if ( $item->get_total() >= 0 ) {

				// Fees

				$line_items[] = array(
					'id'          => $item_id,
					'type'        => 'fee',
					'price'       => $item->get_total(),
					'title'       => $item->get_name(),
					'description' => $item->get_name(),
				);

			} elseif ( $item->get_total() < 0 ) {

				// Discounts

				$line_items[] = array(
					'id'          => $item_id,
					'type'        => 'discount',
					'price'       => $item->get_total(),
					'title'       => $item->get_name(),
					'description' => $item->get_name(),
				);

			}
		}

		// Date
		$order_date = $order->get_date_paid();

		if ( is_object( $order_date ) && isset( $order_date->date ) ) {
			$order_date = strtotime( $order_date->date );
		} else {
			$order_date = get_the_date( 'U', $order->get_id() );
		}

		$order_number = $order->get_order_number();

		$order_args = array(
			'order_label'     => 'WooCommerce Order #' . $order_number,
			'order_number'    => $order_number,
			'order_edit_link' => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
			'payment_method'  => $payment_method_title,
			'products'        => $products,
			'user_email'      => $order->get_billing_email(),
			'line_items'      => $line_items,
			'total'           => $order->get_total(),
			'currency'        => $currency,
			'currency_symbol' => get_woocommerce_currency_symbol( $currency ),
			'order_date'      => $order_date,
			'provider'        => 'woocommerce',
			'user_id'         => $user_id,
		);

		return $order_args;

	}

	/**
	 * Sends order data to CRM's ecommerce system
	 *
	 * @access  public
	 * @return  void
	 */

	public function send_order_data( $order_id, $contact_id ) {

		$wpf_complete = get_post_meta( $order_id, 'wpf_ec_complete', true );

		if ( ! empty( $wpf_complete ) ) {
			return true;
		}

		$order = wc_get_order( $order_id );

		$order_args = $this->get_order_args( $order );

		// Add order
		$result = wp_fusion_ecommerce()->crm->add_order( $order_id, $contact_id, $order_args );

		if ( is_wp_error( $result ) ) {

			wpf_log( 'error', $user_id, 'Error adding WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>: ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			$order->add_order_note( 'Error creating order in ' . wp_fusion()->crm->name . '. Error: ' . $result->get_error_message() );

			return false;
		}

		if ( $result === true ) {

			$order->add_order_note( wp_fusion()->crm->name . ' invoice successfully created.' );

		} elseif ( $result != null ) {

			// CRMs with invoice IDs
			$order->add_order_note( wp_fusion()->crm->name . ' invoice #' . $result . ' successfully created.' );
			update_post_meta( $order_id, 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', $result );

		}

		// Denotes that the WPF actions have already run for this order
		update_post_meta( $order_id, 'wpf_ec_complete', true );

		do_action( 'wpf_ecommerce_complete', $order_id, $result, $contact_id, $order_args );

	}


	/**
	 * Handles changed order statuses
	 *
	 * @access  public
	 * @return  void
	 */

	public function order_status_changed( $order_id, $from_status, $to_status, $order ) {

		$user_id = $order->get_user_id();

		if ( in_array( 'deal_stages', wp_fusion_ecommerce()->crm->supports ) ) {

			$invoice_id = get_post_meta( $order_id, 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', true );

			$stage = wp_fusion()->settings->get( 'ec_woo_status_wc-' . $to_status );

			if ( ! empty( $invoice_id ) && ! empty( $stage ) ) {

				$result = wp_fusion_ecommerce()->crm->change_stage( $invoice_id, $stage );

				if ( is_wp_error( $result ) ) {

					wpf_log( 'error', $user_id, 'Error changing status for WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>: ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
					$order->add_order_note( 'Error changing status for order in ' . wp_fusion()->crm->name . '. Error: ' . $result->get_error_message() );

				}
			}
		} elseif ( $to_status == 'refunded' && in_array( 'refunds', wp_fusion_ecommerce()->crm->supports ) ) {

			$invoice_id = get_post_meta( $order_id, 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', true );

			if ( ! empty( $invoice_id ) ) {

				$result = wp_fusion_ecommerce()->crm->refund_order( $invoice_id, $order->get_total_refunded() );

				if ( is_wp_error( $result ) ) {

					wpf_log( 'error', $user_id, 'Error refunding WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>: ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
					$order->add_order_note( 'Error refunding order in ' . wp_fusion()->crm->name . '. Error: ' . $result->get_error_message() );

				} else {

					wpf_log( 'info', $user_id, 'WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a> refunded in ' . wp_fusion()->crm->name, array( 'source' => 'wpf-ecommerce' ) );
					$order->add_order_note( wp_fusion()->crm->name . ' invoice #' . $invoice_id . ' successfully marked as refunded.' );

				}
			}
		} elseif ( in_array( 'status_changes', wp_fusion_ecommerce()->crm->supports ) ) {

			$invoice_id = get_post_meta( $order_id, 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', true );

			if ( ! empty( $invoice_id ) ) {

				$args = $this->get_order_args( $order );

				$args['refund_amount'] = $order->get_total_refunded();

				$contact_id = wp_fusion()->user->get_contact_id( $user_id );

				if ( empty( $contact_id ) ) {
					$contact_id = get_post_meta( $order_id, wp_fusion()->crm->slug . '_contact_id', true );
				}

				if ( empty( $contact_id ) ) {
					wpf_log( 'error', $user_id, 'Error updating order status in ' . wp_fusion()->crm->name . ' to ' . $to_status . ' for WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>: Unable to find contact ID for customer.', array( 'source' => 'wpf-ecommerce' ) );
					$order->add_order_note( 'Error updating order status in ' . wp_fusion()->crm->name . ': Unable to find contact ID for customer.' );
					return;
				}

				$result = wp_fusion_ecommerce()->crm->order_status_changed( $order_id, $contact_id, $to_status, $args );

				if ( is_wp_error( $result ) ) {

					wpf_log( 'error', $user_id, 'Error updating status in ' . wp_fusion()->crm->name . ' to ' . $to_status . ' for WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>: ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
					$order->add_order_note( 'Error updating order status in ' . wp_fusion()->crm->name . '. Error: ' . $result->get_error_message() );

				} elseif ( false !== $result ) {

					wpf_log( 'info', $user_id, 'WooCommerce Order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a> status updated to ' . $to_status . ' in ' . wp_fusion()->crm->name, array( 'source' => 'wpf-ecommerce' ) );
					$order->add_order_note( wp_fusion()->crm->name . ' invoice #' . $invoice_id . ' successfully marked as ' . $to_status . '.' );

				}
			}
		}

	}

	/**
	 * Support utilities
	 *
	 * @access public
	 * @return void
	 */

	public function settings_page_init() {

		if ( isset( $_GET['woo_reset_wpf_ec_complete'] ) ) {

			$args = array(
				'numberposts' => -1,
				'post_type'   => 'shop_order',
				'post_status' => array( 'wc-processing', 'wc-completed' ),
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'key'     => 'wpf_ec_complete',
						'compare' => 'EXISTS',
					),
				),
			);

			$orders = get_posts( $args );

			foreach ( $orders as $order_id ) {
				delete_post_meta( $order_id, 'wpf_ec_complete' );
			}

			echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Success:</strong><code>wpf_ec_complete</code> meta key removed from ' . count( $orders ) . ' orders.</p></div>';

		}

		if ( isset( $_GET['woo_reset_wpf_product_ids'] ) ) {

			$args = array(
				'numberposts' => -1,
				'post_type'   => 'product',
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'key'     => wp_fusion()->crm->slug . '_product_id',
						'compare' => 'EXISTS',
					),
				),
			);

			$products = get_posts( $args );

			foreach ( $products as $product_id ) {
				delete_post_meta( $product_id, wp_fusion()->crm->slug . '_product_id' );
			}

			echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Success:</strong> ' . count( $products ) . ' products reset.</p></div>';

		}

	}


	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds WooCommerce checkbox to available export options
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['woocommerce_ecom'] = array(
			'label'   => 'WooCommerce orders (Ecommerce addon)',
			'title'   => 'Orders',
			'tooltip' => 'Finds WooCommerce orders that have been processed by WP Fusion but have not been processed by the Ecommerce Addon, and adds invoices to ' . wp_fusion()->crm->name . '. Not necessary if you\'ve already run the WooCommerce Orders operation.',
		);

		return $options;

	}

	/**
	 * Counts total number of orders to be processed
	 *
	 * @access public
	 * @return int Count
	 */

	public function batch_init() {

		$args = array(
			'numberposts' => - 1,
			'post_type'   => 'shop_order',
			'post_status' => array( 'wc-processing', 'wc-completed' ),
			'fields'      => 'ids',
			'order'       => 'ASC',
			'meta_query'  => array(
				array(
					'key'     => 'wpf_complete',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'wpf_ec_complete',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$orders = get_posts( $args );

		wpf_log( 'info', 0, 'Beginning <strong>WooCommerce Orders</strong> batch operation on ' . count( $orders ) . ' orders', array( 'source' => 'batch-process' ) );

		return $orders;

	}

	/**
	 * Processes order actions in batches
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $order_id ) {

		$contact_id = get_post_meta( $order_id, wp_fusion()->crm->slug . '_contact_id', true );

		if ( empty( $contact_id ) ) {

			$order   = wc_get_order( $order_id );
			$user_id = $order->get_user_id();

			if ( ! empty( $user_id ) ) {
				$contact_id = wp_fusion()->user->get_contact_id( $user_id );
			}
		}

		if ( ! empty( $contact_id ) ) {

			$this->send_order_data( $order_id, $contact_id );

		}

	}


}

new WPF_EC_Woocommerce();
