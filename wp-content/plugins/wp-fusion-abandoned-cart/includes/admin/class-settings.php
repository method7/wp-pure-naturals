<?php

class WPF_Abandoned_Cart_Settings {

	/**
	 * Get things started
	 *
	 * @since 1.0
	 * @return void
	 */

	public function __construct() {

		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

	}


	/**
	 * Adds Addons tab if not already present
	 *
	 * @access public
	 * @return void
	 */

	public function configure_sections( $page, $options ) {

		if ( ! isset( $page['sections']['addons'] ) ) {
			$page['sections'] = wp_fusion()->settings->insert_setting_before( 'import', $page['sections'], array( 'addons' => __( 'Addons', 'wp-fusion' ) ) );
		}

		return $page;

	}

	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['abandoned_cart_header'] = array(
			'title'   => __( 'Abandoned Cart Tracking', 'wp-fusion' ),
			'desc'    => __( '<a href="https://wpfusion.com/documentation/abandoned-cart-tracking/abandoned-cart-overview/" target="_blank">Read our documentation</a> for more information on abandoned cart tracking with WP Fusion.', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'addons',
		);

		if ( isset( wp_fusion_abandoned_cart()->crm ) && in_array( 'add_cart', wp_fusion_abandoned_cart()->crm->supports ) ) {

			$settings['abandoned_cart_sync_carts'] = array(
				'title'   => __( 'Sync Carts', 'wp-fusion' ),
				'desc'    => sprintf( __( 'Sync cart contents over the %s Abandoned Cart API.', 'wp-fusion' ), wp_fusion()->crm->name ),
				'std'     => 0,
				'type'    => 'checkbox',
				'section' => 'addons',
			);

			if ( class_exists( 'WooCommerce' ) ) {

				$choices = array();

				$sizes = get_intermediate_image_sizes();

				foreach ( $sizes as $size ) {
					$choices[ $size ] = $size;
				}

				asort( $choices );

				$settings['abandoned_cart_image_size'] = array(
					'title'   => __( 'Cart Items Image Size', 'wp-fusion' ),
					'desc'    => sprintf( __( 'Select an image size for product thumbnails sent to %s.', 'wp-fusion' ), wp_fusion()->crm->name ),
					'std'     => 'medium',
					'type'    => 'select',
					'choices' => $choices,
					'section' => 'addons',
				);

				$settings['abandoned_cart_categories'] = array(
					'title'   => __( 'Product Categories', 'wp-fusion' ),
					'std'     => 'categories',
					'type'    => 'radio',
					'choices' => array(
						'categories' => __( 'Sync the categories from the product as categories', 'wp-fusion' ),
						'attributes' => __( 'Sync the selected attributes of the cart item as categories', 'wp-fusion' ),
					),
					'section' => 'addons',
				);

			}

		}

		if ( class_exists( 'WooCommerce' ) ) {

			$settings['abandoned_cart_recovery_url_destination'] = array(
				'title'   => __( 'Recovery URL Destination', 'wp-fusion' ),
				'std'     => 'checkout',
				'type'    => 'radio',
				'choices' => array(
					'checkout' => 'Checkout',
					'cart'     => 'Cart',
					'current'  => 'Current Page',
				),
				'section' => 'addons',
			);

			$settings['abandoned_cart_recovery_url'] = array(
				'title'   => __( 'Recovery URL', 'wp-fusion' ),
				'desc'    => 'Select a custom field in ' . wp_fusion()->crm->name . ' to use for storing your cart recovery URL.',
				'std'     => false,
				'type'    => 'crm_field',
				'section' => 'addons',
			);

			$settings['abandoned_cart_value_field'] = array(
				'title'   => __( 'Value Field', 'wp-fusion' ),
				'desc'    => 'You can select a custom field in ' . wp_fusion()->crm->name . ' and the total value of the cart contents will be synced to this field. When checkout is completed the value will be set back to zero.',
				'std'     => false,
				'type'    => 'crm_field',
				'section' => 'addons',
			);

		}

		$settings['abandoned_cart_apply_tags'] = array(
			'title'   => __( 'Apply Tags', 'wp-fusion' ),
			'desc'    => __( 'Apply these tags when a user begins checkout. Read <a href="https://wpfusionplugin.com/documentation/#abandoned-cart-tracking" target="_blank">our documentation</a> for strategies for tracking abandoned carts.', 'wp-fusion' ),
			'std'     => array(),
			'type'    => 'assign_tags',
			'section' => 'addons',
		);

		$settings['abandoned_cart_add_to_cart'] = array(
			'title'   => __( 'Trigger on Add to Cart', 'wp-fusion' ),
			'desc'    => __( 'Trigger abandoned cart actions when a product is added to the cart for logged in users (instead of at checkout).', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'addons',
		);

		return $settings;

	}

}
