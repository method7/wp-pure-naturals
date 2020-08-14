<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_EDD_Recurring extends WPF_Integrations_Base {

	/**
	 * Get things started
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		$this->slug = 'edd-recurring';

		// Add additional meta fields
		add_action( 'wpf_edd_meta_box', array( $this, 'meta_box_content' ), 10, 2 );
		add_action( 'edd_download_price_table_row', array( $this, 'variable_meta_box_content' ), 10, 3 );

		// Subscription status triggers
		add_action( 'edd_subscription_status_change', array( $this, 'subscription_status_change' ), 10, 3 );

		// Export functions
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_action( 'wpf_batch_edd_recurring_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_edd_recurring', array( $this, 'batch_step' ) );

	}

	/**
	 * Determines if a product or variable price option is a recurring charge
	 *
	 * @access  public
	 * @return  bool
	 */

	private function is_recurring( $download_id ) {

		if ( EDD_Recurring()->is_recurring( $download_id ) == true ) {
			return true;
		}

		if ( edd_has_variable_prices( $download_id ) ) {

			$prices = edd_get_variable_prices( $download_id );

			foreach ( $prices as $price_id => $price ) {
				if ( EDD_Recurring()->is_price_recurring( $download_id, $price_id ) ) {
					return true;
				}
			}

		}

		return false;

	}

	/**
	 * Triggered when a subscription status changes
	 *
	 * @access  public
	 * @return  void
	 */

	public function subscription_status_change( $old_status, $status, $subscription ) {

		if ( $this->is_recurring( $subscription->product_id ) == false ) {
			return;
		}

		$wpf_options = get_post_meta( $subscription->product_id, 'wpf-settings-edd', true );

		// Remove tags if option is selected
		if ( isset( $wpf_options['remove_tags'] ) && $status != 'active' ) {
			wp_fusion()->user->remove_tags( $wpf_options['apply_tags'], $subscription->customer->user_id );
		}

		if ( isset( $wpf_options[ 'apply_tags_' . $status ] ) ) {
			wp_fusion()->user->apply_tags( $wpf_options[ 'apply_tags_' . $status ], $subscription->customer->user_id );
		}

		$price_id = 0;

		// Gets Price ID and Recurring Product ID
		$payment = edd_get_payment_meta( $subscription->customer->payment_ids );

		if ( ! empty( $payment['downloads'] ) ) {

			foreach ( $payment['downloads'] as $download ) {

				if ( $download['id'] == $subscription->product_id && ! empty( $download['options']['price_id'] ) ) {
					$price_id = $download['options']['price_id'];
				}

			}

		}

		// Sets tags if Price id is a Recurring Product
		if( ! empty( $price_id ) ) {

			if ( ! empty( $wpf_options[ 'apply_tags_' . $status . '_price' ] ) && ! empty( $wpf_options[ 'apply_tags_' . $status . '_price' ][$price_id] ) ) {
				wp_fusion()->user->apply_tags( $wpf_options[ 'apply_tags_' . $status . '_price' ][$price_id], $subscription->customer->user_id );
			}

			// Remove price ID tags if applicable

			if ( isset( $wpf_options['remove_tags'] ) && $status != 'active' && ! empty( $wpf_options[ 'apply_tags_price' ][ $price_id ] ) ) {

				wp_fusion()->user->remove_tags( $wpf_options[ 'apply_tags_price' ][ $price_id ], $subscription->customer->user_id );

			}

		}

		// Possibly remove any of the other status tags

		if( $status == 'active' && $old_status != 'pending' ) {

			foreach($wpf_options as $setting => $values) {

				if( ! is_array($values) || empty($values) || $setting == 'apply_tags' || $setting == 'apply_tags_license_expired' ) {
					continue;
				}

				// Main product

				wp_fusion()->user->remove_tags( $wpf_options[$setting], $subscription->customer->user_id );

				// Variations

				if ( ! empty( $price_id ) && ! empty( $wpf_options[ $setting ] ) && ! empty( $wpf_options[ $setting ][$price_id] ) ) {
					wp_fusion()->user->remove_tags( $wpf_options[ $setting ][$price_id], $subscription->customer->user_id );
				}

			}

			// Apply active tags

			wp_fusion()->user->apply_tags( $wpf_options[ 'apply_tags' ], $subscription->customer->user_id );

			// Active tags for variations

			if ( ! empty( $price_id ) && ! empty( $wpf_options[ 'apply_tags_price' ] ) && ! empty( $wpf_options[ 'apply_tags_price' ][$price_id] ) ) {
				wp_fusion()->user->apply_tags( $wpf_options[ 'apply_tags_price' ][$price_id], $subscription->customer->user_id );
			}


		}

	}

	/**
	 * Outputs fields to EDD meta box
	 *
	 * @access public
	 * @return mixed
	 */

	public function meta_box_content( $post, $settings ) {

		$defaults = array(
			'remove_tags' 			=> false,
			'apply_tags_completed' 	=> array(),
			'apply_tags_failing' 	=> array(),
			'apply_tags_expired' 	=> array(),
			'apply_tags_cancelled' 	=> array()
		);

		$settings = wp_parse_args( $settings, $defaults );

		echo '<hr />';

		echo '<table class="form-table wpf-edd-recurring-options' . ( $this->is_recurring( $post->ID ) == true ? '' : ' hidden' ) . '"><tbody>';

		// Remove tags

		echo '<tr>';

		echo '<th scope="row"><label for="remove_tags">' . __( 'Remove Tags', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';
		echo '<input class="checkbox" type="checkbox" id="remove_tags" name="wpf-settings-edd[remove_tags]" value="1" ' . checked( $settings['remove_tags'], 1, false ) . ' />';
		echo '<span class="description">' . __( 'Remove tags when the subscription is completed, fails to charge, is cancelled, or expires.', 'wp-fusion' ) . '</span>';
		echo '</td>';

		echo '</tr>';

		// Completed

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags_completed">' . __( 'Subscription Completed', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';
		wpf_render_tag_multiselect( array( 'setting' => $settings['apply_tags_completed'], 'meta_name' => 'wpf-settings-edd', 'field_id' => 'apply_tags_completed' ) );
		echo '<span class="description">' . __( 'Apply these when a subscription is complete (number of payments matches the Times field).', 'wp-fusion' ) . '</span>';
		echo '</td>';

		echo '</tr>';

		// Failing

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags_expired">' . __( 'Subscription Failing', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';
		wpf_render_tag_multiselect( array( 'setting' => $settings['apply_tags_failing'], 'meta_name' => 'wpf-settings-edd', 'field_id' => 'apply_tags_failing' ) );
		echo '<span class="description">' . __( 'Apply these when a subscription has a failed payment.', 'wp-fusion' ) . '</span>';
		echo '</td>';

		echo '</tr>';

		// Expired

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags_expired">' . __( 'Subscription Expired', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';
		wpf_render_tag_multiselect( array( 'setting' => $settings['apply_tags_expired'], 'meta_name' => 'wpf-settings-edd', 'field_id' => 'apply_tags_expired' ) );
		echo '<span class="description">' . __( 'Apply these when a subscription has multiple failed payments or is marked Expired.', 'wp-fusion' ) . '</span>';
		echo '</td>';

		echo '</tr>';

		// Cancelled

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags_cancelled">' . __( 'Subscription Cancelled', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';
		wpf_render_tag_multiselect( array( 'setting' => $settings['apply_tags_cancelled'], 'meta_name' => 'wpf-settings-edd', 'field_id' => 'apply_tags_cancelled' ) );
		echo '<span class="description">' . __( 'Apply these when a subscription is cancelled.', 'wp-fusion' ) . '</span>';
		echo '</td>';

		echo '</tr>';

		echo '</tbody></table>';

	}

	/** 
	* //
	* // OUTPUTS EDD METABOXES
	* //
	*  @access public
	*  @return mixed  
	**/

	public function variable_meta_box_content( $post_id, $key, $args ) {

		$settings = get_post_meta( $post_id, 'wpf-settings-edd', true );

		if( empty( $settings ) ) {
			$settings = array();
		}

		$defaults = array(
			'apply_tags_completed_price' 	=> array(),
			'apply_tags_failing_price' 		=> array(),
			'apply_tags_expired_price' 		=> array(),
			'apply_tags_cancelled_price' 	=> array()
		);

		$settings = array_merge( $defaults, $settings );

		if ( empty( $settings['apply_tags_completed_price'][ $key ] ) ) {
			$settings['apply_tags_completed_price'][ $key ] = array();
		}
		if ( empty( $settings['apply_tags_failing_price'][ $key ] ) ) {
			$settings['apply_tags_failing_price'][ $key ] = array();
		}
		if ( empty( $settings['apply_tags_expired_price'][ $key ] ) ) {
			$settings['apply_tags_expired_price'][ $key ] = array();
		}
		if ( empty( $settings['apply_tags_cancelled_price'][ $key ] ) ) {
			$settings['apply_tags_cancelled_price'][ $key ] = array();
		}

		$variable_price = edd_get_variable_prices( $post_id );

		$recurring = false;

		if ( ! empty( $variable_price[ $key ]['recurring'] ) && $variable_price[ $key ]['recurring'] == 'yes' ) {
			$recurring = true;
		}

		echo '<div class="wpf-edd-recurring-options' . ( $recurring == true ? '' : ' hidden' ) . '" style="' . ( $recurring == true ? '' : 'display: none;' ) . '">';

		// Completed

		echo '<div style="display:inline-block; width:50%;margin-bottom:20px;">';
		echo '<label>' . __( 'Subscription Completed', 'wp-fusion' ) . ':</label>';
		wpf_render_tag_multiselect( array( 'setting' => $settings['apply_tags_completed_price'], 'meta_name' => 'wpf-settings-edd', 'field_id' => 'apply_tags_completed_price', 'field_sub_id' => $key ) );	
		echo '</div>';

		// Failing

		echo '<div style="display:inline-block; width:50%;margin-bottom:20px;">';
		echo '<label for="apply_tags_expired_price">' . __( 'Subscription Failing', 'wp-fusion' ) . ':</label>';
		wpf_render_tag_multiselect( array( 'setting' => $settings['apply_tags_failing_price'], 'meta_name' => 'wpf-settings-edd', 'field_id' => 'apply_tags_failing_price', 'field_sub_id' => $key ) );
		echo '</div>';

		// Expired

		echo '<div style="display:inline-block; width:50%;margin-bottom:20px;">';
		echo '<label for="apply_tags_expired_price">' . __( 'Subscription Expired', 'wp-fusion' ) . ':</label>';
		wpf_render_tag_multiselect( array( 'setting' => $settings['apply_tags_expired_price'], 'meta_name' => 'wpf-settings-edd', 'field_id' => 'apply_tags_expired_price', 'field_sub_id' => $key ) );
		echo '</div>';

		// Cancelled

		echo '<div style="display:inline-block; width:50%;margin-bottom:20px;">';
		echo '<label for="apply_tags_cancelled_price">' . __( 'Subscription Cancelled', 'wp-fusion' ) . ':</label>';
		wpf_render_tag_multiselect( array( 'setting' => $settings['apply_tags_cancelled_price'], 'meta_name' => 'wpf-settings-edd', 'field_id' => 'apply_tags_cancelled_price', 'field_sub_id' => $key ) );
		echo '</div>';

		echo '</div>';

	}


	/**
	 * //
	 * // EXPORT TOOLS
	 * //
	 **/

	/**
	 * Adds EDD Recurring checkbox to available export options
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['edd_recurring'] = array(
			'label'   => __( 'EDD Recurring Payments statuses', 'wp-fusion' ),
			'title'   => __( 'Orders', 'wp-fusion' ),
			'tooltip' => __( 'Updates user tags for all subscriptions based on current subscription status', 'wp-fusion' ),
		);

		return $options;

	}

	/**
	 * Gets array of all subscriptions to be processed
	 *
	 * @access public
	 * @return array Subscriptions
	 */

	public function batch_init() {

		$edd_db = new EDD_Subscriptions_DB;
		$db_subscriptions = $edd_db->get_subscriptions(array('number' => 0));

		$subscriptions = array();
		foreach ($db_subscriptions as $subscription_object) {
			$subscriptions[] = $subscription_object->id;
		}

		return $subscriptions;

	}

	/**
	 * Update subscription statuses in batches
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $subscription_id ) {

		$subscription = new EDD_Subscription( $subscription_id );
		$this->subscription_status_change( $subscription_id, $subscription );

	}


}

new WPF_EDD_Recurring;
