<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_GTranslate extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'gtranslate';

		add_filter( 'wpf_api_add_contact_args', array( $this, 'merge_language_code' ) );
		add_filter( 'wpf_api_update_contact_args', array( $this, 'merge_language_code' ) );

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

		if ( isset( $_COOKIE['googtrans'] ) ) {

			$selected = explode( '/', $_COOKIE['googtrans'] );

			if ( is_array( $selected ) && ! empty( $selected[2] ) ) {

				$language_code = $selected[2];

			}
		} elseif ( isset( $_SERVER['HTTP_X_GT_LANG'] ) ) {

			$language_code = $_SERVER['HTTP_X_GT_LANG'];

		} else {

			$data = get_option( 'GTranslate' );

			$language_code = $data['default_language'];

		}

		if ( is_array( $args[0] ) ) {

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
	 * Add Language Code field to contact fields list
	 *
	 * @access  public
	 * @return  array Field Groups
	 */

	public function add_meta_field_group( $field_groups ) {

		if ( ! isset( $field_groups['gtranslate'] ) ) {
			$field_groups['gtranslate'] = array(
				'title'  => 'GTranslate',
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
			'group' => 'gtranslate',
		);

		return $meta_fields;

	}


}

new WPF_GTranslate();
