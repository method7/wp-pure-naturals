<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_EC_Event_Espresso extends WPF_EC_Integrations_Base {

	/**
	 * Get things started
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		$this->slug = 'event-espresso';

		// Send plan data
		add_action( 'wpf_event_espresso_payment_complete', array( $this, 'payment_complete' ), 10, 2 );

		// Meta Box
		add_action( 'AHEE__event_tickets_datetime_ticket_row_template__advanced_details_end', array( $this, 'show_admin_settings' ), 10, 2 );

	}

	/**
	 * Sends order data to CRM's ecommerce system
	 *
	 * @access  public
	 * @return  void
	 */

	public function payment_complete( $registration, $contact_id ) {

		$event_id     = $registration->event_ID();
		$ticket       = $registration->ticket();
		$ticket_id    = $registration->ticket_ID();
		$product_name = $registration->event_name() . ' - ' . $ticket->name();
		$order_date   = strtotime( $registration->date() );

		// Get stored product ID

		$crm_product_id = false;

		if ( is_array( wp_fusion_ecommerce()->crm->supports ) && in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {

			$event_settings = get_post_meta( $event_id, 'wpf_settings_event_espresso', true );

			if ( ! empty( $event_settings ) && isset( $event_settings[ wp_fusion()->crm->slug . '_product_id' ] ) && ! empty( $event_settings[ wp_fusion()->crm->slug . '_product_id' ][ $ticket_id ] ) ) {
				$crm_product_id = $event_settings[ wp_fusion()->crm->slug . '_product_id' ][ $ticket_id ];
			}

			$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

			if ( ! isset( $available_products[ $crm_product_id ] ) ) {
				$crm_product_id = false;
			}

			// See of an existing product matches by name
			if ( $crm_product_id == false ) {

				$crm_product_id = array_search( $product_name, $available_products );

			}
		}

		$products = array(
			array(
				'id'             => $ticket_id,
				'name'           => $product_name,
				'price'          => $registration->paid(),
				'qty'            => $registration->count(),
				'crm_product_id' => $crm_product_id,
			),
		);

		$payment_method = $registration->payment_method();

		if ( is_object( $payment_method ) ) {
			$payment_method = $payment_method->name();
		}

		$attendee = $registration->attendee();

		$order_args = array(
			'order_label'     => 'Event Espresso transaction #' . $registration->transaction_ID(),
			'order_number'    => $registration->transaction_ID(),
			'order_edit_link' => admin_url( 'post.php?post=' . $registration->transaction_ID() . '&action=edit' ),
			'payment_method'  => $payment_method,
			'user_email'      => $attendee->email(),
			'products'        => $products,
			'line_items'      => array(),
			'total'           => $registration->paid(),
			'currency'        => 'USD',
			'currency_symbol' => '$',
			'order_date'      => $order_date,
			'provider'        => 'event-espresso',
			'user_id'         => 0,
		);

		// Add order
		$result = wp_fusion_ecommerce()->crm->add_order( $registration->transaction_ID(), $contact_id, $order_args );

		if ( is_wp_error( $result ) ) {

			wpf_log( 'error', 0, 'Error adding Event Espresso transaction #' . $registration->transaction_ID() . ': ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return false;

		}

		do_action( 'wpf_ecommerce_complete', $registration->transaction_ID(), $result, $contact_id, $order_args );

	}

	/**
	 * Product drop down creation
	 *
	 * @access  public
	 * @return  bool
	 */

	public function show_admin_settings( $ticket_row, $ticket_id ) {

		if ( ! is_array( wp_fusion_ecommerce()->crm->supports ) || ! in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {
			return;
		}

		global $post;

		$settings = get_post_meta( $post->ID, 'wpf_settings_event_espresso', true );

		if ( empty( $settings ) ) {
			$settings = array();
		}

		$defaults = array(
			wp_fusion()->crm->slug . '_product_id' => array( $ticket_id => false ),
		);

		$settings = array_merge( $defaults, $settings );

		$selected_product_id = $settings[ wp_fusion()->crm->slug . '_product_id' ][ $ticket_id ];

		echo '<h4 class="tickets-heading">' . sprintf( __( '%s Product', 'wp-fusion' ), wp_fusion()->crm->name ) .'</h4><br />';

		echo '<select class="select4-search" data-placeholder="Select a product" name="ticket_wpf_settings[' . wp_fusion()->crm->slug . '_product_id][' . $ticket_id . ']">';
		echo '<option value="">' . __( 'Select Product', 'wp-fusion' ) . '</option>';

		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		asort( $available_products );

		foreach ( $available_products as $id => $name ) {
			echo '<option value="' . $id . '"' . selected( $id, $selected_product_id, false ) . '>' . esc_attr( $name ) . '</option>';
		}

		echo '</select>';

	}

}

new WPF_EC_Event_Espresso();
