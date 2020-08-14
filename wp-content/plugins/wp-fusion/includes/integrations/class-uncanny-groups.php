<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Uncanny_Groups extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'uncanny-groups';

		add_action( 'ulgm_group_user_invited', array( $this, 'group_user_added' ), 10, 3 );
		add_action( 'ulgm_existing_group_user_added', array( $this, 'group_user_added' ), 10, 3 );

		add_filter( 'wpf_user_register', array( $this, 'user_register' ), 10, 2 );
		add_action( 'wpf_woocommerce_panel', array( $this, 'panel_content' ) );

	}

	/**
	 * Apply tags when a user is added to a group
	 *
	 * @access  public
	 * @return  void
	 */

	public function group_user_added( $user_data, $group_id, $order_id ) {

		$product_id = uncanny_learndash_groups\SharedFunctions::get_product_id_from_group_id( $group_id );

		$license = get_post_meta( $product_id['product_id'], '_ulgm_license', true );

		$settings = get_post_meta( $license[0], 'wpf-settings-woo', true );

		if ( ! empty( $settings ) && ! empty( $settings['apply_tags_group_user_added'] ) ) {

			$user = get_user_by( 'email', $user_data['user_email'] );

			wp_fusion()->user->apply_tags( $settings['apply_tags_group_user_added'], $user->ID );

		}

	}

	/**
	 * Fix names getting added as arrays with Uncanny Groups
	 *
	 * @access  public
	 * @return  array Post data
	 */

	public function user_register( $post_data, $user_id ) {

		if ( is_array( $post_data['first_name'] ) ) {
			$post_data['first_name'] = get_user_meta( $user_id, 'first_name', true );
		}

		if ( is_array( $post_data['last_name'] ) ) {
			$post_data['last_name'] = get_user_meta( $user_id, 'last_name', true );
		}

		if ( is_array( $post_data['user_email'] ) ) {

			$user = get_userdata( $user_id );
			$post_data['user_email'] = $user->user_email;

		}

		return $post_data;

	}

	/**
	 * Output Woo settings
	 *
	 * @access  public
	 * @return  mixed Panel Content
	 */

	public function panel_content() {

		global $post;

		$settings = array(
			'apply_tags_group_user_added' => array(),
		);

		if ( get_post_meta( $post->ID, 'wpf-settings-woo', true ) ) {
			$settings = array_merge( $settings, get_post_meta( $post->ID, 'wpf-settings-woo', true ) );
		}

		echo '<div class="options_group wpf-product show_if_courses">';

		echo '<p class="form-field"><label><strong>Group License</strong></label></p>';

		echo '<p class="form-field"><label for="wpf-apply-tags-woo">' . __( 'Apply tags when a user is added to this group course', 'wp-fusion' ) . '</label>';
		wpf_render_tag_multiselect(
			array(
				'setting'   => $settings['apply_tags_group_user_added'],
				'meta_name' => 'wpf-settings-woo',
				'field_id'  => 'apply_tags_group_user_added',
			)
		);

		echo '</p>';

		echo '</div>';

	}


}

new WPF_Uncanny_Groups();
