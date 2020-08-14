<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Woo_Memberships extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */

	public function init() {

		$this->slug = 'woo-memberships';

		// Detect changes
		add_action( 'wc_memberships_grant_membership_access_from_purchase', array( $this, 'sync_expiration_date' ), 20, 2 ); // 20 so it runs after save_subscription_data()
		add_action( 'wc_memberships_user_membership_created', array( $this, 'membership_level_created' ), 20, 2 );
		add_action( 'wc_memberships_user_membership_saved', array( $this, 'membership_level_saved' ), 20, 2 );
		add_action( 'wc_memberships_user_membership_status_changed', array( $this, 'membership_status_changed' ), 10, 3 );
		add_action( 'wpf_tags_modified', array( $this, 'update_memberships' ), 10, 2 );

		// Add meta boxes to Woo membership level editor
		add_action( 'wc_membership_plan_data_tabs', array( $this, 'membership_plan_data_tabs' ) );
		add_action( 'wc_membership_plan_data_panels', array( $this, 'membership_write_panel' ) );

		// Saving
		add_action( 'save_post', array( $this, 'save_meta_box_data' ) );

		// Custom field stuff
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );

		// Batch operations
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_action( 'wpf_batch_woo_memberships_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_woo_memberships', array( $this, 'batch_step' ) );

	}

	/**
	 * Updates tags for a user membership based on membership status
	 *
	 * @access public
	 * @return void
	 */

	public function apply_tags_for_user_membership( $user_membership, $status = false ) {

		$settings = get_post_meta( $user_membership->plan_id, 'wpf-settings-woo', true );

		if ( empty( $settings ) ) {
			return;
		}

		if ( false === $status ) {
			$status = $user_membership->get_status();
		}

		$apply_keys  = array();
		$remove_keys = array();

		// Active vs inactive

		$active_statuses = array( 'active', 'pending', 'complimentary', 'free_trial' );

		if ( in_array( $status, $active_statuses ) ) {

			$apply_keys  = array( 'apply_tags_active', 'tag_link' );
			$remove_keys = array( 'apply_tags_expired', 'apply_tags_cancelled', 'apply_tags_paused' );

			// Only remove the pending cancel tags if the membership is actually active
			if ( 'active' === $status ) {
				$remove_keys[] = 'apply_tags_pending';
			}
		} else {

			$remove_keys = array( 'tag_link' );

			if ( true == $settings['remove_tags'] ) {
				$remove_keys[] = 'apply_tags_active';
			}
		}

		// Additional statuses (like complimentary, free trial, etc)

		$apply_keys[] = 'apply_tags_' . $status;

		$apply_tags  = array();
		$remove_tags = array();

		// Figure out which tags to apply and remove

		foreach ( $apply_keys as $key ) {

			if ( ! empty( $settings[ $key ] ) ) {

				$apply_tags = array_unique( array_merge( $apply_tags, $settings[ $key ] ) );

			}

		}

		foreach ( $remove_keys as $key ) {

			if ( ! empty( $settings[ $key ] ) ) {

				$remove_tags = array_unique( array_merge( $remove_tags, $settings[ $key ] ) );

			}

		}

		// Disable tag link function

		remove_action( 'wpf_tags_modified', array( $this, 'update_memberships' ), 10, 2 );

		if ( ! empty( $remove_tags ) ) {
			wp_fusion()->user->remove_tags( $remove_tags, $user_membership->user_id );
		}

		if ( ! empty( $apply_tags ) ) {
			wp_fusion()->user->apply_tags( $apply_tags, $user_membership->user_id );
		}

		add_action( 'wpf_tags_modified', array( $this, 'update_memberships' ), 10, 2 );

	}

	/**
	 * Sync membership expiration date when it's updated. This runs on a new purchase
	 *
	 * @access public
	 * @return void
	 */

	public function sync_expiration_date( $membership, $args ) {

		$user_membership = wc_memberships_get_user_membership( $args['user_membership_id'] );

		if( ! empty( $user_membership->get_end_date() ) ) {
			wp_fusion()->user->push_user_meta( $args['user_id'], array( 'membership_expiration' => $user_membership->get_end_date() ) );
		}

	}

	/**
	 * Apply / remove linked tags when membership level is changed. This runs on a new purchase
	 *
	 * @access public
	 * @return void
	 */

	public function membership_level_created( $membership, $args ) {

		// No need to do these things twice

		remove_action( 'wc_memberships_grant_membership_access_from_purchase', array( $this, 'sync_expiration_date' ), 20, 2 );
		remove_action( 'wc_memberships_user_membership_status_changed', array( $this, 'membership_status_changed' ), 10, 3 );
		remove_action( 'wc_memberships_user_membership_saved', array( $this, 'membership_level_created' ), 20, 2 );

		if ( empty( $membership ) ) {
			return;
		}

		$user_membership = wc_memberships_get_user_membership( $args['user_membership_id'] );

		if( empty( $user_membership ) ) {
			return;
		}

		$status = $user_membership->get_status();

		$update_data = array(
			'membership_status' => $status,
		);

		// Sync expiry date

		if ( ! empty( $user_membership->get_end_date() ) ) {
			$update_data['membership_expiration'] = $user_membership->get_end_date();
		}

		wp_fusion()->user->push_user_meta( $args['user_id'], $update_data );

		// Apply tags for the status

		wpf_log( 'info', $args['user_id'], 'WooCommerce membership <a href="' . admin_url( 'post.php?post=' . $user_membership->id . '&action=edit' ) . '" target="_blank">' . get_the_title( $membership->id ) . '</a> saved with status <strong>' . $status . '</strong>.', array( 'source' => 'woo-memberships' ) );

		$this->apply_tags_for_user_membership( $user_membership );

	}

	/**
	 * Sync expiry date when a level is saved in the admin
	 *
	 * @access public
	 * @return void
	 */

	public function membership_level_saved( $membership, $args ) {

		if ( is_admin() && doing_action( 'save_post' ) ) {

			$user_membership = wc_memberships_get_user_membership( $args['user_membership_id'] );

			if ( empty( $user_membership ) ) {
				return;
			}

			$update_data = array(
				'membership_status' => $user_membership->get_status(),
			);

			if ( ! empty( $user_membership->get_end_date() ) ) {
				$update_data['membership_expiration'] = $user_membership->get_end_date();
			}

			wp_fusion()->user->push_user_meta( $args['user_id'], $update_data );

		}

	}

	/**
	 * Apply / remove tags when membership status is changed. This runs on a status change for an existing membership but not a new purchase
	 *
	 * @access public
	 * @return void
	 */

	public function membership_status_changed( $user_membership, $old_status, $new_status ) {

		// Don't need to do this twice
		remove_action( 'wc_memberships_user_membership_saved', array( $this, 'membership_level_created' ), 20, 2 );

		wpf_log( 'info', $user_membership->user_id, 'WooCommerce membership <a href="' . admin_url( 'post.php?post=' . $user_membership->id . '&action=edit' ) . '" target="_blank">' . get_the_title( $user_membership->plan_id ) . '</a> status changed from <strong>' . $old_status . '</strong> to <strong>' . $new_status . '</strong>.', array( 'source' => 'woo-memberships' ) );

		$this->apply_tags_for_user_membership( $user_membership, $new_status );

	}

	/**
	 * Update user memberships when tags are modified
	 *
	 * @access public
	 * @return void
	 */

	public function update_memberships( $user_id, $user_tags ) {

		$linked_memberships = get_posts( array(
			'post_type'  => 'wc_membership_plan',
			'nopaging'   => true,
			'meta_query' => array(
				array(
					'key'     => 'wpf-settings-woo',
					'compare' => 'EXISTS'
				),
			),
			'fields'     => 'ids'
		) );


		if( empty( $linked_memberships ) )
			return;

		// Prevent looping
		remove_action( 'wc_memberships_user_membership_saved', array( $this, 'membership_level_created' ), 20, 2 );
		remove_action( 'wc_memberships_user_membership_status_changed', array( $this, 'membership_status_changed' ), 10, 3 );

		// Update membership access based on user tags

		foreach ( $linked_memberships as $plan_id ) {

			$settings = get_post_meta( $plan_id, 'wpf-settings-woo', true );

			if ( empty( $settings['tag_link'] ) ) {
				continue;
			}

			$tag_id = $settings['tag_link'][0];
			$user_membership = wc_memberships_get_user_membership( $user_id, $plan_id );

			if ( in_array( $tag_id, $user_tags ) && ( $user_membership == false || ! wc_memberships_is_user_active_member( $user_id, $plan_id ) ) ) {

				// Create new member if needed
				if( $user_membership == false ) {
					$user_membership = wc_memberships_create_user_membership( array( 'plan_id' => $plan_id, 'user_id' => $user_id ) );
				}

				// Logger
				wpf_log( 'info', $user_id, 'User granted WooCommerce membership <a href="' . get_edit_post_link( $plan_id ) . '" target="_blank">' . get_the_title($plan_id) . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>', array( 'source' => 'woo-memberships' ) );

				$user_membership->activate_membership();

				$user_membership->add_note( 'Membership activated by WP Fusion (linked tag "' . wp_fusion()->user->get_tag_label( $tag_id ) . '" was applied).' );

			} elseif ( ! in_array( $tag_id, $user_tags ) && wc_memberships_is_user_active_member( $user_id, $plan_id ) ) {

				// Logger
				wpf_log( 'info', $user_id, 'User removed from WooCommerce membership <a href="' . get_edit_post_link( $plan_id ) . '" target="_blank">' . get_the_title($plan_id) . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>', array( 'source' => 'woo-memberships' ) );

				$user_membership->pause_membership();

				$user_membership->add_note( 'Membership paused by WP Fusion (linked tag "' . wp_fusion()->user->get_tag_label( $tag_id ) . '" was removed).' );

			}

		}

		add_action( 'wc_memberships_user_membership_saved', array( $this, 'membership_level_created' ), 20, 2 );
		add_action( 'wc_memberships_user_membership_status_changed', array( $this, 'membership_status_changed' ), 10, 3 );

	}

	/**
	 * Adds WooCommerce Memberships field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */

	public function add_meta_field_group( $field_groups ) {

		$field_groups['woocommerce_memberships'] = array(
			'title'  => 'WooCommerce Memberships',
			'fields' => array(),
		);

		return $field_groups;

	}

	/**
	 * Adds membership meta fields to WPF contact fields list
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$meta_fields['membership_status'] = array(
			'label' => 'Membership Status',
			'type'  => 'text',
			'group' => 'woocommerce_memberships',
		);

		$meta_fields['membership_expiration'] = array(
			'label' => 'Membership Expiration Date',
			'type'  => 'date',
			'group' => 'woocommerce_memberships',
		);

		return $meta_fields;

	}

	/**
	 * Adds WP Fusion settings tab to membership config
	 *
	 * @access public
	 * @return array Tabs
	 */

	public function membership_plan_data_tabs( $tabs ) {

		$tabs['wp_fusion'] = array(
			'label'  => __( 'WP Fusion', 'wp-fusion' ),
			'target' => 'membership-plan-data-wp-fusion',
			'class'  => array('panel', 'woocomerce_options_panel')
		);

		return $tabs;

	}

	/**
	 * Displays "apply tags" field on the WPF membership plan configuration panel
	 *
	 * @access public
	 * @return mixed
	 */

	public function membership_write_panel() {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'wpf_meta_box_woo', 'wpf_meta_box_woo_nonce' );

		global $post;

		$settings = array(
			'tag_link' 					=> array(),
			'remove_tags'               => false,
			'apply_tags_active'			=> array(),
			'apply_tags_expired'		=> array(),
			'apply_tags_cancelled'		=> array(),
			'apply_tags_pending'		=> array(),
			'apply_tags_complimentary'	=> array(),
			'apply_tags_free_trial'	    => array(),
			'apply_tags_paused'			=> array(),
		);

		if ( get_post_meta( $post->ID, 'wpf-settings-woo', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf-settings-woo', true ) );
		}

		echo '<div id="membership-plan-data-wp-fusion" class="panel woocommerce_options_panel">';

			echo '<div class="options_group wpf-product">';

				if( class_exists( 'WC_Subscriptions' ) ) {

					echo '<p class="notice notice-warning" style="border-top: 1px solid #eee; margin: 15px 10px 0;">';
					echo '<strong>Heads up:</strong> It looks like WooCommerce Subscriptions is active. If you\'re selling this membership plan via a subscription, it\'s preferrable to configure tagging by editing the subscription product. Specifying tags in any of these settings may cause unexpected behavior.';
					echo '</p>';

				}

				echo '<p>' . sprintf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/membership/woocommerce-memberships/" target="_blank">', '</a>' ) . '</p>';

				echo '<p class="form-field"><label><strong>' . __( 'Automated Enrollment', 'wp-fusion' ) . '</strong></label></p>';

				echo '<p class="form-field"><label for="wpf-apply-tags-woo">' . __( 'Link with Tag', 'wp-fusion' ) . '</label>';

					$args = array(
						'setting' 		=> $settings['tag_link'],
						'meta_name'		=> 'wpf-settings-woo',
						'field_id'		=> 'tag_link',
						'placeholder'	=> 'Select Tag',
						'limit'			=> 1
					);

					wpf_render_tag_multiselect( $args );

					echo '<span class="description">' . sprintf( __( 'When this tag is applied in %s, the user will automatically be enrolled in the membership plan. Likewise, if the tag is removed, the user will be un-enrolled.', 'wp-fusion' ), wp_fusion()->crm->name ) . '</span>';

					echo '<small>' . __( '<strong>Note:</strong> This setting is only needed if you are triggering membership enrollments via your CRM or an outside system (like ThriveCart).', 'wp-fusion' ) . '</small>';

				echo '</p>';

			echo '</div>';

			echo '<div class="options_group wpf-product">';

				echo '<p class="form-field"><label><strong>' . __( 'Active Memberships', 'wp-fusion' ) . '</strong></label></p>';

				// Active

				echo '<p class="form-field">';

					echo '<label for="wpf-settings-woo-apply_tags_active">' . __( 'Apply tags', 'wp-fusion' ) . '</label>';

					$args = array(
						'setting' 		=> $settings['apply_tags_active'],
						'meta_name'		=> 'wpf-settings-woo',
						'field_id'		=> 'apply_tags_active'
					);

					wpf_render_tag_multiselect( $args );

					echo '<span class="description">' . __( 'Apply these tags when the membership is active (status either Active, Complimentary, or Free Trial).', 'wp-fusion' ) . '</span>';

				echo '</p>';

				echo '<p class="form-field"><label for="wpf-remove-tags-woo">' . __( 'Remove tags', 'wp-fusion' ) . '</label>';
				echo '<input class="checkbox" type="checkbox" id="wpf-remove-tags-woo" name="wpf-settings-woo[remove_tags]" value="1" ' . checked( $settings['remove_tags'], 1, false ) . ' />';
				echo '<span class="description">' . __( 'Remove active tags (above) when the membership is paused, expires, or is fully cancelled.', 'wp-fusion' ) . '</span>';
				echo '</p>';

			echo '</div>';

			echo '<div class="options_group wpf-product" style="margin-bottom: 20px;">';

				echo '<p class="form-field"><label><strong>' . __( 'Additional Statuses', 'wp-fusion' ) . '</strong></label></p>';

				// Complimentary

				echo '<p class="form-field">';

					echo '<label for="wpf-settings-woo-apply_tags_active">' . __( 'Complimentary', 'wp-fusion' ) . '</label>';

					$args = array(
						'setting' 		=> $settings['apply_tags_complimentary'],
						'meta_name'		=> 'wpf-settings-woo',
						'field_id'		=> 'apply_tags_complimentary'
					);

					wpf_render_tag_multiselect( $args );

					echo '<span class="description">' . __( 'Apply these tags when the membership is set to Complimentary.', 'wp-fusion' ) . '</span>';

				echo '</p>';

				// Free Trial

				echo '<p class="form-field">';

					echo '<label for="wpf-settings-woo-apply_tags_active">' . __( 'Free Trial', 'wp-fusion' ) . '</label>';

					$args = array(
						'setting' 		=> $settings['apply_tags_free_trial'],
						'meta_name'		=> 'wpf-settings-woo',
						'field_id'		=> 'apply_tags_free_trial'
					);

					wpf_render_tag_multiselect( $args );

					echo '<span class="description">' . __( 'Apply these tags when the membership is set to Free Trial.', 'wp-fusion' ) . '</span>';

				echo '</p>';

				// Paused

				echo '<p class="form-field">';

					echo '<label for="wpf-settings-woo-apply_tags_active">' . __( 'Paused', 'wp-fusion' ) . '</label>';

					$args = array(
						'setting' 		=> $settings['apply_tags_paused'],
						'meta_name'		=> 'wpf-settings-woo',
						'field_id'		=> 'apply_tags_paused'
					);

					wpf_render_tag_multiselect( $args );

					echo '<span class="description">' . __( 'Apply these tags when the membership is Paused. Will be removed if the membership is reactivated.', 'wp-fusion' ) . '</span>';

				echo '</p>';


				// Expired

				echo '<p class="form-field">';

					echo '<label for="wpf-settings-woo-apply_tags_expired">' . __( 'Expired', 'wp-fusion' ) . '</label>';

					$args = array(
						'setting' 		=> $settings['apply_tags_expired'],
						'meta_name'		=> 'wpf-settings-woo',
						'field_id'		=> 'apply_tags_expired'
					);

					wpf_render_tag_multiselect( $args );

					echo '<span class="description">' . __( 'Apply these tags when the membership expires. Will be removed if the membership is reactivated.', 'wp-fusion' ) . '</span>';

				echo '</p>';

				// Pending

				echo '<p class="form-field">';

					echo '<label for="wpf-settings-woo-apply_tags_pending">' . __( 'Pending Cancellation', 'wp-fusion' ) . '</label>';

					$args = array(
						'setting' 		=> $settings['apply_tags_pending'],
						'meta_name'		=> 'wpf-settings-woo',
						'field_id'		=> 'apply_tags_pending'
					);

					wpf_render_tag_multiselect( $args );

					echo '<span class="description">' . __( 'Apply these tags when a membership has been cancelled by the user but there is still time remaining in the membership. Will be removed if the membership is reactivated.', 'wp-fusion' ) . '</span>';

				echo '</p>';


				// Cancel

				echo '<p class="form-field">';

					echo '<label for="wpf-settings-woo-apply_tags_cancelled">' . __( 'Cancelled', 'wp-fusion' ) . '</label>';

					$args = array(
						'setting' 		=> $settings['apply_tags_cancelled'],
						'meta_name'		=> 'wpf-settings-woo',
						'field_id'		=> 'apply_tags_cancelled'
					);

					wpf_render_tag_multiselect( $args );

					echo '<span class="description">' . __( 'Apply these tags when the membership is fully cancelled. Will be removed if the membership is reactivated.', 'wp-fusion' ) . '</span>';

				echo '</p>';

			echo '</div>';

		echo '</div>';

	}

	/**
	 * Saves WPF configuration to membership
	 *
	 * @access public
	 * @return void
	 */

	public function save_meta_box_data( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_meta_box_woo_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpf_meta_box_woo_nonce'], 'wpf_meta_box_woo' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Don't update on revisions
		if ( $_POST['post_type'] != 'wc_membership_plan' ) {
			return;
		}

		if ( isset( $_POST['wpf-settings-woo'] ) ) {
			$data = $_POST['wpf-settings-woo'];
		} else {
			$data = array();
		}

		// Update the meta field in the database.
		update_post_meta( $post_id, 'wpf-settings-woo', $data );

	}


	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds Woo Memberships option to available export options
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['woo_memberships'] = array(
			'label'     => __( 'WooCommerce Memberships statuses', 'wp-fusion' ),
			'title'     => __( 'Memberships', 'wp-fusion' ),
			'tooltip'   => __( 'Updates tags for all members based on current membership status. Does not create new contact records.', 'wp-fusion' ),
		);

		return $options;

	}

	/**
	 * Counts total number of memberships to be processed
	 *
	 * @access public
	 * @return array Membership IDs
	 */

	public function batch_init() {

		$args = array(
			'numberposts' => - 1,
			'post_type'   => 'wc_user_membership',
			'post_status' => 'any',
			'fields'      => 'ids',
			'order'       => 'ASC',
		);

		$memberships = get_posts( $args );

		wpf_log( 'info', 0, 'Beginning <strong>WooCommerce Membership Statuses</strong> batch operation on ' . count($memberships) . ' memberships', array( 'source' => 'batch-process' ) );

		return $memberships;

	}

	/**
	 * Processes subscription actions in batches
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $user_membership_id ) {

		$user_membership = wc_memberships_get_user_membership( $user_membership_id );

		$status = $user_membership->get_status();

		wpf_log( 'info', $user_membership->user_id, 'Processing user membership <a href="' . admin_url( 'post.php?post=' . $user_membership_id . '&action=edit' ) . '">#' . $user_membership_id . '</a> with status <strong>' . $status . '</strong>.' );

		$this->apply_tags_for_user_membership( $user_membership );

	}


}

new WPF_Woo_Memberships;
