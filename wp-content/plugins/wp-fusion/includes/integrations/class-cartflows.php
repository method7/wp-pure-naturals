<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_CartFlows extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'cartflows';

		add_action( 'init', array( $this, 'add_action' ) );

		add_action( 'wpf_woocommerce_payment_complete', array( $this, 'maybe_block_ecom_addon' ), 5, 2 );

		// Offer stuff
		add_action( 'cartflows_offer_accepted', array( $this, 'offer_accepted' ), 10, 2 );
		add_action( 'cartflows_offer_rejected', array( $this, 'offer_rejected' ), 10, 2 );

		// Admin settings
		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 20 );

		// Cartflows admin settings
		add_filter( 'cartflows_offer_meta_options', array( $this, 'offer_meta_options' ) );
		add_filter( 'cartflows_offer_panel_tabs', array( $this, 'offer_panel_tabs' ), 10, 2 );
		add_action( 'cartflows_offer_panel_tab_content', array( $this, 'offer_panel_tab_content' ), 10, 2 );

	}

	/**
	 * Adds CartFlows order status trigger if enabled
	 *
	 * @access public
	 * @return void
	 */

	public function add_action() {

		if ( wp_fusion()->settings->get( 'cartflows_main_order' ) == true ) {

			add_action( 'woocommerce_order_status_wcf-main-order', array( wp_fusion()->integrations->woocommerce, 'woocommerce_apply_tags_checkout' ) );

			add_action( 'woocommerce_order_status_processing', array( $this, 'clear_wpf_complete' ), 5 );
			add_action( 'woocommerce_order_status_completed', array( $this, 'clear_wpf_complete' ), 5 );

		}

	}

	/**
	 * Don't run the ecommerce addon when the main order is complete
	 *
	 * @access public
	 * @return void
	 */

	public function maybe_block_ecom_addon( $order_id, $contact_id ) {

		$order  = wc_get_order( $order_id );
		$status = $order->get_status();

		// Ecom addon
		if ( function_exists( 'wp_fusion_ecommerce' ) && 'wcf-main-order' == $status && wp_fusion()->settings->get( 'cartflows_main_order' ) == true ) {

			remove_action( 'wpf_woocommerce_payment_complete', array( wp_fusion_ecommerce()->integrations->woocommerce, 'send_order_data' ), 10, 2 );

		}

	}

	/**
	 * Clear the wpf_complete flag so the order can be processed again after the main checkout is complete
	 *
	 * @access public
	 * @return void
	 */

	public function clear_wpf_complete( $order_id ) {

		delete_post_meta( $order_id, 'wpf_complete' );

	}

	/**
	 * Offer accepted
	 *
	 * @access public
	 * @return void
	 */

	public function offer_accepted( $order, $offer_product ) {

		$setting = get_post_meta( $offer_product['step_id'], 'wpf-offer-accepted', true );

		if ( ! empty( $setting ) ) {

			$user_id = $order->get_user_id();

			if ( ! empty( $user_id ) ) {

				wp_fusion()->user->apply_tags( $setting, $user_id );

			} else {

				$contact_id = get_post_meta( $order->get_id(), wp_fusion()->crm->slug . '_contact_id', true );

				if ( ! empty( $contact_id ) ) {

					wp_fusion()->crm->apply_tags( $setting, $contact_id );

				}
			}
		}

	}

	/**
	 * Offer rejected
	 *
	 * @access public
	 * @return void
	 */

	public function offer_rejected( $order, $offer_product ) {

		$setting = get_post_meta( $offer_product['step_id'], 'wpf-offer-rejected', true );

		if ( ! empty( $setting ) ) {

			$user_id = $order->get_user_id();

			if ( ! empty( $user_id ) ) {

				wp_fusion()->user->apply_tags( $setting, $user_id );

			} else {

				$contact_id = get_post_meta( $order->get_id(), wp_fusion()->crm->slug . '_contact_id', true );

				if ( ! empty( $contact_id ) ) {

					wp_fusion()->crm->apply_tags( $setting, $contact_id );

				}
			}
		}

	}

	/**
	 * Adds Integrations tab if not already present
	 *
	 * @access public
	 * @return void
	 */

	public function configure_sections( $page, $options ) {

		if ( ! isset( $page['sections']['integrations'] ) ) {
			$page['sections'] = wp_fusion()->settings->insert_setting_after( 'contact-fields', $page['sections'], array( 'integrations' => __( 'Integrations', 'wp-fusion' ) ) );
		}

		return $page;

	}


	/**
	 * Registers CartFlows settings
	 *
	 * @access  public
	 * @return  array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['cartflows_header'] = array(
			'title'   => __( 'CartFlows Integration', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['cartflows_main_order'] = array(
			'title'   => __( 'Run on Main Order Accepted', 'wp-fusion' ),
			'desc'    => __( 'Runs WP Fusion post-checkout actions when the order status is Main Order Accepted instead of waiting for Completed.', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'integrations',
		);

		return $settings;

	}

	/**
	 * Adds CartFlows custom fields to Contact Fields list
	 *
	 * @access  public
	 * @return  array Meta fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$args = array(
			'post_type' => 'cartflows_step',
			'fields'    => 'ids',
			'nopaging'  => true,
		);

		$steps = get_posts( $args );

		if ( ! empty( $steps ) ) {

			foreach ( $steps as $step_id ) {

				$fields = get_post_meta( $step_id, 'wcf_field_order_billing', true );

				if ( empty( $fields ) ) {
					continue;
				}

				$shipping_fields = get_post_meta( $step_id, 'wcf_field_order_shipping', true );

				if ( empty( $shipping_fields ) ) {
					$shipping_fields = array();
				}

				$fields = array_merge( $fields, $shipping_fields );

				foreach ( $fields as $key => $field ) {

					if ( ! isset( $meta_fields[ $key ] ) ) {

						if ( ! isset( $field['type'] ) ) {
							$field['type'] = 'text';
						}

						$meta_fields[ $key ] = array(
							'label' => $field['label'],
							'type'  => $field['type'],
							'group' => 'woocommerce',
						);

					}
				}
			}
		}

		return $meta_fields;

	}

	/**
	 * Register WPF options
	 *
	 * @access  public
	 * @return  array Options
	 */

	public function offer_meta_options( $options ) {

		$options['wpf-offer-accepted'] = array(
			'default'  => array(),
			'sanitize' => 'FILTER_CARTFLOWS_ARRAY',
		);

		$options['wpf-offer-rejected'] = array(
			'default'  => array(),
			'sanitize' => 'FILTER_CARTFLOWS_ARRAY',
		);

		return $options;

	}

	/**
	 * Add WPF panel tab to CartFlows upsell
	 *
	 * @access  public
	 * @return  array Tabs
	 */

	public function offer_panel_tabs( $tabs, $active_tab ) {

		$tabs[] = array(
			'title' => __( 'WP Fusion', 'wp-fusion' ),
			'id'    => 'wcf-offer-wp-fusion',
			'class' => 'wcf-offer-wp-fusion' === $active_tab ? 'wcf-tab wp-ui-text-highlight active' : 'wcf-tab',
			'icon'  => 'dashicons-tag',
		);

		return $tabs;

	}

	/**
	 * Panel tab content
	 *
	 * @access  public
	 * @return  mixed HTML Output
	 */

	public function offer_panel_tab_content( $options, $post_id ) {

		echo '<div class="wcf-offer-wp-fusion wcf-tab-content widefat">';

		echo '<div class="wcf-field-row field-wcf-offer-yes">';

		echo '<div class="wcf-field-row-heading">';

		echo '<label>' . __( 'Apply Tags', 'wp-fusion' ) . '-' . __( 'Offer Accepted', 'wp-fusion' ) . '</label>';

		echo '</div>';

		echo '<div class="wcf-field-row-content">';

		$args = array(
			'setting'   => $options['wpf-offer-accepted'],
			'meta_name' => 'wpf-offer-accepted',
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">' . sprintf( __( 'Apply these tags in %s when the offer is accepted', 'wp-fusion' ), wp_fusion()->crm->name ) . '</span>';

		echo '</div>';

		echo '</div>'; // End field row

		echo '<div class="wcf-field-row field-wcf-offer-no">';

		echo '<div class="wcf-field-row-heading">';

		echo '<label>' . __( 'Apply Tags', 'wp-fusion' ) . '-' . __( 'Offer Rejected', 'wp-fusion' ) . '</label>';

		echo '</div>';

		echo '<div class="wcf-field-row-content">';

		$args = array(
			'setting'   => $options['wpf-offer-rejected'],
			'meta_name' => 'wpf-offer-rejected',
		);

		wpf_render_tag_multiselect( $args );

		echo '<span class="description">' . sprintf( __( 'Apply these tags in %s when the offer is rejected', 'wp-fusion' ), wp_fusion()->crm->name ) . '</span>';

		echo '</div>';

		echo '</div>'; // End field row

		echo '</div>'; // End tab content

	}


}

new WPF_CartFlows();
