<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Woo_Shipment_Tracking extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'woo-shipment-tracking';

		add_action( 'added_post_meta', array( $this, 'sync_shipping_link' ), 10, 4 );
		add_action( 'updated_post_meta', array( $this, 'sync_shipping_link' ), 10, 4 );

		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );

	}


	/**
	 * Sync shipment tracking link when it's saved
	 *
	 * @access public
	 * @return void
	 */

	public function sync_shipping_link( $meta_id, $post_id, $meta_key, $meta_value ) {

		if ( '_wc_shipment_tracking_items' !== $meta_key ) {
			return;
		}

		if ( is_array( $meta_value ) && ! empty( $meta_value[0]['custom_tracking_link'] ) ) {

			$order = wc_get_order( $post_id );

			$user_id    = $order->get_user_id();
			$contact_id = get_post_meta( $order_id, wp_fusion()->crm->slug . '_contact_id', true );

			$update_data = array(
				'wc_shipment_tracking_link' => $meta_value[0]['custom_tracking_link'],
			);

			if ( ! empty( $user_id ) ) {

				wp_fusion()->user->push_user_meta( $user_id, $update_data );

			} elseif ( ! empty( $contact_id ) ) {

				wp_fusion()->crm->update_contact( $contact_id, $update_data );

			}

		}

	}

	/**
	 * Add field to settings
	 *
	 * @access  public
	 * @return  array Meta fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$meta_fields['wc_shipment_tracking_link'] = array(
			'label' => 'Shipment Tracking Link',
			'type'  => 'text',
			'group' => 'woocommerce',
		);

		return $meta_fields;

	}


}

new WPF_Woo_Shipment_Tracking();
