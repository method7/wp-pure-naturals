<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// NB: TranslatePress suppresses error_log() calls - function trp_debug_mode_off()

class WPF_TranslatePress extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'translatepress';

		add_filter( 'wpf_api_add_contact_args', array( $this, 'merge_language_code' ) );
		add_filter( 'wpf_api_update_contact_args', array( $this, 'merge_language_code' ) );

		add_filter( 'wpf_user_update', array( $this, 'user_update' ), 10, 2 );
		add_filter( 'wpf_pulled_user_meta', array( $this, 'pulled_user_meta' ), 10, 2 );

		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ), 10 );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 10 );

	}


	/**
	 * Filters registration / update data before sending to the CRM
	 *
	 * @access public
	 * @return array Args
	 */

	public function merge_language_code( $args ) {

		$language_code = false;

		$contact_fields = wp_fusion()->settings->get( 'contact_fields' );

		if ( empty( $contact_fields['language_code'] ) || $contact_fields['language_code']['active'] != true || empty( $contact_fields['language_code']['crm_field'] ) ) {
			return $args;
		}

		$crm_field = $contact_fields['language_code']['crm_field'];

		if ( isset( $_COOKIE['trp_language'] ) ) {

			$language_code = $_COOKIE['trp_language'];

		} elseif ( isset( $_COOKIE['lang'] ) ) {

			$language_code = $_COOKIE['lang'];

		} elseif ( isset( $_SERVER['HTTP_X_GT_LANG'] ) ) {

			$language_code = $_SERVER['HTTP_X_GT_LANG'];

		} else {

			$data = get_option( 'trp_settings' );

			$language_code = $data['default-language'];

		}

		if ( is_array( $args[0] ) ) {

			wpf_log( 'info', 0, 'Creating contact with language code <strong>' . $language_code . '</strong>' );

			// Add contact
			if ( ! isset( $args[1] ) || $args[1] == true ) {

				$args[0]['language_code'] = $language_code;

			} else {

				$args[0][ $crm_field ] = $language_code;

			}
		} else {

			// Update contact
			if ( ! isset( $args[2] ) || $args[2] == true ) {

				$args[1]['language_code'] = $language_code;

			} else {

				$args[1][ $crm_field ] = $language_code;

			}
		}

		return $args;

	}

	/**
	 * Sync locale field to language_code field
	 *
	 * @access public
	 * @return array User Meta
	 */

	public function user_update( $user_meta, $user_id ) {

		if ( ! empty( $user_meta['locale'] ) ) {
			$user_meta['language_code'] = $user_meta['locale'];
		}

		return $user_meta;

	}

	/**
	 * Load the custom field from the CRM into usermeta
	 *
	 * @access public
	 * @return array User Meta
	 */

	public function pulled_user_meta( $user_meta, $user_id ) {

		if ( isset( $user_meta['language_code'] ) ) {
			$user_meta['locale'] = $user_meta['language_code'];
			unset( $user_meta['language_code'] );
		}

		return $user_meta;

	}

	/**
	 * Add Language Code field to contact fields list
	 *
	 * @access  public
	 * @return  array Field Groups
	 */

	public function add_meta_field_group( $field_groups ) {

		if ( ! isset( $field_groups['translatepress'] ) ) {
			$field_groups['translatepress'] = array(
				'title'  => 'TranslatePress',
				'fields' => array(),
			);
		}

		return $field_groups;

	}

	/**
	 * Adds meta fields to WPF contact fields list
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$meta_fields['language_code'] = array(
			'label' => 'Language Code',
			'type'  => 'text',
			'group' => 'translatepress',
		);

		return $meta_fields;

	}


}

new WPF_TranslatePress();
