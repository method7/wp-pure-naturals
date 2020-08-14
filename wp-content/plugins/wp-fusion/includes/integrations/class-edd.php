<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_EDD extends WPF_Integrations_Base {

	/**
	 * Get things started
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		$this->slug = 'edd';

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ), 10 );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 20 );
		add_action( 'save_post', array( $this, 'save_meta_box_data' ) );

		// Payment stuff
		add_filter( 'wpf_bypass_profile_update', array( $this, 'bypass_profile_update' ), 10, 2 );
		add_action( 'edd_complete_purchase', array( $this, 'complete_purchase' ), 10 );
		add_action( 'wpf_edd_async_checkout', array( $this, 'complete_purchase' ), 10, 2 );
		add_action( 'edd_post_refund_payment', array( $this, 'refund_complete' ), 10 );

		// Discounts
		add_action( 'edd_add_discount_form_before_notes', array( $this, 'discount_fields' ), 10, 2 );
		add_action( 'edd_edit_discount_form_before_notes', array( $this, 'discount_fields' ), 10, 2 );
		add_action( 'edd_add_discount', array( $this, 'save_discount' ), 8 );
		add_action( 'edd_edit_discount', array( $this, 'save_discount' ), 8 );

		add_filter( 'edd_is_discount_valid', array( $this, 'is_discount_valid' ), 10, 4 );

		// Auto-register addon
		add_action( 'edd_auto_register_insert_user', array( $this, 'auto_register_insert_user' ), 10, 3 );

		// Admin settings
		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

		// WPF hooks
		add_filter( 'wpf_user_register', array( $this, 'user_register' ) );
		add_filter( 'wpf_user_update', array( $this, 'user_update' ), 10, 2 );

		// Export functions
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_action( 'wpf_batch_edd_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_edd', array( $this, 'batch_step' ) );

		// Variable price column fields
		add_action( 'edd_download_price_table_row', array( $this, 'download_table_price_row' ), 10, 3 );
		add_filter( 'edd_purchase_variable_prices', array( $this, 'purchase_variable_prices' ), 10, 2 );

		// Super secret admin / debugging tools
		add_action( 'wpf_settings_page_init', array( $this, 'settings_page_init' ) );

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
	 * Registers additional EDD settings
	 *
	 * @access  public
	 * @return  array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['edd_header'] = array(
			'title'   => __( 'Easy Digital Downloads Integration', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['edd_tags'] = array(
			'title'   => __( 'Apply Tags to Customers', 'wp-fusion' ),
			'desc'    => __( 'These tags will be applied to all EDD customers.', 'wp-fusion' ),
			'std'     => array(),
			'type'    => 'assign_tags',
			'section' => 'integrations',
		);

		$settings['edd_async'] = array(
			'title'   => __( 'Asynchronous Checkout', 'wp-fusion' ),
			'desc'    => __( 'Runs WP Fusion post-checkout actions asynchronously to speed up load times.', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'integrations',
		);

		return $settings;

	}


	/**
	 * Adds EDD field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */

	public function add_meta_field_group( $field_groups ) {

		if ( ! isset( $field_groups['edd'] ) ) {
			$field_groups['edd'] = array(
				'title'  => 'Easy Digital Downloads',
				'fields' => array(),
			);
		}

		return $field_groups;

	}

	/**
	 * Sets field labels and types for EDD custom fields
	 *
	 * @access  public
	 * @return  array Meta fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$meta_fields['billing_address_1'] = array(
			'label' => 'Billing Address 1',
			'type'  => 'text',
			'group' => 'edd',
		);
		$meta_fields['billing_address_2'] = array(
			'label' => 'Billing Address 2',
			'type'  => 'text',
			'group' => 'edd',
		);
		$meta_fields['billing_city']      = array(
			'label' => 'Billing City',
			'type'  => 'text',
			'group' => 'edd',
		);
		$meta_fields['billing_state']     = array(
			'label' => 'Billing State',
			'type'  => 'text',
			'group' => 'edd',
		);
		$meta_fields['billing_country']   = array(
			'label' => 'Billing Country',
			'type'  => 'text',
			'group' => 'edd',
		);
		$meta_fields['billing_postcode']  = array(
			'label' => 'Billing Postcode',
			'type'  => 'text',
			'group' => 'edd',
		);
		$meta_fields['customer_id']       = array(
			'label' => 'Customer ID',
			'type'  => 'integer',
			'group' => 'edd',
		);

		return $meta_fields;

	}

	/**
	 * Triggered at user registration to adapt EDD fields to WP standard
	 *
	 * @access public
	 * @return array Post Data
	 */

	public function user_register( $post_data ) {

		// Trim "edd_" from the beginning of each key
		foreach ( $post_data as $key => $value ) {
			if ( substr( $key, 0, 4 ) == 'edd_' ) {
				$key               = substr( $key, 4 );
				$post_data[ $key ] = $value;
				unset( $post_data[ 'edd_' . $key ] );
			}
		}

		$field_map = array(
			'email' => 'user_email',
			'first' => 'first_name',
			'last'  => 'last_name',
		);

		$post_data = $this->map_meta_fields( $post_data, $field_map );

		return $post_data;

	}

	/**
	 * Extracts EDD payment fields from EDD meta array and explodes for use in pushing meta data
	 *
	 * @access public
	 * @return array User Meta
	 */

	public function user_update( $user_meta, $user_id ) {

		if ( ! empty( $user_meta['_edd_user_address'] ) ) {

			$edd_user_address = maybe_unserialize( $user_meta['_edd_user_address'] );

			$user_meta['billing_address_1'] = $edd_user_address['line1'];
			$user_meta['billing_address_2'] = $edd_user_address['line2'];
			$user_meta['billing_city']      = $edd_user_address['city'];
			$user_meta['billing_state']     = $edd_user_address['state'];
			$user_meta['billing_country']   = $edd_user_address['country'];
			$user_meta['billing_postcode']  = $edd_user_address['zip'];

		} else {

			$field_map = array(
				'edd_first_name'      => 'first_name',
				'edd_last_name'       => 'last_name',
				'edd_display_name'    => 'display_name',
				'edd_email'           => 'user_email',
				'edd_address_line1'   => 'billing_address_1',
				'edd_address_line2'   => 'billing_address_2',
				'edd_address_city'    => 'billing_city',
				'edd_address_state'   => 'billing_state',
				'edd_address_zip'     => 'billing_postcode',
				'edd_address_country' => 'billing_country',
				'edd_new_user_pass1'  => 'user_pass',
			);

			$user_meta = $this->map_meta_fields( $user_meta, $field_map );

		}

		return $user_meta;

	}

	/**
	 * Maybe bypass the profile_update hook in WPF_User if it's an EDD checkout
	 *
	 * @access public
	 * @return bool Bypass
	 */

	public function bypass_profile_update( $bypass, $request ) {

		if ( ! empty( $request ) && isset( $request['edd_action'] ) && $request['edd_action'] == 'purchase' ) {
			$bypass = true;
		}

		return $bypass;

	}

	/**
	 * Triggered when an order is completed. Updates contact record (or creates it) and applies tags
	 *
	 * @access public
	 * @return void
	 */

	public function complete_purchase( $payment_id, $doing_async = false, $force = false ) {

		// Defer until next page if async checkout is enabled
		if ( ! is_admin() && wp_fusion()->settings->get( 'edd_async' ) == true && $doing_async == false ) {

			wp_fusion()->batch->quick_add( 'wpf_edd_async_checkout', array( $payment_id, true ) );
			return;

		}

		$payment = new EDD_Payment( $payment_id );

		// Prevents the API calls being sent multiple times for the same order
		$wpf_complete = $payment->get_meta( 'wpf_complete', true );

		if ( ! empty( $wpf_complete ) && $force == false ) {
			return true;
		}

		// Get user info
		$payment_meta = $payment->get_meta();

		$user_meta = array(
			'user_email'  => $payment_meta['email'],
			'first_name'  => $payment_meta['user_info']['first_name'],
			'last_name'   => $payment_meta['user_info']['last_name'],
			'customer_id' => $payment->customer_id,
		);

		// Address fields
		if ( ! empty( $payment_meta['user_info']['address'] ) ) {

			$user_meta['billing_address_1'] = $payment_meta['user_info']['address']['line1'];
			$user_meta['billing_address_2'] = $payment_meta['user_info']['address']['line2'];
			$user_meta['billing_city']      = $payment_meta['user_info']['address']['city'];
			$user_meta['billing_state']     = $payment_meta['user_info']['address']['state'];
			$user_meta['billing_country']   = $payment_meta['user_info']['address']['country'];
			$user_meta['billing_postcode']  = $payment_meta['user_info']['address']['zip'];

		}

		// See if the user already exists locally
		$user_id = $payment->user_id;

		// Make sure user exists
		$user = get_userdata( $user_id );

		if ( $user === false ) {
			$user_id = 0;
		}

		if ( (int) $user_id < 1 ) {

			// Guest checkouts
			$contact_id = wp_fusion()->crm->get_contact_id( $user_meta['user_email'] );

			if ( empty( $contact_id ) ) {

				// New contact
				wpf_log(
					'info', 0, 'New EDD guest checkout. Order <a href="' . admin_url( '/edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $payment_id ) . '">#' . $payment_id . '</a> ', array(
						'meta_array' => $user_meta,
						'source'     => 'edd',
					)
				);

				// Create contact and add note
				$contact_id = wp_fusion()->crm->add_contact( $user_meta );

				if ( is_wp_error( $contact_id ) ) {

					$payment->add_note( 'Error creating contact in ' . wp_fusion()->crm->name . ': ' . $contact_id->get_error_message() );

					wpf_log( 'error', 0, 'Error creating contact in ' . wp_fusion()->crm->name . ': ' . $contact_id->get_error_message() );

					return false;

				} else {

					$payment->add_note( wp_fusion()->crm->name . ' contact ID ' . $contact_id . ' created via guest checkout.' );

				}
			} else {

				// Existing contact
				wpf_log(
					'info', 0, 'New EDD guest checkout. Order <a href="' . admin_url( '/edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $payment_id ) . '">#' . $payment_id . '</a>, for existing contact ID ' . $contact_id . ': ', array(
						'meta_array' => $user_meta,
						'source'     => 'edd',
					)
				);

				wp_fusion()->crm->update_contact( $contact_id, $user_meta );

			}
		} else {

			// Registered user checkouts
			wpf_log( 'info', $user_id, 'New EDD order <a href="' . admin_url( '/edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $payment_id ) . '">#' . $payment_id . '</a>', array( 'source' => 'edd' ) );

			$contact_id = wp_fusion()->user->get_contact_id( $user_id );

			if ( empty( $contact_id ) ) {

				// If not found, check in the CRM and update locally
				$contact_id = wp_fusion()->user->get_contact_id( $user_id, true );

			}

			// Contact needs to be created
			if ( $contact_id == false ) {

				// Create contact and add note
				$contact_id = wp_fusion()->crm->add_contact( $user_meta );

				if ( is_wp_error( $contact_id ) ) {

					$payment->add_note( 'Error creating contact in ' . wp_fusion()->crm->name . ': ' . $contact_id->get_error_message() );

					wpf_log( 'error', $user_id, 'Error creating contact in ' . wp_fusion()->crm->name . ': ' . $contact_id->get_error_message() );

					return false;

				} else {

					$payment->add_note( wp_fusion()->crm->name . ' contact ID ' . $contact_id . ' created.' );

				}
			} else {

				// If contact is found for user, update their info
				wp_fusion()->user->push_user_meta( $user_id, $user_meta );

			}
		}

		// Store the contact ID for future operations
		$payment->update_meta( wp_fusion()->crm->slug . '_contact_id', $contact_id );

		// Apply tags
		$apply_tags = array();

		$global_tags = wp_fusion()->settings->get( 'edd_tags', array() );

		if ( ! empty( $global_tags ) ) {
			$apply_tags = array_merge( $apply_tags, $global_tags );
		}

		foreach ( $payment_meta['cart_details'] as $item ) {

			$wpf_settings = get_post_meta( $item['id'], 'wpf-settings-edd', true );

			if ( empty( $wpf_settings ) ) {
				continue;
			}

			if ( isset( $wpf_settings['apply_tags'] ) ) {
				$apply_tags = array_merge( $apply_tags, $wpf_settings['apply_tags'] );
			}

			// Variable pricing tags
			if ( isset( $wpf_settings['apply_tags_price'] ) && ! empty( $item['item_number']['options']['price_id'] ) ) {

				$price_id = $item['item_number']['options']['price_id'];

				if ( isset( $wpf_settings['apply_tags_price'][ $price_id ] ) ) {

					$apply_tags = array_merge( $apply_tags, $wpf_settings['apply_tags_price'][ $price_id ] );

				}
			}
		}

		if ( function_exists( 'edd_get_adjustment_meta' ) ) {

			$discounts = $payment->discounts;

			if ( ! is_array( $discounts ) ) {
				$discounts = explode( ',', $discounts );
			}

			foreach ( $discounts as $code ) {

				if ( $code == 'none' || ! $disc = edd_get_discount_by_code( $code ) ) {
					continue;
				}

				$settings = edd_get_adjustment_meta( $disc->ID, 'wpf_settings', true );

				if ( empty( $settings ) || empty( $settings['apply_tags'] ) ) {
					continue;
				}

				$apply_tags = array_merge( $apply_tags, $settings['apply_tags'] );

			}
		}

		$apply_tags = apply_filters( 'wpf_edd_apply_tags_checkout', $apply_tags, $payment );

		// Guest checkout
		if ( (int) $user_id < 1 ) {

			// Logging
			wpf_log(
				'info', 0, 'EDD guest checkout applying tags: ', array(
					'tag_array' => $apply_tags,
					'source'    => 'edd',
				)
			);

			wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );

		} else {

			wp_fusion()->user->apply_tags( $apply_tags, $user_id );

		}

		// Denotes that the WPF actions have already run for this payment
		$payment->update_meta( 'wpf_complete', true );

		// Run payment complete action
		do_action( 'wpf_edd_payment_complete', $payment_id, $contact_id );

	}

	/**
	 * Output settings on the discount add / edit screen
	 *
	 * @access public
	 * @return bool
	 */

	public function discount_fields( $discount_id, $discount ) {

		if ( ! function_exists( 'edd_get_adjustment_meta' ) ) {
			return;
		}

		$defaults = array(
			'allow_tags' => array(),
			'apply_tags' => array(),
		);

		$settings = edd_get_adjustment_meta( $discount_id, 'wpf_settings', true );

		if ( empty( $settings ) ) {
			$settings = array();
		}

		$settings = array_merge( $defaults, $settings );

		?>

		<tr>
			<th scope="row" valign="top">
				<label for="notes"><?php _e( 'Required Tags', 'wp-fusion' ); ?></label>
			</th>
			<td>

				<?php

				$args = array(
					'setting'   => $settings['allow_tags'],
					'meta_name' => 'wpf_settings',
					'field_id'  => 'allow_tags',
				);

				wpf_render_tag_multiselect( $args );

				?>

				<p class="description"><?php _e( 'If specified a user must be logged in and have the selected tags to use the discount.', 'wp-fusion' ); ?></p>
			</td>
		</tr>

	   <tr>
			<th scope="row" valign="top">
				<label for="notes"><?php _e( 'Apply Tags', 'wp-fusion' ); ?></label>
			</th>
			<td>

				<?php

				$args = array(
					'setting'   => $settings['apply_tags'],
					'meta_name' => 'wpf_settings',
					'field_id'  => 'apply_tags',
				);

				wpf_render_tag_multiselect( $args );

				?>

				<p class="description"><?php echo sprintf( __( 'The selected tags will be applied in %s when the discount is used.', 'wp-fusion' ), wp_fusion()->crm->name ); ?></p>
			</td>
		</tr>

		<?php

	}

	/**
	 * Save changes to discount settings
	 *
	 * @access public
	 * @return void
	 */

	public function save_discount() {

		if ( ! function_exists( 'edd_get_adjustment_meta' ) ) {
			return;
		}

		if ( ! empty( $_POST['wpf_settings'] ) ) {

			edd_update_adjustment_meta( $_POST['discount-id'], 'wpf_settings', $_POST['wpf_settings'] );

		}

	}


	/**
	 * Allows using tags to restrict access to EDD discounts
	 *
	 * @access public
	 * @return bool
	 */

	public function is_discount_valid( $is_valid, $discount_id, $code, $lookup_user ) {

		if ( ! function_exists( 'edd_get_adjustment_meta' ) ) {
			return $is_valid;
		}

		// If no user, EDD is checking general validity, not specific user's access
		if ( ! $lookup_user ) {
			return $is_valid;
		}

		// If it's already not valid, we don't need to ask WPF about it
		if ( ! $is_valid ) {
			return $is_valid;
		}

		$settings = edd_get_adjustment_meta( $discount_id, 'wpf-settings', true );

		if ( empty( $settings ) || empty( $settings['allow_tags'] ) ) {
			return $is_valid;
		}

		$is_valid = false;

		if ( ! wpf_is_user_logged_in() ) {

			edd_set_error(
				'edd-discount-error', __( 'You must be logged in to use this discount code.', 'wp-fusion' )
			);

		} else {

			$user_tags = wp_fusion()->user->get_tags();

			if ( ! empty( array_intersect( $user_tags, $settings['allow_tags'] ) ) ) {

				$is_valid = true;

			} else {

				edd_set_error(
					'edd-discount-error', __( 'You do not have access to use this discount code.', 'wp-fusion' )
				);

			}
		}

		return $is_valid;

	}


	/**
	 * Triggered when an order is refunded. Updates contact record and removes original purchase tags / applies refund tags if applicable
	 *
	 * @access public
	 * @return void
	 */

	public function refund_complete( $payment ) {

		$remove_tags         = array();
		$apply_tags_refunded = array();

		$payment_meta = $payment->get_meta();

		foreach ( $payment_meta['cart_details'] as $item ) {

			$wpf_settings = get_post_meta( $item['id'], 'wpf-settings-edd', true );

			if ( empty( $wpf_settings ) ) {
				continue;
			}

			if ( isset( $wpf_settings['apply_tags'] ) ) {
				$remove_tags = array_merge( $remove_tags, $wpf_settings['apply_tags'] );

			}

			if ( isset( $wpf_settings['apply_tags_refunded'] ) ) {
				$apply_tags_refunded = array_merge( $apply_tags_refunded, $wpf_settings['apply_tags_refunded'] );
			}

			// Variable pricing tags
			if ( isset( $wpf_settings['apply_tags_price'] ) && ! empty( $item['item_number']['options']['price_id'] ) ) {

				$price_id = $item['item_number']['options']['price_id'];

				if ( isset( $wpf_settings['apply_tags_price'][ $price_id ] ) ) {

					$remove_tags = array_merge( $remove_tags, $wpf_settings['apply_tags_price'][ $price_id ] );

				}
			}

			// Variable pricing tags: refund tag
			if ( isset( $wpf_settings['apply_tags_refund_price'] ) && ! empty( $item['item_number']['options']['price_id'] ) ) {

				$price_id = $item['item_number']['options']['price_id'];

				if ( isset( $wpf_settings['apply_tags_refund_price'][ $price_id ] ) ) {

					$apply_tags_refunded = array_merge( $apply_tags_refunded, $wpf_settings['apply_tags_refund_price'][ $price_id ] );

				}
			}
		}

		$user_id = $payment->user_id;

		// Guest checkout
		if ( (int) $user_id < 1 ) {

			$contact_id = $payment->get_meta( wp_fusion()->crm->slug . '_contact_id', true );

			if ( empty( $contact_id ) ) {

				$user_email = $payment_meta['email'];
				$contact_id = wp_fusion()->crm->get_contact_id( $user_email );

			}

			if ( ! is_wp_error( $contact_id ) && ! empty( $contact_id ) ) {

				if ( ! empty( $remove_tags ) ) {
					wp_fusion()->crm->remove_tags( $remove_tags, $contact_id );
				}

				if ( ! empty( $apply_tags_refunded ) ) {
					wp_fusion()->crm->apply_tags( $apply_tags_refunded, $contact_id );
				}
			}
		} else {

			if ( ! empty( $remove_tags ) ) {
				wp_fusion()->user->remove_tags( $remove_tags, $user_id );
			}

			if ( ! empty( $apply_tags_refunded ) ) {
				wp_fusion()->user->apply_tags( $apply_tags_refunded, $user_id );
			}
		}

	}

	/**
	 * Sync data from auto registered users
	 *
	 * @access public
	 * @return void
	 */

	public function auto_register_insert_user( $user_id, $user_args, $payment_id ) {

		wp_fusion()->user->push_user_meta( $user_id, array( 'user_pass' => $user_args['user_pass'] ) );

	}


	/**
	 * Outputs WPF fields to variable price rows
	 *
	 * @access public
	 * @return mixed HTML Output
	 */

	public function download_table_price_row( $post_id, $key, $args ) {

		echo '<div class="edd-custom-price-option-section">';

		echo '<span class="edd-custom-price-option-section-title">' . __( 'WP Fusion Settings', 'wp-fusion' ) . '</span>';

		$settings = array(
			'apply_tags_price'        => array(),
			'apply_tags_refund_price' => array(),
		);

		if ( get_post_meta( $post_id, 'wpf-settings-edd', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post_id, 'wpf-settings-edd', true ) );
		}

		if ( empty( $settings['apply_tags_price'][ $key ] ) ) {
			$settings['apply_tags_price'][ $key ] = array();
		}

		if ( empty( $settings['apply_tags_refund_price'][ $key ] ) ) {
			$settings['apply_tags_refund_price'][ $key ] = array();
		}

		if ( empty( $settings['allow_tags_price'][ $key ] ) ) {
			$settings['allow_tags_price'][ $key ] = array();
		}

		echo '<div style="width:48%; padding: 0px; display:inline-block;">';
		echo '<label>' . __( 'Apply tags when purchased', 'wp-fusion' ) . ':</label>';
		wpf_render_tag_multiselect(
			array(
				'setting'      => $settings['apply_tags_price'],
				'meta_name'    => 'wpf-settings-edd',
				'field_id'     => 'apply_tags_price',
				'field_sub_id' => $key,
			)
		);
		echo '</div>';

		echo '<div style="width:48%; padding: 0px; display:inline-block;">';
		echo '<label>' . __( 'Apply tags when refunded', 'wp-fusion' ) . ':</label>';
		wpf_render_tag_multiselect(
			array(
				'setting'      => $settings['apply_tags_refund_price'],
				'meta_name'    => 'wpf-settings-edd',
				'field_id'     => 'apply_tags_refund_price',
				'field_sub_id' => $key,
			)
		);
		echo '</div>';

		echo '<div style="width:48%; padding: 0px; display:inline-block; margin-top: 10px;">';
		echo '<label>' . __( 'Restrict access tags', 'wp-fusion' ) . ':</label>';
		wpf_render_tag_multiselect(
			array(
				'setting'      => $settings['allow_tags_price'],
				'meta_name'    => 'wpf-settings-edd',
				'field_id'     => 'allow_tags_price',
				'field_sub_id' => $key,
			)
		);
		echo '<span class="description">' . __( 'If the user doesn\'t have <em>any</em> of these tags, the price ID will not show as an option for purchase.', 'wp-fusion' ) . '</span>';
		echo '</div>';

		echo '</div>';

	}


	/**
	 * Allow hiding a price ID by tag
	 *
	 * @access public
	 * @return array Prices
	 */

	public function purchase_variable_prices( $prices, $download_id ) {

		$settings = get_post_meta( $download_id, 'wpf-settings-edd', true );

		foreach ( $prices as $price_id => $data ) {

			if ( isset( $settings['allow_tags_price'] ) && ! empty( $settings['allow_tags_price'][ $price_id ] ) ) {

				$can_access = true;

				if ( ! wpf_is_user_logged_in() ) {

					$can_access = false;

				} else {

					$user_tags = wp_fusion()->user->get_tags();

					if ( empty( array_intersect( $user_tags, $settings['allow_tags_price'][ $price_id ] ) ) ) {
						$can_access = false;
					}
				}

				if ( current_user_can( 'manage_options' ) && wp_fusion()->settings->get( 'exclude_admins' ) == true ) {
					$can_access = true;
				}

				$can_access = apply_filters( 'wpf_user_can_access', $can_access, wpf_get_current_user_id(), $price_id );

				if ( ! $can_access ) {
					unset( $prices[ $price_id ] );
				}
			}
		}

		return $prices;

	}


	/**
	 * Registers meta box
	 *
	 * @access public
	 * @return voic
	 */

	public function add_meta_box() {

		add_meta_box(
			'wpf-edd-meta', __( 'WP Fusion Download Settings', 'wp-fusion' ), array(
				$this,
				'meta_box_callback',
			), 'download', 'normal', 'default'
		);

	}

	/**
	 * Displays meta box content
	 *
	 * @access public
	 * @return mixed
	 */

	public function meta_box_callback( $post ) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'wpf_meta_box_edd', 'wpf_meta_box_edd_nonce' );

		$settings = array(
			'apply_tags'          => array(),
			'apply_tags_refunded' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf-settings-edd', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf-settings-edd', true ) );
		}

		echo '<table class="form-table wpf-edd-settings"><tbody>';

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags">' . __( 'Apply Tags', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';
		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['apply_tags'],
				'meta_name' => 'wpf-settings-edd',
				'field_id'  => 'apply_tags',
			)
		);
		echo '<span class="description">' . sprintf( __( 'Apply these tags in %s when purchased', 'wp-fusion' ), wp_fusion()->crm->name ) . '</span>';
		echo '</td>';

		echo '</tr>';

		echo '<tr>';

		echo '<th scope="row"><label for="apply_tags_refunded">' . __( 'Refund Tags', 'wp-fusion' ) . ':</label></th>';
		echo '<td>';
		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['apply_tags_refunded'],
				'meta_name' => 'wpf-settings-edd',
				'field_id'  => 'apply_tags_refunded',
			)
		);
		echo '<span class="description">' . sprintf( __( 'Apply these tags in %s when refunded', 'wp-fusion' ), wp_fusion()->crm->name ) . '</span>';
		echo '</td>';

		echo '</tr>';

		do_action( 'wpf_edd_meta_box_inner', $post, $settings );

		echo '</tbody></table>';

		// Allows other plugins to add additional fields to meta box
		do_action( 'wpf_edd_meta_box', $post, $settings );

	}

	/**
	 * Saves WPF configuration to product
	 *
	 * @access public
	 * @return mixed
	 */

	public function save_meta_box_data( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_meta_box_edd_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpf_meta_box_edd_nonce'], 'wpf_meta_box_edd' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Don't update on revisions
		if ( $_POST['post_type'] == 'revision' ) {
			return;
		}

		if ( isset( $_POST['wpf-settings-edd'] ) ) {
			$data = $_POST['wpf-settings-edd'];

		} else {
			$data = array();
		}

		// Update the meta field in the database.
		update_post_meta( $post_id, 'wpf-settings-edd', $data );

	}



	/**
	 * //
	 * // EXPORT TOOLS
	 * //
	 **/

	/**
	 * Adds EDD checkbox to available export options
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['edd'] = array(
			'label'   => __( 'Easy Digital Downloads orders', 'wp-fusion' ),
			'title'   => __( 'Orders', 'wp-fusion' ),
			'tooltip' => __( 'Finds EDD orders that have not been processed by WP Fusion, and adds/updates contacts while applying tags based on the products purchased', 'wp-fusion' ),
		);

		return $options;

	}

	/**
	 * Counts total number of orders to be processed
	 *
	 * @access public
	 * @return array Payments
	 */

	public function batch_init() {

		$args = array(
			'number'     => -1,
			'fields'     => 'ids',
			'meta_query' => array(
				array(
					'key'     => 'wpf_complete',
					'compare' => 'NOT EXISTS',
				),
			),
		);

		$payments = edd_get_payments( $args );

		return $payments;

	}

	/**
	 * Processes payments actions in batches
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $payment_id ) {

		$this->complete_purchase( $payment_id );

	}


	/**
	 * Support utilities
	 *
	 * @access public
	 * @return void
	 */

	public function settings_page_init() {

		if ( isset( $_GET['edd_reset_wpf_complete'] ) ) {

			$args = array(
				'number'     => -1,
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => 'wpf_complete',
						'compare' => 'EXISTS',
					),
				),
			);

			$payments = edd_get_payments( $args );

			foreach ( $payments as $payment_id ) {
				delete_post_meta( $payment_id, 'wpf_complete' );
			}

			echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Success:</strong><code>wpf_complete</code> meta key removed from ' . count( $payments ) . ' orders.</p></div>';

		}

	}

}

new WPF_EDD();
