<?php

class WPF_EC_Settings {

	public function __construct() {

		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 40, 2 );

		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 10 );

	}

	/**
	 * Adds Addons tab if not already present
	 *
	 * @access public
	 * @since  1.15.2
	 * @return void
	 */

	public function configure_sections( $page, $options ) {

		if ( ! isset( $page['sections']['ecommerce'] ) ) {
			$page['sections'] = wp_fusion()->settings->insert_setting_before( 'import', $page['sections'], array( 'ecommerce' => __( 'Enhanced Ecommerce', 'wp-fusion' ) ) );
		}

		return $page;

	}

	/**
	 * Add fields to settings page
	 *
	 * @access public
	 * @since  1.15.2
	 * @return array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['total_revenue_field'] = array(
			'title'   => __( 'Total Revenue Field', 'wp-fusion' ),
			'desc'    => sprintf( __( 'Optionally, select a custom field in %s to use for total revenue tracking.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'std'     => 0,
			'type'    => 'crm_field',
			'section' => 'ecommerce',
			'tooltip' => __( 'Most CRMs track this data for you automatically, so it\'s not necessary to also track total revenue in a custom field. Enabling this setting will also slow down your checkout by a couple of seconds.', 'wp-fusion' ),
		);

		return $settings;

	}

	/**
	 * Adds total revenue field to the list of meta fields and activates it
	 *
	 * @access public
	 * @since  1.15.2
	 * @return array Meta Fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$revenue_field = wp_fusion()->settings->get( 'total_revenue_field' );

		if ( empty( $revenue_field ) ) {
			return $meta_fields;
		}

		// Set field to active and selected CRM field
		$contact_fields = wp_fusion()->settings->get( 'contact_fields' );

		$contact_fields['wpf_total_revenue'] = array(
			'active'    => 1,
			'crm_field' => $revenue_field['crm_field'],
		);

		wp_fusion()->settings->set( 'contact_fields', $contact_fields );

		$meta_fields['wpf_total_revenue'] = array(
			'label'  => 'Total Revenue',
			'type'   => 'text',
			'hidden' => true,
		);

		return $meta_fields;

	}

}
