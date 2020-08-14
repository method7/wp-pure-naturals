<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_WP_Members extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   3.33.2
	 * @return  void
	 */

	public function init() {

		$this->name = 'WP Members';
		$this->slug = 'wp-members';

		add_action( 'user_register', array( $this, 'user_register' ), 10 );
		add_action( 'wpmem_user_activated', array( $this, 'user_activated' ) );

		// WPF stuff
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 5 ); // 5 so other plugins can set their own groups

		// Settings
		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

	}

	/**
	 * Triggered before registration, allows removing WPF create_user hook
	 *
	 * @access public
	 * @return void
	 */

	public function user_register( $user_id ) {

		if ( true == wp_fusion()->settings->get( 'wp_members_defer' ) && ! wpmem_is_user_activated( $user_id ) ) {

			remove_action( 'user_register', array( wp_fusion()->user, 'user_register' ), 20 );

		}

	}

	/**
	 * Triggered after activation, syncs the new user to the CRM
	 *
	 * @access public
	 * @return void
	 */

	public function user_activated( $user_id ) {

		if ( true == wp_fusion()->settings->get( 'wp_members_defer' ) ) {

			wp_fusion()->user->user_register( $user_id );

		}

	}


	/**
	 * Adds WP Members field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */

	public function add_meta_field_group( $field_groups ) {

		$field_groups['wp_members'] = array(
			'title'  => 'WP Members',
			'fields' => array(),
		);

		return $field_groups;

	}


	/**
	 * Adds WP Members meta fields to WPF contact fields list
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$fields = wpmem_fields();

		foreach ( $fields as $key => $field ) {

			$skip_fields = array( 'username', 'confirm_email', 'password', 'confirm_password' );

			if ( ! in_array( $key, $skip_fields ) ) {

				$meta_fields[ $key ] = array(
					'label' => $field['label'],
					'type'  => $field['type'],
					'group' => 'wp_members',
				);

			}
		}

		return $meta_fields;

	}


	/**
	 * Adds Integrations tab if not already present
	 *
	 * @access public
	 * @return array Page
	 */

	public function configure_sections( $page, $options ) {

		if ( ! isset( $page['sections']['integrations'] ) ) {
			$page['sections'] = wp_fusion()->settings->insert_setting_after( 'contact-fields', $page['sections'], array( 'integrations' => __( 'Integrations', 'wp-fusion' ) ) );
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

		$settings['wp_members_header'] = array(
			'title'   => __( 'WP-Members Integration', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['wp_members_defer'] = array(
			'title'   => __( 'Defer Until Activation', 'wp-fusion' ),
			'desc'    => sprintf( __( 'Don\'t send any data to %s until the account has been activated.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'integrations',
		);

		return $settings;

	}

}

new WPF_WP_Members();
