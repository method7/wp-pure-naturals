<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_Divi extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'divi';

		add_filter( 'et_pb_all_fields_unprocessed_et_pb_section', array( $this, 'add_field' ) );
		add_filter( 'et_pb_all_fields_unprocessed_et_pb_row', array( $this, 'add_field' ) );
		add_filter( 'et_pb_all_fields_unprocessed_et_pb_column', array( $this, 'add_field' ) );
		add_filter( 'et_pb_all_fields_unprocessed_et_pb_text', array( $this, 'add_field' ) );

		add_filter( 'et_pb_module_shortcode_attributes', array( $this, 'shortcode_attributes' ), 10 );

	}

	/**
	 * Add new field to Divi settings display
	 *
	 * @access  public
	 * @return  array Fields
	 */

	public function add_field( $fields ) {

		$fields['wpf_tag'] = array(
			'label' 		=> 'Required tags (any)',
            'type' 			=> 'text',
            'tab_slug' 		=> 'custom_css',
            'toggle_slug' 	=> 'visibility',
            'description'	=> 'Enter a comma-separated list of tags that are required to view this element.'
		);

		return $fields;

	}


	/**
	 * Shortcode attributes
	 *
	 * @access  public
	 * @return  array Shortcode atts
	 */

	public function shortcode_attributes( $props ) {

		if( ! empty( $props['wpf_tag'] ) ) {

			$can_access = true;

			if ( wp_fusion()->settings->get( 'exclude_admins' ) == true && current_user_can( 'manage_options' ) ) {

				$can_access = true;

			} else {

				if( ! wpf_is_user_logged_in() ) {

					$can_access = false;

				} else {

					$setting_tags_string = explode( ',', $props['wpf_tag'] );
					$setting_tags = array();

					foreach( $setting_tags_string as $tag ) {
						$setting_tags[] = wp_fusion()->user->get_tag_id( $tag );
					}

					$user_tags = wp_fusion()->user->get_tags();

					if ( empty( array_intersect( $user_tags, $setting_tags ) ) ) {
						$can_access = false;
					}

				}

			}

			$can_access = apply_filters( 'wpf_user_can_access', $can_access, wpf_get_current_user_id(), false );

			$can_access = apply_filters( 'wpf_divi_can_access', $can_access, $props );

			if ( false === $can_access ) {
				$props['disabled'] = 'on';
			}

		}

		return $props;

	}

}

new WPF_Divi;
