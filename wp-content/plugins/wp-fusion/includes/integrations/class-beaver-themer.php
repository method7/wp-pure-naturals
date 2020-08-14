<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Beaver_Themer extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   3.33.7
	 * @return  void
	 */

	public function init() {

		$this->slug = 'beaver-themer';

		add_action( 'bb_logic_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );

		// This class loads on plugins_loaded, after bb_logic_init has fired, so we can't add_action( 'bb_logic_init' ) here

		if ( did_action( 'bb_logic_init' ) ) {
			$this->logic_init();
		}

	}


	/**
	 * Load scripts
	 *
	 * @access public
	 * @return void
	 */

	public function enqueue_scripts() {

		wp_enqueue_script( 'wpf-bb-themer', WPF_DIR_URL . 'assets/js/wpf-bb-themer.js', array( 'bb-logic-core' ), WP_FUSION_VERSION, true );

	}

	/**
	 * Initialize logic
	 *
	 * @access public
	 * @return void
	 */

	public function logic_init() {

		BB_Logic_Rules::register( array(
			'wp-fusion/user-tags' => __CLASS__ . '::evaluate_rule',
		) );

	}

	/**
	 * Evaluate the rule
	 *
	 * @access public
	 * @return bool
	 */

	public static function evaluate_rule( $rule ) {

		global $post;

		$post_id = 0;

		if ( ! empty( $post ) ) {
			$post_id = $post->ID;
		}

		if ( wp_fusion()->settings->get( 'exclude_admins' ) == true && current_user_can( 'manage_options' ) ) {

			$can_access = apply_filters( 'wpf_user_can_access', true, wpf_get_current_user_id(), $post_id );
			return $can_access;

		}

		$can_access = true;

		$user_tags = wp_fusion()->user->get_tags();

		if ( 'contains' === $rule->operator ) {

			if ( ! wpf_is_user_logged_in() || ! in_array( $rule->compare, $user_tags ) ) {
				$can_access = false;
			}

		} elseif ( 'does_not_contain' === $rule->operator ) {

			if ( in_array( $rule->compare, $user_tags ) ) {
				$can_access = false;
			}

		}

		$can_access = apply_filters( 'wpf_user_can_access', $can_access, wpf_get_current_user_id(), $post_id );

		if ( $can_access ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Register the REST route to get available tags
	 *
	 * @access public
	 * @return void
	 */

	public function register_rest_route() {

		register_rest_route(
			'wp-fusion', '/available-tags', array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => __CLASS__ . '::get_tags',
				),
			)
		);

	}

	/**
	 * Get available tags for REST endpoint
	 *
	 * @access public
	 * @return array Available Tags
	 */

	public static function get_tags() {

		$response = array();

		$available_tags = wp_fusion()->settings->get_available_tags_flat();
		
		foreach ( $available_tags as $id => $label ) {
			$response[] = array(
				'label' => $label,
				'value' => $id,
			);
		}

		return rest_ensure_response( $response );

	}


}

new WPF_Beaver_Themer();
