<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}



class WPF_PMP extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */

	public function init() {

		$this->slug = 'pmpro';

		// Admin settings
		add_action( 'pmpro_membership_level_after_other_settings', array( $this, 'membership_level_settings' ) );
		add_action( 'pmpro_save_membership_level', array( $this, 'save_level_settings' ) );

		add_action( 'pmpro_discount_code_after_settings', array( $this, 'discount_code_settings' ) );
		add_action( 'pmpro_save_discount_code', array( $this, 'save_discount_code_settings' ) );

		// New Order
		add_action( 'pmpro_after_checkout', array( $this, 'after_checkout' ), 10, 2 );

		// Change membership level
		add_action( 'pmpro_before_change_membership_level', array( $this, 'before_change_membership_level' ), 10, 4 );
		add_action( 'pmpro_after_change_membership_level', array( $this, 'after_change_membership_level' ), 10, 2 );

		// Cron member expiry
		add_action( 'pmpro_membership_pre_membership_expiry', array( $this, 'membership_expiry' ), 10, 2 );

		// Recurring payments
		add_action( 'pmpro_subscription_payment_failed', array( $this, 'subscription_payment_failed' ) );
		add_action( 'pmpro_subscription_payment_completed', array( $this, 'subscription_payment_completed' ) );

		if ( class_exists( 'PMPro_Approvals' ) ) {

			// Approvals support
			add_filter( 'wpf_meta_fields', array( $this, 'prepare_approval_meta_fields' ) );
			add_action( 'pmpro_before_change_membership_level', array( $this, 'sync_approval_status' ), 30, 4 );

			// Set approval from CRM
			add_filter( 'wpf_pulled_user_meta', array( $this, 'pulled_approval_meta' ), 10, 2 );

		}

		// WPF Stuff
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ), 15 );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );
		add_filter( 'wpf_user_register', array( $this, 'user_register' ), 10, 2 );
		add_filter( 'wpf_user_update', array( $this, 'user_update' ), 10, 2 );
		add_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );

		add_action( 'wpf_user_updated', array( $this, 'user_updated' ), 10, 2 );
		add_action( 'wpf_user_imported', array( $this, 'user_updated' ), 10, 2 );

		// Batch operations
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_action( 'wpf_batch_pmpro_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_pmpro', array( $this, 'batch_step' ) );

		// Meta batch operation
		add_action( 'wpf_batch_pmpro_meta_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_pmpro_meta', array( $this, 'batch_step_meta' ) );

	}


	/**
	 * Syncs custom fields for a membership level when a user is added to the level or via the batch process
	 *
	 * @access  public
	 * @return  void
	 */

	public function sync_membership_level_fields( $user_id, $membership_level ) {

		if ( ! empty( $membership_level ) ) {

			$update_data = array(
				'pmpro_status'           => $this->get_membership_level_status( $user_id, $membership_level->id ),
				'pmpro_start_date'       => date( get_option( 'date_format' ), $membership_level->startdate ),
				'pmpro_membership_level' => $membership_level->name,
			);

			if ( ! empty( $membership_level->enddate ) ) {

				// Take it out of UNIX time
				$update_data['pmpro_expiration_date'] = date( get_option( 'date_format' ), $membership_level->enddate );

			} else {

				// Never expires
				$update_data['pmpro_expiration_date'] = null;

			}

		} else {

			// No level

			$update_data = array(
				'pmpro_membership_level' => null,
				'pmpro_status'           => 'inactive',
			);

		}

		// Send the updated data
		wp_fusion()->user->push_user_meta( $user_id, $update_data );

	}

	/**
	 * Applies tags based on a user's current status in a membership level, either from being added to a level or via a batch operation
	 *
	 * @access  public
	 * @return  void
	 */

	public function apply_membership_level_tags( $user_id, $level_id, $status = false ) {

		// New level apply tags
		$settings = get_option( 'wpf_pmp_' . $level_id );

		if ( empty( $settings ) ) {
			return;
		}

		if ( false === $status ) {
			$status = $this->get_membership_level_status( $user_id, $level_id );
		}

		if ( empty( $status ) ) {
			return;
		}

		$apply_keys  = array();
		$remove_keys = array();

		if ( 'active' == $status ) {

			// Active

			$apply_keys  = array( 'apply_tags', 'tag_link' );
			$remove_keys = array( 'apply_tags_expired', 'apply_tags_cancelled', 'apply_tags_payment_failed' );

		} elseif ( 'expired' == $status ) {

			// Expired

			$apply_keys  = array( 'apply_tags_expired' );
			$remove_keys = array( 'tag_link' );

			if ( $settings['remove_tags'] == true ) {
				$remove_keys[] = 'apply_tags';
			}

		} elseif ( 'cancelled' == $status ) {

			// Cancelled

			$apply_keys  = array( 'apply_tags_cancelled' );
			$remove_keys = array( 'tag_link' );

			if ( $settings['remove_tags'] == true ) {
				$remove_keys[] = 'apply_tags';
			}

		} elseif ( 'inactive' == $status ) {

			// Inactive

			$remove_keys = array( 'tag_link' );

			if ( $settings['remove_tags'] == true ) {
				$remove_keys[] = 'apply_tags';
			}

		}

		$apply_tags  = array();
		$remove_tags = array();

		// Figure out which tags to apply and remove

		foreach ( $apply_keys as $key ) {

			if ( ! empty( $settings[ $key ] ) ) {

				$apply_tags = array_merge( $apply_tags, $settings[ $key ] );

			}

		}

		foreach ( $remove_keys as $key ) {

			if ( ! empty( $settings[ $key ] ) ) {

				$remove_tags = array_merge( $remove_tags, $settings[ $key ] );

			}

		}

		// Disable tag link function
		remove_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );

		if ( ! empty( $remove_tags ) ) {
			wp_fusion()->user->remove_tags( $remove_tags, $user_id );
		}

		if ( ! empty( $apply_tags ) ) {
			wp_fusion()->user->apply_tags( $apply_tags, $user_id );
		}

		add_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );

	}


	/**
	 * Adds options to PMP membership level settings
	 *
	 * @access  public
	 * @return  mixed
	 */

	public function membership_level_settings() {

		$edit = $_GET['edit'];
		global $wpdb;

		$level = $wpdb->get_row( "SELECT * FROM $wpdb->pmpro_membership_levels WHERE id = '$edit' LIMIT 1", OBJECT );

		$settings = array(
			'remove_tags'          => 0,
			'tag_link'             => array(),
			'apply_tags'           => array(),
			'apply_tags_expired'   => array(),
			'apply_tags_cancelled' => array(),
		);

		if ( get_option( 'wpf_pmp_' . $edit ) ) {
			$settings = array_merge( $settings, get_option( 'wpf_pmp_' . $edit ) );
		}

		?>

		<h3 class="topborder"><?php _e( 'WP Fusion Settings', 'wp-fusion' ); ?></h3>

		<span class="description"><?php printf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/membership/paid-memberships-pro/" target="_blank">', '</a>' ); ?></span>

		<table class="form-table" id="wp_fusion_tab">
			<tbody>
			<tr>
				<th scope="row" valign="top"><label><?php _e( 'Apply Tags', 'wp-fusion' ); ?>:</label></th>
				<td>
					<?php
					wpf_render_tag_multiselect(
						array(
							'setting'   => $settings['apply_tags'],
							'meta_name' => 'wpf-settings',
							'field_id'  => 'apply_tags',
							'no_dupes'  => array( 'tag_link' ),
						)
					);
					?>
					<br/>
					<small><?php printf( __( 'These tags will be applied to the customer in %s upon registering for this membership.', 'wp-fusion' ), wp_fusion()->crm->name ); ?></small>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label><?php _e( 'Remove Tags', 'wp-fusion' ); ?>:</label></th>
				<td>
					<input class="checkbox" type="checkbox" id="wpf-remove-tags" name="wpf-settings[remove_tags]"
						   value="1" <?php echo checked( $settings['remove_tags'], 1, false ); ?> />
					<label for="wpf-remove-tags"><?php _e( 'Remove original tags (above) when the membership is cancelled or expires.', 'wp-fusion' ) ?></label>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label><?php _e( 'Link with Tag', 'wp-fusion' ); ?>:</label></th>
				<td>
					<?php

						$args = array(
							'setting'     => $settings['tag_link'],
							'meta_name'   => 'wpf-settings',
							'field_id'    => 'tag_link',
							'placeholder' => 'Select a Tag',
							'limit'       => 1,
							'no_dupes'    => array( 'apply_tags' ),
						);

						wpf_render_tag_multiselect( $args );

					?>
					<br/>
					<small><?php printf( __( 'This tag will be applied in %1$s when a member is registered. Likewise, if this tag is applied to a user from within %2$s, they will be automatically enrolled in this membership. If the tag is removed they will be removed from the membership.', 'wp-fusion' ), wp_fusion()->crm->name, wp_fusion()->crm->name ) ?></small>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top"><label><?php _e( 'Apply Tags - Cancelled', 'wp-fusion' ); ?>:</label></th>
				<td>
					<?php
					wpf_render_tag_multiselect(
						array(
							'setting'   => $settings['apply_tags_cancelled'],
							'meta_name' => 'wpf-settings',
							'field_id'  => 'apply_tags_cancelled',
						)
					);
					?>
					<br/>
					<small><?php _e( 'Apply these tags when a subscription is cancelled. Happens when an admin or user cancels a subscription, or if the payment gateway has canceled the subscription due to too many failed payments (will be removed if the membership is resumed).', 'wp-fusion' ); ?></small>
				</td>
			</tr>
			<tr class="expiration_info" 
			<?php
			if ( ! pmpro_isLevelExpiring( $level ) ) {
				echo 'style="display: none;"';
			}
			?>
			>
				<th scope="row" valign="top"><label><?php _e( 'Apply Tags - Expired', 'wp-fusion' ); ?>:</label>
				</th>
				<td>
					<?php
					wpf_render_tag_multiselect(
						array(
							'setting'   => $settings['apply_tags_expired'],
							'meta_name' => 'wpf-settings',
							'field_id'  => 'apply_tags_expired',
						)
					);
					?>
					<br/>
					<small><?php _e( 'Apply these tags when a membership expires (will be removed if the membership is resumed).', 'wp-fusion' ); ?></small>
				</td>
			</tr>

			<tr class="recurring_info"
			<?php
			if ( ! pmpro_isLevelRecurring( $level ) ) {
				echo 'style="display: none;"';
			}
			?>
			>
				<th scope="row" valign="top"><label><?php _e( 'Apply Tags - Payment Failed', 'wp-fusion' ); ?>:</label></th>
				<td>
					<?php
					wpf_render_tag_multiselect(
						array(
							'setting'   => $settings['apply_tags_payment_failed'],
							'meta_name' => 'wpf-settings',
							'field_id'  => 'apply_tags_payment_failed',
						)
					);
					?>
					<br/>
					<small><?php _e( 'Apply these tags when a recurring payment fails (will be removed if a payment is made).', 'wp-fusion' ); ?></small>
				</td>
			</tr>
			</tbody>
		</table>

		<?php

	}

	/**
	 * Saves changes to membership level settings
	 *
	 * @access  public
	 * @return  void
	 */

	public function save_level_settings( $saveid ) {

		if ( isset( $_POST['wpf-settings'] ) ) {
			update_option( 'wpf_pmp_' . $saveid, $_POST['wpf-settings'] );
		} else {
			delete_option( 'wpf_pmp_' . $saveid );
		}

	}

	/**
	 * Adds options to PMP discount code settings
	 *
	 * @access  public
	 * @return  mixed
	 */

	public function discount_code_settings( $edit ) {

		$settings = get_option( 'wpf_pmp_discount_' . $edit );

		if ( empty( $settings ) ) {
			$settings = array( 'apply_tags' => array() );
		}

		?>
		<table class="form-table">
			<tr>
				<th scope="row" valign="top"><label><?php _e( 'Apply Tags', 'wp-fusion' ); ?>:</label></th>
				<td>
					<?php
					wpf_render_tag_multiselect(
						array(
							'setting'   => $settings['apply_tags'],
							'meta_name' => 'wpf-settings',
							'field_id'  => 'apply_tags',
						)
					);
?>
					<br/>
					<small>Apply the selected tags in <?php echo wp_fusion()->crm->name; ?> when the coupon is used.</small>
				</td>
			</tr>

		   </table>

		<?php

	}

	/**
	 * Saves changes to discount code settings
	 *
	 * @access  public
	 * @return  void
	 */

	public function save_discount_code_settings( $saveid ) {

		if ( isset( $_POST['wpf-settings'] ) ) {
			update_option( 'wpf_pmp_discount_' . $saveid, $_POST['wpf-settings'] );
		}

	}

	/**
	 * Triggered when new order is placed, sends purchase gateway (rest of data is collected from after_change_membership_level)
	 *
	 * @access  public
	 * @return  void
	 */

	public function after_checkout( $user_id, $order ) {

		$user_meta = array(
			'pmpro_payment_method' => $order->gateway,
		);

		wp_fusion()->user->push_user_meta( $user_id, $user_meta );

		// Discount codes
		global $discount_code_id;

		if ( ! empty( $discount_code_id ) ) {

			$settings = get_option( 'wpf_pmp_discount_' . $discount_code_id );

			if ( ! empty( $settings ) && ! empty( $settings['apply_tags'] ) ) {
				wp_fusion()->user->apply_tags( $settings['apply_tags'], $user_id );
			}
		}

	}

	/**
	 * Triggered before a user's membership level is changed. Removes tags from the previous level and maybe applies cancelled tags
	 *
	 * @access  public
	 * @return  void
	 */

	public function before_change_membership_level( $level_id, $user_id, $old_levels, $cancel_level ) {

		// Disable tag link function
		remove_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );

		// Check if this is a user profile edit page and remove actions as necessary
		global $pagenow;

		if ( $pagenow == 'profile.php' || $pagenow == 'user-edit.php' ) {
			remove_action( 'profile_update', array( wp_fusion()->admin_interfaces->user_profile, 'user_profile_update' ), 5 );
		}

		if ( ! empty( $old_levels[0] ) ) {

			$old_level = $old_levels[0];

			$old_level_settings = get_option( 'wpf_pmp_' . $old_level->ID );

			// Remove tags / perform actions on previous level
			if ( ! empty( $old_level_settings ) ) {

				wpf_log( 'info', $user_id, 'User left Paid Memberships Pro level <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $old_level->ID ) . '">' . $old_level->name . '</a>.', array( 'source' => 'pmpro' ) );

				if ( $old_level_settings['remove_tags'] == true && ! empty( $old_level_settings['apply_tags'] ) ) {
					wp_fusion()->user->remove_tags( $old_level_settings['apply_tags'], $user_id );
				}

				if ( ! empty( $old_level_settings['tag_link'] ) ) {
					wp_fusion()->user->remove_tags( $old_level_settings['tag_link'], $user_id );
				}

				if ( $level_id == 0 && ! empty( $old_level_settings['apply_tags_cancelled'] ) ) {
					wp_fusion()->user->apply_tags( $old_level_settings['apply_tags_cancelled'], $user_id );
				}
			}
		}

		add_action( 'wpf_tags_modified', array( $this, 'update_membership' ), 10, 2 );

	}

	/**
	 * Get a user's most recent membership status in a given level
	 *
	 * @access  public
	 * @return  string / null
	 */

	public function get_membership_level_status( $user_id, $level_id ) {

		global $wpdb;

		$level_status = $wpdb->get_row(
			"SELECT
				status as status
				FROM {$wpdb->pmpro_memberships_users}
				WHERE user_id = $user_id
				AND membership_id = $level_id
				ORDER BY id DESC
				LIMIT 1"
		);

		if ( ! empty( $level_status->status ) ) {
			return $level_status->status;
		} else {
			return null;
		}

	}


	/**
	 * Triggered when a user's membership level is changed. Syncs metadata and applies tags for the new level
	 *
	 * @access  public
	 * @return  void
	 */

	public function after_change_membership_level( $level_id, $user_id ) {

		// Get new level
		$membership_level = pmpro_getMembershipLevelForUser( $user_id );

		if ( ! empty( $membership_level ) ) {

			wpf_log( 'info', $user_id, 'User joined Paid Memberships Pro level <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level_id ) . '">' . $membership_level->name . '</a>.', array( 'source' => 'pmpro' ) );

		}

		// Sync custom fields
		$this->sync_membership_level_fields( $user_id, $membership_level );

		// Apply tags
		$this->apply_membership_level_tags( $user_id, $membership_level->id );

	}


	/**
	 * Triggered when a user's membership expires
	 *
	 * @access  public
	 * @return  void
	 */

	public function membership_expiry( $user_id, $level_id ) {

		// PMPro will remove their level after the expiry, so there's no need to run before_change_membership_level() again.
		// This will also prevent Cancelled tags getting applied when someone expires

		remove_action( 'pmpro_before_change_membership_level', array( $this, 'before_change_membership_level' ), 10, 4 );

		// Update level meta

		$update_data = array(
			'pmpro_status'           => 'expired',
			'pmpro_membership_level' => null,
		);

		wp_fusion()->user->push_user_meta( $user_id, $update_data );

		// Update tags

		$settings = get_option( 'wpf_pmp_' . $level_id );

		if ( ! empty( $settings ) ) {

			$pmpro_level = pmpro_getLevel( $level_id );

			wpf_log( 'info', $user_id, 'User expired from Paid Memberships Pro level <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level_id ) . '">' . $pmpro_level->name . '</a>.', array( 'source' => 'pmpro' ) );

			$this->apply_membership_level_tags( $user_id, $level_id, 'expired' );

		}

	}

	/**
	 * Triggered when a recurring subscription payment fails
	 *
	 * @access  public
	 * @return  void
	 */

	public function subscription_payment_failed( $old_order ) {

		$level = $old_order->getMembershipLevel();

		$settings = get_option( 'wpf_pmp_' . $level->id );

		if ( ! empty( $settings ) ) {

			wpf_log( 'info', $user_id, 'Payment failure for Paid Memberships Pro level <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level->id ) . '">' . $level->name . '</strong>.', array( 'source' => 'pmpro' ) );

			if ( ! empty( $settings['apply_tags_payment_failed'] ) ) {
				wp_fusion()->user->apply_tags( $settings['apply_tags_payment_failed'], $old_order->user_id );
			}

		}

	}

	/**
	 * Triggered when a recurring subscription payment succeeds, removes any payment failed tags
	 *
	 * @access  public
	 * @return  void
	 */

	public function subscription_payment_completed( $order ) {

		$level = $order->getMembershipLevel();

		$settings = get_option( 'wpf_pmp_' . $level->id );

		if ( ! empty( $settings ) ) {

			if ( ! empty( $settings['apply_tags_payment_failed'] ) ) {
				wp_fusion()->user->remove_tags( $settings['apply_tags_payment_failed'], $order->user_id );
			}

		}

	}

	/**
	 * Updates user's memberships if a linked tag is added/removed
	 *
	 * @access public
	 * @return void
	 */

	public function update_membership( $user_id, $user_tags ) {

		global $wpdb;

		$membership_levels = $wpdb->get_results(
			"
		    SELECT option_name, option_value 
		    FROM {$wpdb->prefix}options 
		    WHERE option_name 
		    LIKE 'wpf_pmp_%'
		    ",
			ARRAY_N
		);

		if ( empty( $membership_levels ) ) {
			return;
		}

		// Update role based on user tags
		foreach ( $membership_levels as $level ) {

			$level_id = str_replace( 'wpf_pmp_', '', $level[0] );
			$settings = unserialize( $level[1] );

			if ( empty( $settings['tag_link'] ) ) {
				continue;
			}

			$tag_id = $settings['tag_link'][0];

			if ( in_array( $tag_id, $user_tags ) && pmpro_hasMembershipLevel( $level_id, $user_id ) == false ) {

				// Prevent looping
				remove_action( 'pmpro_before_change_membership_level', array( $this, 'before_change_membership_level' ), 10, 4 );
				remove_action( 'pmpro_after_change_membership_level', array( $this, 'after_change_membership_level' ), 10, 2 );

				$pmpro_level = pmpro_getLevel( $level_id );

				$startdate = current_time( 'mysql' );

				if ( ! empty( $pmpro_level->expiration_number ) ) {
					$enddate = date_i18n( 'Y-m-d', strtotime( '+ ' . $pmpro_level->expiration_number . ' ' . $pmpro_level->expiration_period, current_time( 'timestamp' ) ) );
				} else {
					$enddate = 'NULL';
				}

				$level_data = array(
					'user_id'         => $user_id,
					'membership_id'   => $level_id,
					'code_id'         => 0,
					'initial_payment' => 0,
					'billing_amount'  => 0,
					'cycle_number'    => 0,
					'cycle_period'    => 0,
					'billing_limit'   => 0,
					'trial_amount'    => 0,
					'trial_limit'     => 0,
					'startdate'       => $startdate,
					'enddate'         => $enddate,
				);

				// Logger
				wpf_log( 'info', $user_id, 'Adding user to PMPro membership <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level_id ) . '">' . $pmpro_level->name . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>', array( 'source' => 'pmpro' ) );

				// Add user to level
				pmpro_changeMembershipLevel( $level_data, $user_id, $old_level_status = 'inactive', $cancel_level = null );

			} elseif ( ! in_array( $tag_id, $user_tags ) && pmpro_hasMembershipLevel( $level_id, $user_id ) == true ) {

				$pmpro_level = pmpro_getLevel( $level_id );

				// Logger
				wpf_log( 'info', $user_id, 'Removing user from PMPro membership <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level_id ) . '">' . $pmpro_level->name . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>', array( 'source' => 'pmpro' ) );

				// Remove user from level
				pmpro_cancelMembershipLevel( $level_id, $user_id, $old_level_status = 'inactive' );

			}
		}

	}

	/**
	 * Runs when meta data is loaded from the CRM. Updates the start date and expiry date if found
	 *
	 * @access public
	 * @return void
	 */

	public function user_updated( $user_id, $user_meta ) {

		if ( ! empty( $user_meta['pmpro_start_date'] ) || ! empty( $user_meta['pmpro_expiration_date'] ) ) {

			global $wpdb;
			$membership_level = pmpro_getMembershipLevelForUser( $user_id );

			if ( ! empty( $user_meta['pmpro_start_date'] ) ) {

				$start_date = strtotime( $user_meta['pmpro_start_date'] );

				if ( ! empty( $start_date ) ) {

					$start_date = date( 'Y-m-d 00:00:00', $start_date );

					$wpdb->query(
						$wpdb->prepare(
							"UPDATE $wpdb->pmpro_memberships_users SET `startdate`='%s' WHERE `id`=%d",
							array(
								$start_date,
								$membership_level->subscription_id,
							)
						)
					);

				}
			}

			if ( ! empty( $user_meta['pmpro_expiration_date'] ) ) {

				$expiration_date = strtotime( $user_meta['pmpro_expiration_date'] );

				if ( $expiration_date > time() ) {

					$expiration_date = date( 'Y-m-d 00:00:00', $expiration_date );

					$wpdb->query(
						$wpdb->prepare(
							"UPDATE $wpdb->pmpro_memberships_users SET `enddate`='%s' WHERE `id`=%d",
							array(
								$expiration_date,
								$membership_level->subscription_id,
							)
						)
					);

				}
			}
		}

	}


	/**
	 * Updates user meta after checkout
	 *
	 * @access  public
	 * @return  array Post Data
	 */

	public function user_register( $post_data, $user_id ) {

		$field_map = array(
			'bfirstname'      => 'first_name',
			'blastname'       => 'last_name',
			'bemail'          => 'user_email',
			'username'        => 'user_login',
			'password'        => 'user_pass',
			'baddress1'       => 'billing_address_1',
			'baddress2'       => 'billing_address_2',
			'bcity'           => 'billing_city',
			'bstate'          => 'billing_state',
			'bzipcode'        => 'billing_postcode',
			'bcountry'        => 'billing_country',
			'bphone'          => 'phone_number',
			'pmpro_baddress1' => 'billing_address_1',
			'pmpro_baddress2' => 'billing_address_2',
			'pmpro_bcity'     => 'billing_city',
			'pmpro_bstate'    => 'billing_state',
			'pmpro_bzipcode'  => 'billing_postcode',
			'pmpro_bcountry'  => 'billing_country',
			'pmpro_bphone'    => 'phone_number',
		);

		$post_data = $this->map_meta_fields( $post_data, $field_map );

		return $post_data;

	}

	/**
	 * Filters data when pushing user meta
	 *
	 * @access  public
	 * @return  array Post Data
	 */

	public function user_update( $post_data, $user_id ) {

		$field_map = array(
			'pmpro_bemail'    => 'user_email',
			'pmpro_baddress1' => 'billing_address_1',
			'pmpro_baddress2' => 'billing_address_2',
			'pmpro_bcity'     => 'billing_city',
			'pmpro_bstate'    => 'billing_state',
			'pmpro_bzipcode'  => 'billing_postcode',
			'pmpro_bcountry'  => 'billing_country',
			'pmpro_bphone'    => 'phone_number',
		);

		$post_data = $this->map_meta_fields( $post_data, $field_map );

		return $post_data;

	}

	/**
	 * Adds PMP field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */

	public function add_meta_field_group( $field_groups ) {

		if ( ! isset( $field_groups['pmp'] ) ) {
			$field_groups['pmp'] = array(
				'title'  => 'Paid Memberships Pro',
				'fields' => array(),
			);
		}

		return $field_groups;

	}

	/**
	 * Adds PMP meta fields to WPF contact fields list
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$meta_fields['billing_address_1'] = array(
			'label' => 'Billing Address 1',
			'type'  => 'text',
			'group' => 'pmp',
		);
		$meta_fields['billing_address_2'] = array(
			'label' => 'Billing Address 2',
			'type'  => 'text',
			'group' => 'pmp',
		);
		$meta_fields['billing_city']      = array(
			'label' => 'Billing City',
			'type'  => 'text',
			'group' => 'pmp',
		);
		$meta_fields['billing_state']     = array(
			'label' => 'Billing State',
			'type'  => 'text',
			'group' => 'pmp',
		);
		$meta_fields['billing_country']   = array(
			'label' => 'Billing Country',
			'type'  => 'text',
			'group' => 'pmp',
		);
		$meta_fields['billing_postcode']  = array(
			'label' => 'Billing Postcode',
			'type'  => 'text',
			'group' => 'pmp',
		);
		$meta_fields['phone_number']      = array(
			'label' => 'Phone Number',
			'type'  => 'text',
			'group' => 'pmp',
		);

		global $pmprorh_registration_fields;

		if ( ! empty( $pmprorh_registration_fields ) ) {

			foreach ( $pmprorh_registration_fields as $section ) {

				foreach ( $section as $field ) {

					$meta_fields[ $field->id ] = array(
						'label' => $field->label,
						'type'  => $field->type,
						'group' => 'pmp',
					);

				}
			}
		}

		$meta_fields['pmpro_membership_level'] = array(
			'label' => 'Membership Level',
			'type'  => 'text',
			'group' => 'pmp',
		);
		$meta_fields['pmpro_status']           = array(
			'label' => 'Membership Status',
			'type'  => 'text',
			'group' => 'pmp',
		);
		$meta_fields['pmpro_payment_method']   = array(
			'label' => 'Payment Method',
			'type'  => 'text',
			'group' => 'pmp',
		);
		$meta_fields['pmpro_start_date']       = array(
			'label' => 'Start Date',
			'type'  => 'date',
			'group' => 'pmp',
		);
		$meta_fields['pmpro_expiration_date']  = array(
			'label' => 'Expiration Date',
			'type'  => 'date',
			'group' => 'pmp',
		);

		return $meta_fields;

	}

	/**
	 * //
	 * // APPROVALS
	 * //
	 **/


	/**
	 * Adds PMP Approvals meta fields to WPF contact fields list
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function prepare_approval_meta_fields( $meta_fields ) {

		$meta_fields['pmpro_approval'] = array(
			'label' => 'Approval',
			'type'  => 'text',
			'group' => 'pmp',
		);

		return $meta_fields;

	}

	/**
	 * Filter user meta at registration
	 *
	 * @access  public
	 * @return  void
	 */

	public function sync_approval_status( $level_id, $user_id, $old_levels, $cancel_level ) {

		$approval_status = get_user_meta( $user_id, 'pmpro_approval_' . $level_id, true );

		if ( ! empty( $approval_status ) ) {
			wp_fusion()->user->push_user_meta( $user_id, array( 'pmpro_approval' => $approval_status['status'] ) );
		}

	}

	/**
	 * Filter user meta at registration
	 *
	 * @access  public
	 * @return  array User Meta
	 */

	public function pulled_approval_meta( $user_meta, $user_id ) {

		if ( ! empty( $user_meta['pmpro_approval'] ) ) {

			$level = pmpro_getMembershipLevelForUser( $user_id );

			if ( ! empty( $level ) ) {

				$status = get_user_meta( $user_id, 'pmpro_approval_' . $level->id, true );

				$status['status']    = $user_meta['pmpro_approval'];
				$status['timestamp'] = current_time( 'timestamp' );

				$user_meta[ 'pmpro_approval_' . $level->id ] = $status;

				unset( $user_meta['pmpro_approval'] );

			}
		}

		return $user_meta;

	}


	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds PMPro checkbox to available export options
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['pmpro'] = array(
			'label'   => __( 'Paid Memberships Pro membership statuses', 'wp-fusion' ),
			'title'   => __( 'Members', 'wp-fusion' ),
			'tooltip' => __( 'Updates tags for all members based on their current membership level status. Does not modify any metadata or create new contact records.', 'wp-fusion' ),
		);

		$options['pmpro_meta'] = array(
			'label'   => __( 'Paid Memberships Pro membership meta', 'wp-fusion' ),
			'title'   => __( 'Members', 'wp-fusion' ),
			'tooltip' => __( 'Syncs meta fields for all members, including Start Date, Expiration Date, Membership Level, and Status. Does not modify any tags or create new contact records.', 'wp-fusion' ),
		);

		return $options;

	}

	/**
	 * Counts total number of members to be processed
	 *
	 * @access public
	 * @return array Members
	 */

	public function batch_init() {

		global $wpdb;
		$members = array();

		$query = "
					SELECT u.ID FROM $wpdb->users u 
					LEFT JOIN $wpdb->pmpro_memberships_users mu ON u.ID = mu.user_id 
					LEFT JOIN $wpdb->pmpro_membership_levels m ON mu.membership_id = m.id 
					WHERE mu.membership_id > 0
					GROUP BY u.ID 
					ORDER BY u.user_registered ASC
					";

		$result = $wpdb->get_results( $query, ARRAY_A );

		if ( ! empty( $result ) ) {
			foreach ( $result as $member ) {
				$members[] = $member['ID'];
			}
		}

		wpf_log( 'info', 0, 'Beginning <strong>Paid Memberships Pro</strong> batch operation on ' . count( $members ) . ' members', array( 'source' => 'batch-process' ) );

		return $members;

	}

	/**
	 * Processes member actions in batches
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $user_id ) {

		$levels = pmpro_getMembershipLevelsForUser( $user_id, true );

		// We want to process the levels in order of oldest subscription to newest, doing each level only once

		array_reverse( $levels );

		$did = array();

		foreach ( $levels as $level ) {

			if ( in_array( $level->id, $did ) ) {
				continue;
			}

			$did[] = $level->id;

			$status = $this->get_membership_level_status( $user_id, $level->id );

			wpf_log( 'info', $user_id, 'Processing level <a href="' . admin_url( 'admin.php?page=pmpro-membershiplevels&edit=' . $level->id ) . '">' . $level->name . '</a> with status <strong>' . $status . '</strong>.', array( 'source' => 'pmpro' ) );

			$this->apply_membership_level_tags( $user_id, $level->id, $status );

		}

	}


	/**
	 * Processes member actions in batches
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step_meta( $user_id ) {

		$levels = pmpro_getMembershipLevelsForUser( $user_id, true );

		// This will return all levels but we only want to process the most recent (highest subscription ID)

		usort( $levels, function( $a, $b ) {
			return $a->subscription_id < $b->subscription_id;
		});

		$this->sync_membership_level_fields( $user_id, $levels[0] );

	}

}

new WPF_PMP();
