<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Abandoned_Cart_EDD extends WPF_Abandoned_Cart_Integrations_Base {

	/**
	 * Get things started
	 *
	 * @access public
	 * @return void
	 */

	public function init() {

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Meta boxes
		add_action( 'wpf_edd_meta_box', array( $this, 'meta_box_content' ), 10, 2 );

		// Variable price column fields
		add_action( 'edd_download_price_table_row', array( $this, 'download_table_price_row' ), 10, 3 );

		// Abandoned cart tracking
		add_action( 'edd_before_checkout_cart', array( $this, 'checkout_begin' ) );
		add_action( 'wpf_abandoned_cart_start', array( $this, 'checkout_begin' ), 30, 4 );

		// After checkout complete
		add_action( 'wpf_edd_payment_complete', array( $this, 'checkout_complete' ), 20, 2 ); // 20 so we don't delete the transient before the Ecom addon runs

		// Cart recovery
		//add_action( 'edd_restore_cart', array( $this, 'recover_cart' ) );
		add_action( 'init', array( $this, 'recover_cart' ) );

	}

	/**
	 * Enqueue scripts on checkout page
	 *
	 * @access public
	 * @return void
	 */

	public function enqueue_scripts() {

		if ( edd_is_checkout() && ! is_user_logged_in() ) {
			wp_enqueue_script( 'wpf-abandoned-cart', WPF_ABANDONED_CART_DIR_URL . 'assets/wpf-abandoned-cart.js', array( 'jquery' ), WPF_ABANDONED_CART_VERSION, true );
			wp_localize_script( 'wpf-abandoned-cart', 'wpf_ac_ajaxurl', admin_url( 'admin-ajax.php' ) );
		}

	}

	/**
	 * Additional fields in EDD meta box
	 *
	 * @access public
	 * @return mixed
	 */

	public function meta_box_content( $post, $settings ) {

		if ( empty( $settings['apply_tags_abandoned'] ) ) {
			$settings['apply_tags_abandoned'] = array();
		}

		echo '<table class="form-table"><tbody>';

			echo '<tr>';

				echo '<th scope="row"><label for="apply_tags_abandoned">' . __( 'Apply Tags - Abandoned Cart', 'wp-fusion' ) . ':</label></th>';
				echo '<td>';
					wpf_render_tag_multiselect(
						array(
							'setting'   => $settings['apply_tags_abandoned'],
							'meta_name' => 'wpf-settings-edd',
							'field_id'  => 'apply_tags_abandoned',
						)
					);
					echo '<span class="description">Use these tags for abandoned cart tracking</span>';
				echo '</td>';

			echo '</tr>';

		echo '</tbody></table>';

	}


	/**
	 * Outputs WPF fields to variable price rows
	 *
	 * @access public
	 * @return voic
	 */

	public function download_table_price_row( $post_id, $key, $args ) {

		echo '<div class="edd-custom-price-option-section">';

		echo '<span class="edd-custom-price-option-section-title">WP Fusion - Abandoned Cart Settings</span>';

		$settings = array(
			'apply_tags_abandoned' => array(),
		);

		if ( get_post_meta( $post_id, 'wpf-settings-edd', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post_id, 'wpf-settings-edd', true ) );
		}

		if ( empty( $settings['apply_tags_abandoned_price'][ $key ] ) ) {
			$settings['apply_tags_abandoned_price'][ $key ] = array();
		}

		echo '<label>Apply tags when cart abandoned:</label><br />';

		wpf_render_tag_multiselect(
			array(
				'setting'      => $settings['apply_tags_abandoned_price'],
				'meta_name'    => 'wpf-settings-edd',
				'field_id'     => 'apply_tags_abandoned_price',
				'field_sub_id' => $key,
			)
		);

		echo '</div>';

	}

	/**
	 * Get cart recovery URL
	 *
	 * @access public
	 * @return string Recovery URL
	 */

	public function get_cart_recovery_url( $contact_id, $user_data ) {

		$cart_contents = array(
			'contents' => EDD()->session->get( 'edd_cart' ),
			'user'     => $user_data,
		);

		$transient = get_transient( 'wpf_abandoned_cart_' . $contact_id );

		// Merge with the existing data so we don't lose any order IDs

		if ( ! empty( $transient ) ) {
			$cart_contents = array_merge( $transient, $cart_contents );
		}

		set_transient( 'wpf_abandoned_cart_' . $contact_id, $cart_contents, 7 * DAY_IN_SECONDS );

		$url = add_query_arg( 'wpfrc', $contact_id, edd_get_checkout_uri() );

		return $url;

	}

	/**
	 * Recover a saved cart
	 *
	 * @access public
	 * @return void
	 */

	public function recover_cart() {

		if ( isset( $_GET['wpfrc'] ) ) {

			// No need to send another abandoned cart
			remove_action( 'wpf_abandoned_cart_start', array( $this, 'checkout_begin' ), 30, 2 );

			$cart = get_transient( 'wpf_abandoned_cart_' . $_GET['wpfrc'] );

			if ( ! empty( $cart ) ) {

				EDD()->session->set( 'edd_cart', $cart['contents'] );

				EDD()->cart->contents = $cart['contents'];

			}
		}

	}


	/**
	 * Get cart ID from EDD session
	 *
	 * @access public
	 * @return bool / int Cart ID
	 */

	public function get_cart_id() {

		if ( isset( $_COOKIE['edd_cart_token'] ) ) {

			$token = $_COOKIE['edd_cart_token'];

		} else {

			$token = edd_generate_cart_token();

			setcookie( 'edd_cart_token', $token, time() + 3600 * 24 * 7, COOKIEPATH, COOKIE_DOMAIN );

		}

		return $token;

	}

	/**
	 * Applies product specific abandoned cart tags when user data is first captured
	 *
	 * @access public
	 * @return void
	 */

	public function checkout_begin( $contact_id = false, $apply_tags = array(), $user_data = array(), $source = 'edd' ) {

		// Don't run on initial checkout load for guests
		if ( ! is_user_logged_in() && $contact_id == false ) {
			return;
		}

		if ( empty( $user_data ) && is_user_logged_in() ) {

			$user                    = wp_get_current_user();
			$user_data['user_email'] = $user->user_email;
			$user_data['first_name'] = $user->first_name;
			$user_data['last_name']  = $user->last_name;

		}

		$cart_contents = edd_get_cart_contents();

		foreach ( $cart_contents as $product ) {

			$settings = get_post_meta( $product['id'], 'wpf-settings-edd', true );

			if ( ! empty( $settings ) && is_array( $settings ) && ! empty( $settings['apply_tags_abandoned'] ) ) {
				$apply_tags = array_merge( $apply_tags, $settings['apply_tags_abandoned'] );
			}

			// Variable pricing tags

			if ( isset( $settings['apply_tags_abandoned_price'] ) && isset( $product['options'] ) && isset( $product['options']['price_id'] ) ) {

				if ( ! empty( $settings['apply_tags_abandoned_price'][ $product['options']['price_id'] ] ) ) {
					$apply_tags = array_merge( $apply_tags, $settings['apply_tags_abandoned_price'][ $product['options']['price_id'] ] );
				}
			}
		}

		// Apply any tags

		if ( ! empty( $apply_tags ) ) {

			if ( is_user_logged_in() ) {

				wp_fusion()->user->apply_tags( $apply_tags );

			} elseif ( false != $contact_id ) {

				wp_fusion()->logger->handle(
					'info', get_current_user_id(), 'Applying abandoned cart tags to contact #' . $contact_id . ':', array(
						'tag_array' => $apply_tags,
						'source'    => 'wpf-abandoned-cart',
					)
				);

				wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );

			}
		}

		//
		// Sync carts stuff
		//

		$items = array();

		foreach ( edd_get_cart_contents() as $item ) {

			//
			// Put together the item data
			//

			$price = edd_get_cart_item_price( $item['id'], $item['options'] );

			$item_data = array(
				'product_id'  => $item['id'],
				'name'        => get_post_field( 'post_title', $item['id'], 'raw' ),
				'quantity'    => $item['quantity'],
				'product_url' => get_the_permalink( $item['id'] ),
				'price'       => $price,
				'total'       => ( $price * $item['quantity'] ),
			);

			$items[] = $item_data;

		}

		if ( get_transient( 'wpf_abandoned_cart_' . $contact_id ) ) {
			$update = true;
		} else {
			$update = false;
		}

		//
		// Gets the recovery URL
		//

		$args = array(
			'cart_id'      => $this->get_cart_id(),
			'recovery_url' => $this->get_cart_recovery_url( $contact_id, $user_data ),
			'items'        => $items,
			'user_email'   => $user_data['user_email'],
			'provider'     => 'Easy Digital Downloads',
			'update'       => $update,
			'currency'     => edd_get_currency(),
		);

		do_action( 'wpf_abandoned_cart_created', $contact_id, $args );

	}

	/**
	 * Remove abandoned cart tags
	 *
	 * @access public
	 * @return void
	 */

	public function checkout_complete( $payment_id, $contact_id ) {

		// Get tags to be removed
		$remove_tags = wp_fusion()->settings->get( 'abandoned_cart_apply_tags' );

		if ( empty( $remove_tags ) ) {
			$remove_tags = array();
		}

		$cart_items = edd_get_payment_meta_cart_details( $payment_id );

		foreach ( $cart_items as $item ) {

			$settings = get_post_meta( $item['id'], 'wpf-settings-edd', true );

			if ( ! empty( $settings ) && is_array( $settings ) && ! empty( $settings['apply_tags_abandoned'] ) ) {
				$remove_tags = array_merge( $remove_tags, $settings['apply_tags_abandoned'] );
			}

			// Variable pricing tags
			if ( isset( $settings['apply_tags_abandoned_price'] ) ) {

				$payment_details = get_post_meta( $payment_id, '_edd_payment_meta', true );

				foreach ( $payment_details['downloads'] as $download ) {

					$price_id = edd_get_cart_item_price_id( $download );

					if ( isset( $settings['apply_tags_abandoned_price'][ $price_id ] ) ) {
						$remove_tags = array_merge( $remove_tags, $settings['apply_tags_abandoned_price'][ $price_id ] );
					}
				}
			}
		}

		if ( ! empty( $remove_tags ) ) {

			$user_id = edd_get_payment_user_id( $payment_id );

			if ( $user_id == '-1' ) {

				// Guest checkout
				wp_fusion()->crm->remove_tags( $remove_tags, $contact_id );

			} else {

				// Logged in users
				wp_fusion()->user->remove_tags( $remove_tags, $user_id );

			}
		}

		// Clear out transient

		delete_transient( 'wpf_abandoned_cart_' . $contact_id );

	}


}

new WPF_Abandoned_Cart_EDD();
