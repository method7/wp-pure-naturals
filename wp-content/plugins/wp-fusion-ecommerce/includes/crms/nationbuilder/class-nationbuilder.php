<?php

class WPF_EC_NationBuilder {

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

		$this->supports = array();

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

		// Convert date
		$offset      = get_option( 'gmt_offset' );
		$order_date -= $offset * 60 * 60;

		$order_date = new DateTime( date( 'c', $order_date ) );

		// DateTimeZone throws an error with 0 as the timezone
		if ( $offset >= 0 ) {
			$offset = '+' . $offset;
		}

		$order_date->setTimezone( new DateTimeZone( $offset ) );

		$order_args['currency_symbol'] = html_entity_decode( $order_args['currency_symbol'] );

		$data = array(
			'donor_id'              => $contact_id,
			'amount'                => $order_args['currency_symbol'] . $order_args['total'],
			'amount_in_cents'       => $order_args['total'] * 100,
			'payment_type_name'     => 'Credit Card',
			'payment_type_ngp_code' => 'D',
			'succeeded_at'          => $order_date->format( 'c' ),
			'note'                  => $order_args['order_label'],
		);

		wpf_log(
			'info', $order_args['user_id'], 'Adding <a href="' . $order_args['order_edit_link'] . '" target="_blank">' . $order_args['order_label'] . '</a>:', array(
				'meta_array_nofilter' => $data,
				'source'              => 'wpf-ecommerce',
			)
		);

		$params         = wp_fusion()->crm->get_params();
		$params['body'] = json_encode( array( 'donation' => $data ) );

		$request  = 'https://' . wp_fusion()->crm->url_slug . '.nationbuilder.com/api/v1/donations?access_token=' . wp_fusion()->crm->token;
		$response = wp_remote_post( $request, $params );

		if ( is_wp_error( $response ) ) {

			wpf_log( $response->get_error_code(), $order_args['user_id'], 'Error adding donation: ' . $response->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			return $response;

		}

		$response = json_decode( wp_remote_retrieve_body( $response ) );

		return $response->donation->id;

	}

}
