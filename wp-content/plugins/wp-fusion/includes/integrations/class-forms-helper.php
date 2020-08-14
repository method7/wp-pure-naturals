<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Forms_Helper {


	/**
	 * Sends data to CRM from form plugins
	 *
	 * @access  public
	 * @since   3.24
	 * @return  Contact ID / WP_Error
	 */

	public static function process_form_data( $args ) {

		$args = apply_filters( 'wpf_forms_args', $args );

		// $email_address, $update_data, $apply_tags, $integration_slug, $integration_name, $form_id, $form_title, $form_edit_link

		extract( $args );

		// If no email and user not logged in don't bother

		if ( empty( $email_address ) && ! wpf_is_user_logged_in() ) {

			wpf_log( 'error', 0, 'Unable to process feed. No email address found.', array( 'source' => sanitize_title( $integration_name ) ) );

			return new WP_Error( 'error', 'Unable to process feed. No email address found.' );

		} elseif ( empty( $email_address ) && wpf_is_user_logged_in() ) {

			global $current_user;
			$contact_id = wp_fusion()->user->get_contact_id( $current_user->ID );

			if( empty( $contact_id ) ) {

				// If not found, check in the CRM and update locally
				$contact_id = wp_fusion()->user->get_contact_id( $current_user->ID, true );
			}

			$user_id = $current_user->ID;

		} else {

			// Email is set

			if ( is_object( get_user_by( 'email', $email_address ) ) ) {

				// Check and see if a local user exists with that email

				$user       = get_user_by( 'email', $email_address );

				$contact_id = wp_fusion()->user->get_contact_id( $user->ID );

				if( empty( $contact_id ) ) {

					// If not found, check in the CRM and update locally
					$contact_id = wp_fusion()->user->get_contact_id( $user->ID, true );

				}

				$user_id 	= $user->ID;

			} elseif( defined( 'DOING_WPF_AUTO_LOGIN' ) ) {

				// Auto login situations
				$user_id = wpf_get_current_user_id();
				$contact_id = wp_fusion()->user->get_contact_id( $user_id );

			}

		}

		if( empty( $user_id ) && wpf_is_user_logged_in() ) {

			$user_id = wpf_get_current_user_id();

		} elseif( empty( $user_id ) && ! wpf_is_user_logged_in() ) {

			$user_id = false;

		}

		// Try and look up CID
		if( empty( $contact_id ) ) {

			$contact_id = wp_fusion()->crm->get_contact_id( $email_address );

			$current_user_contact_id = wp_fusion()->user->get_contact_id();

			// Update contact ID if not set locally
			if( wpf_is_user_logged_in() && ! empty( $contact_id ) && ! is_object( $contact_id ) && empty( $current_user_contact_id ) ) {
				update_user_meta( $user_id, wp_fusion()->crm->slug . '_contact_id', $contact_id );
			}

		}

		if ( is_wp_error( $contact_id ) ) {
			wpf_log( $contact_id->get_error_code(), $user_id, 'Error getting contact ID: ' . $contact_id->get_error_message(), array( 'source' => sanitize_title( $integration_name ) ) );
			return $contact_id;
		}

		// Filter update data
		$update_data = apply_filters( 'wpf_forms_pre_submission', $update_data, $user_id, $contact_id, $form_id );
		$update_data = apply_filters( 'wpf_' . $integration_slug . '_pre_submission', $update_data, $user_id, $contact_id, $form_id );

		// Dynamic tagging
		if ( in_array( 'add_tags', wp_fusion()->crm->supports ) ) {

			foreach ( $update_data as $key => $value ) {

				if ( false !== strpos( $key, 'add_tag_' ) ) {

					if ( is_array( $value ) ) {

						$apply_tags = array_merge( $apply_tags, $value );

					} elseif ( ! empty( $value ) ) {

						$apply_tags[] = $value;

					}

					unset( $update_data[ $key ] );

				}
			}
		}

		// Filter contact ID
		$contact_id = apply_filters( 'wpf_forms_pre_submission_contact_id', $contact_id, $update_data, $user_id, $form_id );
		$contact_id = apply_filters( 'wpf_' . $integration_slug . '_pre_submission_contact_id', $contact_id, $update_data, $user_id, $form_id );

		$log_text = $integration_name . ' <a href="' . $form_edit_link . '">' . $form_title . '</a> submission.';

		if ( ! empty( $contact_id ) ) {
			$log_text .= ' Updating existing contact #' . $contact_id . ': ';
		} else {
			$log_text .= ' Creating new contact: ';
		}

		wpf_log( 'info', $user_id, $log_text, array( 'meta_array_nofilter' => $update_data, 'source' => sanitize_title( $integration_name ) ) );

		if( ! empty( $contact_id ) && isset( $add_only ) && $add_only == true ) {

			wpf_log( 'info', $user_id, 'Contact already exists and <em>Add Only</em> is enabled. Aborting.', array( 'source' => sanitize_title( $integration_name ) ) );
			return;

		}

		if ( ! empty( $contact_id ) ) {

			// Update CRM if contact ID exists
			$result = wp_fusion()->crm->update_contact( $contact_id, $update_data, false );

			do_action( 'wpf_guest_contact_updated', $contact_id, $email_address );

		} else {

			// Add contact if doesn't exist yet
			$contact_id = wp_fusion()->crm->add_contact( $update_data, false );

			if( is_wp_error( $contact_id ) ) {

				wpf_log( $contact_id->get_error_code(), $user_id, 'Error adding contact to ' . wp_fusion()->crm->name . ': ' . $contact_id->get_error_message(), array( 'source' => sanitize_title( $integration_name ) ) );

				return new WP_Error( 'error', 'Error adding contact to ' . wp_fusion()->crm->name . ': ' . $contact_id->get_error_message() );

			}

			if ( wpf_is_user_logged_in() && wp_fusion()->user->get_contact_id() == false ) {
				update_user_meta( $user_id, wp_fusion()->crm->slug . '_contact_id', $contact_id );
			}

			do_action( 'wpf_guest_contact_created', $contact_id, $email_address );

		}

		// Start auto login for guests (before tags are applied)

		if( wp_fusion()->settings->get( 'auto_login_forms', false ) == true ) {
			wp_fusion()->auto_login->start_auto_login( $contact_id );
		}

		$apply_tags = apply_filters( 'wpf_forms_apply_tags', $apply_tags, $user_id, $contact_id, $form_id );
		$apply_tags = apply_filters( 'wpf_' . $integration_slug . '_apply_tags', $apply_tags, $user_id, $contact_id, $form_id );
		$apply_tags = apply_filters( 'wpf_' . $integration_slug . '_apply_tags_' . $form_id, $apply_tags, $user_id, $contact_id, $form_id );

		// Apply tags if set
		if ( ! empty( $apply_tags ) ) {

			// Even if the user is logged in, they may have submitted the form with a different email. This makes sure the tags are applied to the right record
			if ( ! empty( $user_id ) && ! defined( 'DOING_WPF_AUTO_LOGIN' ) ) {

				$user_info = get_userdata( $user_id );

			} elseif( defined( 'DOING_WPF_AUTO_LOGIN' ) ) {

				$user_id = wpf_get_current_user_id();
				$user_email = get_user_meta( $user_id, 'user_email', true );
				$user_info = (object) array( 'user_email' => $user_email );

			} else {

				$user_info = false;

			}

			if ( is_object( $user_info ) && ( $user_info->user_email == $email_address || empty( $email_address ) ) ) {

				// If user exists locally and the email address matches, apply the tags locally as well
				wp_fusion()->user->apply_tags( $apply_tags, $user_id );

			} else {

				// Logger
				wpf_log( 'info', 0, $integration_name . ' applying tags: ', array( 'tag_array' => $apply_tags, 'source' => sanitize_title( $integration_name ) ) );

				wp_fusion()->crm->apply_tags( $apply_tags, $contact_id );

			}

		}

		do_action( 'wpf_forms_post_submission', $update_data, $user_id, $contact_id, $form_id );
		do_action( 'wpf_' . $integration_slug . '_post_submission', $update_data, $user_id, $contact_id, $form_id );
		do_action( 'wpf_' . $integration_slug . '_post_submission_' . $form_id, $update_data, $user_id, $contact_id, $form_id );

		return $contact_id;

	}

}
