<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_EC_EDD extends WPF_EC_Integrations_Base {

	/**
	 * Get things started
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		$this->slug = 'edd';

		add_action( 'wpf_edd_payment_complete', array( $this, 'send_order_data' ), 10, 2 );

		// Renewal payments
		add_action( 'edd_recurring_add_subscription_payment', array( $this, 'recurring_renewal' ), 10, 2 );

		// Infusionsoft meta box
		add_action( 'wpf_edd_meta_box', array( $this, 'edd_product_select' ), 20, 2 );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ) );

		// Settings for gateways
		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

		// Export functions
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_action( 'wpf_batch_edd_ecom_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_edd_ecom', array( $this, 'batch_step' ) );

	}


	/**
	 * Adds EDD product mapping meta box to EDD Downloads
	 *
	 * @access  public
	 * @return  mixed
	 */

	public function edd_product_select( $post, $settings ) {

		if ( ! in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {
			return;
		}

		global $post;

		$product_id = get_post_meta( $post->ID, wp_fusion()->crm->slug . '_product_id', true );

		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		echo '<table class="form-table wpf-ec-edd-options"><tbody>';

			echo '<tr>';
				echo '<th scope="row"><label for="wpf-ec-product">' . wp_fusion()->crm->name . ' Product</label>';
				echo '<td>';

				echo '<select id="wpf-ec-product" class="select4-search" data-placeholder="None" name="' . wp_fusion()->crm->slug . '_product_id">';

					echo '<option></option>';

					foreach ( $available_products as $id => $name ) {

						echo '<option value="' . $id . '"' . selected( $id, $product_id, false ) . '>' . $name . '</option>';

					}

				echo '</select>';

				echo '</td>';

			echo '</tr>';

		echo '</tbody></table>';

	}

	/**
	 * Saves Infusionsoft product ID selected in dropdown
	 *
	 * @access public
	 * @return mixed
	 */

	public function save_meta_box_data( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_meta_box_edd_nonce'] ) || ! isset( $_POST[ wp_fusion()->crm->slug . '_product_id' ] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpf_meta_box_edd_nonce'], 'wpf_meta_box_edd' ) ) {
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
	 * Sends order data to CRM's ecommerce system
	 *
	 * @access  public
	 * @return  bool
	 */

	public function send_order_data( $payment_id, $contact_id = false ) {

		$payment = new EDD_Payment( $payment_id );

		// Prevents the API calls being sent multiple times for the same order
		$wpf_complete = $payment->get_meta( 'wpf_ec_complete', true );

		if ( ! empty( $wpf_complete ) ) {
			return true;
		}

		if ( false === $contact_id ) {

			$contact_id = $payment->get_meta( wp_fusion()->crm->slug . '_contact_id', true );

			if ( empty( $contact_id ) && $payment->user_id > 0 ) {
				$contact_id = wp_fusion()->user->get_contact_id( $payment->user_id );
			}

		}

		$products   = array();
		$line_items = array();

		$discount   = 0;
		$cart_items = edd_get_payment_meta_cart_details( $payment_id );

		foreach ( $cart_items as $item ) {

			$crm_product_id = get_post_meta( $item['id'], wp_fusion()->crm->slug . '_product_id', true );

			$products[] = array(
				'id'             => $item['id'],
				'crm_product_id' => $crm_product_id,
				'name'           => preg_replace( '/[^(\x20-\x7F)]*/', '', $item['name'] ),
				'price'          => $item['item_price'],
				'qty'            => $item['quantity'],
				'sku'            => '',
			);

			if ( ! empty( $item['discount'] ) ) {
				$discount += $item['discount'];
			}

			// Discounts pro support
			if ( ! empty( $item['fees'] ) ) {

				foreach ( $item['fees'] as $fee ) {

					if ( (int) $fee['amount'] < 0 ) {
						$line_items[] = array(
							'type'        => 'discount',
							'price'       => $fee['amount'],
							'title'       => 'Discount ' . $fee['label'],
							'description' => 'Used discount code ' . $fee['label'],
						);
					}
				}
			}
		}

		// Check gateway
		$enabled_gateways = wp_fusion()->settings->get( 'enabled_gateways', array() );

		if ( ! empty( $enabled_gateways ) ) {

			if ( ! isset( $enabled_gateways[ $payment->gateway ] ) || $enabled_gateways[ $payment->gateway ] == false ) {
				return;
			}
		}

		if ( $payment->tax > 0 ) {
			$line_items[] = array(
				'type'        => 'tax',
				'price'       => $payment->tax,
				'title'       => 'Tax',
				'description' => '',
			);
		}

		if ( ! empty( $discount ) ) {
			$line_items[] = array(
				'type'        => 'discount',
				'price'       => -$discount,
				'title'       => 'Discounts Applied',
				'description' => 'Used discount codes: ' . $payment->discounts,
			);
		}

		$order_args = array(
			'order_label'     => 'EDD Order #' . $payment_id,
			'order_number'    => $payment_id,
			'order_edit_link' => admin_url( 'post.php?post=' . $payment_id . '&action=edit' ),
			'payment_method'  => $payment->gateway,
			'user_email'      => $payment->email,
			'products'        => $products,
			'line_items'      => $line_items,
			'total'           => $payment->total,
			'currency'        => $payment->currency,
			'currency_symbol' => '$',
			'order_date'      => strtotime( $payment->get_meta( '_edd_completed_date' ) ),
			'provider'        => 'easy-digital-downloads',
			'user_id'         => $payment->user_id,
		);

		// Add order
		$result = wp_fusion_ecommerce()->crm->add_order( $payment_id, $contact_id, $order_args );

		if ( is_wp_error( $result ) ) {

			wpf_log( 'error', $payment->user_id, 'Error adding EDD Order <a href="' . admin_url( 'post.php?post=' . $payment_id . '&action=edit' ) . '" target="_blank">#' . $payment_id . '</a>: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			$payment->add_note( 'Error creating order in ' . wp_fusion()->crm->name . '. Error: ' . $result->get_error_message() );
			return false;

		}

		$payment->add_note( wp_fusion()->crm->name . ' invoice #' . $result . ' successfully created.' );

		// Denotes that the WPF actions have already run for this order
		$payment->update_meta( 'wpf_ec_complete', true );
		$payment->update_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', $result );

		do_action( 'wpf_ecommerce_complete', $payment_id, $result, $contact_id, $order_args );

	}

	/**
	 * Triggers order complete on recurring renewal payment
	 *
	 * @access  public
	 * @return  void
	 */

	public function recurring_renewal( $payment_obj, $subscription ) {

		$contact_id = wp_fusion()->user->get_contact_id( $subscription->customer->user_id );
		$this->send_order_data( $payment_obj->ID, $contact_id );

	}

	/**
	 * Adds Addons tab if not already present
	 *
	 * @access public
	 * @return void
	 */

	public function configure_sections( $page, $options ) {

		if ( ! isset( $page['sections']['ecommerce'] ) ) {
			$page['sections'] = wp_fusion()->settings->insert_setting_before( 'import', $page['sections'], array( 'ecommerce' => __( 'Addons', 'wp-fusion' ) ) );
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

		$settings['ecommerce_header'] = array(
			'title'   => __( 'Ecommerce Addon', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'ecommerce',
		);

		$gateways = edd_get_payment_gateways();

		$gateways_for_option = array();
		$std                 = array();

		foreach ( $gateways as $slug => $gateway ) {

			$gateways_for_option[ $slug ] = $gateway['admin_label'];
			$std[ $slug ]                 = true;

		}

		$settings['enabled_gateways'] = array(
			'title'   => __( 'Enabled Gateways', 'wp-fusion' ),
			'desc'    => 'Select which payment gateways should send ecommerce data.',
			'std'     => $std,
			'type'    => 'checkboxes',
			'section' => 'ecommerce',
			'options' => $gateways_for_option,
		);

		return $settings;

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

		$options['edd_ecom'] = array(
			'label'   => 'Easy Digital Downloads orders (Ecommerce addon)',
			'title'   => 'Orders',
			'tooltip' => 'Finds EDD orders that have been processed by WP Fusion but have not been processed by the Ecommerce Addon, and adds invoices to ' . wp_fusion()->crm->name . '. Not necessary if you\'ve already run the EDD Orders operation.',
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
			'number'     => -1,
			'fields'     => 'ids',
			'meta_query' => array(
				array(
					'key'     => 'wpf_complete',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'wpf_ecommerce_complete',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$payments = edd_get_payments( $args );

		wpf_log( 'info', 0, 'Beginning <strong>Easy Digital Downloads orders (Ecommerce addon)</strong> batch operation on ' . count( $payments ) . ' orders', array( 'source' => 'batch-process' ) );

		return $payments;

	}

	/**
	 * Processes order actions in batches
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $order_id ) {

		$this->send_order_data( $order_id );

	}

}

new WPF_EC_EDD();
