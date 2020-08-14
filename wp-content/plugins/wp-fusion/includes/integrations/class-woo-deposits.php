<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Woo_Deposits extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */

	public function init() {

		$this->slug = 'woo-deposits';

		add_action( 'wpf_woocommerce_panel', array( $this, 'panel_content' ) );
		add_action( 'woocommerce_order_status_changed' , array( $this ,'order_status_changed' ), 10, 3 );

		// Run initial actions on partial payment
		add_action( 'woocommerce_order_status_partially-paid', array( wp_fusion()->integrations->woocommerce, 'woocommerce_apply_tags_checkout' ) );
		add_action( 'woocommerce_order_status_partial-payment', array( wp_fusion()->integrations->woocommerce, 'woocommerce_apply_tags_checkout' ) );

	}

	/**
	 * Writes subscriptions options to WPF/Woo panel
	 *
	 * @access public
	 * @return mixed
	 */

	public function panel_content() {

		global $post;
		$settings = array(
			'apply_tags_paid_in_full' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf-settings-woo', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf-settings-woo', true ) );
		}

		echo '<div class="options_group">';

		echo '<p class="form-field"><label><strong>Deposits</strong></label></p>';

		// Paid In Full
		echo '<p class="form-field"><label>Apply tags when<br />paid in full</label>';
		wpf_render_tag_multiselect( array( 'setting' => $settings['apply_tags_paid_in_full'], 'meta_name' => 'wpf-settings-woo', 'field_id' => 'apply_tags_paid_in_full' ) );
		echo '<span class="description">Apply these tags when a product with a deposit has been paid in full.</span>';
		echo '</p>';

		echo '</div>';

	}

	/**
	 * Apply tags when order status changed
	 *
	 * @access public
	 * @return void
	 */

	public function order_status_changed( $order_id, $old_status, $new_status ) {

		$order = wc_get_order( $order_id );

		$order_has_deposit = $order->get_meta( '_wc_deposits_order_has_deposit' , true );

		if ( $order_has_deposit === 'yes' ){

			$deposit_paid = $order->get_meta( '_wc_deposits_deposit_paid' , true );

			if ( $old_status === 'partially-paid' && ( $new_status === 'processing' || $new_status === 'completed' ) && $deposit_paid === 'yes' ){

				$user_id = $order->get_user_id();

				if ( empty( $user_id ) ) {
					$email_address = $order->get_billing_email();
					$contact_id = wp_fusion()->crm->get_contact_id( $email_address );
				}

				$products = $order->get_items();

				foreach ( $products as $product ) {

					$wpf_settings = get_post_meta( $product['product_id'], 'wpf-settings-woo', true );

					if( empty( $wpf_settings ) || empty( $wpf_settings['apply_tags_paid_in_full'] ) ) {
						continue;
					}

					if( ! empty( $user_id ) ) {

						wp_fusion()->user->apply_tags( $wpf_settings['apply_tags_paid_in_full'], $user_id );

					} elseif( ! empty( $contact_id ) ) {

						wp_fusion()->crm->apply_tags( $wpf_settings['apply_tags_paid_in_full'], $contact_id );

					}

				}

			}

		}

	}

}

new WPF_Woo_Deposits;