<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Oxygen extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   3.30.1
	 * @return  void
	 */

	public function init() {

		$this->slug = 'oxygen';

		add_action( 'init', array( $this, 'register_condition' ) );

	}

	/**
	 * Register custom Oxygen condition
	 *
	 * @access public
	 * @return void
	 */

	public function register_condition() {

		$available_tags = wp_fusion()->settings->get( 'available_tags', array() );

		$options = array();

		foreach ( $available_tags as $id => $label ) {

			if ( is_array( $label ) ) {
				$label = $label['label'];
			}

			$options[ $id ] = $label;

		}

		$args = array(
			'options' => $options,
			'custom'  => true,
		);

		$operators = array( __( 'Has tag', 'wp-fusion' ), __( 'Does not have tag', 'wp-fusion' ) );

		oxygen_vsb_register_condition( sprintf( __( '%s Tags', 'wp-fusion' ), wp_fusion()->crm->name ), $args, $operators, 'wpf_oxygen_condition_callback', 'User' );

	}


}

/**
 * Check conditions to determine visibility of component (this should really be in the class but Oxygen doesn't support array syntax for callbacks)
 *
 * @access public
 * @return bool Can Access
 */

function wpf_oxygen_condition_callback( $value, $operator ) {

	$can_access = true;

	if ( 'Has tag' == $operator && ! wp_fusion()->user->has_tag( $value ) ) {

		$can_access = false;

	} elseif ( 'Does not have tag' == $operator && ! wpf_is_user_logged_in() ) {

		$can_access = true;

	} elseif ( 'Does not have tag' == $operator && wp_fusion()->user->has_tag( $value ) ) {

		$can_access = false;

	}

	if ( wp_fusion()->settings->get( 'exclude_admins' ) == true && current_user_can( 'manage_options' ) ) {
		$can_access = true;
	}

	global $post;

	$post_id = 0;

	if ( ! empty( $post ) ) {
		$post_id = $post->ID;
	}

	$can_access = apply_filters( 'wpf_user_can_access', $can_access, wpf_get_current_user_id(), $post_id );

	return $can_access;

}

new WPF_Oxygen();
