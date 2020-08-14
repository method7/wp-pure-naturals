<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Woo_Appointments extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   3.33.10
	 * @return  void
	 */

	public function init() {

		$this->slug = 'woo-appointments';

		// Status changes
		add_action( 'woocommerce_appointment_unpaid', array( $this, 'status_transition' ), 10, 2 );
		add_action( 'woocommerce_appointment_pending-confirmation', array( $this, 'status_transition' ), 10, 2 );
		add_action( 'woocommerce_appointment_confirmed', array( $this, 'status_transition' ), 10, 2 );
		add_action( 'woocommerce_appointment_cancelled', array( $this, 'status_transition' ), 10, 2 );
		add_action( 'woocommerce_appointment_complete', array( $this, 'status_transition' ), 10, 2 );

		add_filter( 'wpf_woocommerce_customer_data', array( $this, 'sync_appointment_date' ), 10, 2 );

		add_filter( 'wpf_meta_fields', array( $this, 'add_meta_field' ), 30 );
		add_action( 'wpf_woocommerce_panel', array( $this, 'panel_content' ) );

	}

	/**
	 * Apply tags during status changes
	 *
	 * @access public
	 * @return void
	 */

	public function status_transition( $appointment_id, $appointment ) {

		$product_id = $appointment->get_product_id();
		$status     = $appointment->get_status();
		$settings   = get_post_meta( $product_id, 'wpf-settings-woo', true );

		if ( ! empty( $settings ) && ! empty( $settings[ 'apply_tags_' . $status ] ) ) {

			$order   = $appointment->get_order();
			$user_id = $order->get_user_id();

			if ( ! empty( $user_id ) ) {

				wp_fusion()->user->apply_tags( $settings[ 'apply_tags_' . $status ], $user_id );

			} else {

				// Guests

				$contact_id = wp_fusion()->integrations->woocommerce->maybe_create_contact_from_order( $order->get_id() );

				if ( ! empty( $contact_id ) && ! is_wp_error( $contact_id ) ) {

					wpf_log( 'info', 0, 'WooCommerce Appointments guest booking applying tag(s) to contact ID ' . $contact_id .': ', array( 'tag_array' => $settings[ 'apply_tags_' . $status ] ) );

					wp_fusion()->crm->apply_tags( $settings[ 'apply_tags_' . $status ], $contact_id );

				}
			}
		}

	}

	/**
	 * Merge appointment info into the order data
	 *
	 * @access public
	 * @return array Customer Data
	 */

	public function sync_appointment_date( $customer_data, $order ) {

		$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_from_order_id( $order->get_id() );

		if ( ! empty( $appointment_ids ) ) {

			$appointment = get_wc_appointment( $appointment_ids[0] );

			$start = $appointment->get_start();

			$start_time = date( 'Y-m-d H:i:s', $start );

			$customer_data['appointment_date'] = $start_time;

		}

		return $customer_data;

	}

	/**
	 * Adds booking date field to contact fields list
	 *
	 * @access public
	 * @return array Settings
	 */

	public function add_meta_field( $meta_fields ) {

		$meta_fields['appointment_date'] = array(
			'label' => 'Appointment Date',
			'type'  => 'date',
			'group' => 'woocommerce',
		);

		return $meta_fields;

	}


	/**
	 * Writes subscriptions options to WPF/Woo panel
	 *
	 * @access public
	 * @return mixed
	 */

	public function panel_content( $post_id ) {

		$statuses = get_wc_appointment_statuses( 'user', true );

		// Set defaults

		$settings = array();

		foreach ( $statuses as $key => $label ) {
			$settings[ 'apply_tags_' . $key ] = array();
		}

		if ( get_post_meta( $post_id, 'wpf-settings-woo', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post_id, 'wpf-settings-woo', true ) );
		}

		echo '<div class="options_group show_if_appointment">';

		echo '<p class="form-field"><label><strong>' . __( 'Appointment', 'wp-fusion' ) . '</strong></label></p>';

		echo '<p>' . sprintf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/events/woocommerce-appointments/" target="_blank">', '</a>' ) . '</p>';

		foreach ( $statuses as $key => $label ) {

			// Payment failed
			echo '<p class="form-field"><label>' . $label . '</label>';
			wpf_render_tag_multiselect(
				array(
					'setting'   => $settings[ 'apply_tags_' . $key ],
					'meta_name' => 'wpf-settings-woo',
					'field_id'  => 'apply_tags_' . $key,
				)
			);
			echo '<span class="description">' . sprintf( __( 'Apply these tags when an appointment status is set to %s.', 'wp-fusion' ), strtolower( $label ) ) . '</span>';
			echo '</p>';

		}

		echo '</div>';

	}

}

new WPF_Woo_Appointments();
