<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_ACF extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */

	public function init() {

		$this->slug = 'acf';

		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ), 15 );
		add_filter( 'wpf_meta_fields', array( $this, 'set_contact_field_names' ), 10 );
		add_action( 'wpf_user_meta_updated', array( $this, 'user_meta_updated' ), 10, 3 );
		add_action( 'wpf_user_update', array( $this, 'user_update' ), 10, 2 );
		add_action( 'wpf_user_register', array( $this, 'user_update' ), 10, 2 );

		add_filter( 'wpf_meta_box_post_types', array( $this, 'unset_wpf_meta_boxes' ) );

		add_action( 'af/form/submission', array( $this, 'save_user_form' ), 10, 3 ); // Advanced Forms Pro

	}

	/**
	 * Adds ACF field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */

	public function add_meta_field_group( $field_groups ) {

		if ( ! isset( $field_groups['acf'] ) ) {
			$field_groups['acf'] = array( 'title' => 'Advanced Custom Fields', 'fields' => array() );
		}

		return $field_groups;

	}


	/**
	 * Set field labels from ACF field labels
	 *
	 * @access public
	 * @return array Settings
	 */

	public function set_contact_field_names( $meta_fields ) {

		// Only works with ACF pro
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return $meta_fields;
		}

		// Query ACF for field groups registered on the user edit page
		$field_groups = acf_get_field_groups();

		if ( empty( $field_groups ) ) {
			return $meta_fields;
		}

		// Limit it to just user field groups
		foreach ( $field_groups as $i => $group ) {

			if( $group['location'][0][0]['param'] != 'user_form' && $group['location'][0][0]['param'] != 'user_role' ) {
				unset( $field_groups[$i] );
			}
		}

		foreach ( $field_groups as $field_group ) {

			$fields = acf_get_fields( $field_group );

			foreach ( (array) $fields as $field => $data ) {

				// Fix formats
				if( $data['type'] == 'date_picker' || $data['type'] == 'date_time_picker' ) {
					$data['type'] = 'date';
				} elseif ( $data['type'] == 'checkbox' ) {
					$data['type'] = 'multiselect';
				} elseif ( $data['type'] == 'true_false' ) {
					$data['type'] = 'checkbox';
				}

				$meta_fields[ $data['name'] ] = array(
					'label' => $data['label'],
					'type'  => $data['type'],
					'group'	=> 'acf'
				);

			}
		}

		return $meta_fields;

	}

	/**
	 * Removes standard WPF meta boxes from ACF related post types
	 *
	 * @access  public
	 * @return  array Post Types
	 */

	public function unset_wpf_meta_boxes( $post_types ) {

		unset( $post_types['acf-field'] );
		unset( $post_types['acf'] );
		unset( $post_types['acf-field-group'] );

		return $post_types;

	}


	/**
	 * Formats ACF fields from internal forms before sending update to CRM
	 *
	 * @access  public
	 * @return  void
	 */

	public function user_update( $post_data, $user_id ) {

		if ( isset( $post_data['acf'] ) ) {

			foreach ( (array) $post_data['acf'] as $field_id => $field_data ) {

				$field_object = get_field_object( $field_id );

				// Don't erase a value with an empty one

				if ( ! empty( $post_data[ $field_object['name'] ] ) && empty( $field_data ) ) {
					continue;
				}

				$post_data[ $field_object['name'] ] = $field_data;

			}

		}

		$all_fields = wp_fusion()->settings->get( 'contact_fields' );

		// Formatting

		foreach( $post_data as $key => $value ) {

			$post_data[$key] = maybe_unserialize( $value );

			if( is_array( $post_data[$key] ) && isset( $all_fields[$key] ) && $all_fields[$key]['active'] == true && $all_fields[$key]['type'] == 'relationship' ) {

				foreach( $post_data[$key] as $i => $post_id ) {

					$post_data[$key][$i] = get_the_title( $post_id );

				}

			}

			if ( isset( $all_fields[ $key ] ) && isset( $all_fields[ $key ]['type'] ) && $all_fields[ $key ]['type'] == 'date' ) {

				// Make sure we aren't converting something that's already a timestamp

				$maybe_value = strtotime( $value );

				if ( false !== $maybe_value ) {
					$post_data[ $key ] = date( 'c', $maybe_value );
				}

			}

		}

		return $post_data;

	}


	/**
	 * Updates ACF fields when user meta is loaded from the CRM
	 *
	 * @access  public
	 * @return  void
	 */

	public function user_meta_updated( $user_id, $key, $value ) {

		if ( get_field_object( $key, 'user_' . $user_id ) ) {

			$field_object = get_field_object( $key, 'user_' . $user_id );
			update_field( $field_object['key'], $value, 'user_' . $user_id );

		}

	}

	/**
	 * Syncs ACF form data when user data is saved via a frontend form
	 *
	 * @access  public
	 * @return  void
	 */

	public function save_user_form( $form, $fields, $args ) {

		if ( ! empty( $args['user'] ) ) {
			wp_fusion()->user->push_user_meta( $args['user'], $_POST );
		}

	}

}

new WPF_ACF;
