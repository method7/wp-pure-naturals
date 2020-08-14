<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Woo_Coupons extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */

	public function init() {

		$this->slug = 'woo-coupons';

		// Detect changes
		add_action( 'wc_sc_new_coupon_generated', array( $this, 'new_coupon_generated' ), 10, 1 );

		add_filter( 'wpf_woocommerce_customer_data', array( $this, 'get_coupon_data' ), 10, 2 );

		// Add coupon code to meta fields list
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 20 );

	}

	/**
	 * Copy settings to new coupon
	 *
	 * @access public
	 * @return void
	 */

	public function new_coupon_generated( $args ) {

		if ( empty( $args['ref_coupon'] ) ) {
			return;
		}

		$new_coupon_id = $args['new_coupon_id'];
		$ref_coupon    = $args['ref_coupon'];

		$settings      = get_post_meta( $ref_coupon->get_id(), 'wpf-settings-woo', true );

		if ( ! empty( $settings ) ) {

			update_post_meta( $new_coupon_id, 'wpf-settings-woo', $settings );

		}

	}

	/**
	 * Send generated coupons to the contact record
	 *
	 * @access public
	 * @return array
	 */

	public function get_coupon_data( $customer_data, $order ) {

		$order_id = $order->get_id();

		$coupon_data = get_post_meta( $order_id, 'sc_coupon_receiver_details', true );

		if ( ! empty( $coupon_data ) ) {
			$customer_data['wc_smart_coupon'] = $coupon_data[0]['code'];
		}

		return $customer_data;

	}

	/**
	 * Add coupon code to meta fields list
	 *
	 * @access  public
	 * @return  array Meta fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$meta_fields['wc_smart_coupon'] = array(
			'label' => 'Smart Coupon Code',
			'type'  => 'text',
			'group' => 'woocommerce',
		);

		return $meta_fields;

	}

}

new WPF_Woo_Coupons();
