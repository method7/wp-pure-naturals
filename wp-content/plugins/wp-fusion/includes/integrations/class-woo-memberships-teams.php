<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Woo_Memberships_Teams extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */

	public function init() {

		$this->slug = 'woo-memberships';

		add_action( 'wc_memberships_for_teams_add_team_member', array( $this, 'add_team_member' ), 10, 3 );
		add_action( 'wc_memberships_for_teams_after_remove_team_member', array( $this, 'after_remove_team_member' ), 10, 3 );

		add_action( 'updated_user_meta', array( $this, 'sync_teams_role' ), 10, 4 );
		add_action( 'added_user_meta', array( $this, 'sync_teams_role' ), 10, 4 );

		add_action( 'wpf_woocommerce_panel', array( $this, 'panel_content' ) );

		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 20 );

	}


	/**
	 * Runs when a team member accepts an invite and registers an account
	 *
	 * @access public
	 * @return void
	 */

	public function add_team_member( $member, $team, $user_membership ) {

		$product = $team->get_product();

		if ( empty( $product ) ) {
			return;
		}

		$product_id = $product->get_id();

		$parent_id = $product->get_parent_id();

		if ( ! empty( $parent_id ) ) {
			$product_id = $parent_id;
		}

		$settings = get_post_meta( $product_id, 'wpf-settings-woo', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_members'] ) ) {

			wp_fusion()->user->apply_tags( $settings['apply_tags_members'], $member->get_id() );

		}

		// Sync name

		wp_fusion()->user->push_user_meta( $member->get_id(), array( 'wc_memberships_for_teams_team_name' => $team->get_name() ) );

	}

	/**
	 * Runs when a team member accepts an invite and registers an account
	 *
	 * @access public
	 * @return void
	 */

	public function after_remove_team_member( $user_id, $team ) {

		$product = $team->get_product();

		if ( empty( $product ) ) {
			return;
		}

		$product_id = $product->get_id();

		$parent_id = $product->get_parent_id();

		if ( ! empty( $parent_id ) ) {
			$product_id = $parent_id;
		}

		$settings = get_post_meta( $product_id, 'wpf-settings-woo', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_members'] ) && ! empty( $settings['remove_tags_members'] ) ) {

			wp_fusion()->user->remove_tags( $settings['apply_tags_members'], $user_id );

		}

	}


	/**
	 * Sync changes to teams roles
	 *
	 * @access public
	 * @return void
	 */

	public function sync_teams_role( $meta_id, $user_id, $meta_key, $value ) {

		if ( strpos( $meta_key, '_wc_memberships_for_teams_team_' ) !== false && strpos( $meta_key, '_role' ) !== false ) {

			wp_fusion()->user->push_user_meta( $user_id, array( 'wc_memberships_for_teams_team_role' => $value ) );

		}

	}


	/**
	 * Writes subscriptions options to WPF/Woo panel
	 *
	 * @access public
	 * @return mixed
	 */

	public function panel_content( $post_id ) {

		$settings = array(
			'apply_tags_members'  => array(),
			'remove_tags_members' => false,
		);

		if ( get_post_meta( $post_id, 'wpf-settings-woo', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post_id, 'wpf-settings-woo', true ) );
		}

		echo '<div class="options_group wpf-product js-wc-memberships-for-teams-show-if-has-team-membership hidden">';

		echo '<p class="form-field"><label><strong>Team Membership</strong></label></p>';

		echo '<p class="form-field"><label>' . __( 'Apply tags to team members', 'wp-fusion' );
		echo ' <span class="dashicons dashicons-editor-help wpf-tip bottom" data-tip="' . __( 'These tags will be applied to users when they are added as members to the team, and accept the invite.', 'wp-fusion' ) . '"></span>';
		echo '</label>';

		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['apply_tags_members'],
				'meta_name' => 'wpf-settings-woo',
				'field_id'  => 'apply_tags_members',
			)
		);

		echo '</p>';

		echo '<p class="form-field"><label for="wpf-remove-tags-members">' . __( 'Remove tags', 'wp-fusion' ) . '</label>';
		echo '<input class="checkbox" type="checkbox" id="wpf-remove-tags-members" name="wpf-settings-woo[remove_tags_members]" value="1" ' . checked( $settings['remove_tags_members'], 1, false ) . ' />';
		echo '<span class="description">' . __( 'Remove original tags (above) when members are removed from the team.', 'wp-fusion' ) . '</span>';
		echo '</p>';

		echo '</div>';

	}

	/**
	 * Sets field labels and types for WooCommerce custom fields
	 *
	 * @access  public
	 * @return  array Meta fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$meta_fields['wc_memberships_for_teams_team_role'] = array(
			'label' => 'Memberships for Teams Role',
			'type'  => 'text',
			'group' => 'woocommerce',
		);

		$meta_fields['wc_memberships_for_teams_team_name'] = array(
			'label' => 'Memberships for Teams Team Name',
			'type'  => 'text',
			'group' => 'woocommerce',
		);

		return $meta_fields;

	}


}

new WPF_Woo_Memberships_Teams();
