<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Popup_Maker extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'popup-maker';
		$this->name = 'Popup Maker';

		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 8, 2 );

		// Form submissions
		add_action( 'pum_sub_form_submission', array( $this, 'form_submission' ), 10, 3 );

		add_filter( 'wpf_meta_box_post_types', array( $this, 'unset_wpf_meta_boxes' ) );

		// Load conditions
		add_filter( 'pum_registered_conditions', array( $this, 'registered_conditions' ) );

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
	 * Registers additional Popup Maker settings
	 *
	 * @access  public
	 * @return  array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['pm_header'] = array(
			'title'   => __( 'Popup Maker Integration', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['pm_add_contacts'] = array(
			'title'   => __( 'Add Contacts', 'wp-fusion' ),
			'desc'    => sprintf( __( 'Add contacts to %s when a Popup Maker subscription form is submitted.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'std'     => 1,
			'type'    => 'checkbox',
			'section' => 'integrations',
		);

		return $settings;

	}

	/**
	 * Sync Popup Maker form submissions to the CRM
	 *
	 * @access public
	 * @return void
	 */

	public function form_submission( $values, $response, $errors ) {

		if ( wp_fusion()->settings->get( 'pm_add_contacts' ) != true ) {
			return;
		}

		// Give up if they didn't opt in
		if ( 'deleted@site.invalid' == $values['email'] ) {
			return;
		}

		$contact_data = array(
			'first_name' => $values['fname'],
			'last_name'  => $values['lname'],
			'user_email' => $values['email'],
		);

		// Send the meta data
		if ( wpf_is_user_logged_in() ) {

			wp_fusion()->user->push_user_meta( wpf_get_current_user_id(), $contact_data );

		} else {

			$contact_id = $this->guest_registration( $contact_data['user_email'], $contact_data );

		}

	}

	/**
	 * Removes standard WPF meta boxes from Popup Maker post type
	 *
	 * @access  public
	 * @return  array Post Types
	 */

	public function unset_wpf_meta_boxes( $post_types ) {

		unset( $post_types['popup'] );

		return $post_types;

	}

	/**
	 * Loads conditions into Targeting panel
	 *
	 * @access public
	 * @return array Conditions
	 */

	public function registered_conditions( $conditions ) {

		$available_tags = wp_fusion()->settings->get( 'available_tags', array() );

		if ( is_array( reset( $available_tags ) ) ) {

			// Handling for select with category groupings
			$data = array();

			$tag_categories = array();
			foreach ( $available_tags as $value ) {
				if ( ! isset( $data[ $value['category'] ] ) ) {
					$data[ $value['category'] ] = array();
				}
			}

			foreach ( $available_tags as $id => $value ) {

				$data[ $value['category'] ][ $id ] = $value['label'];

			}
		} else {

			$data = $available_tags;

		}

		$wpf_conditions = array(
			'wpf_tags' => array(
				'group'    => wp_fusion()->crm->name,
				'name'     => __( 'User Tags', 'wp-fusion' ),
				'callback' => array( $this, 'show_popup' ),
				'fields'   => array(
					'selected' => array(
						'placeholder' => __( 'Select tags', 'wp-fusion' ),
						'type'        => 'select',
						'multiple'    => true,
						'select2'     => true,
						'as_array'    => true,
						'class'       => 'select4-wpf-tags-wrapper',
						'options'     => $data,
					),
				),
			),

		);

		$conditions = array_merge( $conditions, $wpf_conditions );

		return $conditions;

	}


	/**
	 * Determine if the user should see the popup
	 *
	 * @access public
	 * @return bool
	 */

	public function show_popup( $settings ) {

		if ( ! wpf_is_user_logged_in() ) {
			return false;
		}

		if ( empty( $settings['selected'] ) ) {
			return true;
		}

		if ( wp_fusion()->settings->get( 'exclude_admins' ) == true && current_user_can( 'manage_options' ) ) {
			return true;
		}

		$user_tags = wp_fusion()->user->get_tags();

		$result = array_intersect( (array) $settings['selected'], $user_tags );

		if ( ! empty( $result ) ) {
			return true;
		} else {
			return false;
		}

	}

}

new WPF_Popup_Maker();
