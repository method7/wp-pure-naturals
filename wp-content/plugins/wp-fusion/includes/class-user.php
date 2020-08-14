<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_User {


	/**
	 * WPF_User constructor.
	 */

	public function __construct() {

		// Register and profile updates
		add_action( 'user_register', array( $this, 'user_register' ), 20 ); // 20 so usermeta added by other plugins is saved
		add_action( 'profile_update', array( $this, 'profile_update' ), 10, 2 );
		add_action( 'add_user_to_blog', array( $this, 'add_user_to_blog' ) );

		// Deleted users
		add_action( 'delete_user', array( $this, 'user_delete' ) );
		add_action( 'remove_user_from_blog', array( $this, 'user_delete' ) );

		add_action( 'password_reset', array( $this, 'password_reset' ), 10, 2 );
		add_action( 'wp_login', array( $this, 'login' ), 10, 2 );

		// Roles
		add_action( 'set_user_role', array( $this, 'update_user_role' ), 10, 3 );
		add_action( 'add_user_role', array( $this, 'add_remove_user_role' ), 10, 2 );
		add_action( 'remove_user_role', array( $this, 'add_remove_user_role' ), 10, 2 );

		// User meta
		add_action( 'updated_user_meta', array( $this, 'push_user_meta_single' ), 10, 4 );
		add_action( 'added_user_meta', array( $this, 'push_user_meta_single' ), 10, 4 );

		// After-import actions
		add_action( 'wpf_user_imported', array( $this, 'return_password' ), 10, 2 );

		// Dyanmic tagging (so so other plugins have had a chance to make their field chancges)
		add_filter( 'wpf_user_update', array( $this, 'dynamic_tagging' ), 30, 2 );
		add_filter( 'wpf_user_register', array( $this, 'dynamic_tagging' ), 30, 2 );

	}

	/**
	 * Gets the current user ID, with support for auto-logged-in users
	 *
	 * @access public
	 * @return int User ID
	 */

	public function get_current_user_id() {

		if ( is_user_logged_in() ) {
			return get_current_user_id();
		}

		if ( defined( 'DOING_WPF_AUTO_LOGIN' ) ) {
			return wp_fusion()->auto_login->auto_login_user['user_id'];
		}

		return 0;

	}


	/**
	 * Checks if user is logged in, with support for auto-logged-in users
	 *
	 * @access public
	 * @return bool Logged In
	 */

	public function is_user_logged_in() {

		if ( is_user_logged_in() ) {
			return true;
		}

		if ( defined( 'DOING_WPF_AUTO_LOGIN' ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Used by create user to map post data for PHP versions less than 5.3
	 *
	 * @access public
	 * @return mixed
	 */

	public function map_user_meta( $a ) {
		return maybe_unserialize( $a[0] );
	}

	/**
	 * Triggered when a new user is added to a blog in multisite. Applies the tags for this blog
	 *
	 * @access public
	 *
	 * @param $user_id
	 *
	 * @return void
	 */

	public function add_user_to_blog( $user_id ) {

		// Don't need to do this if they've just registered

		if ( did_action( 'user_register' ) ) {
			return;
		}

		$assign_tags = wp_fusion()->settings->get( 'assign_tags' );

		if ( ! empty( $assign_tags ) ) {
			$this->apply_tags( $assign_tags, $user_id );
		}

	}


	/**
	 * Triggered when a new user is registered. Creates the user in the CRM and stores contact ID
	 *
	 * @access public
	 *
	 * @param $user_id
	 * @param array   $post_data
	 * @param bool    $force
	 *
	 * @return mixed Contact ID
	 */

	public function user_register( $user_id, $post_data = false, $force = false ) {

		remove_action( 'profile_update', array( $this, 'profile_update' ), 10, 2 );

		// Don't load tags or meta when someone registers
		remove_action( 'wp_login', array( $this, 'login' ), 10, 2 );

		do_action( 'wpf_user_register_start', $user_id, $post_data );

		// Get posted data from the registration form

		if ( false == $post_data && ! empty( $_POST ) && is_array( $_POST ) ) {
			$post_data = $_POST;
		} elseif ( empty( $post_data ) ) {
			$post_data = array();
		}

		$user_meta = $this->get_user_meta( $user_id );

		// Merge what's in the database with what was submitted on the form
		$post_data = array_merge( $user_meta, $post_data );

		// Allow outside modification of this data
		$post_data = apply_filters( 'wpf_user_register', $post_data, $user_id );

		// Allows for cancelling of registration via filter
		if ( $post_data == null || empty( $post_data['user_email'] ) ) {

			wpf_log( 'notice', $user_id, 'User registration not synced to ' . wp_fusion()->crm->name . ' because email address wasn\'t detected in the submitted data.' );
			return false;
		}

		// Check if contact already exists in CRM
		$contact_id = $this->get_contact_id( $user_id, true );

		if ( wp_fusion()->settings->get( 'create_users' ) != true && $force == false && $contact_id == false ) {

			wpf_log( 'notice', $user_id, 'User registration not synced to ' . wp_fusion()->crm->name . ' because "Create Contacts" is disabled in the WP Fusion settings. You will not be able to apply tags to this user.' );
			return false;

		}

		if ( $contact_id == false ) {

			// See if user role is elligible for being created as a contact
			$valid_roles = wp_fusion()->settings->get( 'user_roles', false );

			$valid_roles = apply_filters( 'wpf_register_valid_roles', $valid_roles, $user_id, $post_data );

			if ( is_array( $valid_roles ) && ! empty( $valid_roles[0] ) && ! in_array( $post_data['role'], $valid_roles ) && $force == false ) {

				wpf_log( 'notice', $user_id, 'User not added to ' . wp_fusion()->crm->name . ' because role <strong>' . $post_data['role'] . '</strong> isn\'t enabled for contact creation.' );

				return false;

			}

			// Logger
			wpf_log( 'info', $user_id, 'New user registration. Adding contact to ' . wp_fusion()->crm->name . ':', array( 'meta_array' => $post_data ) );

			$contact_id = wp_fusion()->crm->add_contact( $post_data );

			// Error logging
			if ( is_wp_error( $contact_id ) ) {

				wpf_log( $contact_id->get_error_code(), $user_id, 'Error adding contact to ' . wp_fusion()->crm->name . ': ' . $contact_id->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
				return false;

			}

			update_user_meta( $user_id, wp_fusion()->crm->slug . '_contact_id', $contact_id );

		} else {

			wpf_log( 'info', $user_id, 'New user registration. Updating contact ID ' . $contact_id . ' in ' . wp_fusion()->crm->name . ': ', array( 'meta_array' => $post_data ) );

			// If contact exists, update data and pull down anything new from the CRM
			$result = wp_fusion()->crm->update_contact( $contact_id, $post_data );

			if ( is_wp_error( $result ) ) {

				wpf_log( $result->get_error_code(), $user_id, 'Error updating contact: ' . $result->get_error_message() );
				return false;

			}

			$this->get_tags( $user_id, true, false );

		}

		// Assign any tags specified in the WPF settings page
		$assign_tags = wp_fusion()->settings->get( 'assign_tags' );

		if ( ! empty( $assign_tags ) ) {
			$this->apply_tags( $assign_tags, $user_id );
		}

		do_action( 'wpf_user_created', $user_id, $contact_id, $post_data );

		return $contact_id;

	}

	/**
	 * Triggered when profile updated
	 *
	 * @access public
	 * @return void
	 */

	public function profile_update( $user_id, $old_user_data ) {

		$bypass = apply_filters( 'wpf_bypass_profile_update', false, $_REQUEST );

		// This doesn't need to run twice on a page load
		remove_action( 'profile_update', array( $this, 'profile_update' ), 10, 2 );

		if ( ! empty( $_POST ) && false === $bypass ) {
			$this->push_user_meta( $user_id, $_POST );
		}

	}


	/**
	 * Triggered when a user is deleted or deletes their own account. Applies tag for tracking.
	 *
	 * @access public
	 * @return void
	 */

	public function user_delete( $user_id ) {

		// Users are removed from the main blog when added to a new site so we we'll ignore those
		if ( doing_action( 'wpmu_activate_user' ) || doing_action( 'wpmu_activate_blog' ) ) {
			return;
		}

		$tags = wp_fusion()->settings->get( 'deletion_tags', array() );

		if ( ! empty( $tags ) ) {
			$this->apply_tags( $tags, $user_id );
		}

	}


	/**
	 * Determine if a user has a contact record
	 *
	 * @access public
	 * @return bool
	 */

	public function has_contact_id( $user_id ) {

		$contact_id = get_user_meta( $user_id, wp_fusion()->crm->slug . '_contact_id', true );

		if ( ! empty( $contact_id ) ) {
			return true;
		} else {
			return false;
		}

	}


	/**
	 * Gets contact ID from user ID
	 *
	 * @access public
	 * @return int Contact ID
	 */

	public function get_contact_id( $user_id = false, $force_update = false ) {

		if ( false == $user_id ) {
			$user_id = $this->get_current_user_id();
		}

		if ( $user_id == 0 ) {
			return false;
		}

		do_action( 'wpf_get_contact_id_start', $user_id );

		$contact_id = get_user_meta( $user_id, wp_fusion()->crm->slug . '_contact_id', true );

		$user = get_user_by( 'id', $user_id );

		if ( empty( $user ) && defined( 'DOING_WPF_AUTO_LOGIN' ) ) {

			$user             = new stdClass();
			$user->user_email = get_user_meta( $user_id, 'user_email', true );

		}

		// Allow filtering the email used for lookups
		$email_address = apply_filters( 'wpf_get_contact_id_email', $user->user_email, $user_id );

		// If contact ID is already set
		if ( ( ! empty( $contact_id ) || $contact_id == false ) && $force_update == false ) {
			return apply_filters( 'wpf_contact_id', $contact_id, $email_address );
		}

		// If no user email set, don't bother with an API call
		if ( ! is_email( $email_address ) ) {
			return false;
		}

		$contact_id = wp_fusion()->crm->get_contact_id( $email_address );

		if ( is_wp_error( $contact_id ) ) {

			wpf_log( $contact_id->get_error_code(), $user_id, 'Error getting contact ID for <strong>' . $email_address . '</strong>: ' . $contact_id->get_error_message() );
			return false;

		}

		$contact_id = apply_filters( 'wpf_contact_id', $contact_id, $email_address );

		if ( $contact_id == false ) {

			// Error logging
			wpf_log( 'info', $user_id, 'No contact found in ' . wp_fusion()->crm->name . ' for <strong>' . $email_address . '</strong>' );

		}

		update_user_meta( $user_id, wp_fusion()->crm->slug . '_contact_id', $contact_id );

		do_action( 'wpf_got_contact_id', $user_id, $contact_id );

		return $contact_id;

	}

	/**
	 * Gets and saves updated user meta from the CRM
	 *
	 * @access public
	 * @return array User Meta
	 */

	public function pull_user_meta( $user_id = false ) {

		if ( false == $user_id ) {
			$user_id = $this->get_current_user_id();
		}

		$contact_id = $this->get_contact_id( $user_id );

		if ( empty( $contact_id ) ) {
			wpf_log( 'notice', $user_id, 'Error loading user meta: no contact record found.' );
			return false;
		}

		do_action( 'wpf_pre_pull_user_meta', $user_id );

		$user_meta = wp_fusion()->crm->load_contact( $contact_id );

		// Error logging
		if ( is_wp_error( $user_meta ) ) {

			wpf_log( $user_meta->get_error_code(), $user_id, 'Error loading contact user meta: ' . $user_meta->get_error_message() );
			return false;

		} elseif ( empty( $user_meta ) ) {

			wpf_log( 'notice', $user_id, 'No elligible user meta loaded' );
			return false;

		}

		$user_meta = apply_filters( 'wpf_pulled_user_meta', $user_meta, $user_id );

		// Allows for cancelling via filter
		if ( null === $user_meta ) {
			return;
		}

		// Logger
		wpf_log( 'info', $user_id, 'Loaded meta data from ' . wp_fusion()->crm->name . ':', array( 'meta_array' => $user_meta ) );

		// Don't push updates back to CRM
		remove_action( 'updated_user_meta', array( $this, 'push_user_meta_single' ), 10, 4 );
		remove_action( 'added_user_meta', array( $this, 'push_user_meta_single' ), 10, 4 );

		$this->set_user_meta( $user_id, $user_meta );

		do_action( 'wpf_user_updated', $user_id, $user_meta );

		return $user_meta;

	}

	/**
	 * Get all the available metadata from the database for a user
	 *
	 * @access public
	 * @return array User Meta
	 */

	public function get_user_meta( $user_id ) {

		$user_meta = array_map( array( $this, 'map_user_meta' ), get_user_meta( $user_id ) );
		$userdata  = get_userdata( $user_id );

		$user_meta['user_id']         = $user_id;
		$user_meta['user_login']      = $userdata->user_login;
		$user_meta['user_email']      = $userdata->user_email;
		$user_meta['user_registered'] = $userdata->user_registered;
		$user_meta['user_nicename']   = $userdata->user_nicename;
		$user_meta['user_url']        = $userdata->user_url;
		$user_meta['display_name']    = $userdata->display_name;

		if ( is_array( $userdata->roles ) ) {
			$user_meta['role'] = $userdata->roles[0];
		}

		$user_meta = apply_filters( 'wpf_get_user_meta', $user_meta, $user_id );

		return $user_meta;

	}

	/**
	 * Sets an array of meta data for the user
	 *
	 * @access public
	 * @return void
	 */

	public function set_user_meta( $user_id, $user_meta ) {

		// Don't send updates back
		remove_action( 'profile_update', array( $this, 'profile_update' ), 10, 2 );

		// Save all of it to usermeta table if doing auto login
		if ( defined( 'DOING_WPF_AUTO_LOGIN' ) ) {

			foreach ( $user_meta as $key => $value ) {

				update_user_meta( $user_id, $key, $value );

			}
		} else {

			$user = get_userdata( $user_id );

			foreach ( $user_meta as $key => $value ) {

				if ( empty( $value ) && $value != '0' && $value !== null ) {
					continue;
				}

				// Don't reset passwords for admins
				if ( $key == 'user_pass' && ! empty( $value ) && ! user_can( $user_id, 'manage_options' ) ) {

					// Only update pass if it's changed
					if ( wp_check_password( $value, $user->data->user_pass, $user_id ) == false ) {

						wpf_log( 'notice', $user_id, 'User password set to <strong>' . $value . '</strong>' );

						// Don't send it back again
						remove_action( 'password_reset', array( $this, 'password_reset' ), 10, 2 );
						wp_set_password( $value, $user_id );

					}
				} elseif ( $key == 'display_name' ) {

					wp_update_user(
						array(
							'ID'           => $user_id,
							'display_name' => $value,
						)
					);

				} elseif ( $key == 'user_email' && strtolower( $value ) != strtolower( $user->user_email ) && ! user_can( $user_id, 'manage_options' ) ) {

					// Don't change admin user email addresses, for security reasons

					wp_update_user(
						array(
							'ID'         => $user_id,
							'user_email' => $value,
						)
					);

				} elseif ( $key == 'user_registered' ) {

					// Don't override the registered date
					continue;

				} elseif ( $key == 'user_url' ) {

					wp_update_user(
						array(
							'ID'       => $user_id,
							'user_url' => $value,
						)
					);

				} elseif ( $key == 'role' && ! user_can( $user_id, 'manage_options' ) && wp_roles()->is_role( $value ) && ! in_array( $value, (array) $user->roles ) ) {

					// Don't send it back again
					remove_action( 'set_user_role', array( $this, 'update_user_role' ), 10, 3 );
					wp_update_user(
						array(
							'ID'   => $user_id,
							'role' => $value,
						)
					);

				} elseif ( $key == 'wp_capabilities' && ! user_can( $user_id, 'manage_options' ) ) {

					if ( ! is_array( $value ) ) {
						$value = explode( ',', $value );
					}

					if ( is_array( $value ) ) {

						foreach ( $value as $i => $role ) {

							if ( ! wp_roles()->is_role( $role ) ) {
								unset( $value[ $i ] );
							}
						}

						if ( ! empty( $value ) ) {
							update_user_meta( $user_id, $key, $value );
						}
					}
				} else {

					update_user_meta( $user_id, $key, $value );

				}

				do_action( 'wpf_user_meta_updated', $user_id, $key, $value );

			}
		}

	}

	/**
	 * Gets all tags currently applied to the user
	 *
	 * @access public
	 * @return array Tags applied to the user
	 */

	public function get_tags( $user_id = false, $force_update = false, $lookup_cid = true ) {

		if ( false == $user_id ) {
			$user_id = $this->get_current_user_id();
		}

		do_action( 'wpf_get_tags_start', $user_id );

		$user_tags = get_user_meta( $user_id, wp_fusion()->crm->slug . '_tags', true );

		if ( is_array( $user_tags ) && $force_update == false ) {
			return apply_filters( 'wpf_user_tags', $user_tags, $user_id );
		}

		// If no tags
		if ( empty( $user_tags ) && $force_update == false ) {
			return apply_filters( 'wpf_user_tags', array(), $user_id );
		}

		if ( empty( $user_tags ) ) {
			$user_tags = array();
		}

		// Don't get the CID again if the request came from a webhook
		if ( $lookup_cid == false ) {
			$force_update = false;
		}

		$contact_id = $this->get_contact_id( $user_id, $force_update );

		// If contact doesn't exist in CRM
		if ( $contact_id == false ) {
			return apply_filters( 'wpf_user_tags', array(), $user_id );
		}

		$tags = wp_fusion()->crm->get_tags( $contact_id );

		if ( is_wp_error( $tags ) ) {

			wpf_log( $tags->get_error_code(), $user_id, 'Failed loading tags: ' . $tags->get_error_message() );
			return apply_filters( 'wpf_user_tags', $user_tags, $user_id );

		}

		$this->set_tags( $tags, $user_id );

		return apply_filters( 'wpf_user_tags', $tags, $user_id );

	}

	/**
	 * Sets an array of tags to the DB and triggers relevant actions, does not send any API calls
	 *
	 * @access public
	 * @return void
	 */

	public function set_tags( $tags, $user_id ) {

		// Compare new tags to current tags to see what's changed

		$user_tags = get_user_meta( $user_id, wp_fusion()->crm->slug . '_tags', true );

		if ( empty( $user_tags ) ) {
			$user_tags = array();
		}

		// Tags should be stored as strings

		$tags = array_map( 'strval', $tags );

		if ( $tags == $user_tags ) {

			// Doing the action here so that automated enrollments are triggered
			do_action( 'wpf_tags_modified', $user_id, $user_tags );

			// If nothing changed
			return;

		}

		// Check if tags were added

		$tags_applied = array_diff( $tags, $user_tags );

		// Check if tags were removed

		$tags_removed = array_diff( $user_tags, $tags );

		$user_tags = (array) $tags;

		wpf_log( 'info', $user_id, __( 'Loaded tag(s)', 'wp-fusion' ) . ': ', array( 'tag_array' => $tags ) );

		// Check and see if new tags have been pulled, and if so, resync the available tags list
		if ( is_admin() ) {

			$sync_needed    = false;
			$available_tags = wp_fusion()->settings->get( 'available_tags' );

			foreach ( (array) $user_tags as $tag ) {

				if ( ! isset( $available_tags[ $tag ] ) ) {
					$sync_needed = true;
				}
			}

			if ( $sync_needed == true ) {
				wp_fusion()->crm->sync_tags();
			}
		}

		// Save it to the DB

		update_user_meta( $user_id, wp_fusion()->crm->slug . '_tags', $user_tags );

		if ( ! empty( $tags_applied ) ) {

			/**
			 * Triggers after tags are loaded for the user, contains just the new tags that were applied
			 *
			 * @param int   $user_id      ID of the user that was updated
			 * @param array $tags_applied Tags that were applied to the user
			 */

			do_action( 'wpf_tags_applied', $user_id, $tags_applied );

		}

		if ( ! empty( $tags_removed ) ) {

			/**
			 * Triggers after tags are loaded for the user, contains just the tags that no longer are present
			 *
			 * @param int   $user_id      ID of the user that was updated
			 * @param array $tags_removed Tags that were removed from the user
			 */

			do_action( 'wpf_tags_removed', $user_id, $tags_removed );

		}

		/**
		 * Triggers after tags are loaded for a user, contains all of the user's tags
		 *
		 * @param int   $user_id   ID of the user that was updated
		 * @param array $user_tags The user's CRM tags
		 */

		do_action( 'wpf_tags_modified', $user_id, $user_tags );

	}

	/**
	 * Applies an array of tags to a given user ID
	 *
	 * @access public
	 * @return bool
	 */

	public function apply_tags( $tags, $user_id = false ) {

		if ( empty( $tags ) || ! is_array( $tags ) ) {
			return;
		}

		if ( false == $user_id ) {
			$user_id = $this->get_current_user_id();
		}

		/**
		 * Triggers before tags are applied to the user
		 *
		 * @param int   $user_id ID of the user being updated
		 * @param array $tags    Tags to be applied to the user
		 */

		do_action( 'wpf_apply_tags_start', $user_id, $tags );

		/**
		 * Filters the tags to be applied to the user
		 *
		 * @param array $tags    Tags to be applied to the user
		 * @param int   $user_id ID of the user being updated
		 */

		$tags = apply_filters( 'wpf_apply_tags', $tags, $user_id );

		$contact_id = $this->get_contact_id( $user_id );

		// If no contact ID, don't try applying tags

		if ( false == $contact_id ) {

			wpf_log( 'notice', $user_id, __( 'No contact ID for user. Failed to apply tag(s)', 'wp-fusion' ) . ': ', array( 'tag_array' => $tags ) );
			return false;

		}

		$user_tags = $this->get_tags( $user_id );

		// Maybe quit early if user already has the tag
		$diff = array_diff( (array) $tags, $user_tags );

		/**
		 * By default WP Fusion will not send an API call to apply tags that a user already has. This can be overridden here
		 *
		 * @param bool $prevent_reapply_tags Whether to prevent re-applying tags
		 */

		$prevent_reapply = apply_filters( 'wpf_prevent_reapply_tags', wp_fusion()->settings->get( 'prevent_reapply', true ) );

		if ( empty( $diff ) && true == $prevent_reapply ) {
			return true;
		}

		// If we're only applying tags the user doesn't have already
		if ( true == $prevent_reapply ) {
			$tags = $diff;
		}

		// Logging
		wpf_log( 'info', $user_id, __( 'Applying tag(s)', 'wp-fusion' ) . ': ', array( 'tag_array' => $tags ) );

		$result = wp_fusion()->crm->apply_tags( $tags, $contact_id );

		if ( is_wp_error( $result ) ) {
			wpf_log( $result->get_error_code(), $user_id, 'Error while applying tags: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return false;
		}

		// Save to the database

		$user_tags = array_unique( array_merge( $user_tags, $tags ) );

		update_user_meta( $user_id, wp_fusion()->crm->slug . '_tags', $user_tags );

		/**
		 * Triggers after tags are applied to the user, contains just the tags that were applied
		 *
		 * @param int   $user_id ID of the user that was updated
		 * @param array $tags    Tags that were applied to the user
		 */

		do_action( 'wpf_tags_applied', $user_id, $tags );

		/**
		 * Triggers after tags are updated for a user, contains all of the user's tags
		 *
		 * @param int   $user_id   ID of the user that was updated
		 * @param array $user_tags The user's CRM tags
		 */

		do_action( 'wpf_tags_modified', $user_id, $user_tags );

		return true;

	}

	/**
	 * Removes an array of tags from a given user ID
	 *
	 * @access public
	 * @return bool
	 */

	public function remove_tags( $tags, $user_id = false ) {

		if ( empty( $tags ) || ! is_array( $tags ) ) {
			return;
		}

		if ( false == $user_id ) {
			$user_id = $this->get_current_user_id();
		}

		/**
		 * Triggers before tags are removed from the user
		 *
		 * @param int   $user_id ID of the user being updated
		 * @param array $tags    Tags to be removed from the user
		 */

		do_action( 'wpf_remove_tags_start', $user_id, $tags );

		/**
		 * Filters the tags to be removed from the user
		 *
		 * @param array $tags    Tags to be removed from the user
		 * @param int   $user_id ID of the user being updated
		 */

		$tags = apply_filters( 'wpf_remove_tags', $tags, $user_id );

		$contact_id = $this->get_contact_id( $user_id );

		// If no contact ID, don't try applying tags

		if ( false == $contact_id ) {

			wpf_log( 'notice', $user_id, __( 'No contact ID for user. Failed to remove tag(s)', 'wp-fusion' ) . ': ', array( 'tag_array' => $tags ) );
			return false;

		}

		$user_tags = $this->get_tags( $user_id );

		$tags = array_intersect( (array) $tags, $user_tags );

		// Maybe quit early if user doesn't have the tag anyway

		if ( empty( $tags ) ) {
			return true;
		}

		// Logging
		wpf_log( 'info', $user_id, __( 'Removing tag(s)', 'wp-fusion' ) . ': ', array( 'tag_array' => $tags ) );

		$result = wp_fusion()->crm->remove_tags( $tags, $contact_id );

		if ( is_wp_error( $result ) ) {
			wpf_log( $result->get_error_code(), $user_id, 'Error while removing tags: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return false;
		}

		// Save to the database

		$user_tags = array_unique( array_diff( $user_tags, $tags ) );

		update_user_meta( $user_id, wp_fusion()->crm->slug . '_tags', $user_tags );

		/**
		 * Triggers after tags are removed from the user, contains just the tags that were removed
		 *
		 * @param int   $user_id ID of the user that was updated
		 * @param array $tags    Tags that were removed from the user
		 */

		do_action( 'wpf_tags_removed', $user_id, $tags );

		/**
		 * Triggers after tags are updated for a user, contains all of the user's tags
		 *
		 * @param int   $user_id   ID of the user that was updated
		 * @param array $user_tags The user's CRM tags
		 */

		do_action( 'wpf_tags_modified', $user_id, $user_tags );

		return true;

	}

	/**
	 * Triggered when a password is reset
	 *
	 * @access public
	 * @return void
	 */

	public function password_reset( $user, $new_pass ) {

		$this->push_user_meta( $user->ID, array( 'user_pass' => $new_pass ) );

	}


	/**
	 * Returns generated password to CRM
	 *
	 * @access public
	 * @return void
	 */

	public function return_password( $user_id, $user_meta ) {

		$password_field = wp_fusion()->settings->get( 'return_password_field', array() );

		if ( wp_fusion()->settings->get( 'return_password' ) == true && ! empty( $password_field['crm_field'] ) ) {

			wpf_log( 'info', $user_id, 'Returning generated password <strong>' . $user_meta['user_pass'] . '</strong> to ' . wp_fusion()->crm->name );

			$contact_id = $this->get_contact_id( $user_id );
			$result     = wp_fusion()->crm->update_contact( $contact_id, array( $password_field['crm_field'] => $user_meta['user_pass'] ), false );

			if ( is_wp_error( $result ) ) {
				wpf_log( $result->get_error_code(), $user_id, 'Error while returning password: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			}

			$this->push_user_meta(
				$user_id, array(
					'user_login' => $user_meta['user_login'],
					'user_id'    => $user_id,
				)
			);

		} else {

			$this->push_user_meta(
				$user_id, array(
					'user_pass'  => $user_meta['user_pass'],
					'user_login' => $user_meta['user_login'],
					'user_id'    => $user_id,
				)
			);

		}

	}

	/**
	 * Applies dynamic tags from field values
	 *
	 * @access public
	 * @return array User Meta
	 */

	public function dynamic_tagging( $user_meta, $user_id ) {

		if ( is_array( $user_meta ) && is_array( wp_fusion()->crm->supports ) && in_array( 'add_tags', wp_fusion()->crm->supports ) ) {

			$apply_tags = array();

			foreach ( $user_meta as $key => $value ) {

				if ( empty( $value ) ) {
					continue;
				}

				$crm_field = wp_fusion()->crm_base->get_crm_field( $key );

				if ( false !== strpos( $crm_field, 'add_tag_' ) && wp_fusion()->crm_base->is_field_active( $key ) ) {

					if ( is_array( $value ) ) {
						$apply_tags = array_merge( $apply_tags, $value );
					} else {
						$apply_tags[] = $value;
					}

					unset( $user_meta[ $key ] );

				}
			}

			if ( ! empty( $apply_tags ) ) {

				$contact_id = get_user_meta( $user_id, wp_fusion()->crm->slug . '_contact_id', true );

				if ( ! empty( $contact_id ) ) {

					// User update for existing contact ID, easy
					$this->apply_tags( $apply_tags, $user_id );

				} else {

					// New user registration, harder
					add_action(
						'wpf_user_created', function( $user_id, $contact_id, $post_data ) use ( &$apply_tags ) {

							$this->apply_tags( $apply_tags, $user_id );

						}, 10, 3
					);

				}
			}
		}

		return $user_meta;

	}


	/**
	 * Triggered when user role updated
	 *
	 * @access public
	 * @return void
	 */

	public function update_user_role( $user_id, $role, $old_roles ) {

		$this->push_user_meta( $user_id, array( 'role' => $role ) );

	}

	/**
	 * Triggered when user role added or removed
	 *
	 * @access public
	 * @return void
	 */

	public function add_remove_user_role( $user_id, $role ) {

		$user = get_userdata( $user_id );

		if ( ! empty( $user->caps ) && is_array( $user->caps ) ) {

			$roles = implode( ', ', array_keys( $user->caps ) );

			$this->push_user_meta( $user_id, array( 'wp_capabilities' => $roles ) );

		}

	}

	/**
	 * Update tags on login
	 *
	 * @access public
	 * @return void
	 */

	public function login( $user_login, $user = false ) {

		if ( $user == false ) {
			$user = get_user_by( 'login', $user_login );
		}

		if ( wp_fusion()->settings->get( 'login_sync' ) == true ) {

			$cid = $this->get_contact_id( $user->ID );

			if ( ! empty( $cid ) ) {
				$this->get_tags( $user->ID, true, false );
			}
		}

		if ( wp_fusion()->settings->get( 'login_meta_sync' ) == true ) {

			$cid = $this->get_contact_id( $user->ID );

			if ( ! empty( $cid ) ) {
				$this->pull_user_meta( $user->ID );
			}
		}

	}

	/**
	 * Gets user ID from contact ID
	 *
	 * @access public
	 * @return int User ID
	 */

	public function get_user_id( $contact_id ) {

		$users = get_users(
			array(
				'meta_key'   => wp_fusion()->crm->slug . '_contact_id',
				'meta_value' => $contact_id,
				'fields'     => array( 'ID' ),
			)
		);

		if ( ! empty( $users ) ) {
			return $users[0]->ID;
		} else {
			return false;
		}

	}

	/**
	 * Checks to see if a user has a given tag
	 *
	 * @access public
	 * @return bool
	 */

	public function has_tag( $tags, $user_id = false ) {

		$user_tags = $this->get_tags( $user_id );

		// Allow overrides by admin bar
		if ( wpf_is_user_logged_in() && current_user_can( 'manage_options' ) && get_query_var( 'wpf_tag' ) ) {

			if ( get_query_var( 'wpf_tag' ) == 'unlock-all' ) {
				return true;
			}

			if ( get_query_var( 'wpf_tag' ) == 'lock-all' ) {
				return false;
			}
		}

		if ( empty( $user_tags ) ) {
			return false;
		}

		if ( ! is_array( $tags ) ) {
			$tags = array( $tags );
		}

		$tags = array_map( array( $this, 'get_tag_id' ), $tags );

		if ( ! empty( array_intersect( $tags, $user_tags ) ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Gets tag ID from tag name
	 *
	 * @access public
	 * @return int ID
	 */

	public function get_tag_id( $tag_name ) {

		$tag_name = trim( $tag_name );

		// If it's already an ID
		if ( is_numeric( $tag_name ) ) {
			return $tag_name;
		}

		$available_tags = wp_fusion()->settings->get( 'available_tags' );

		// If it's already an ID
		if ( isset( $available_tags[ $tag_name ] ) ) {
			return $tag_name;
		}

		foreach ( $available_tags as $id => $data ) {

			if ( isset( $data['label'] ) && $data['label'] == $tag_name ) {

				return $id;

			} elseif ( is_string( $data ) && trim( $data ) == $tag_name ) {

				return $id;

			}
		}

		// If no match found, and CRM supports add_tags, return the label
		if ( in_array( 'add_tags', wp_fusion()->crm->supports ) ) {
			return $tag_name;
		}

		return false;

	}

	/**
	 * Gets the display label for a given tag ID
	 *
	 * @access public
	 * @return string Label for given tag
	 */

	public function get_tag_label( $tag_id ) {

		$available_tags = wp_fusion()->settings->get( 'available_tags' );

		if ( isset( $available_tags[ $tag_id ] ) && is_array( $available_tags[ $tag_id ] ) ) {

			// CRMs with tag optgroups

			return $available_tags[ $tag_id ]['label'];

		} elseif ( isset( $available_tags[ $tag_id ] ) ) {

			// CRMs with id => label

			return $available_tags[ $tag_id ];

		} elseif ( is_array( wp_fusion()->crm->supports ) && in_array( 'add_tags', wp_fusion()->crm->supports ) ) {

			// CRMs that support add_tags

			return $tag_id;

		} elseif ( ! isset( $available_tags[ $tag_id ] ) ) {

			// Unknown tags

			return '(Unknown Tag: ' . $tag_id . ')';

		} else {

			return false;

		}

	}

	/**
	 * Triggered when any single user_meta field is updated
	 *
	 * @access public
	 * @return void
	 */

	public function push_user_meta_single( $meta_id, $object_id, $meta_key, $_meta_value ) {

		// Allow itegrations to register fields that should always sync when modified
		$watched_fields = apply_filters( 'wpf_watched_meta_fields', array() );

		// Don't even try if the field isn't enabled for sync
		if ( wp_fusion()->settings->get( 'push_all_meta' ) != true && ! in_array( $meta_key, $watched_fields ) ) {
			return;
		}

		$contact_fields = wp_fusion()->settings->get( 'contact_fields' );

		if ( empty( $contact_fields[ $meta_key ] ) || $contact_fields[ $meta_key ]['active'] != true && ! in_array( $meta_key, $watched_fields ) ) {
			return;
		}

		$this->push_user_meta( $object_id, array( $meta_key => $_meta_value ) );

	}


	/**
	 * Sends updated user meta to CRM
	 *
	 * @access public
	 * @return bool
	 */

	public function push_user_meta( $user_id, $user_meta = false ) {

		if ( wp_fusion()->settings->get( 'push' ) != true ) {
			return;
		}

		do_action( 'wpf_push_user_meta_start', $user_id, $user_meta );

		// If nothing's been supplied, get the latest from the DB

		if ( false === $user_meta ) {
			$user_meta = $this->get_user_meta( $user_id );
		}

		$user_meta = apply_filters( 'wpf_user_update', $user_meta, $user_id );

		$contact_id = $this->get_contact_id( $user_id );

		if ( empty( $user_meta ) || false == $contact_id ) {
			return;
		}

		wpf_log( 'info', $user_id, 'Pushing meta data to ' . wp_fusion()->crm->name . ': ', array( 'meta_array' => $user_meta ) );

		$result = wp_fusion()->crm->update_contact( $contact_id, $user_meta );

		if ( is_wp_error( $result ) ) {

			wpf_log( $result->get_error_code(), $user_id, 'Error while updating meta data: ' . $result->get_error_message(), array( 'source' => wp_fusion()->crm->slug ) );
			return false;

		} elseif ( false == $result ) {

			// If nothing was updated
			return false;

		}

		do_action( 'wpf_pushed_user_meta', $user_id, $contact_id, $user_meta );

		return true;

	}

	/**
	 * Imports a user
	 *
	 * @access public
	 * @return int / WP_Error User ID of newly created user
	 */

	public function import_user( $contact_id, $send_notification = false, $role = false ) {

		// First see if user already exists
		$users = get_users(
			array(
				'meta_key'   => wp_fusion()->crm->slug . '_contact_id',
				'meta_value' => $contact_id,
				'fields'     => array( 'ID' ),
			)
		);

		if ( ! empty( $users ) ) {

			$this->pull_user_meta( $users[0]->ID );
			$this->get_tags( $users[0]->ID, true, false );

			// Maybe change role (but not for admins)
			if ( ! empty( $role ) && ! user_can( $users[0]->ID, 'manage_options' ) && wp_roles()->is_role( $role ) ) {

				$user = new WP_User( $users[0]->ID );
				$user->set_role( $role );
			}

			return $users[0]->ID;

		}

		$user_meta = wp_fusion()->crm->load_contact( $contact_id );

		if ( is_wp_error( $user_meta ) ) {

			wpf_log( 'error', 0, 'Error importing contact ID ' . $contact_id . ': ' . $user_meta->get_error_message() );
			return $user_meta;

		} elseif ( empty( $user_meta['user_email'] ) ) {

			wpf_log( 'error', 0, 'No email found for imported contact ID ' . $contact_id . '.' );
			return new WP_Error( 'error', 'No email provided for imported user' );

		}

		// See if user with matching email exists
		$user = get_user_by( 'email', $user_meta['user_email'] );

		if ( is_wp_error( $user ) ) {

			wpf_log( 'error', 0, 'Error importing contact ID ' . $contact_id . ' with error: ' . $user->get_error_message() );
			return false;

		} elseif ( is_object( $user ) ) {

			$user_meta = apply_filters( 'wpf_pulled_user_meta', $user_meta, $user->ID );

			// Don't push updates back to CRM
			remove_action( 'updated_user_meta', array( $this, 'push_user_meta_single' ), 10, 4 );

			update_user_meta( $user->ID, wp_fusion()->crm->slug . '_contact_id', $contact_id );
			$this->set_user_meta( $user->ID, $user_meta );
			$this->get_tags( $user->ID, true, false );

			// Maybe change role (but not for admins)
			if ( ! empty( $role ) && ! user_can( $user->ID, 'manage_options' ) && wp_roles()->is_role( $role ) ) {

				$user = new WP_User( $user->ID );
				$user->set_role( $role );

			}

			do_action( 'wpf_user_updated', $user->ID, $user_meta );

			return $user->ID;

		}

		if ( empty( $user_meta['user_pass'] ) ) {

			// Generate a password if one hasn't been supplied
			$user_meta['user_pass']           = wp_generate_password( 12, false );
			$user_meta['generated_user_pass'] = 'true';

			// If the action got removed by another user, add it back
			if ( ! has_action( 'wpf_user_imported', array( $this, 'return_password' ) ) ) {
				add_action( 'wpf_user_imported', array( $this, 'return_password' ), 10, 2 );
			}
		} else {

			// If we're not generating a password, no need to send it back
			remove_action( 'wpf_user_imported', array( $this, 'return_password' ), 10, 2 );

		}

		// If user name is set
		if ( empty( $user_meta['user_login'] ) ) {
			$user_meta['user_login'] = $user_meta['user_email'];
		}

		if ( empty( $role ) || $role == 'administrator' || ! wp_roles()->is_role( $role ) ) {
			$user_meta['role'] = get_option( 'default_role' );
		} else {
			$user_meta['role'] = $role;
		}

		// Set contact ID
		$user_meta[ wp_fusion()->crm->slug . '_contact_id' ] = $contact_id;

		// Apply filters
		$user_meta = apply_filters( 'wpf_import_user', $user_meta, $contact_id );

		// Allows for cancelling via filter
		if ( null == $user_meta ) {
			wpf_log( 'notice', 0, 'Import of contact ID ' . $contact_id . ' aborted: no metadata found for user.' );
			return false;
		}

		// Prevent the default registration hook from running
		remove_action( 'user_register', array( $this, 'user_register' ), 20 );

		// Don't push updates back to CRM
		remove_action( 'updated_user_meta', array( $this, 'push_user_meta_single' ), 10, 4 );
		remove_action( 'added_user_meta', array( $this, 'push_user_meta_single' ), 10, 4 );
		remove_action( 'set_user_role', array( $this, 'update_user_role' ), 10, 3 );

		// Prevent mail from being sent
		if ( false == $send_notification ) {

			add_filter(
				'wp_mail', function() {
					return array(
						'to'      => '',
						'subject' => '',
						'message' => '',
					);
				}, 100
			);
		}

		// Insert user and store meta
		$user_id = wp_insert_user( $user_meta );

		if ( is_wp_error( $user_id ) ) {

			wpf_log( 'error', 0, 'Error importing contact ID ' . $contact_id . ' with error: ' . $user_id->get_error_message() );
			return false;

		}

		// Logger
		wpf_log( 'info', $user_id, 'Imported contact ID <strong>' . $contact_id . '</strong>, with meta data: ', array( 'meta_array_nofilter' => $user_meta ) );

		// Remove log data for generated pass
		unset( $user_meta['generated_user_pass'] );

		// Save any custom fields (wp insert user ignores them)
		$this->set_user_meta( $user_id, $user_meta );

		// Get tags
		$this->get_tags( $user_id, true, false );

		// Send notification. This is after loading tags and meta in case any other plugins have modified the password reset key
		if ( $send_notification == true ) {
			wp_new_user_notification( $user_id, null, 'user' );
		}

		// Denote user was imported
		do_action( 'wpf_user_imported', $user_id, $user_meta );

		return $user_id;

	}


}
