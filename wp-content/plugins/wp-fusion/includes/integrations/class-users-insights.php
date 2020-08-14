<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_Users_Insights extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function init() {

		$this->slug = 'users-insights';

		add_filter( 'wpf_user_register', array( $this, 'merge_geo_data' ), 10, 2 );
		add_filter( 'wpf_user_update', array( $this, 'merge_geo_data' ), 10, 2 );

		add_filter( 'usin_fields', array( $this, 'add_module_fields' ) );
		add_filter( 'usin_user_db_data', array( $this, 'get_tags_data' ) );

		// WPF stuff
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ) );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );

	}

	/**
	 * Merge Geo data into update data
	 *
	 * @access public
	 * @return array User Meta
	 */

	public function merge_geo_data( $user_meta, $user_id ) {

		$user = new USIN_User_Data( $user_id );

		$data = $user->get_all();

		if ( empty( $data ) ) {
			return $user_meta;
		}

		$user_meta = array_merge( $user_meta, (array) $data );

		return $user_meta;

	}

	/**
	 * Adds CRM tags field to filters
	 *
	 * @access public
	 * @return array Fields
	 */

	public function add_module_fields( $fields ) {

		$available_tags = wp_fusion()->settings->get_available_tags_flat();

		$data = array();

		foreach ( $available_tags as $id => $label ) {

			$data[] = array(
				'key' => $id,
				'val' => $label,
			);

		}

		$fields[] = array(
			'name'      => sprintf( __( '%s tags', 'wp-fusion' ), wp_fusion()->crm->name ),
			'id'        => 'wpf_tags',
			'order'     => false,
			'show'      => true,
			'fieldType' => 'general',
			'filter'    => array(
				'type'    => 'include_exclude',
				'options' => $data,
			),
		);

		return $fields;

	}

	/**
	 * Gets CRM tags for display
	 *
	 * @access public
	 * @return array Data
	 */

	public function get_tags_data( $data ) {

		$tag_ids = wp_fusion()->user->get_tags( $data->ID );

		$tag_names = array_map( array( wp_fusion()->user, 'get_tag_label' ), $tag_ids );

		$data->wpf_tags = implode( ', ', $tag_names );

		return $data;

	}

	/**
	 * Adds Users Insights field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */

	public function add_meta_field_group( $field_groups ) {

		$field_groups['users_insights'] = array(
			'title'  => 'Users Insights',
			'fields' => array(),
		);

		return $field_groups;

	}


	/**
	 * Adds Users Insights meta fields to WPF contact fields list
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$meta_fields['last_seen'] = array(
			'label' => 'Last Seen',
			'type'  => 'date',
			'group' => 'users_insights',
		);

		$meta_fields['sessions'] = array(
			'label' => 'Sessions',
			'type'  => 'int',
			'group' => 'users_insights',
		);

		if ( ! USIN_Geolocation_Status::is_paused() ) {

			$meta_fields['country'] = array(
				'label' => 'Country',
				'type'  => 'country',
				'group' => 'users_insights',
			);

			$meta_fields['region'] = array(
				'label' => 'Region',
				'type'  => 'state',
				'group' => 'users_insights',
			);

			$meta_fields['city'] = array(
				'label' => 'City',
				'type'  => 'text',
				'group' => 'users_insights',
			);

			$meta_fields['coordinates'] = array(
				'label' => 'Coordinates',
				'type'  => 'text',
				'group' => 'users_insights',
			);

		}

		$meta_fields['browser'] = array(
			'label' => 'Browser',
			'type'  => 'text',
			'group' => 'users_insights',
		);

		return $meta_fields;

	}

}

new WPF_Users_Insights();
