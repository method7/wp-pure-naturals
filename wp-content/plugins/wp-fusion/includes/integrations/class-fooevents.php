<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_FooEvents extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'fooevents';

		add_filter( 'wpf_woocommerce_customer_data', array( $this, 'merge_custom_fields' ), 10, 2 );
		add_action( 'wpf_woocommerce_payment_complete', array( $this, 'add_attendee_data' ), 20, 2 );

		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'add_meta_fields' ) );

		// Product settings
		add_action( 'wpf_woocommerce_panel', array( $this, 'panel_content' ) );
		add_action( 'wpf_woocommerce_variation_panel', array( $this, 'variation_panel_content' ), 10, 2 );

	}

	/**
	 * Merges custom fields for the primary contact on the order
	 *
	 * @access  public
	 * @return  array Customer Data
	 */

	public function merge_custom_fields( $customer_data, $order ) {

		$order_data = $order->get_data();

		foreach ( $order_data['meta_data'] as $meta ) {

			if ( ! is_a( $meta, 'WC_Meta_Data' ) ) {
				continue;
			}

			$data = $meta->get_data();

			if ( 'WooCommerceEventsOrderTickets' != $data['key'] ) {
				continue;
			}

			foreach ( $data['value'] as $sub_value ) {

				if ( ! is_array( $sub_value ) ) {
					continue;
				}

				foreach ( $sub_value as $attendee ) {

					if ( ! isset( $customer_data['event_name'] ) ) {

						// Going to merge the event and venue fields into the main customer even if they aren't an attendee, just to save confusion
						$product_id = $attendee['WooCommerceEventsProductID'];

						$hour    = get_post_meta( $product_id, 'WooCommerceEventsHour', true );
						$minutes = get_post_meta( $product_id, 'WooCommerceEventsMinutes', true );
						$period  = get_post_meta( $product_id, 'WooCommerceEventsPeriod', true );

						$event_fields = array(
							'event_name'       => get_the_title( $product_id ),
							'event_start_date' => get_post_meta( $product_id, 'WooCommerceEventsDate', true ),
							'event_start_time' => $hour . ':' . $minutes . ' ' . $period,
							'event_venue_name' => get_post_meta( $product_id, 'WooCommerceEventsLocation', true ),
						);

						$customer_data = array_merge( $customer_data, $event_fields );

					}

					if ( $attendee['WooCommerceEventsAttendeeEmail'] == $order->get_billing_email() ) {

						// Merge name fields if blank on the main order
						if ( empty( $customer_data['first_name'] ) ) {
							$customer_data['first_name'] = $attendee['WooCommerceEventsAttendeeName'];
						}

						if ( empty( $customer_data['billing_first_name'] ) ) {
							$customer_data['billing_first_name'] = $attendee['WooCommerceEventsAttendeeName'];
						}

						if ( empty( $customer_data['last_name'] ) ) {
							$customer_data['last_name'] = $attendee['WooCommerceEventsAttendeeLastName'];
						}

						if ( empty( $customer_data['billing_last_name'] ) ) {
							$customer_data['billing_last_name'] = $attendee['WooCommerceEventsAttendeeLastName'];
						}

						// Merge custom fields, they only go if the customer is also an attendee
						if ( ! empty( $attendee['WooCommerceEventsCustomAttendeeFields'] ) ) {

							foreach ( $attendee['WooCommerceEventsCustomAttendeeFields'] as $key => $value ) {

								$key = str_replace( 'fooevents_custom_', '', $key );

								$customer_data[ $key ] = $value;

							}
						}
					}
				}
			}
		}

		return $customer_data;

	}


	/**
	 * Add / tag contacts for event attendees
	 *
	 * @access  public
	 * @return  void
	 */

	public function add_attendee_data( $order_id, $contact_id ) {

		$order = wc_get_order( $order_id );

		$order_data = $order->get_data();

		foreach ( $order_data['meta_data'] as $meta ) {

			if ( ! is_a( $meta, 'WC_Meta_Data' ) ) {
				continue;
			}

			$data = $meta->get_data();

			if ( 'WooCommerceEventsOrderTickets' != $data['key'] ) {
				continue;
			}

			foreach ( $data['value'] as $sub_value ) {

				if ( ! is_array( $sub_value ) ) {
					continue;
				}

				foreach ( $sub_value as $attendee ) {

					$settings = get_post_meta( $attendee['WooCommerceEventsProductID'], 'wpf-settings-woo', true );

					if ( empty( $settings ) || ! isset( $settings['add_attendees'] ) || $settings['add_attendees'] != true ) {
						continue;
					}

					$update_data = array(
						'first_name'      => $attendee['WooCommerceEventsAttendeeName'],
						'last_name'       => $attendee['WooCommerceEventsAttendeeLastName'],
						'user_email'      => $attendee['WooCommerceEventsAttendeeEmail'],
						'billing_phone'   => $attendee['WooCommerceEventsAttendeeTelephone'],
						'phone_number'    => $attendee['WooCommerceEventsAttendeeTelephone'],
						'billing_company' => $attendee['WooCommerceEventsAttendeeCompany'],
						'company'         => $attendee['WooCommerceEventsAttendeeCompany'],
					);

					// Merge event and venue fields
					$product_id = $attendee['WooCommerceEventsProductID'];

					$hour    = get_post_meta( $product_id, 'WooCommerceEventsHour', true );
					$minutes = get_post_meta( $product_id, 'WooCommerceEventsMinutes', true );
					$period  = get_post_meta( $product_id, 'WooCommerceEventsPeriod', true );

					$event_fields = array(
						'event_name'       => get_the_title( $product_id ),
						'event_start_date' => get_post_meta( $product_id, 'WooCommerceEventsDate', true ),
						'event_start_time' => $hour . ':' . $minutes . ' ' . $period,
						'event_venue_name' => get_post_meta( $product_id, 'WooCommerceEventsLocation', true ),
					);

					$update_data = array_merge( $update_data, $event_fields );

					// Merge custom fields
					if ( ! empty( $attendee['WooCommerceEventsCustomAttendeeFields'] ) ) {

						foreach ( $attendee['WooCommerceEventsCustomAttendeeFields'] as $key => $value ) {

							$key = str_replace( 'fooevents_custom_', '', $key );

							$update_data[ $key ] = $value;

						}
					}

					$contact_id = wp_fusion()->crm->get_contact_id( $update_data['user_email'] );

					// This was already sent in the main order data so it doesn't need to be sent again
					if ( $attendee['WooCommerceEventsAttendeeEmail'] != $order->get_billing_email() ) {

						wpf_log( 'info', 0, 'FooEvents adding new event attendee for order <a href="' . admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . '" target="_blank">#' . $order_id . '</a>:', array( 'meta_array' => $update_data ) );

						if ( empty( $contact_id ) ) {

							$contact_id = wp_fusion()->crm->add_contact( $update_data );

						} else {

							wp_fusion()->crm->update_contact( $contact_id, $update_data );

						}
					}

					$apply_tags = array();

					// Product settings

					if ( ! empty( $settings['apply_tags_event_attendees'] ) ) {
						$apply_tags = array_merge( $apply_tags, $settings['apply_tags_event_attendees'] );
					}

					// Variation settings

					if ( ! empty( $attendee['WooCommerceEventsVariationID'] ) ) {

						$settings = get_post_meta( $attendee['WooCommerceEventsVariationID'], 'wpf-settings-woo', true );

						if ( ! empty( $settings ) && ! empty( $settings['apply_tags_event_attendees_variation'] ) && ! empty( $settings['apply_tags_event_attendees_variation'][ $attendee['WooCommerceEventsVariationID'] ] ) ) {
							$apply_tags = array_merge( $apply_tags, $settings['apply_tags_event_attendees_variation'][ $attendee['WooCommerceEventsVariationID'] ] );
						}
					}

					if ( ! empty( $apply_tags ) ) {

						wpf_log( 'info', 0, 'Applying tags to FooEvents attendee for contact ID ' . $contact_id . ': ', array( 'tag_array' => $apply_tags ) );

						wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );

					}
				}
			}
		}

	}

	/**
	 * Adds FE field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */

	public function add_meta_field_group( $field_groups ) {

		if ( ! isset( $field_groups['fooevents'] ) ) {
			$field_groups['fooevents'] = array(
				'title'  => 'FooEvents',
				'fields' => array(),
			);
		}

		return $field_groups;

	}

	/**
	 * Loads FE fields for inclusion in Contact Fields table
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function add_meta_fields( $meta_fields ) {

		$meta_fields['event_name'] = array(
			'label' => 'Event Name',
			'type'  => 'text',
			'group' => 'fooevents',
		);

		$meta_fields['event_start_date'] = array(
			'label' => 'Event Start Date',
			'type'  => 'date',
			'group' => 'fooevents',
		);

		$meta_fields['event_start_time'] = array(
			'label' => 'Event Start Time',
			'type'  => 'text',
			'group' => 'fooevents',
		);

		$meta_fields['event_venue_name'] = array(
			'label' => 'Event Venue Name',
			'type'  => 'text',
			'group' => 'fooevents',
		);

		$args = array(
			'numberposts' => - 1,
			'post_type'   => 'product',
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'key'     => 'fooevents_custom_attendee_fields_options_serialized',
					'compare' => 'EXISTS',
				),
			),
		);

		$products = get_posts( $args );

		if ( ! empty( $products ) ) {

			foreach ( $products as $product_id ) {

				$fields = get_post_meta( $product_id, 'fooevents_custom_attendee_fields_options_serialized', true );

				$fields = json_decode( $fields );

				if ( ! empty( $fields ) ) {

					foreach ( $fields as $key => $field ) {

						$id   = str_replace( '_option', '', $key );
						$slug = strtolower( str_replace( ' ', '_', $field->{$id . '_label'} ) );

						$meta_fields[ $slug ] = array(
							'label' => $field->{$id . '_label'},
							'type'  => $field->{$id . '_type'},
							'group' => 'fooevents',
						);

					}
				}
			}
		}

		return $meta_fields;

	}


	/**
	 * Display event settings
	 *
	 * @access public
	 * @return mixed
	 */

	public function panel_content( $post_id ) {

		$settings = array(
			'apply_tags_event_attendees' => array(),
			'add_attendees'              => false,
		);

		if ( get_post_meta( $post_id, 'wpf-settings-woo', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post_id, 'wpf-settings-woo', true ) );
		}

		echo '<div class="options_group wpf-product">';

		echo '<p class="form-field"><label><strong>FooEvents</strong></label></p>';

		echo '<p class="form-field"><label for="wpf-add-attendees">' . __( 'Add attendees', 'wp-fusion' ) . '</label>';
		echo '<input class="checkbox" type="checkbox" id="wpf-add-attendees" name="wpf-settings-woo[add_attendees]" data-unlock="wpf-settings-woo-apply_tags_event_attendees" value="1" ' . checked( $settings['add_attendees'], 1, false ) . ' />';
		echo '<span class="description">' . sprintf( __( 'Add each event attendee as a separate contact in %s.', 'wp-fusion' ), wp_fusion()->crm->name ) . '</span>';
		echo '</p>';

		echo '<p class="form-field"><label for="wpf-apply-tags-woo">' . __( 'Apply tags to event attendees', 'wp-fusion' );

		echo ' <span class="dashicons dashicons-editor-help wpf-tip bottom" data-tip="' . __( 'These tags will only be applied to event attendees entered on the registration form, not the customer who placed the order. <strong>Add attendees</strong> must be enabled.', 'wp-fusion' ) . '"></span>';

		echo '</label>';

		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['apply_tags_event_attendees'],
				'meta_name' => 'wpf-settings-woo',
				'field_id'  => 'apply_tags_event_attendees',
				'disabled'  => $settings['add_attendees'] ? false : true,
			)
		);

		echo '</p>';

		echo '</div>';

	}


	/**
	 * Display event settings (Variations)
	 *
	 * @access public
	 * @return mixed
	 */

	public function variation_panel_content( $variation_id, $settings ) {

		$defaults = array(
			'apply_tags_event_attendees_variation' => array( $variation_id => array() ),
		);

		$settings = array_merge( $defaults, $settings );

		echo '<div><p class="form-row form-row-full">';
		echo '<label for="wpf-settings-woo-variation-apply_tags_event_attendees_variation-' . $variation_id . '">';
		_e( 'Apply tags to event attendees at this variation:', 'wp-fusion' );

		echo ' <span class="dashicons dashicons-editor-help wpf-tip bottom" data-tip="' . __( 'These tags will only be applied to event attendees entered on the registration form, not the customer who placed the order. <strong>Add attendees</strong> must be enabled on the main WP Fusion settings panel.', 'wp-fusion' ) . '"></span>';

		echo '</label>';

		wpf_render_tag_multiselect(
			array(
				'setting'      => $settings['apply_tags_event_attendees_variation'],
				'meta_name'    => 'wpf-settings-woo-variation',
				'field_id'     => 'apply_tags_event_attendees_variation',
				'field_sub_id' => $variation_id,
			)
		);

		echo '</p></div>';

	}


}

new WPF_FooEvents();
