<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_API {

	public function __construct() {

		add_action( 'init', array( $this, 'get_actions' ) );
		add_action( 'after_setup_theme', array( $this, 'thrivecart' ) ); // after_setup_theme to get around issues with ThriveCart and headers already being sent by init

		add_action( 'wpf_update', array( $this, 'update_user' ) );
		add_action( 'wpf_update_tags', array( $this, 'update_tags' ) );
		add_action( 'wpf_add', array( $this, 'add_user' ) );

		// Import / update user actions
		add_action( 'wp_ajax_nopriv_wpf_update_user', array( $this, 'update_user' ) );
		add_action( 'wp_ajax_nopriv_wpf_add_user', array( $this, 'add_user' ) );

	}


	/**
	 * Gets actions passed as query params
	 *
	 * @access public
	 * @return void
	 */

	public function get_actions() {

		if ( isset( $_REQUEST['wpf_action'] ) ) {

			if ( ! isset( $_REQUEST['access_key'] ) || $_REQUEST['access_key'] != wp_fusion()->settings->get( 'access_key' ) ) {

				wpf_log( 'error', 0, 'Webhook received but access key ' . $_REQUEST['access_key'] . ' was invalid.', array( 'source' => 'api' ) );

				wp_die( 'Invalid Access Key' );

			}

			if ( $_REQUEST['wpf_action'] == 'test' ) {

				echo json_encode( array( 'status' => 'success' ) );
				die();

			}

			if ( has_action( 'wp_ajax_nopriv_wpf_' . $_REQUEST['wpf_action'] ) ) {
				do_action( 'wp_ajax_nopriv_wpf_' . $_REQUEST['wpf_action'] );
			} else {
				do_action( 'wpf_' . $_REQUEST['wpf_action'] );
			}
		}

	}

	/**
	 * Adds async actions to background queue
	 *
	 * @access public
	 * @return void
	 */

	public function push_to_queue( $post_data ) {

		$defaults = array(
			'notify' => false,
			'role'   => false,
		);

		$post_data = array_merge( $defaults, $post_data );

		wp_fusion()->batch->includes();
		wp_fusion()->batch->init();

		wp_fusion()->batch->process->push_to_queue(
			array(
				'action' => 'wpf_batch_import_users',
				'args'   => array( $post_data['contact_id'], $post_data ),
			)
		);
		wp_fusion()->batch->process->save()->dispatch();

	}


	/**
	 * Called by CRM HTTP Posts to update a user
	 *
	 * @access public
	 * @return null
	 */

	public function update_user() {

		$post_data = apply_filters( 'wpf_crm_post_data', $_REQUEST );

		if ( empty( $post_data ) || ! isset( $post_data['contact_id'] ) ) {

			wpf_log( 'error', 0, 'Update webhook received but contact data was not found or in an invalid format.', array( 'source' => 'api', 'meta_array_nofilter' => $post_data ) );

			wp_die( '<h3>Abort</h3>Contact data not found or contact not eligible for update.', 'Abort', 200 );

		}

		$args = array(
			'meta_key'   => wp_fusion()->crm->slug . '_contact_id',
			'meta_value' => $post_data['contact_id'],
			'fields'     => array( 'ID' ),
		);

		$users = get_users( $args );

		// If user is found
		if ( isset( $users[0] ) ) {

			// Logger

			$message = 'Received update webhook for contact ID <strong>' . $post_data['contact_id'] . '</strong>';

			if ( isset( $post_data['async'] ) ) {
				$message .= '. Dispatching to async queue.';
			}

			wpf_log( 'info', $users[0]->ID, $message, array( 'source' => 'api' ) );

			// Async queue
			if ( isset( $post_data['async'] ) && $post_data['async'] == true ) {

				$this->push_to_queue( $post_data );
				wp_die( '<h3>Success</h3>Contact ID ' . $post_data['contact_id'] . ' pushed to async queue.', 'Success', 200 );

			}

			// Catch output from other plugins
			ob_start();

			$user_meta = wp_fusion()->user->pull_user_meta( $users[0]->ID );
			$tags      = wp_fusion()->user->get_tags( $users[0]->ID, true, false );

			// Maybe change role (but not for admins)
			if ( isset( $post_data['role'] ) && ! user_can( $users[0]->ID, 'manage_options' ) && wp_roles()->is_role( $post_data['role'] ) ) {

				$user = new WP_User( $users[0]->ID );
				$user->set_role( $post_data['role'] );
			}

			ob_clean();

			do_action( 'wpf_api_success', $users[0]->ID, 'update' );

			wp_die( '<h3>Success</h3>Updated user meta:<pre>' . print_r( $user_meta, true ) . '</pre><br />Updated tags:<pre>' . print_r( $tags, true ) . '</pre>', 'Success', 200 );

		} else {

			wpf_log( 'notice', 0, 'Update webhook received but no matching user found for contact ID <strong>' . $post_data['contact_id'] . '</strong>', array( 'source' => 'api' ) );

			wp_die( 'No matching user found', 'Not Found', 200 );

		}
	}


	/**
	 * Called by CRM HTTP Posts to update a user
	 *
	 * @access public
	 * @return null
	 */

	public function update_tags() {

		$post_data = apply_filters( 'wpf_crm_post_data', $_REQUEST );

		if ( empty( $post_data ) || ! isset( $post_data['contact_id'] ) ) {

			wpf_log( 'error', 0, 'Update tags webhook received but contact data was not found or in an invalid format.', array( 'source' => 'api', 'meta_array_nofilter' => $post_data ) );

			wp_die( '<h3>Abort</h3>Contact data not found or contact not eligible for update.', 'Abort', 200 );

		}

		$args = array(
			'meta_key'   => wp_fusion()->crm->slug . '_contact_id',
			'meta_value' => $post_data['contact_id'],
			'fields'     => array( 'ID' ),
		);

		$users = get_users( $args );

		// If user is found
		if ( isset( $users[0] ) ) {

			// Logger

			$message = 'Received update tags webhook for contact ID <strong>' . $post_data['contact_id'] . '</strong>';

			if ( isset( $post_data['async'] ) ) {
				$message .= '. Dispatching to async queue.';
			}

			wpf_log( 'info', $users[0]->ID, $message, array( 'source' => 'api' ) );

			// ActiveCampaign can read the tags out of the payload, we don't need another API call
			if ( in_array( 'quick_update_tags', wp_fusion()->crm->supports ) ) {

				$tags = wp_fusion()->crm->quick_update_tags( $_REQUEST, $users[0]->ID );

			} else {

				// Async queue
				if ( isset( $post_data['async'] ) && $post_data['async'] == true ) {

					$this->push_to_queue( $post_data );
					wp_die( '<h3>Success</h3>Contact ID ' . $post_data['contact_id'] . ' pushed to async queue.', 'Success', 200 );

				}

				// Catch output from other plugins
				ob_start();

				$tags = wp_fusion()->user->get_tags( $users[0]->ID, true, false );

				ob_clean();

			}

			do_action( 'wpf_api_success', $users[0]->ID, 'update_tags' );

			wp_die( '<h3>Success</h3>Updated tags:<pre>' . print_r( $tags, true ) . '</pre>', 'Success', 200 );

		} else {

			wpf_log( 'notice', 0, 'Update tags webhook received but no matching user found for contact ID <strong>' . $post_data['contact_id'] . '</strong>', array( 'source' => 'api' ) );

			wp_die( 'No matching user found', 'Not Found', 200 );

		}
	}


	/**
	 * Called by CRM HTTP Posts to add a user
	 *
	 * @access public
	 * @return null
	 */

	public function add_user() {

		$post_data = apply_filters( 'wpf_crm_post_data', $_REQUEST );

		if ( empty( $post_data ) || empty( $post_data['contact_id'] ) ) {

			wpf_log( 'error', 0, 'Import webhook received but contact data was not found or in an invalid format.', array( 'source' => 'api', 'meta_array_nofilter' => $post_data ) );

			wp_die( '<h3>Abort</h3>Contact data not found or contact not eligible for import.', 'Abort', 200 );

		}

		if ( isset( $post_data['send_notification'] ) && $post_data['send_notification'] == 'true' ) {
			$post_data['send_notification'] = true;
		} elseif ( ! isset( $post_data['send_notification'] ) ) {
			$post_data['send_notification'] = false;
		}

		if ( ! isset( $post_data['role'] ) ) {
			$post_data['role'] = 'subscriber';
		}

		// Logger

		$message = 'Receved import user webhook for contact ID <strong>' . $post_data['contact_id'] . '</strong>';

		if ( isset( $post_data['async'] ) ) {
			$message .= '. Dispatching to async queue.';
		}

		wpf_log( 'info', $users[0]->ID, $message, array( 'source' => 'api' ) );

		// Async queue
		if ( isset( $post_data['async'] ) && $post_data['async'] == true ) {

			$this->push_to_queue( $post_data );
			wp_die( '<h3>Success</h3>Contact ID ' . $post_data['contact_id'] . ' pushed to async queue.', 'Success', 200 );

		}

		// Catch output from other plugins
		ob_start();

		$user_id = wp_fusion()->user->import_user( $post_data['contact_id'], $post_data['send_notification'], $post_data['role'] );

		ob_clean();

		if ( is_wp_error( $user_id ) ) {

			wpf_log( 'error', 0, 'Import user failed for contact ID <strong>' . $post_data['contact_id'] . '</strong>. Error: ' . $user_id->get_error_message(), array( 'source' => 'api' ) );
			wp_die( '<h3>Error</h3>Error importing user: ' . print_r( $user_id, true ) );

		}

		if ( is_multisite() ) {
			$result = add_user_to_blog( get_current_blog_id(), $user_id, $post_data['role'] );
		}

		do_action( 'wpf_api_success', $user_id, 'add' );

		wp_die( '<h3>Success</h3>User imported with ID ' . $user_id, 'Success', 200 );

	}

	/**
	 * Handle ThriveCart auto login
	 *
	 * @access public
	 * @return null
	 */

	public function thrivecart() {

		if ( ! isset( $_GET['wpf_action'] ) || $_GET['wpf_action'] != 'thrivecart' ) {
			return;
		}

		if ( ! isset( $_REQUEST['access_key'] ) || $_REQUEST['access_key'] != wp_fusion()->settings->get( 'access_key' ) ) {
			return;
		}

		if ( ! isset( $_REQUEST['thrivecart']['customer']['email'] ) ) {

			wpf_log( 'error', 0, 'ThriveCart success URL detected but customer data was not found or in an invalid format.', array( 'source' => 'api', 'meta_array_nofilter' => $_REQUEST ) );
			return;

		}

		if ( wp_fusion()->settings->get( 'auto_login_thrivecart' ) != true ) {
			return;
		}

		$email_address = sanitize_email( $_REQUEST['thrivecart']['customer']['email'] );

		$user = get_user_by( 'email', $email_address );

		if ( ! empty( $user ) ) {

			// Existing user
			$user_id = $user->ID;

			$contact_id = wp_fusion()->user->get_contact_id( $user_id );

		} else {

			// Create new user
			$password = wp_generate_password( 12, false );

			$userdata = array(
				'user_login' => $email_address,
				'user_email' => $email_address,
				'first_name' => sanitize_text_field( $_REQUEST['thrivecart']['customer']['firstname'] ),
				'last_name'  => sanitize_text_field( $_REQUEST['thrivecart']['customer']['lastname'] ),
				'user_pass'  => $password,
			);

			$userdata = apply_filters( 'wpf_import_user', $userdata, false );

			wpf_log( 'info', 0, 'ThriveCart user creation triggered for ' . $email_address . ':', array( 'meta_array_nofilter' => $userdata ) );

			$user_id = wp_insert_user( $userdata );

			$contact_id = wp_fusion()->user->get_contact_id( $user_id );

			do_action( 'wpf_user_imported', $user_id, $userdata );

			$user = get_user_by( 'id', $user_id );

			// Send notification
			if ( isset( $_GET['send_notification'] ) ) {
				wp_new_user_notification( $user_id, null, 'user' );
			}
		}

		// Load tags
		wp_fusion()->user->get_tags( $user_id, true, false );

		// Apply the tags

		if ( ! empty( $_GET['apply_tags'] ) ) {

			$apply_tags = urldecode( $_GET['apply_tags'] );

			$apply_tags = explode( ',', $apply_tags );

			foreach ( $apply_tags as $i => $tag ) {

				$apply_tags[ $i ] = wp_fusion()->user->get_tag_id( $tag );

			}

			wp_fusion()->user->apply_tags( $apply_tags, $user_id );

		}

		// Maybe change role (but not for admins)
		if ( isset( $_GET['role'] ) && ! user_can( $user_id, 'manage_options' ) && wp_roles()->is_role( $_GET['role'] ) ) {
			$user->set_role( $_GET['role'] );
		}

		// Handle login
		wp_set_current_user( $user_id, $user->user_login );
		wp_set_auth_cookie( $user_id, true );
		do_action( 'wp_login', $user->user_login, $user );

	}

}


new WPF_API();
