<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Ninja_Forms extends NF_Abstracts_Action {

	/**
	 * @var string
	 */
	public $slug = 'ninja-forms';

	/**
	 * @var string
	 */
	protected $_name = 'wpfusion';

	/**
	 * @var array
	 */
	protected $_tags = array();

	/**
	 * @var string
	 */
	protected $_timing = 'late';

	/**
	 * @var int
	 */
	protected $_priority = 10;


	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function __construct() {

		wp_fusion()->integrations->{'ninja-forms'} = $this;

		parent::__construct();

		$this->_nicename = 'WP Fusion';

		$settings = $this->get_settings();

		$this->_settings = array_merge( $this->_settings, $settings );

	}


	/**
	 * Add admin action settings
	 *
	 * @access public
	 * @return array Settings
	 */

	public function get_settings() {

		$settings = array();

		$settings['apply_tags'] = array(
			'name'        => 'apply_tags',
			'type'        => 'textbox',
			'group'       => 'primary',
			'label'       => __( 'Apply Tags', 'wp-fusion' ),
			'width'       => 'full',
			'placeholder' => __( 'Comma-separated list of tag names or IDs', 'wp-fusion' ),
		);

		$settings['wpfmessage'] = array(
			'name'           => 'wpfmessage',
			'type'           => 'html',
			'group'          => 'primary',
			'value'          => sprintf( __( 'Use Ninja Forms merge fields below to configure what data is sent to %s.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'width'          => 'full',
			'use_merge_tags' => true,
		);

		$fields_merged    = array();
		$available_fields = wp_fusion()->settings->get( 'crm_fields', array() );

		if ( isset( $available_fields['Standard Fields'] ) ) {

			$fields_merged = array_merge( $available_fields['Standard Fields'], $available_fields['Custom Fields'] );

		} else {

			$fields_merged = $available_fields;

		}

		asort( $fields_merged );

		foreach ( $fields_merged as $key => $label ) {

			$settings[ 'field_' . $key ] = array(
				'name'           => 'field_' . $key,
				'type'           => 'textbox',
				'group'          => 'primary',
				'label'          => $label,
				'placeholder'    => '',
				'value'          => '',
				'width'          => 'full',
				'use_merge_tags' => true,
			);

		}

		return $settings;

	}

	/**
	 * Save
	 *
	 * @access  public
	 * @return  void
	 */

	public function save( $action_settings ) {

	}

	/**
	 * Process form sumbission
	 *
	 * @access  public
	 * @return  void
	 */

	public function process( $action_settings, $form_id, $data ) {

		$email_address = false;
		$update_data   = array();

		foreach ( $data['fields'] as $field ) {

			if ( $field['settings']['type'] == 'email' && is_email( $field['value'] ) ) {
				$email_address = $field['value'];
			}
		}

		foreach ( $action_settings as $key => $setting ) {

			if ( strpos( $key, 'field_' ) === false || ( empty( $setting ) && null !== $setting ) ) {
				continue;
			}

			$field_key = str_replace( 'field_', '', $key );

			$update_data[ $field_key ] = $setting;

		}

		$apply_tags = array();

		if ( ! empty( $action_settings['apply_tags'] ) ) {

			$tags_exploded = explode( ',', $action_settings['apply_tags'] );

			foreach ( $tags_exploded as $tag ) {
				$apply_tags[] = wp_fusion()->user->get_tag_id( $tag );
			}
		}

		$args = array(
			'email_address'    => $email_address,
			'update_data'      => $update_data,
			'apply_tags'       => $apply_tags,
			'integration_slug' => 'ninja_forms',
			'integration_name' => 'Ninja Forms',
			'form_id'          => $form_id,
			'form_title'       => $data['settings']['title'],
			'form_edit_link'   => admin_url( 'admin.php?page=ninja-forms&form_id=' . $form_id ),
		);

		require_once WPF_DIR_PATH . 'includes/integrations/class-forms-helper.php';

		$contact_id = WPF_Forms_Helper::process_form_data( $args );

	}

}

Ninja_Forms()->actions['wpfusion'] = new WPF_Ninja_Forms();
