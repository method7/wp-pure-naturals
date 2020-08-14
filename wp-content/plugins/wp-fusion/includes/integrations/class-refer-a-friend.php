<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Refer_A_Friend extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   3.30.3
	 * @return  void
	 */

	public function init() {

		$this->slug = 'refer-a-friend';

		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'set_contact_field_names' ) );
		add_filter( 'wpf_watched_meta_fields', array( $this, 'watch_meta_fields' ) );

	}

	/**
	 * Adds field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */

	public function add_meta_field_group( $field_groups ) {

		$field_groups['raf'] = array(
			'title'  => 'Refer A Friend',
			'fields' => array(),
		);

		return $field_groups;

	}

	/**
	 * Set field labels
	 *
	 * @access public
	 * @return array Settings
	 */

	public function set_contact_field_names( $meta_fields ) {

		$meta_fields['gens_referral_id'] = array(
			'label' => 'Referral ID',
			'type'  => 'text',
			'group' => 'raf',
		);

		return $meta_fields;

	}

	/**
	 * Watch Referral ID field for changes
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function watch_meta_fields( $meta_fields ) {

		$meta_fields[] = 'gens_referral_id';

		return $meta_fields;

	}


}

new WPF_Refer_A_Friend();
