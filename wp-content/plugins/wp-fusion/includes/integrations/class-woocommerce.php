<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Woocommerce extends WPF_Integrations_Base {

	/**
	 * Woo 3.x compatibility check
	 */

	public $is_v3;

	/**
	 * Get things started
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		$this->slug  = 'woocommerce';
		$this->is_v3 = true;

		add_filter( 'wpf_user_register', array( $this, 'user_register' ) );
		add_filter( 'wpf_user_update', array( $this, 'user_update' ) );
		add_filter( 'wpf_meta_box_post_types', array( $this, 'unset_wpf_meta_boxes' ) );
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ), 10 );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 15 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );
		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_filter( 'wpf_skip_auto_login', array( $this, 'skip_auto_login' ) );
		add_filter( 'wpf_woocommerce_customer_data', array( $this, 'merge_fields_data' ), 10, 2 );

		// Login redirect
		add_filter( 'woocommerce_login_redirect', array( $this, 'maybe_bypass_login_redirect' ), 10, 2 );

		// Last updated
		add_filter( 'woocommerce_user_last_update_fields', array( $this, 'last_update_fields' ) );

		// Account info update
		add_filter( 'woocommerce_save_account_details', array( $this, 'save_account_details' ) );

		// Taxonomy settings
		add_action( 'admin_init', array( $this, 'register_taxonomy_form_fields' ) );

		// Order status changes
		add_action( 'woocommerce_order_status_processing', array( $this, 'woocommerce_apply_tags_checkout' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'woocommerce_apply_tags_checkout' ), 10, 1 );
		add_action( 'woocommerce_order_status_failed', array( $this, 'woocommerce_apply_tags_checkout' ), 10, 1 );
		add_action( 'wpf_woocommerce_async_checkout', array( $this, 'woocommerce_apply_tags_checkout' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 4 );

		// Sync auto generated passwords
		add_action( 'woocommerce_created_customer', array( $this, 'push_autogen_password' ), 10, 3 );

		// Cancelled / refunded orderes
		add_action( 'woocommerce_order_status_refunded', array( $this, 'woocommerce_order_refunded' ), 10 );

		// Remove add to cart buttons on Shop pages for restricted products
		add_action( 'woocommerce_loop_add_to_cart_link', array( $this, 'add_to_cart_buttons' ), 10, 2 );

		// Remove restricted products from shop loop & prevent adding to cart
		add_action( 'the_posts', array( $this, 'exclude_restricted_products' ), 10, 2 );
		add_action( 'woocommerce_add_to_cart_validation', array( $this, 'prevent_restricted_add_to_cart' ), 10, 3 );

		// Hide restricted variations
		add_filter( 'woocommerce_variation_is_purchasable', array( $this, 'variation_is_purchaseable' ), 10, 2 );
		add_filter( 'woocommerce_variation_is_active', array( $this, 'variation_is_purchaseable' ), 10, 2 );
		add_action( 'wp_print_styles', array( $this, 'variation_styles' ) );

		// Add meta boxes to Woo product editor
		add_action( 'woocommerce_product_data_panels', array( $this, 'woocommerce_write_panels' ) );
		add_action( 'woocommerce_product_write_panel_tabs', array( $this, 'woocommerce_write_panel_tabs' ) );
		add_action( 'wpf_woocommerce_panel', array( $this, 'panel_content' ), 5 );

		// Add order action
		add_filter( 'woocommerce_order_actions', array( $this, 'order_actions' ) );
		add_action( 'woocommerce_order_action_wpf_process', array( $this, 'process_order_action' ) );

		// Variations fields
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'variable_fields' ), 15, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variable_fields' ), 10, 2 );

		// Save changes to Woo meta box data
		add_action( 'save_post', array( $this, 'save_meta_box_data' ) );

		// Export functions
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_woocommerce_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_woocommerce', array( $this, 'batch_step' ) );

		// Coupons settings
		add_filter( 'woocommerce_coupon_data_tabs', array( $this, 'coupon_tabs' ) );
		add_action( 'woocommerce_coupon_data_panels', array( $this, 'coupon_data_panel' ) );
		add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'coupon_usage_restriction' ), 10, 2 );
		add_action( 'save_post_shop_coupon', array( $this, 'save_meta_box_data_coupon' ) );
		add_filter( 'woocommerce_coupon_is_valid', array( $this, 'coupon_is_valid' ), 10, 3 );

		// Apply coupons
		add_action( 'woocommerce_add_to_cart', array( $this, 'add_to_cart_apply_coupon' ), 30 ); // 30 so the cart totals have time to recalculate
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'add_to_cart_apply_coupon' ) );
		add_action( 'wpf_tags_modified', array( $this, 'maybe_apply_coupons' ), 10, 2 );

		// Coupon labels
		add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'rename_coupon_label' ), 10, 2 );
		add_filter( 'woocommerce_coupon_message', array( $this, 'coupon_success_message' ), 10, 3 );

		// Restrict access to shop page
		add_action( 'template_redirect', array( $this, 'restrict_access_to_shop' ) );

		// Maybe hide coupon field
		add_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_field_on_cart' ) );

		// Woo 3.x compatibility check
		add_action( 'init', array( $this, 'compatibility_test' ) );

		// Super secret admin / debugging tools
		add_action( 'wpf_settings_page_init', array( $this, 'settings_page_init' ) );
		add_action( 'admin_init', array( $this, 'clear_cron' ) );

	}


	/**
	 * Registers a new contact record for an order, for cases where we need to apply tags to guests before the order was received
	 *
	 * @access public
	 * @return int / false Contact ID
	 */

	public function maybe_create_contact_from_order( $order_id ) {

		$contact_id = get_post_meta( $order_id, wp_fusion()->crm->slug . '_contact_id', true );

		if ( ! empty( $contact_id ) ) {
			return $contact_id;
		}

		$order = wc_get_order( $order_id );

		$customer_data = array(
			'first_name' => $order->get_billing_first_name(),
			'last_name'  => $order->get_billing_last_name(),
			'user_email' => $order->get_billing_email(),
		);

		wpf_log(
			'info', 0, 'Creating contact record from guest for order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>', array(
				'source'     => 'woocommerce',
				'meta_array' => $customer_data,
			)
		);

		$contact_id = wp_fusion()->crm->add_contact( $customer_data );

		if ( ! empty( $contact_id ) && ! is_wp_error( $contact_id ) ) {
			update_post_meta( $order_id, wp_fusion()->crm->slug . '_contact_id', $contact_id );
		}

		return $contact_id;

	}

	/**
	 * Formats field data updated via the Update Account form
	 *
	 * @access public
	 * @return array User Meta
	 */

	public function save_account_details( $user_id ) {

		$user_meta = $_POST;

		if ( isset( $user_meta['account_first_name'] ) ) {
			$user_meta['first_name'] = $user_meta['account_first_name'];
		}

		if ( isset( $user_meta['account_email'] ) ) {
			$user_meta['user_email'] = $user_meta['account_email'];
		}

		if ( isset( $user_meta['account_last_name'] ) ) {
			$user_meta['last_name'] = $user_meta['account_last_name'];
		}

		if ( isset( $user_meta['password_1'] ) && ! empty( $user_meta['password_1'] ) ) {
			$user_meta['user_pass'] = $user_meta['password_1'];
		}

		wp_fusion()->user->push_user_meta( $user_id, $user_meta );

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
	 * Skips auto login on checkout pages
	 *
	 * @access public
	 * @return bool Skip
	 */

	public function skip_auto_login( $skip ) {

		if ( defined( 'WC_DOING_AJAX' ) ) {
			$skip = true;
		}

		$request_uris = array(
			'checkout',
		);

		foreach ( $request_uris as $uri ) {

			if ( strpos( $_SERVER['REQUEST_URI'], $uri ) !== false ) {
				$skip = true;
			}
		}

		return $skip;

	}

	/**
	 * Stop WooCommerce from redirecting to the My Account page if the wpf_return_to cookie is set
	 *
	 * @access public
	 * @return string Redirect
	 */

	public function maybe_bypass_login_redirect( $redirect, $user ) {

		if ( isset( $_COOKIE['wpf_return_to'] ) ) {

			wp_fusion()->access->return_after_login( $user->user_login, $user );

		}

		return $redirect;

	}

	/**
	 * Set the last_updated meta key on the user when tags or contact ID are modified, for Metorik
	 *
	 * @access public
	 * @return array Fields
	 */

	public function last_update_fields( $fields ) {

		$fields[] = wp_fusion()->crm->slug . '_tags';
		$fields[] = wp_fusion()->crm->slug . '_contact_id';

		return $fields;

	}

	/**
	 * Merge WCFF data into the checkout meta data
	 *
	 * @access  public
	 * @return  array Customer data
	 */

	public function merge_fields_data( $customer_data, $order ) {

		foreach ( $order->get_items() as $item ) {

			$item_meta = $item->get_meta_data();

			if ( ! empty( $item_meta ) ) {

				foreach ( $item_meta as $meta ) {

					if ( is_a( $meta, 'WC_Meta_Data' ) ) {

						$data = $meta->get_data();

						$key                   = strtolower( str_replace( ' ', '_', $data['key'] ) );
						$customer_data[ $key ] = $data['value'];

					}
				}
			}
		}

		return $customer_data;

	}

	/**
	 * Registers additional Woocommerce settings
	 *
	 * @access  public
	 * @return  array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['woo_header'] = array(
			'title'   => __( 'WooCommerce Integration', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['woo_tags'] = array(
			'title'   => __( 'Apply Tags to Customers', 'wp-fusion' ),
			'desc'    => __( 'These tags will be applied to all WooCommerce customers.', 'wp-fusion' ),
			'std'     => array(),
			'type'    => 'assign_tags',
			'section' => 'integrations',
		);

		$settings['woo_hide'] = array(
			'title'   => __( 'Hide Restricted Products', 'wp-fusion' ),
			'desc'    => __( 'If a user can\'t access a product, hide it from the Shop page.', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'integrations',
		);

		$settings['woo_error_message'] = array(
			'title'   => __( 'Restricted Product Error Message', 'wp-fusion' ),
			'desc'    => __( 'This message will be displayed if a customer attempts to add a restricted product to their cart.', 'wp-fusion' ),
			'std'     => 'You do not have sufficient privileges to purchase this product. Please contact support.',
			'type'    => 'text',
			'format'  => 'html',
			'section' => 'integrations',
		);

		$settings['woo_async'] = array(
			'title'   => __( 'Asynchronous Checkout', 'wp-fusion' ),
			'desc'    => __( 'Runs WP Fusion post-checkout actions asynchronously to speed up load times.', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'integrations',
		);

		$settings['woo_hide_coupon_field'] = array(
			'title'   => __( 'Hide Coupon Field', 'wp-fusion' ),
			'desc'    => __( 'Hide the coupon input field on the checkout / cart screen (used with auto-applied coupons).', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'integrations',
		);

		if ( is_array( wp_fusion()->crm->supports ) && in_array( 'add_tags', wp_fusion()->crm->supports ) ) {

			$settings['woo_header_2'] = array(
				'title'   => __( 'WooCommerce Automatic Tagging', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'heading',
				'section' => 'integrations',
			);

			$settings['woo_category_tagging'] = array(
				'title'   => __( 'Product Category Tagging', 'wp-fusion' ),
				'desc'    => __( 'Generate and apply tags based on the category of every product purchased.', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'checkbox',
				'section' => 'integrations',
			);

			$settings['woo_name_tagging'] = array(
				'title'   => __( 'Product Name Tagging', 'wp-fusion' ),
				'desc'    => __( 'Generate and apply tags based on the name of every product purchased.', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'checkbox',
				'section' => 'integrations',
			);

			$settings['woo_sku_tagging'] = array(
				'title'   => __( 'Product SKU Tagging', 'wp-fusion' ),
				'desc'    => __( 'Generate and apply tags based on the SKU of every product purchased.', 'wp-fusion' ),
				'std'     => 0,
				'type'    => 'checkbox',
				'section' => 'integrations',
			);

			$settings['woo_tagging_prefix'] = array(
				'title'   => __( 'Tag Prefix', 'wp-fusion' ),
				'desc'    => __( 'Enter a prefix (i.e. "Purchased") for any automatically-generated tags. Use shortcode [status] to dynamically insert the order status.', 'wp-fusion' ),
				'type'    => 'text',
				'section' => 'integrations',
			);

		}

		$settings['woo_header_3'] = array(
			'title'   => __( 'WooCommerce Order Status Tagging', 'wp-fusion' ),
			'std'     => 0,
			'desc'    => __( '<p>The settings here let you apply tags to a contact when an order status is changed in WooCommerce.</p><p>This is useful if you\'re manually changing order statuses, for example marking an order Completed after it\'s been shipped.</p>', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$statuses = wc_get_order_statuses();

		// Maybe get custom statuses from Woo Order Status Manager

		if ( function_exists( 'wc_order_status_manager_get_order_status_posts' ) ) {

			$statuses = array();

			foreach ( wc_order_status_manager_get_order_status_posts() as $status ) {
				$statuses[ 'wc-' . $status->post_name ] = $status->post_title;
			}

		}

		foreach ( $statuses as $key => $label ) {

			$settings[ 'woo_status_tagging_' . $key ] = array(
				'title'       => $label,
				'type'        => 'assign_tags',
				'section'     => 'integrations',
			);

		}

		return $settings;

	}

	/**
	 * Adds WooCommerce field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */

	public function add_meta_field_group( $field_groups ) {

		$field_groups['woocommerce'] = array(
			'title'  => 'WooCommerce Customer',
			'fields' => array(),
		);

		$field_groups['woocommerce_variations'] = array(
			'title'  => 'WooCommerce Attributes',
			'fields' => array(),
		);

		$field_groups['woocommerce_order'] = array(
			'title'  => 'WooCommerce Order',
			'fields' => array(),
		);

		return $field_groups;

	}

	/**
	 * Sets field labels and types for WooCommerce custom fields
	 *
	 * @access  public
	 * @return  array Meta fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$shipping_fields = WC()->countries->get_address_fields( '', 'shipping_' );
		$billing_fields  = WC()->countries->get_address_fields( '', 'billing_' );

		$woocommerce_fields = array_merge( $shipping_fields, $billing_fields );

		// Support for WooCommerce Checkout Field Editor
		$additional_fields = get_option( 'wc_fields_additional' );

		if ( ! empty( $additional_fields ) ) {
			$woocommerce_fields = array_merge( $woocommerce_fields, $additional_fields );
		}

		foreach ( $woocommerce_fields as $key => $data ) {

			if ( ! isset( $meta_fields[ $key ] ) ) {
				$meta_fields[ $key ] = array();
			}

			if ( isset( $data['label'] ) ) {
				$woo_field_data = array( 'label' => $data['label'] );
			} else {
				$woo_field_data = array( 'label' => '' );
			}

			if ( isset( $data['type'] ) ) {
				$woo_field_data['type'] = $data['type'];
			} else {
				$woo_field_data['type'] = 'text';
			}

			$meta_fields[ $key ] = array_merge( $meta_fields[ $key ], $woo_field_data );

			$meta_fields[ $key ]['group'] = 'woocommerce';

		}

		// Support for WooCommerce Checkout Field Editor Pro

		if ( class_exists( 'WCFE_Checkout_Section' ) ) {

			$additional_fields = get_option( 'thwcfe_sections' );

			if ( ! empty( $additional_fields ) ) {

				foreach ( $additional_fields as $section ) {

					if ( ! empty( $section->fields ) ) {

						foreach ( $section->fields as $field ) {

							if ( ! isset( $meta_fields[ $field->id ] ) ) {

								$meta_fields[ $field->id ] = array(
									'label' => $field->title,
									'type'  => $field->type,
									'group' => 'woocommerce',
								);

							}
						}
					}
				}
			}
		}

		$meta_fields['generated_password'] = array(
			'label' => 'Generated Password',
			'type'  => 'text',
			'group' => 'woocommerce',
		);

		$meta_fields['order_notes'] = array(
			'label' => 'Order Notes',
			'type'  => 'text',
			'group' => 'woocommerce_order',
		);

		$meta_fields['order_date'] = array(
			'label' => 'Last Order Date',
			'type'  => 'date',
			'group' => 'woocommerce_order',
		);

		$meta_fields['coupon_code'] = array(
			'label' => 'Last Coupon Used',
			'type'  => 'text',
			'group' => 'woocommerce_order',
		);

		$meta_fields['order_id'] = array(
			'label' => 'Last Order ID',
			'type'  => 'int',
			'group' => 'woocommerce_order',
		);

		$meta_fields['order_total'] = array(
			'label' => 'Last Order Total',
			'type'  => 'int',
			'group' => 'woocommerce_order',
		);

		$meta_fields['order_payment_method'] = array(
			'label' => 'Last Order Payment Method',
			'type'  => 'text',
			'group' => 'woocommerce_order',
		);

		// Get attributes

		$args = array(
			'posts_per_page' => 100,
			'post_type'      => 'product',
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_product_attributes',
					'compare' => 'EXISTS',
				),
			),
		);

		$products = get_posts( $args );

		if ( ! empty( $products ) ) {

			foreach ( $products as $product_id ) {

				$attributes = get_post_meta( $product_id, '_product_attributes', true );

				if ( ! empty( $attributes ) ) {

					foreach ( $attributes as $key => $attribute ) {

						$meta_fields[ $key ] = array(
							'label' => $attribute['name'],
							'type'  => 'text',
							'group' => 'woocommerce_variations',
						);

					}
				}
			}
		}

		return $meta_fields;

	}


	/**
	 * Removes standard WPF meta boxes from Woo admin pages
	 *
	 * @access  public
	 * @return  array Post Types
	 */

	public function unset_wpf_meta_boxes( $post_types ) {

		unset( $post_types['shop_order'] );
		unset( $post_types['shop_coupon'] );

		return $post_types;

	}


	/**
	 * Removes restricted products from shop archives
	 *
	 * @access  public
	 * @return  array Posts
	 */

	public function exclude_restricted_products( $posts, $query ) {

		if ( is_admin() || wp_fusion()->settings->get( 'woo_hide' ) != true ) {
			return $posts;
		}

		if ( ! $query->is_archive() ) {
			return $posts;
		}

		if ( $query->query_vars['post_type'] != 'product' && ! isset( $query->query_vars['product_cat'] ) ) {
			return $posts;
		}

		foreach ( $posts as $index => $product ) {

			if ( ! wp_fusion()->access->user_can_access( $product->ID ) ) {
				unset( $posts[ $index ] );
			}
		}

		return array_values( $posts );

	}

	/**
	 * Prevents restricted products from being added to the cart
	 *
	 * @access  public
	 * @return  bool Passed
	 */

	public function prevent_restricted_add_to_cart( $passed, $product_id, $quantity ) {

		if ( $quantity == 0 || wp_fusion()->access->user_can_access( $product_id ) ) {
			return $passed;
		}

		wc_add_notice( wp_fusion()->settings->get( 'woo_error_message' ), 'error' );

		return false;

	}

	/**
	 * Blocks restricted variations from purchase
	 *
	 * @access  public
	 * @return  bool Is purchaseable
	 */

	public function variation_is_purchaseable( $is_purchaseable, $variation ) {

		if ( ! wp_fusion()->access->user_can_access( $variation->get_id() ) ) {
			return false;
		}

		return $is_purchaseable;

	}

	/**
	 * Hide restricted variations
	 *
	 * @access  public
	 */

	public function variation_styles() {

		echo '<!-- WP Fusion -->';
		echo '<style type="text/css">.woocommerce .product .variations option:disabled { display: none; } </style>';

	}


	/**
	 * Removes Add to Cart buttons in Store page for restricted products
	 *
	 * @access  public
	 * @return  string Link
	 */

	public function add_to_cart_buttons( $link, $product ) {

		if ( ! wp_fusion()->access->user_can_access( $product->get_id() ) ) {
			$link = '';
		}

		return $link;

	}

	/**
	 * Adapt WooCommerce checkout fields to CRM fields for creating customers at checkout
	 *
	 * @access public
	 * @return array Post Data
	 */

	public function user_register( $post_data ) {

		$field_map = array(
			'account_password'   => 'user_pass',
			'password'           => 'user_pass',
			'billing_email'      => 'user_email',
			'account_username'   => 'user_login',
			'billing_first_name' => 'first_name',
			'billing_last_name'  => 'last_name',
		);

		$post_data = $this->map_meta_fields( $post_data, $field_map );

		// Get the username if autogenerated by Woo
		if ( empty( $post_data['user_login'] ) ) {
			$user                    = get_user_by( 'email', $post_data['user_email'] );
			$post_data['user_login'] = $user->user_login;
		}

		return $post_data;

	}

	/**
	 * Format WooCommerce account update fields
	 *
	 * @access public
	 * @return array User Meta
	 */

	public function user_update( $user_meta ) {

		$contact_fields = wp_fusion()->settings->get( 'contact_fields', array() );

		if ( ! empty( $user_meta['billing_country'] ) && isset( $contact_fields['billing_country']['type'] ) && $contact_fields['billing_country']['type'] == 'text' && isset( WC()->countries->countries[ $user_meta['billing_country'] ] ) ) {

			// Allow sending full country name instead of abbreviation

			$user_meta['billing_country'] = WC()->countries->countries[ $user_meta['billing_country'] ];

		}

		if ( ! empty( $user_meta['shipping_country'] ) && isset( $contact_fields['shipping_country']['type'] ) && $contact_fields['shipping_country']['type'] == 'text' && isset( WC()->countries->countries[ $user_meta['shipping_country'] ] ) ) {

			// Allow sending full country name instead of abbreviation

			$user_meta['shipping_country'] = WC()->countries->countries[ $user_meta['shipping_country'] ];

		}

		return $user_meta;

	}

	/**
	 * Gets customer details from the WooCommerce order when customer isn't a registered user
	 *
	 * @access public
	 * @return array Contact Data
	 */

	public function get_customer_data( $order ) {

		$order_data    = $order->get_data();
		$customer_data = array();

		foreach ( $order_data as $key => $value ) {

			if ( is_array( $value ) ) {

				// Nested params like Billing and Shipping info
				foreach ( $value as $sub_key => $sub_value ) {

					if ( is_object( $sub_value ) ) {
						continue;
					}

					$customer_data[ $key . '_' . $sub_key ] = $sub_value;

				}
			} elseif ( ! is_object( $value ) && ! is_a( $value, 'WC_DateTime' ) ) {

				// Regular params
				$customer_data[ $key ] = $value;

			}
		}

		// Meta data
		foreach ( $order_data['meta_data'] as $meta ) {

			if ( is_a( $meta, 'WC_Meta_Data' ) ) {

				$data = $meta->get_data();

				if ( ! is_array( $data['value'] ) ) {

					$customer_data[ $data['key'] ] = $data['value'];

				}
			}
		}

		$contact_fields = wp_fusion()->settings->get( 'contact_fields' );

		$user_id = $order->get_user_id();

		$user = get_userdata( $user_id );

		// Map some common additional fields
		foreach ( $customer_data as $key => $value ) {

			if ( $key == 'billing_email' ) {

				if ( ! empty( $user_id ) ) {

					$customer_data['user_email'] = $user->user_email;

				} else {

					$customer_data['user_email'] = $value;

				}
			} elseif ( $key == 'billing_first_name' ) {

				if ( $contact_fields['billing_first_name']['active'] == false || $contact_fields['billing_first_name']['crm_field'] == $contact_fields['first_name']['crm_field'] || empty( $user_id ) ) {

					$customer_data['first_name'] = $value;

				} else {

					$customer_data['first_name'] = $user->first_name;

				}
			} elseif ( $key == 'billing_last_name' ) {

				if ( $contact_fields['billing_last_name']['active'] == false || $contact_fields['billing_last_name']['crm_field'] == $contact_fields['last_name']['crm_field'] || empty( $user_id ) ) {

					$customer_data['last_name'] = $value;

				} else {

					$customer_data['last_name'] = $user->last_name;

				}
			} elseif ( $key == 'billing_state' || $key == 'shipping_state' ) {

				if ( ! empty( $value ) && isset( WC()->countries->states[ $customer_data['billing_country'] ] ) && isset( WC()->countries->states[ $customer_data['billing_country'] ][ $value ] ) ) {

					$customer_data[ $key ] = WC()->countries->states[ $customer_data['billing_country'] ][ $value ];

				}

			} elseif ( $key == 'billing_country' && ! empty( $value ) && isset( $contact_fields['billing_country']['type'] ) && $contact_fields['billing_country']['type'] == 'text' ) {

				// Allow sending full country name instead of abbreviation

				$customer_data[ $key ] = WC()->countries->countries[ $value ];

			} elseif ( $key == 'shipping_country '&& ! empty( $value ) && isset( $contact_fields['shipping_country']['type'] ) && $contact_fields['shipping_country']['type'] == 'text' ) {

				// Allow sending full country name instead of abbreviation

				$customer_data[ $key ] = WC()->countries->countries[ $value ];

			}
		}

		$order_date = $order->get_date_paid();

		if ( is_object( $order_date ) && isset( $order_date->date ) ) {
			$order_date = $order_date->date;
		} else {
			$order_date = get_the_date( 'c', $order->get_id() );
		}

		$customer_data['order_date']           = $order_date;
		$customer_data['order_total']          = $order->get_total();
		$customer_data['order_id']             = $order->get_order_number();
		$customer_data['order_notes']          = $order->get_customer_note();
		$customer_data['order_payment_method'] = $order->get_payment_method_title();

		// Coupons

		if ( method_exists( $order, 'get_coupon_codes' ) ) {

			$coupons = $order->get_coupon_codes();

			if ( ! empty( $coupons ) ) {
				$customer_data['coupon_code'] = $coupons[0];
			}

		}

		return apply_filters( 'wpf_woocommerce_customer_data', $customer_data, $order );

	}

	/**
	 * Apply tags when a product is purchased in WooCommerce
	 *
	 * @access public
	 * @return void
	 */

	public function woocommerce_apply_tags_checkout( $order_id, $doing_async = false ) {

		// Prevents the API calls being sent multiple times for the same order

		if ( get_post_meta( $order_id, 'wpf_complete', true ) ) {
			return true;
		}

		// Send async request if async enabled

		if ( ! is_admin() && wp_fusion()->settings->get( 'woo_async' ) == true && $doing_async == false ) {

			$order = wc_get_order( $order_id );

			wpf_log( 'info', $order->get_user_id(), 'Dispatching WooCommerce order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a> to async checkout queue.' );

			wp_fusion()->batch->quick_add( 'wpf_woocommerce_async_checkout', array( $order_id, true ) );
			return;

		}

		// See if checkout process is already running
		$started = get_transient( 'wpf_woo_started_' . $order_id );

		if ( ! empty( $started ) ) {
			return true;
		} else {
			set_transient( 'wpf_woo_started_' . $order_id, true, HOUR_IN_SECONDS );
		}

		$order = wc_get_order( $order_id );

		if ( false == $order ) {

			wpf_log( 'error', 0, 'Unable to find order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>. Aborting.', array( 'source' => 'woocommerce' ) );

			delete_transient( 'wpf_woo_started_' . $order_id );

			return false;
		}

		$email   = $order->get_billing_email();
		$user_id = $order->get_user_id();
		$status  = $order->get_status();

		// Allow overriding the billing email used for lookup
		$email = apply_filters( 'wpf_woocommerce_billing_email', $email, $order );

		// Allow overriding the user ID used for lookup
		$user_id = apply_filters( 'wpf_woocommerce_user_id', $user_id, $order );

		// Sometimes the status may have changed between when the function was called and when get_status() is run during an automated renewal
		if ( 'woocommerce_order_status_failed' == current_filter() ) {
			$status = 'failed';
		}

		// Logger
		wpf_log( 'info', $user_id, 'New WooCommerce order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>', array( 'source' => 'woocommerce' ) );

		if ( empty( $email ) && empty( $user_id ) ) {

			wpf_log( 'error', 0, 'No email address specified for WooCommerce order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>. Aborting', array( 'source' => 'woocommerce' ) );

			delete_transient( 'wpf_woo_started_' . $order_id );

			// Denotes that the WPF actions have already run for this order
			update_post_meta( $order_id, 'wpf_complete', true );

			return false;

		}

		if ( ! empty( $user_id ) ) {

			// If user is found, lookup the contact ID
			$contact_id = wp_fusion()->user->get_contact_id( $user_id );

			if ( empty( $contact_id ) ) {
				// If not found, check in the CRM and update locally
				$contact_id = wp_fusion()->user->get_contact_id( $user_id, true );
			}
		} else {

			// Try seeing if an existing contact ID exists
			$contact_id = wp_fusion()->crm->get_contact_id( $email );

			if ( is_wp_error( $contact_id ) ) {

				wpf_log( $contact_id->get_error_code(), $user_id, 'Error getting contact ID for <strong>' . $email . '</strong>: ' . $contact_id->get_error_message() );
				delete_transient( 'wpf_woo_started_' . $order_id );
				return false;

			}
		}

		if ( user_can( $user_id, 'manage_options' ) ) {
			wpf_log( 'notice', $user_id, 'You\'re currently logged into the site as an administrator. This checkout will update your existing contact ID #' . $contact_id . ' in ' . wp_fusion()->crm->name . '. If you\'re testing checkouts use an incognito window.', array( 'source' => 'woocommerce' ) );
		}

		// Format order data

		$order_data = $this->get_customer_data( $order );

		// Allow for early exit

		if ( empty( $order_data ) ) {
			wpf_log( 'notice', $user_id, 'Aborted checkout for <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . ', no order meta supplied.</a>' );
		}

		// No need to update meta during a renewal order 

		if ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order ) ) {

			$send_meta = false;

			// ...unless order_id or order_date are enabled for sync

			if ( wp_fusion()->crm_base->is_field_active( 'order_id' ) || wp_fusion()->crm_base->is_field_active( 'order_date' ) ) {
				$send_meta = true;
			}
		} else {

			$send_meta = true;

		}

		// Don't run during renewals unless the order meta fields are enabled

		if ( $send_meta ) {

			// If contact doesn't exist in CRM
			if ( $contact_id == false ) {

				// Logger
				wpf_log(
					'info', 0, 'Processing guest checkout for order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>', array(
						'source'     => 'woocommerce',
						'meta_array' => $order_data,
					)
				);

				$contact_id = wp_fusion()->crm->add_contact( $order_data );

				if ( is_wp_error( $contact_id ) ) {

					wpf_log( 'error', $user_id, 'Error while adding contact: ' . $contact_id->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
					delete_transient( 'wpf_woo_started_' . $order_id );
					return false;

				}

				$order->add_order_note( wp_fusion()->crm->name . ' contact ID ' . $contact_id . ' created via guest-checkout.' );

				// Set contact ID locally
				if ( ! empty( $user_id ) ) {
					update_user_meta( $user_id, wp_fusion()->crm->slug . '_contact_id', $contact_id );
				}

				update_post_meta( $order_id, wp_fusion()->crm->slug . '_contact_id', $contact_id );

				do_action( 'wpf_guest_contact_created', $contact_id, $order_data['user_email'] );

			} else {

				if ( empty( $user_id ) ) {

					wpf_log(
						'info', 0, 'Processing guest checkout for order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>, for existing contact ID ' . $contact_id, array(
							'source'     => 'woocommerce',
							'meta_array' => $order_data,
						)
					);

					$result = wp_fusion()->crm->update_contact( $contact_id, $order_data );

					if ( is_wp_error( $result ) ) {
						wpf_log( 'error', $user_id, 'Error while updating contact: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
						delete_transient( 'wpf_woo_started_' . $order_id );
						return false;
					}

					update_post_meta( $order_id, wp_fusion()->crm->slug . '_contact_id', $contact_id );

					do_action( 'wpf_guest_contact_updated', $contact_id, $order_data['user_email'] );

				} else {

					wp_fusion()->user->push_user_meta( $user_id, $order_data );

				}

			}

			$apply_tags  = array();
			$remove_tags = array();

			// Possibly apply tags for any configured coupons

			if ( method_exists( $order, 'get_coupon_codes' ) ) {

				$coupons = $order->get_coupon_codes();

				if ( ! empty( $coupons ) ) {

					foreach ( $coupons as $coupon_code ) {

						$coupon_id = wc_get_coupon_id_by_code( $coupon_code );

						$settings = get_post_meta( $coupon_id, 'wpf-settings-woo', true );

						if ( ! empty( $settings ) && ! empty( $settings['apply_tags'] ) ) {
							$apply_tags = array_merge( $apply_tags, $settings['apply_tags'] );
						}

					}
				}

			}

			if ( $status != 'failed' && $status != 'pending' ) {

				// Get global tags
				$global_tags = wp_fusion()->settings->get( 'woo_tags', array() );

				if ( is_array( $global_tags ) && ! empty( $global_tags ) ) {
					$apply_tags = array_merge( $apply_tags, $global_tags );
				}

				// Prepare for term stuff
				$product_taxonomies = get_object_taxonomies( 'product' );

				foreach ( $order->get_items() as $item ) {

					$product_id = $item->get_product_id();

					$settings = get_post_meta( $product_id, 'wpf-settings-woo', true );

					// Apply tags for products
					if ( ! empty( $settings['apply_tags'] ) ) {
						$apply_tags = array_merge( $apply_tags, $settings['apply_tags'] );
					}

					// Remove transaction failed tags
					if ( ! empty( $settings['apply_tags_failed'] ) ) {
						$remove_tags = array_merge( $remove_tags, $settings['apply_tags_failed'] );
					}

					$product = $item->get_product();

					$auto_tagging_prefix = wp_fusion()->settings->get( 'woo_tagging_prefix', false );

					// Maybe insert the order status
					$auto_tagging_prefix = str_replace( '[status]', wc_get_order_status_name( $status ), $auto_tagging_prefix );

					if ( ! empty( $auto_tagging_prefix ) ) {
						$auto_tagging_prefix = trim( $auto_tagging_prefix ) . ' ';
					}

					// Handling for deleted products

					if ( ! empty( $product ) ) {

						// Apply the tags for variations

						if ( $product->is_type( 'variation' ) ) {

							if ( isset( $settings['apply_tags_variation'] ) && ! empty( $settings['apply_tags_variation'][ $item['variation_id'] ] ) ) {

								// Old method where variation settings were stored on the product
								$apply_tags = array_merge( $apply_tags, $settings['apply_tags_variation'][ $item['variation_id'] ] );

							} else {

								$variation_settings = get_post_meta( $item['variation_id'], 'wpf-settings-woo', true );

								if ( is_array( $variation_settings ) && isset( $variation_settings['apply_tags_variation'][ $item['variation_id'] ] ) ) {

									$apply_tags = array_merge( $apply_tags, $variation_settings['apply_tags_variation'][ $item['variation_id'] ] );

								}
							}

							// For taxonomy tagging we need to exclude attributes

							$variation_attributes = $product->get_variation_attributes();

						}

						// Auto tagging based on name
						if ( wp_fusion()->settings->get( 'woo_name_tagging' ) == true ) {

							if ( ! in_array( $auto_tagging_prefix . $product->get_title(), $apply_tags ) ) {

								$apply_tags[] = $auto_tagging_prefix . $product->get_title();

							}
						}

						// Auto tagging based on SKU
						if ( wp_fusion()->settings->get( 'woo_sku_tagging' ) == true ) {

							if ( ! in_array( $auto_tagging_prefix . $product->get_sku(), $apply_tags ) ) {

								$apply_tags[] = $auto_tagging_prefix . $product->get_sku();

							}
						}
					}

					// Term stuff
					foreach ( $product_taxonomies as $product_taxonomy ) {

						$product_terms = get_the_terms( $product_id, $product_taxonomy );

						if ( ! empty( $product_terms ) ) {

							foreach ( $product_terms as $term ) {

								// For taxonomy tagging we need to exclude attributes

								if ( isset( $variation_attributes ) ) {

									foreach ( $variation_attributes as $key => $value ) {

										$key = str_replace( 'attribute_', '', $key );

										if ( $term->taxonomy == $key && $term->slug != $value ) {
											continue 2;
										}
									}
								}

								$term_tags = get_term_meta( $term->term_id, 'wpf-settings-woo', true );

								if ( ! empty( $term_tags ) && ! empty( $term_tags['apply_tags'] ) ) {

									$apply_tags = array_merge( $apply_tags, $term_tags['apply_tags'] );

								}

								if ( $product_taxonomy == 'product_cat' && wp_fusion()->settings->get( 'woo_category_tagging' ) == true ) {

									if ( ! in_array( $auto_tagging_prefix . $term->name, $apply_tags ) ) {
										$apply_tags[] = $auto_tagging_prefix . $term->name;
									}
								}
							}
						}
					}
				}
			} elseif ( $status == 'failed' ) {

				$did_actions = false;

				foreach ( $order->get_items() as $item ) {

					$product_id = $item->get_product_id();

					$settings = get_post_meta( $product_id, 'wpf-settings-woo', true );

					// Apply tags for products
					if ( ! empty( $settings['apply_tags_failed'] ) ) {
						$apply_tags = array_merge( $apply_tags, $settings['apply_tags_failed'] );
						$did_actions = true;
					}
				}

				if ( $did_actions ) {
					$order->add_order_note( 'WP Fusion order actions completed for failed payment.' );
				}

			}

			// Get global status tags

			$status_tags = wp_fusion()->settings->get( 'woo_status_tagging_wc-' . $status, array() );

			if ( ! empty( $status_tags ) ) {
				$apply_tags = array_merge( $apply_tags, $status_tags );
			}

			// Remove duplicates
			$apply_tags  = array_unique( $apply_tags );
			$remove_tags = array_unique( $remove_tags );

			// Remove transaction failed tags
			if ( ! empty( $remove_tags ) ) {

				if ( empty( $user_id ) ) {

					wp_fusion()->crm->remove_tags( $remove_tags, $contact_id );

				} else {

					// Registered users
					wp_fusion()->user->remove_tags( $remove_tags, $user_id );

				}
			}

			$apply_tags = apply_filters( 'wpf_woocommerce_apply_tags_checkout', $apply_tags, $order );

			// Apply the tags
			if ( ! empty( $apply_tags ) ) {

				if ( empty( $user_id ) ) {

					// Guest checkout
					wpf_log( 'info', 0, 'Applying tags to guest checkout for contact ID ' . $contact_id . ': ', array( 'tag_array' => $apply_tags ) );
					$result = wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );

				} else {

					// Registered users
					$result = wp_fusion()->user->apply_tags( $apply_tags, $user_id );

				}

				if ( is_wp_error( $result ) ) {
					$order->add_order_note( 'Error applying tags for order ID: ' . $order_id );
					wpf_log( 'error', 0, 'Error <strong>' . $result->get_error_message() . '</strong> while applying tags: ', array( 'tag_array' => $apply_tags ) );
					return false;
				}
			}

		} // End check for is_renewal

		if ( $status != 'failed' && $status != 'pending' ) {

			// Denotes that the WPF actions have already run for this order
			update_post_meta( $order_id, 'wpf_complete', true );

			// Run payment complete action

			do_action( 'wpf_woocommerce_payment_complete', $order_id, $contact_id );

			$order->add_order_note( 'WP Fusion order actions completed.' );

		}

		// Order is finished, remove locking
		delete_transient( 'wpf_woo_started_' . $order_id );

	}

	/**
	 * Pushes password fields for when Woo is set to auto generate password
	 *
	 * @access public
	 * @return void
	 */

	public function push_autogen_password( $customer_id, $new_customer_data, $password_generated ) {

		if ( false == $password_generated ) {
			return;
		}

		$update_data = array(
			'user_pass'          => $new_customer_data['user_pass'],
			'generated_password' => $new_customer_data['user_pass'],
		);

		wp_fusion()->user->push_user_meta( $customer_id, $update_data );

	}

	/**
	 * Triggered when an order is refunded / cancelled
	 *
	 * @access public
	 * @return void
	 */

	public function woocommerce_order_refunded( $order_id ) {

		$order   = wc_get_order( $order_id );
		$email   = $order->get_billing_email();
		$user_id = $order->get_user_id();

		if ( empty( $user_id ) ) {
			$contact_id = wp_fusion()->crm->get_contact_id( $email );
		}

		if ( empty( $user_id ) && empty( $contact_id ) ) {
			wpf_log( 'error', 0, 'Unable to process refund actions for order #' . $order_id . '. No user or contact record found.', array( 'source' => 'woocommerce' ) );
		}

		wpf_log( 'info', $user_id, 'Processing refund actions for WooCommerce order #' . $order_id . '.', array( 'source' => 'woocommerce' ) );

		$items = $order->get_items();

		$remove_tags = array();

		foreach ( $items as $item ) {

			$product_id = $item->get_product_id();

			$settings = get_post_meta( $product_id, 'wpf-settings-woo', true );

			if ( ! empty( $settings['apply_tags'] ) ) {
				$remove_tags = array_merge( $remove_tags, $settings['apply_tags'] );
			}

			$product = $item->get_product();

			if ( ! empty( $product ) ) {

				// Auto tagging based on name
				if ( wp_fusion()->settings->get( 'woo_name_tagging' ) == true ) {

					$remove_tags[] = $product->get_title();

				}

				// Auto tagging based on SKU
				if ( wp_fusion()->settings->get( 'woo_sku_tagging' ) == true ) {

					$remove_tags[] = $product->get_sku();

				}

			}

			// Variations
			if ( isset( $item['variation_id'] ) && $item['variation_id'] != 0 ) {

				if ( isset( $settings['apply_tags_variation'] ) && ! empty( $settings['apply_tags_variation'][ $item['variation_id'] ] ) ) {

					$variation_tags = $settings['apply_tags_variation'][ $item['variation_id'] ];

				} else {

					$variation_settings = get_post_meta( $item['variation_id'], 'wpf-settings-woo', true );

					if ( is_array( $variation_settings ) && isset( $variation_settings['apply_tags_variation'][ $item['variation_id'] ] ) ) {

						$variation_tags = $variation_settings['apply_tags_variation'][ $item['variation_id'] ];

					}
				}

				if ( ! empty( $variation_tags ) ) {

					$remove_tags = array_merge( $remove_tags, $variation_tags );
				}
			}

			if ( ! empty( $settings['apply_tags_refunded'] ) && $order->has_status( 'refunded' ) ) {

				if ( ! empty( $user_id ) ) {
					wp_fusion()->user->apply_tags( $settings['apply_tags_refunded'], $user_id );
				} else {
					wp_fusion()->crm->apply_tags( $settings['apply_tags_refunded'], $contact_id );
				}
			}
		}

		if ( ! empty( $remove_tags ) ) {

			if ( ! empty( $user_id ) ) {
				wp_fusion()->user->remove_tags( $remove_tags, $user_id );
			} else {
				wp_fusion()->crm->remove_tags( $remove_tags, $contact_id );
			}
		}

	}

	/**
	 * Triggered when an order status is changed
	 *
	 * @access public
	 * @return void
	 */

	public function order_status_changed( $order_id, $old_status, $new_status, $order ) {

		$status_tags = wp_fusion()->settings->get( 'woo_status_tagging_wc-' . $new_status );

		if ( empty( $status_tags ) ) {
			return;
		}

		$user_id = $order->get_user_id();

		if ( ! empty( $user_id ) ) {

			// Registered users
			wp_fusion()->user->apply_tags( $status_tags, $user_id );

		} else {

			$contact_id = get_post_meta( $order_id, wp_fusion()->crm->slug . '_contact_id', true );

			if ( empty( $contact_id ) ) {

				$contact_id = wp_fusion()->crm->get_contact_id( $order->get_billing_email() );

			}

			if ( ! empty( $contact_id ) ) {

				wpf_log( 'info', 0, 'Order status changed to ' . $new_status . '. Applying tags to ' . $order->get_billing_email() . ':' , array( 'tag_array' => $status_tags, 'source' => 'woocommerce' ) );

				wp_fusion()->crm->apply_tags( $status_tags, $contact_id );

			} 

		}

	}

	/**
	 * Outputs custom panels to WooCommerce product config screen
	 *
	 * @access public
	 * @return mixed
	 */

	public function woocommerce_write_panels() {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'wpf_meta_box_woo', 'wpf_meta_box_woo_nonce' );

		echo '<div id="wp_fusion_tab" class="panel woocommerce_options_panel wpf-meta">';

		global $post;

		// Writes the panel content
		do_action( 'wpf_woocommerce_panel', $post->ID );

		echo '</div>';

	}

	/**
	 * Displays "apply tags" field on the WPF product configuration panel
	 *
	 * @access public
	 * @return mixed
	 */

	public function panel_content() {

		global $post;
		$settings = array(
			'apply_tags'          => array(),
			'apply_tags_refunded' => array(),
			'apply_tags_failed'   => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf-settings-woo', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf-settings-woo', true ) );
		}

		echo '<div class="options_group wpf-product">';

		echo '<p class="form-field"><label><strong>Product</strong></label></p>';

		echo '<p>' . sprintf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/ecommerce/woocommerce/" target="_blank">', '</a>' ) . '</p>';

		echo '<p class="form-field"><label for="wpf-apply-tags-woo">' . __( 'Apply tags when<br />purchased', 'wp-fusion' ) . '</label>';
		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['apply_tags'],
				'meta_name' => 'wpf-settings-woo',
				'field_id'  => 'apply_tags',
			)
		);

		echo '<br /><span style="margin-left: 0px;" class="description show_if_variable">Tags for product variations can be configured within the Variations tab.</span>';

		echo '</p>';

		echo '<p class="form-field"><label for="wpf-apply-tags-woo">' . __( 'Apply tags when<br />refunded', 'wp-fusion' );
		echo ' <span class="dashicons dashicons-editor-help wpf-tip bottom" data-tip="' . __( 'The tags specified above for \'Apply tags when purchased\' will automatically be removed if an order is refunded.', 'wp-fusion' ) . '"></span>';
		echo '</label>';

		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['apply_tags_refunded'],
				'meta_name' => 'wpf-settings-woo',
				'field_id'  => 'apply_tags_refunded',
			)
		);

		echo '</p>';

		echo '<p class="form-field"><label for="wpf-apply-tags-woo">' . __( 'Apply tags when transaction failed', 'wp-fusion' );
		echo ' <span class="dashicons dashicons-editor-help wpf-tip bottom" data-tip="' . __( 'A contact record will be created and these tags will be applied when an initial transaction on an order fails.<br /><br />Note that this may create problems since WP Fusion normally doesn\'t create a contact record until a successful payment is received.<br /><br />In almost all cases it\'s preferable to use abandoned cart tracking instead of failed transaction tagging.', 'wp-fusion' ) . '"></span>';
		echo '</label>';

		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['apply_tags_failed'],
				'meta_name' => 'wpf-settings-woo',
				'field_id'  => 'apply_tags_failed',
			)
		);

		echo '</p>';

		echo '</div>';

	}


	/**
	 * Adds tabs to left side of Woo product editor panel
	 *
	 * @access public
	 * @return mixed
	 */

	public function woocommerce_write_panel_tabs() {

		echo '<li class="custom_tab linked_product_options hide_if_grouped"><a href="#wp_fusion_tab"><span>WP Fusion</span></a></li>';

	}


	/**
	 * Adds tag multiselect to variation fields
	 *
	 * @access public
	 * @return mixed
	 */

	public function variable_fields( $loop, $variation_data, $variation ) {

		$defaults = array(
			'apply_tags_variation' => array( $variation->ID => array() ),
			'allow_tags_variation' => array( $variation->ID => array() ),
		);

		if ( ! isset( $variation_data['wpf-settings-woo'] ) ) {
			$settings = array();
		} else {
			$settings = maybe_unserialize( $variation_data['wpf-settings-woo'][0] );
		}

		$settings = array_merge( $defaults, $settings );

		echo '<div><p class="form-row form-row-full"><label for="wpf-apply-tags-woo">' . sprintf( __( 'Apply these tags in %s when purchased', 'wp-fusion' ), wp_fusion()->crm->name ) . ':</label>';

		wpf_render_tag_multiselect(
			array(
				'setting'      => $settings['apply_tags_variation'],
				'meta_name'    => 'wpf-settings-woo-variation',
				'field_id'     => 'apply_tags_variation',
				'field_sub_id' => $variation->ID,
			)
		);

		echo '</p></div>';

		// Restrict access to variation
		echo '<div><p class="form-row form-row-full"><label for="wpf-allow-tags-woo">Restrict access tags. If the user doesn\'t have <em>any</em> of these tags, the variation will not show as an option for purchase:</label>';

		wpf_render_tag_multiselect(
			array(
				'setting'      => $settings['allow_tags_variation'],
				'meta_name'    => 'wpf-settings-woo-variation',
				'field_id'     => 'allow_tags_variation',
				'field_sub_id' => $variation->ID,
			)
		);

		echo '</p></div>';

		do_action( 'wpf_woocommerce_variation_panel', $variation->ID, $settings );

	}

	/**
	 * Saves variable field data to product
	 *
	 * @access public
	 * @return mixed
	 */

	public function save_variable_fields( $variation_id, $i ) {

		if ( isset( $_POST['wpf-settings-woo-variation'] ) ) {
			$data = $_POST['wpf-settings-woo-variation'];
		} else {
			$data = array();
		}

		// Clean up settings from other variations getting stored with this one

		foreach ( $data as $setting_type => $setting ) {

			if ( ! empty( $setting ) ) {

				foreach ( $setting as $posted_variation_id => $tags ) {

					if ( $posted_variation_id != $variation_id ) {

						unset( $data[ $setting_type ][ $posted_variation_id ] );

					}
				}
			}
		}

		// Variation restriction tags (saved as postmeta to the variation ID now that WooCommerce isn't as shitty as it used to be)

		update_post_meta( $variation_id, 'wpf-settings-woo', $data );

		// Save the normal access restrictions as well so WPF_Access_Control can do its thing

		if ( isset( $data['allow_tags_variation'] ) && ! empty( array_filter( $data['allow_tags_variation'][ $variation_id ] ) ) ) {

			update_post_meta(
				$variation_id, 'wpf-settings', array(
					'lock_content' => true,
					'allow_tags'   => $data['allow_tags_variation'][ $variation_id ],
				)
			);

		} else {

			delete_post_meta( $variation_id, 'wpf-settings' );

		}

		// Clean up old data storage

		$post_id = $_POST['product_id'];

		$post_meta = get_post_meta( $post_id, 'wpf-settings-woo', true );

		if ( isset( $post_meta['apply_tags_variation'] ) ) {

			unset( $post_meta['apply_tags_variation'] );
			update_post_meta( $post_id, 'wpf-settings-woo', $post_meta );

		}

	}


	/**
	 * Saves WPF configuration to product
	 *
	 * @access public
	 * @return mixed
	 */

	public function save_meta_box_data( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_meta_box_woo_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpf_meta_box_woo_nonce'], 'wpf_meta_box_woo' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Don't update on revisions
		if ( $_POST['post_type'] != 'product' ) {
			return;
		}

		if ( isset( $_POST['wpf-settings-woo'] ) ) {
			$data = $_POST['wpf-settings-woo'];
		} else {
			$data = array();
		}

		// Update the meta field in the database.
		update_post_meta( $post_id, 'wpf-settings-woo', $data );

	}

	/**
	 * //
	 * // Order Actions
	 * //
	 **/

	/**
	 * Adds WP Fusion option to Order Actions dropdown
	 *
	 * @access public
	 * @return array Actions
	 */

	public function order_actions( $actions ) {

		$actions['wpf_process'] = __( 'Process WP Fusion actions again', 'wp-fusion' );
		return $actions;

	}

	/**
	 * Processes order action
	 *
	 * @access public
	 * @return void
	 */

	public function process_order_action( $order ) {

		delete_post_meta( $order->get_id(), 'wpf_complete' );
		delete_post_meta( $order->get_id(), 'wpf_ec_complete' );

		$this->woocommerce_apply_tags_checkout( $order->get_id() );

	}

	/**
	 * //
	 * // Coupons
	 * //
	 **/

	/**
	 * Adds WP Fusion settings tab to coupon config
	 *
	 * @access public
	 * @return array Tabs
	 */

	public function coupon_tabs( $tabs ) {

		$tabs['wp_fusion'] = array(
			'label'  => 'WP Fusion',
			'target' => 'wp_fusion_tab',
			'class'  => '',
		);

		return $tabs;

	}

	/**
	 * Output for coupon data panel
	 *
	 * @access public
	 * @return mixed
	 */

	public function coupon_data_panel() {

		echo '<div id="wp_fusion_tab" class="panel woocommerce_options_panel">';

		echo '<div class="options_group">';

		global $post;

		$settings = array(
			'apply_tags'         => array(),
			'auto_apply_tags'    => array(),
			'coupon_label'       => false,
			'coupon_applied_msg' => false,
		);

		if ( get_post_meta( $post->ID, 'wpf-settings-woo', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf-settings-woo', true ) );
		}

		echo '<p class="form-field"><label><strong>Coupon Settings</strong></label></p>';

		echo '<p class="form-field"><label for="wpf-apply-tags-woo">' . __( 'Apply tags when used', 'wp-fusion' ) . '</label>';

		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['apply_tags'],
				'meta_name' => 'wpf-settings-woo',
				'field_id'  => 'apply_tags',
			)
		);

		echo '</p>';

		echo '</div>';
		echo '<div class="options_group">';

		echo '<p class="form-field"><label><strong>Auto-apply Discounts</strong></label></p>';

		echo '<p class="form-field"><label>' . __( 'Auto-apply tags', 'wp-fusion' ) . '</label>';

		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['auto_apply_tags'],
				'meta_name' => 'wpf-settings-woo',
				'field_id'  => 'auto_apply_tags',
			)
		);

		echo '<span class="description"><small>' . __( 'If the user has any of the tags specified here, the coupon will automatically be applied to their cart.', 'wp-fusion' ) . '</small></span>';

		echo '</p>';

		echo '<p class="form-field"><label>' . __( 'Discount label', 'wp-fusion' ) . '</label>';

		echo '<input type="text" class="short" style="" name="wpf-settings-woo[coupon_label]" value="' . $settings['coupon_label'] . '" placeholder="Coupon">';

		echo '<span class="description" style="display: block; clear: both; margin-left: 0px;"><small>' . __( 'Use this setting to override the coupon label at checkout when a coupon has been auto-applied.<br />For example "Discount" or "Promo". (Leave blank for default)', 'wp-fusion' ) . '</small></span>';

		echo '</p>';

		echo '<p class="form-field"><label>' . __( 'Discount message', 'wp-fusion' ) . '</label>';

		echo '<input type="text" class="short" style="" name="wpf-settings-woo[coupon_applied_msg]" value="' . $settings['coupon_applied_msg'] . '" placeholder="Coupon code applied successfully.">';

		echo '<span class="description" style="display: block; clear: both; margin-left: 0px;"><small>' . __( 'Use this setting to override the message at checkout when a coupon has been auto-applied.<br />For example "You received a discount!". (Leave blank for default)', 'wp-fusion' ) . '</small></span>';

		echo '</p>';

		echo '<br />';

		do_action( 'wpf_woocommerce_coupon_panel', $post->ID, $settings );

		echo '</div>';

		echo '</div>';

	}

	/**
	 * Output for coupon usage restriction settings
	 *
	 * @access public
	 * @return mixed
	 */

	public function coupon_usage_restriction( $coupon_id, $coupon ) {

		$settings = array(
			'allow_tags' => array(),
		);

		if ( get_post_meta( $coupon_id, 'wpf-settings', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $coupon_id, 'wpf-settings', true ) );
		}

		echo '<div class="options_group">';

		echo '<p class="form-field"><label>' . __( 'Required tags', 'wp-fusion' ) . '</label>';

		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['allow_tags'],
				'meta_name' => 'wpf-settings',
				'field_id'  => 'allow_tags',
			)
		);

		echo '<span class="description"><small>' . __( 'The user must be logged in and have one of the specified tags to use the coupon.', 'wp-fusion' ) . '</small></span>';

		echo '</p>';

		echo '</div>';

	}

	/**
	 * Saves WPF configuration to product
	 *
	 * @access public
	 * @return mixed
	 */

	public function save_meta_box_data_coupon( $post_id ) {

		// Don't update on revisions
		if ( $_POST['post_type'] == 'revision' ) {
			return;
		}

		if ( isset( $_POST['wpf-settings-woo'] ) ) {

			$data = $_POST['wpf-settings-woo'];

		} else {
			$data = array();
		}

		// Update coupon-specific stuff

		update_post_meta( $post_id, 'wpf-settings-woo', $data );

		// Update coupon restrictions

		if ( isset( $_POST['wpf-settings'] ) ) {

			$_POST['wpf-settings']['lock_content'] = true;

			$data = $_POST['wpf-settings'];

			update_post_meta( $post_id, 'wpf-settings', $data );

		} else {
			delete_post_meta( $post_id, 'wpf-settings' );
		}

	}

	/**
	 * Check if coupon is valid before applying
	 *
	 * @access public
	 * @return bool Valid
	 */

	public function coupon_is_valid( $valid, $coupon, $discount ) {

		if ( ! wp_fusion()->access->user_can_access( $coupon->get_id() ) ) {
			$valid = false;
		}

		return $valid;

	}

	/**
	 * Applies any linked coupons when a product is added to the cart
	 *
	 * @access public
	 * @return void
	 */

	public function add_to_cart_apply_coupon() {

		if ( ! wpf_is_user_logged_in() ) {
			return;
		}

		$args = array(
			'numberposts' => 100,
			'post_type'   => 'shop_coupon',
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'key'     => 'wpf-settings-woo',
					'compare' => 'EXISTS',
				),
			),
		);

		$coupons = get_posts( $args );

		if ( empty( $coupons ) ) {
			return;
		}

		$user_tags = wp_fusion()->user->get_tags();

		if ( empty( $user_tags ) ) {
			return;
		}

		foreach ( $coupons as $coupon_id ) {

			$settings = get_post_meta( $coupon_id, 'wpf-settings-woo', true );

			if ( empty( $settings['auto_apply_tags'] ) ) {
				continue;
			}

			if ( ! empty( array_intersect( $settings['auto_apply_tags'], $user_tags ) ) ) {

				$coupon = new WC_Coupon( $coupon_id );

				if ( $coupon->is_valid() && ! WC()->cart->has_discount( $coupon->get_code() ) ) {

					// Remove filter so the check to wc_coupons_enabled() passes
					remove_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_field_on_cart' ) );

					$result = WC()->cart->apply_coupon( $coupon->get_code() );

					add_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_field_on_cart' ) );

				}
			}
		}

	}

	/**
	 * Applies any linked coupons when tags are modified
	 *
	 * @access public
	 * @return void
	 */

	public function maybe_apply_coupons( $user_id, $user_tags ) {

		if ( is_admin() || empty( WC()->cart ) || ! did_action( 'wp_loaded' ) ) {
			return;
		}

		$args = array(
			'numberposts' => 200,
			'post_type'   => 'shop_coupon',
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'key'     => 'wpf-settings-woo',
					'compare' => 'EXISTS',
				),
			),
		);

		$coupons = get_posts( $args );

		if ( empty( $coupons ) ) {
			return;
		}

		foreach ( $coupons as $coupon_id ) {

			$settings = get_post_meta( $coupon_id, 'wpf-settings-woo', true );

			if ( empty( $settings['auto_apply_tags'] ) ) {
				continue;
			}

			if ( ! empty( array_intersect( $settings['auto_apply_tags'], $user_tags ) ) ) {

				$coupon = new WC_Coupon( $coupon_id );

				if ( $coupon->is_valid() && ! WC()->cart->has_discount( $coupon->get_code() ) ) {

					// Remove filter so the check to wc_coupons_enabled() passes
					remove_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_field_on_cart' ) );

					$result = WC()->cart->apply_coupon( $coupon->get_code() );

					add_filter( 'woocommerce_coupons_enabled', array( $this, 'hide_coupon_field_on_cart' ) );

				}
			}
		}

	}

	/**
	 * Renames coupon fields for auto-applied coupons
	 *
	 * @access public
	 * @return void
	 */

	public function rename_coupon_label( $label, $coupon ) {

		$settings = get_post_meta( $coupon->get_id(), 'wpf-settings-woo', true );

		if ( ! empty( $settings ) && ! empty( $settings['coupon_label'] ) ) {

			return $settings['coupon_label'];

		}

		return $label;

	}

	/**
	 * Allows overriding the coupon success message
	 *
	 * @access public
	 * @return string Coupon success message
	 */

	public function coupon_success_message( $msg, $msg_code, $coupon ) {

		$settings = get_post_meta( $coupon->get_id(), 'wpf-settings-woo', true );

		if ( ! empty( $settings ) && ! empty( $settings['coupon_applied_msg'] ) ) {

			return $settings['coupon_applied_msg'];

		}

		return $msg;

	}


	/**
	 * Allow access control rules to do redirects on the Shop page
	 *
	 * @access public
	 * @return void
	 */

	public function restrict_access_to_shop() {

		if ( is_shop() ) {

			$post_id = get_option( 'woocommerce_shop_page_id' );

			// If user can access, return without doing anything
			if ( wp_fusion()->access->user_can_access( $post_id ) == true ) {
				return;
			}

			// Get redirect URL for the post
			$redirect = wp_fusion()->access->get_redirect( $post_id );

			$redirect = apply_filters( 'wpf_redirect_url', $redirect, $post_id );

			if ( ! empty( $redirect ) ) {

				wp_redirect( $redirect, 302, 'WP Fusion; Post ID ' . $post_id );
				exit();

			}
		}

	}


	/**
	 * Hides the coupon fields on cart / checkout if enabled
	 *
	 * @access public
	 * @return bool
	 */

	public function hide_coupon_field_on_cart( $enabled ) {

		if ( wp_fusion()->settings->get( 'woo_hide_coupon_field' ) == true ) {

			if ( is_cart() || is_checkout() ) {
				$enabled = false;
			}
		}

		return $enabled;

	}


	/**
	 * //
	 * // TAXONOMIES
	 * //
	 **/

	/**
	 * Add settings to taxonomies
	 *
	 * @access public
	 * @return void
	 */

	public function register_taxonomy_form_fields() {

		$product_taxonomies = get_object_taxonomies( 'product' );

		foreach ( $product_taxonomies as $slug ) {
			add_action( $slug . '_edit_form_fields', array( $this, 'taxonomy_form_fields' ), 10, 2 );
			add_action( 'edited_' . $slug, array( $this, 'save_taxonomy_form_fields' ), 10, 2 );
		}

	}

	/**
	 * Output settings to taxonomies
	 *
	 * @access public
	 * @return mixed HTML Output
	 */

	public function taxonomy_form_fields( $term ) {

		?>

		<tr class="form-field">
			<th style="padding-bottom: 0px;" colspan="2"><h3>WP Fusion - WooCommerce Settings</h3></th>
		</tr>

		<tr class="form-field">
			<th scope="row" valign="top"><label for="wpf-apply-tag-product"><?php _e( 'Apply tags when a product with this term is purchased', 'wp-fusion' ); ?></label></th>
			<td style="max-width: 400px;">
				<?php

				// retrieve values for tags to be applied
				$settings = get_term_meta( $term->term_id, 'wpf-settings-woo', true );

				if ( empty( $settings ) ) {
					$settings = array( 'apply_tags' => array() );
				}

				$args = array(
					'setting'   => $settings['apply_tags'],
					'meta_name' => 'wpf-settings-woo',
					'field_id'  => 'apply_tags',
				);

				wpf_render_tag_multiselect( $args );
				?>

			</td>
		</tr>

			<?php

	}

	/**
	 * Save taxonomy settings
	 *
	 * @access public
	 * @return void
	 */

	public function save_taxonomy_form_fields( $term_id ) {

		if ( ! empty( $_POST['wpf-settings-woo'] ) ) {

			update_term_meta( $term_id, 'wpf-settings-woo', $_POST['wpf-settings-woo'] );

		}

	}


	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds WooCommerce checkbox to available export options
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['woocommerce'] = array(
			'label'   => 'WooCommerce orders',
			'title'   => 'Orders',
			'tooltip' => 'Finds WooCommerce orders that have not been processed by WP Fusion, and adds/updates contacts while applying tags based on the products purchased',
		);

		return $options;

	}

	/**
	 * Counts total number of orders to be processed
	 *
	 * @access public
	 * @return int Count
	 */

	public function batch_init() {

		$args = array(
			'numberposts' => - 1,
			'post_type'   => 'shop_order',
			'post_status' => array( 'wc-processing', 'wc-completed' ),
			'fields'      => 'ids',
			'order'       => 'ASC',
			'meta_query'  => array(
				array(
					'key'     => 'wpf_complete',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$orders = get_posts( $args );

		wpf_log( 'info', 0, 'Beginning <strong>WooCommerce Orders</strong> batch operation on ' . count( $orders ) . ' orders', array( 'source' => 'batch-process' ) );

		return $orders;

	}

	/**
	 * Processes order actions in batches
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $order_id ) {

		$this->woocommerce_apply_tags_checkout( $order_id, true );

	}

	/**
	 * Check for Woo < 3.x
	 *
	 * @access public
	 * @return mixed
	 */

	public function compatibility_test() {

		global $woocommerce;

		if ( version_compare( $woocommerce->version, '3.0', '<' ) ) {
			$this->is_v3 = false;
		}

	}

	/**
	 * Clear orphaned cron jobs
	 *
	 * @access public
	 * @return void
	 */

	public function clear_cron() {

		if ( isset( $_GET['wpf_clear_cron'] ) ) {

			$cron = get_option( 'cron' );

			foreach ( $cron as $key => $cron_data ) {

				reset( $cron_data );
				$action = key( $cron_data );

				if ( $action == 'wpf_subscription_status_hold' ) {
					unset( $cron[ $key ] );
				}
			}

			update_option( 'cron', $cron );

		}
	}

	/**
	 * Support utilities
	 *
	 * @access public
	 * @return void
	 */

	public function settings_page_init() {

		if ( isset( $_GET['woo_reset_wpf_complete'] ) ) {

			$args = array(
				'numberposts' => - 1,
				'post_type'   => 'shop_order',
				'post_status' => array( 'wc-processing', 'wc-completed' ),
				'fields'      => 'ids',
				'meta_query'  => array(
					array(
						'key'     => 'wpf_complete',
						'compare' => 'EXISTS',
					),
				),
			);

			$orders = get_posts( $args );

			foreach ( $orders as $order_id ) {
				delete_post_meta( $order_id, 'wpf_complete' );
			}

			echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Success:</strong><code>wpf_complete</code> meta key removed from ' . count( $orders ) . ' orders.</p></div>';

		}

	}

	/**
	 * //
	 * // DEPRECATED
	 * //
	 **/

	/**
	 * Gets customer details from the WooCommerce order when customer isn't a registered user (deprecated)
	 *
	 * @access public
	 * @return array Contact Data
	 */

	public function woocommerce_get_customer_data( $order ) {

		return $this->get_customer_data( $order );

	}

}

new WPF_Woocommerce();
