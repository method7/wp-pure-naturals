<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Weglot extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'weglot';

		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ), 10 );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 10 );
		add_action( 'init', array( $this, 'sync_language' ) );

	}

	/**
	 * Add Language Code field to contact fields list
	 *
	 * @access  public
	 * @since   1.0
	 * @return  array Field Groups
	 */

	public function add_meta_field_group( $field_groups ) {

		if( !isset( $field_groups['weglot'] ) ) {
			$field_groups['weglot'] = array( 'title' => 'Weglot', 'fields' => array() );
		}

		return $field_groups;

	}

	/**
	 * Adds WPML meta fields to WPF contact fields list
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$meta_fields['language_code'] = array( 'label' => 'Language Code', 'type' => 'text', 'group' => 'weglot' );

		return $meta_fields;

	}

	/**
	 * Detects current language and syncs if necessary
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function sync_language() {

		if( ! wpf_is_user_logged_in() ) {
			return;
		}

		$language_code = get_user_meta( wpf_get_current_user_id(), 'language_code', true );
		$current_language_code = weglot_get_current_language();

		if( $language_code != $current_language_code  ) {

			update_user_meta( wpf_get_current_user_id(), 'language_code', $current_language_code );

			wp_fusion()->user->push_user_meta( wpf_get_current_user_id(), array( 'language_code' => $current_language_code ) );

		}

	}

}

new WPF_Weglot;