<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_GamiPress extends WPF_Integrations_Base {


	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'gamipress';

		// Add meta field group
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ), 10 );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 20 );

		// Achievement tagging
		add_action( 'gamipress_award_achievement', array( $this, 'user_complete_achievement' ), 10, 5 );
		add_action( 'gamipress_revoke_achievement_to_user', array( $this, 'user_revoke_achievement' ), 10, 3 );

		// Rank tagging
		add_action( 'gamipress_update_user_rank', array( $this, 'update_user_rank' ), 10, 5 );

		// Points
		add_action( 'gamipress_update_user_points', array( $this, 'update_user_points' ), 10, 8 );

		add_action( 'save_post', array( $this, 'save_multiselect_data' ), 20, 2 );

		// Settings
		add_filter( 'gamipress_achievement_data_fields', array( $this, 'achievement_fields' ) );
		add_filter( 'gamipress_rank_data_fields', array( $this, 'rank_fields' ) );
		add_action( 'cmb2_render_multiselect', array( $this, 'cmb2_render_multiselect'), 10, 5 );

		// Assign / remove linked achievements
		add_action( 'wpf_tags_modified', array( $this, 'update_linked_achievements' ), 10, 2 );

	}


	/**
	 * Adds field group for BadgeOS to contact fields list
	 *
	 * @access  public
	 * @return  array Meta fields
	 */

	public function add_meta_field_group( $field_groups ) {

		if( !isset( $field_groups['gamipress'] ) ) {
			$field_groups['gamipress'] = array( 'title' => 'Gamipress', 'fields' => array() );
		}

		return $field_groups;

	}

	/**
	 * Sets field labels and types for EDD custom fields
	 *
	 * @access  public
	 * @return  array Meta fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$meta_fields['_gamipress_points'] = array( 'label' => 'Default Points', 'type' => 'integer', 'group' => 'gamipress' );

		$points_types = gamipress_get_points_types();

		foreach( $points_types as $slug => $type ) {

			$meta_fields[ '_gamipress_' . $slug . '_points' ] = array( 'label' => $type['plural_name'], 'type' => 'integer', 'group' => 'gamipress' );

		}

		return $meta_fields;

	}

	/**
	 * Applies tags when a GamiPress achievement is attained
	 *
	 * @access public
	 * @return void
	 */

	public function user_complete_achievement( $user_id, $achievement_id, $trigger, $site_id, $args ) {

		$settings = get_post_meta( $achievement_id, 'wpf_settings_gamipress', true );

		if( empty($settings) ) {
			return;
		}

		remove_action( 'wpf_tags_modified', array( $this, 'update_linked_achievements' ), 10, 2 );

		if ( ! empty( $settings['wpf_apply_tags'] ) ) {
			wp_fusion()->user->apply_tags( $settings['wpf_apply_tags'], $user_id );
		}

		if ( ! empty( $settings['wpf_tag_link'] ) ) {
			wp_fusion()->user->apply_tags( $settings['wpf_tag_link'], $user_id );
		}

		add_action( 'wpf_tags_modified', array( $this, 'update_linked_achievements' ), 10, 2 );

	}

	/**
	 * Remove tags when a GamiPress achievement is revoked
	 *
	 * @access public
	 * @return void
	 */

	public function user_revoke_achievement( $user_id, $achievement_id, $earning_id ) {

		$settings = get_post_meta( $achievement_id, 'wpf_settings_gamipress', true );

		if( ! empty( $settings ) && ! empty( $settings['wpf_tag_link'] ) ) {
			wp_fusion()->user->remove_tags( $settings['wpf_tag_link'], $user_id );
		}

	}

	/**
	 * Applies tags when a GamiPress rank is attained
	 *
	 * @access public
	 * @return void
	 */

	public function update_user_rank( $user_id, $new_rank, $old_rank, $admin_id, $achievement_id ) {

		$settings = get_post_meta( $new_rank->ID, 'wpf_settings_gamipress', true );

		if ( ! empty( $settings ) && ! empty( $settings['wpf_apply_tags'] ) ) {
			wp_fusion()->user->apply_tags( $settings['wpf_apply_tags'], $user_id );
		}

	}

	/**
	 * Update points when points updated
	 *
	 * @access public
	 * @return void
	 */

	public function update_user_points( $user_id, $new_points, $total_points, $admin_id, $achievement_id, $points_type, $reason, $log_type ) {

		if( empty( $points_type ) ) {
			$key = '_gamipress_points';
		} else {
			$key = '_gamipress_' . $points_type . '_points';
		}

		wp_fusion()->user->push_user_meta( $user_id, array( $key => $total_points ) );

	}


	/**
	 * Update's user achievements when tags are modified
	 *
	 * @access public
	 * @return void
	 */

	public function update_linked_achievements( $user_id, $user_tags ) {

		$linked_achievements = get_posts( array(
			'post_type'  => gamipress_get_achievement_types_slugs(),
			'nopaging'   => true,
			'meta_query' => array(
				array(
					'key'     => 'wpf_settings_gamipress',
					'compare' => 'EXISTS'
				),
			),
			'fields'	=> 'ids'
		) );

		if ( empty( $linked_achievements ) ) {
			return;
		}

		// Prevent looping when the achievements assigned / removed

		remove_action( 'gamipress_award_achievement', array( $this, 'user_complete_achievement' ), 10, 5 );
		remove_action( 'gamipress_revoke_achievement_to_user', array( $this, 'user_revoke_achievement' ), 10, 3 );

		// Assign / revoke linked achievements 

		foreach ( $linked_achievements as $achievement_id ) {

			$settings = get_post_meta( $achievement_id, 'wpf_settings_gamipress', true );

			if ( empty( $settings ) || empty( $settings['wpf_tag_link'] ) ) {
				continue;
			}

			$tag_id = $settings['wpf_tag_link'][0];

			$earned = gamipress_get_user_achievements( array( 'user_id' => absint( $user_id ), 'achievement_id' => absint( $achievement_id ) ) );

			if ( in_array( $tag_id, $user_tags ) && empty( $earned ) ) {

				// Logger
				wpf_log( 'info', $user_id, 'User granted Gamipress achivement <a href="' . get_edit_post_link( $achievement_id, '' ) . '" target="_blank">' . get_the_title( $achievement_id ) . '</a> by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>', array( 'source' => 'gamipress' ) );

				gamipress_award_achievement_to_user( $achievement_id, $user_id );

			} elseif( ! in_array( $tag_id, $user_tags ) && ! empty( $earned ) ) {

				// Logger
				wpf_log( 'info', $user_id, 'Gamipress achievement <a href="' . get_edit_post_link( $achievement_id, '' ) . '" target="_blank">' . get_the_title( $achievement_id ) . '</a> revoked by tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>', array( 'source' => 'gamipress' ) );

				gamipress_revoke_achievement_to_user( $achievement_id, $user_id );

			}

		}

		add_action( 'gamipress_award_achievement', array( $this, 'user_complete_achievement' ), 10, 5 );
		add_action( 'gamipress_revoke_achievement_to_user', array( $this, 'user_revoke_achievement' ), 10, 3 );

	}


	/**
	 * Renders multiselector
	 *
	 * @access public
	 * @return void
	 */

	public function cmb2_render_multiselect( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {

		wp_nonce_field( 'wpf_multiselect_gamipress', 'wpf_multiselect_gamipress_nonce' );

		$settings = array(
			'wpf_apply_tags' 	=> array(),
			'wpf_tag_link'		=> array()
		);

		if ( get_post_meta( $object_id, 'wpf_settings_gamipress', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $object_id, 'wpf_settings_gamipress', true ) );
		}

		$args = array(
			'setting' 		=> $settings[ $field->args['id'] ],
			'meta_name'		=> 'wpf_settings_gamipress',
			'field_id'		=> $field->args['id']
		);

		if( $field->args['id'] == 'wpf_tag_link' ) {
			$args['limit'] = 1;
			$args['placeholder'] = 'Select a tag';
		}

		wpf_render_tag_multiselect( $args );

		echo '<p class="cmb2-metabox-description">' . $field->args['desc'] . '</p>';

	}

	/**
	 * Add custom achievement fields
	 *
	 * @access public
	 * @return array Fields
	 */

	public function achievement_fields( $fields ) {

		$fields['wpf_apply_tags'] = array(
			'name'		    => __( 'Apply tags', 'gamipress' ),
			'desc' 			=> sprintf( __( 'These tags will be applied in %s when the achievement is earned.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'type' 			=> 'multiselect'
		);

		$fields['wpf_tag_link'] = array(
			'name'		    => __( 'Link with Tag', 'gamipress' ),
			'desc' 			=> sprintf( __( 'This tag will be applied when the achievement is earned. Likewise, if this tag is applied in %s the achievement will be automatically granted. If this tag is removed, the achievement will be revoked.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'desc' 			=> '',
			'type' 			=> 'multiselect'
		);

		return $fields;

	}

	/**
	 * Add custom rank fields
	 *
	 * @access public
	 * @return array Fields
	 */

	public function rank_fields( $fields ) {

		$fields['wpf_apply_tags'] = array(
			'name' => __( 'Apply tags', 'wp-fusion' ),
			'desc' => sprintf( __( 'These tags will be applied in %s when the rank is earned.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'type' => 'multiselect'
		);

		return $fields;

	}

	/**
	 * Runs when WPF multiselector is saved
	 *
	 * @access public
	 * @return void
	 */

	public function save_multiselect_data( $post_id ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_multiselect_gamipress_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpf_multiselect_gamipress_nonce'], 'wpf_multiselect_gamipress' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Don't update on revisions
		if ( $_POST['post_type'] == 'revision' ) {
			return;
		}


		if ( isset( $_POST['wpf_settings_gamipress'] ) ) {
			$data = $_POST['wpf_settings_gamipress'];
		} else {
			$data = array();
		}

		// Update the meta field in the database.
		update_post_meta( $post_id, 'wpf_settings_gamipress', $data );

	}


}

new WPF_GamiPress;