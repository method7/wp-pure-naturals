<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_EC_Give extends WPF_EC_Integrations_Base {

	/**
	 * Get things started
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		$this->slug = 'give';

		// Send plan data
		add_action( 'wpf_give_payment_complete', array( $this, 'payment_complete' ), 10, 3 );

		// Meta Box
		add_filter( 'give_metabox_form_data_settings', array( $this, 'add_settings' ), 20 );

	}

	/**
	 * Sends order data to CRM's ecommerce system
	 *
	 * @access  public
	 * @return  void
	 */

	public function payment_complete( $payment_id, $contact_id, $payment_data ) {

		// Get stored product ID
		$crm_product_id = false;

		if ( is_array( wp_fusion_ecommerce()->crm->supports ) && in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {

			$settings = get_post_meta( $payment_data['give_form_id'], 'wpf_settings_give', true );

			if ( ! empty( $settings[ wp_fusion()->crm->slug . '_product_id' ] ) ) {
				$crm_product_id = $settings[ wp_fusion()->crm->slug . '_product_id' ];
			}

			if ( ! empty( $settings[ wp_fusion()->crm->slug . '_product_id_level' ][ $payment_data['give_price_id'] ] ) ) {
				$crm_product_id = $settings[ wp_fusion()->crm->slug . '_product_id_level' ][ $payment_data['give_price_id'] ];
			}

			$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

			if ( ! isset( $available_products[ $crm_product_id ] ) ) {
				$crm_product_id = false;
			}

			// See of an existing product matches by name
			if ( false === $crm_product_id ) {

				$crm_product_id = array_search( $product_name, $available_products );

			}
		}

		$payment = new Give_Payment( $payment_id );

		$product_name = get_the_title( $payment_data['give_form_id'] );

		if ( ! empty( $payment_data['give_price_id'] ) ) {
			$product_name .= ' - ' . give_get_price_option_name( $payment_data['give_form_id'], $payment_data['give_price_id'], $payment_id );
		}

		// Get user ID

		$user = get_user_by( 'email', $payment_data['user_email'] );

		if ( ! empty( $user ) ) {
			$user_id = $user->ID;
		} else {
			$user_id = 0;
		}

		$products = array(
			array(
				'id'             => $payment_data['give_form_id'],
				'name'           => $product_name,
				'price'          => $payment_data['price'],
				'qty'            => 1,
				'crm_product_id' => $crm_product_id,
			),
		);

		$order_args = array(
			'order_label'     => 'Give payment #' . $payment_id,
			'order_number'    => $payment_id,
			'order_edit_link' => admin_url( 'post.php?post=' . $payment_id . '&action=edit' ),
			'payment_method'  => $payment->gateway,
			'user_email'      => $payment_data['user_email'],
			'products'        => $products,
			'line_items'      => array(),
			'total'           => $payment_data['price'],
			'currency'        => $payment_data['currency'],
			'currency_symbol' => '$',
			'order_date'      => strtotime( $payment_data['date'] ),
			'provider'        => 'give',
			'user_id'         => $user_id,
		);

		// Add order
		$result = wp_fusion_ecommerce()->crm->add_order( $payment_id, $contact_id, $order_args );

		if ( is_wp_error( $result ) ) {

			wpf_log( 'error', 0, 'Error adding Give payment #' . $payment_id . ': ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			return false;

		}

		if ( true === $result ) {

			give_insert_payment_note( $payment_id, wp_fusion()->crm->name . ' invoice successfully created.' );

		} elseif ( null != $result ) {

			// CRMs with invoice IDs
			give_insert_payment_note( $payment_id, wp_fusion()->crm->name . ' invoice #' . $result . ' successfully created.' );
			give_update_meta( $payment_id, '_wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', $result );

		}

		// Denotes that the WPF actions have already run for this order
		give_update_meta( $payment_id, '_wpf_ec_complete', true );

		do_action( 'wpf_ecommerce_complete', $payment_id, $result, $contact_id, $order_args );

	}

	/**
	 * Add product selection dropdown to Give settings
	 *
	 * @access  public
	 * @return  array Settings
	 */

	public function add_settings( $settings ) {

		if ( ! is_object( wp_fusion_ecommerce()->crm ) || ! is_array( wp_fusion_ecommerce()->crm->supports ) || ! in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {
			return $settings;
		}

		foreach ( $settings['form_field_options']['fields'] as $i => $field ) {

			if ( isset( $field['id'] ) && $field['id'] == '_give_donation_levels' ) {

				$settings['form_field_options']['fields'][ $i ]['fields'][] = array(
					'name'     => sprintf( __( '%s Product', 'wp-fusion' ), wp_fusion()->crm->name ),
					'id'       => wp_fusion()->crm->slug . '_product_id',
					'type'     => 'select4',
					'callback' => array( $this, 'select_callback' ),
				);

			}
		}

		return $settings;

	}

	/**
	 * Render WPF select box
	 *
	 * @access  public
	 * @return  mixed HTML Output
	 */

	public function select_callback( $field ) {

		global $post;

		$defaults = array(
			wp_fusion()->crm->slug . '_product_id'       => false,
			wp_fusion()->crm->slug . '_product_id_level' => array(),
		);

		$settings = (array) get_post_meta( $post->ID, 'wpf_settings_give', true );

		$settings = array_merge( $defaults, $settings );

		$selected_product_id = $settings[ wp_fusion()->crm->slug . '_product_id' ];

		$name = 'wpf_settings_give[' . wp_fusion()->crm->slug . '_product_id]';

		if ( isset( $field['repeat'] ) ) {

			$field_sub_id = str_replace( '_give_donation_levels_', '', $field['id'] );
			$field_sub_id = str_replace( '_' . wp_fusion()->crm->slug . '_product_id', '', $field_sub_id );

			$name = 'wpf_settings_give[' . wp_fusion()->crm->slug . '_product_id_level][' . $field_sub_id . ']';

			$selected_product_id = $settings[ wp_fusion()->crm->slug . '_product_id_level' ][ $field_sub_id ];

		}

		// See if there's a matching product name

		if ( empty( $selected_product_id ) ) {

			$product_name = $post->post_title . ' - ' . give_get_price_option_name( $post->ID, $field_sub_id );

			$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

			$selected_product_id = array_search( $product_name, $available_products );

		}

		echo '<fieldset class="give-field-wrap ' . esc_attr( $field['id'] ) . '_field"><span class="give-field-label">' . wp_kses_post( $field['name'] ) . '</span><legend class="screen-reader-text">' . wp_kses_post( $field['name'] ) . '</legend>';

		echo '<select class="select4-search" data-placeholder="Select a product" name="' . $name . '">';
		echo '<option value="">' . __( 'Select Product', 'wp-fusion' ) . '</option>';

		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		asort( $available_products );

		foreach ( $available_products as $id => $name ) {
			echo '<option value="' . $id . '"' . selected( $id, $selected_product_id, false ) . '>' . esc_attr( $name ) . '</option>';
		}

		echo '</select>';

		echo give_get_field_description( $field );
		echo '</fieldset>';

	}

}

new WPF_EC_Give();
