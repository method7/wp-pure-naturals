<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Share_Logins_Pro extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'share-logins-pro';

		add_filter( 'option_share-logins_basics', array( $this, 'register_meta_fields' ), 10, 2 );
		add_action( 'wpf_tags_applied', array( $this, 'tags_modified' ), 10, 2 );
		add_action( 'wpf_tags_removed', array( $this, 'tags_modified' ), 10, 2 );

		// Catch incoming tag changes
		add_action( 'updated_user_meta', array( $this, 'incoming_tags_modified' ), 10, 4 );
		add_action( 'added_user_meta', array( $this, 'incoming_tags_modified' ), 10, 4 );

	}


	/**
	 * Register contact ID and tags fields for automatic sync on profile update
	 *
	 * @access public
	 * @return array Option
	 */

	public function register_meta_fields( $value, $option ) {

		if ( empty( $value ) ) {
			$value = array();
		}

		if ( ! isset( $value['share-meta_keys'] ) ) {
			$value['share-meta_keys'] = array();
		}

		if ( ! in_array( wp_fusion()->crm->slug . '_contact_id', $value['share-meta_keys'] ) ) {
			$value['share-meta_keys'][] = wp_fusion()->crm->slug . '_contact_id';
			$value['share-meta_keys'][] = wp_fusion()->crm->slug . '_tags';
		}

		return $value;

	}

	/**
	 * Sync changed tags to other connected sites
	 *
	 * @access public
	 * @return void
	 */

	public function tags_modified( $user_id, $user_tags ) {

		$plugin  = codexpert\Share_Logins_Pro\Plugin::instance();
		$request = new codexpert\Share_Logins_Pro\Request( $plugin->plugin );
		$request->update_user( $user_id );

	}

	/**
	 * Trigger appropriate actions when tags are modified via incoming request
	 *
	 * @access public
	 * @return void
	 */

	public function incoming_tags_modified( $meta_id, $object_id, $meta_key, $user_tags ) {

		if ( ! defined( 'REST_REQUEST' ) || REST_REQUEST != true ) {
			return;
		}

		if ( wp_fusion()->crm->slug . '_tags' != $meta_key ) {
			return;
		}

		do_action( 'wpf_tags_modified', $object_id, $user_tags );

	}


}

new WPF_Share_Logins_Pro();
