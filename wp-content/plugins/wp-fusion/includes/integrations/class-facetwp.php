<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_FacetWP extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'facetwp';

		add_filter( 'facetwp_pre_filtered_post_ids', array( $this, 'filter_posts' ), 10, 2 );
		add_filter( 'facetwp_settings_admin', array( $this, 'admin_settings' ), 10, 2 );

	}


	/**
	 * Filters restricted posts from results
	 *
	 * @access public
	 * @return array Post IDs
	 */

	public function filter_posts( $post_ids, $class ) {

		if ( 'yes' === FWP()->helper->get_setting( 'wpf_hide_restricted', 'no' ) ) {

			foreach ( $post_ids as $i => $post_id ) {

				if ( ! wp_fusion()->access->user_can_access( $post_id ) ) {
					unset( $post_ids[$i] );
				}

			}

		}

		return $post_ids;

	}

	/**
	 * Add WPF settings to FWP admin
	 *
	 * @access public
	 * @return array Settings
	 */

	public function admin_settings( $settings, $settings_class ) {

		$settings['wp-fusion'] = [
			'label' => __( 'WP Fusion', 'wp-fusion' ),
			'fields' => [
				'wpf_hide_restricted' => [
					'label' => __( 'Exclude restricted items?', 'wp-fusion' ),
					'notes' => __( 'Any posts that the user doesn\'t have access to will be hidden from the results.', 'fwp' ),
					'html' => $settings_class->get_field_html( 'wpf_hide_restricted', 'toggle' )
				],
			],
		];

		return $settings;

	}


}

new WPF_FacetWP();
