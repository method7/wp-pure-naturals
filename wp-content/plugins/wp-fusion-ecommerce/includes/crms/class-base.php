<?php

class WPF_EC_CRM_Base {

	/**
	 * Contains the class object for the currently active CRM
	 *
	 * @var api
	 * @since 1.0
	 */

	public $crm;


	public function __construct() {

		$configured_crms = wp_fusion_ecommerce()->get_crms();

		foreach ( $configured_crms as $slug => $classname ) {

			if ( class_exists( $classname ) ) {

				if ( wp_fusion()->crm->slug == $slug ) {

					$crm       = new $classname();
					$this->crm = $crm;
					$this->crm->init();

				}
			}
		}

		add_action( 'wpf_ecommerce_complete', array( $this, 'sync_total_revenue' ), 10, 4 );

	}



	/**
	 * Total revenue tracking
	 *
	 * @access  public
	 * @since   1.15.2
	 * @return  void
	 */

	public function sync_total_revenue( $order_id, $result, $contact_id, $order_args ) {

		$revenue_field = wp_fusion()->settings->get( 'total_revenue_field' );

		if ( empty( $revenue_field['crm_field'] ) ) {
			return;
		}

		$user_id = wp_fusion()->user->get_user_id( $contact_id );

		if ( false !== $user_id ) {

			// Registered users
			$totals = get_user_meta( $user_id, 'wpf_total_revenue', true );

			if ( ! empty( $totals ) ) {

				$revenue = $order_args['total'] + $totals;

				update_user_meta( $user_id, 'wpf_total_revenue', $revenue );

			} else {

				$user_meta = wp_fusion()->crm->load_contact( $contact_id );

				if ( is_wp_error( $user_meta ) ) {

					wpf_log( $user_meta->get_error_code(), $order_args['user_id'], 'Error loading contact to update revenue. Quitting: ' . $user_meta->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
					return;

				}

				if ( $user_meta != false && isset( $user_meta['wpf_total_revenue'] ) && ! empty( $user_meta['wpf_total_revenue'] ) ) {
					$revenue = $user_meta['wpf_total_revenue'] + $order_args['total'];
				} else {
					$revenue = $order_args['total'];
				}
			}

			update_user_meta( $user_id, 'wpf_total_revenue', $revenue );

		} else {

			// Guests
			$user_meta = wp_fusion()->crm->load_contact( $contact_id );

			if ( is_wp_error( $user_meta ) ) {

				wpf_log( $user_meta->get_error_code(), 0, 'Error loading contact to update revenue. Quitting: ' . $user_meta->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
				return;

			}

			if ( $user_meta != false && isset( $user_meta['wpf_total_revenue'] ) && ! empty( $user_meta['wpf_total_revenue'] ) ) {
				$revenue = $order_args['total'] + $user_meta['wpf_total_revenue'];
			} else {
				$revenue = $order_args['total'];
			}
		}

		$revenue = number_format( $revenue, 2, '.', '' );

		wpf_log( 'info', $order_args['user_id'], 'Updating total revenue to ' . $revenue, array( 'source' => 'wpf-ecommerce' ) );

		wp_fusion()->crm->update_contact( $contact_id, array( 'wpf_total_revenue' => $revenue ) );

	}

}
