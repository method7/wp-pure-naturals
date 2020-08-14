<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Ahoy extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'ahoy';

		// Load conditions
		add_filter( 'ahoy_registered_conditions', array( $this, 'registered_conditions' ) );

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
				'name'     => __( 'User Tags' ),
				'callback' => array( $this, 'show_popup' ),
				'fields'   => array(
					'selected' => array(
						'placeholder' => __( 'Select tags' ),
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

new WPF_Ahoy();
