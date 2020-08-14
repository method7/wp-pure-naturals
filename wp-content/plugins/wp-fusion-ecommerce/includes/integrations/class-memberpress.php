<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_EC_MemberPress extends WPF_EC_Integrations_Base {

	/**
	 * Get things started
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		$this->slug = 'memberpress';

		// Send transaction data
		add_action( 'mepr-txn-store', array( $this, 'transaction_created' ), 10, 2 );

		// Meta Box
		add_filter( 'wpf_memberpress_meta_box', array( $this, 'add_settings' ), 10, 2 );
		add_action( 'save_post_memberpressproduct', array( $this, 'save_post' ) );

	}

	/**
	 * Sends order data to CRM's ecommerce system
	 *
	 * @access  public
	 * @return  void
	 */

	public function transaction_created( $txn, $old_txn ) {

		// Maybe remove Confirmed, waiting on Kay
		if ( 'complete' != $txn->status && 'confirmed' != $txn->status ) {
			return;
		}

		$complete = $txn->get_meta( 'wpf_ec_complete', true );

		if ( $complete ) {
			return;
		}

		// Don't run on free transactions
		if ( 0 == $txn->total || 0 == $txn->amount ) {
			return;
		}

		$products = array(
			array(
				'id'             => $txn->product_id,
				'name'           => get_the_title( $txn->product_id ),
				'price'          => $txn->total,
				'qty'            => 1,
				'image'          => get_the_post_thumbnail_url( $txn->product_id, 'medium' ),
				'crm_product_id' => get_post_meta( $txn->product_id, wp_fusion()->crm->slug . '_product_id', true ),
			),
		);

		$mepr_options = MeprOptions::fetch();

		$userdata = get_userdata( $txn->user_id );

		$order_args = array(
			'order_label'     => 'MemberPress transaction #' . $txn->id,
			'order_number'    => $txn->id,
			'order_edit_link' => admin_url( 'admin.php?page=memberpress-trans&action=edit&id=' . $txn->id ),
			'payment_method'  => $txn->payment_method()->name,
			'user_email'      => $userdata->user_email,
			'products'        => $products,
			'line_items'      => array(),
			'total'           => $txn->total,
			'currency'        => $mepr_options->currency_code,
			'currency_symbol' => $mepr_options->currency_symbol,
			'order_date'      => strtotime( $txn->created_at ),
			'provider'        => 'memberpress',
			'user_id'         => $txn->user_id,
		);

		$contact_id = wp_fusion()->user->get_contact_id( $txn->user_id );

		// Add order
		$result = wp_fusion_ecommerce()->crm->add_order( $txn->id, $contact_id, $order_args );

		if ( is_wp_error( $result ) ) {

			wpf_log( 'error', 0, 'Error adding MemberPress transaction #' . $txn->id . ': ' . $result->get_error_message(), array( 'source' => 'wpf-ecommerce' ) );
			return false;

		}

		if ( true === $result ) {

			// Order added but no invoice ID, nothing to do

		} elseif ( null != $result ) {

			// CRMs with invoice IDs
			$txn->add_meta( 'wpf_ec_' . wp_fusion()->crm->slug . '_invoice_id', $result );

		}

		// Denotes that the WPF actions have already run for this order
		$txn->add_meta( 'wpf_ec_complete', true );

		do_action( 'wpf_ecommerce_complete', $txn->id, $result, $contact_id, $order_args );

	}

	/**
	 * Add product selection dropdown to MemberPress membership level settings
	 *
	 * @access  public
	 * @return  mixed Settings output
	 */

	public function add_settings( $settings, $product ) {

		if ( ! is_array( wp_fusion_ecommerce()->crm->supports ) || ! in_array( 'products', wp_fusion_ecommerce()->crm->supports ) ) {
			return;
		}

		$product_id = get_post_meta( $product->ID, wp_fusion()->crm->slug . '_product_id', true );

		$available_products = get_option( 'wpf_' . wp_fusion()->crm->slug . '_products', array() );

		echo '<br /><br /><label><strong>' . sprintf( __( '%s Product', 'wp-fusion' ), wp_fusion()->crm->name ) . ':</strong></label><br />';

		echo '<select id="wpf-ec-product" class="select4-search" data-placeholder="None" name="' . wp_fusion()->crm->slug . '_product_id">';

			echo '<option></option>';

			foreach ( $available_products as $id => $name ) {

				echo '<option value="' . $id . '"' . selected( $id, $product_id, false ) . '>' . $name . '</option>';

			}

		echo '</select>';

		echo '<br /><span class="description"><small>' . sprintf( __( 'Select a product in %s to use for orders containing this membership.', 'wp-fusion' ), wp_fusion()->crm->name ) . '</small></span>';

	}

	/**
	 * Save product selection
	 *
	 * @access public
	 * @return void
	 */

	public function save_post( $post_id ) {

		if ( ! empty( $_POST[ wp_fusion()->crm->slug . '_product_id' ] ) ) {
			update_post_meta( $post_id, wp_fusion()->crm->slug . '_product_id', $_POST[ wp_fusion()->crm->slug . '_product_id' ] );
		} else {
			delete_post_meta( $post_id, wp_fusion()->crm->slug . '_product_id' );
		}


	}

}

new WPF_EC_MemberPress();
