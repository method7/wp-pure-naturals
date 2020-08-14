<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WPF_S2Member extends WPF_Integrations_Base {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */

	public function init() {

		$this->slug = 's2member';

		add_filter( 'wpf_user_register', array( $this, 'user_meta_filter' ), 10, 2 );
		add_filter( 'wpf_user_update', array( $this, 'user_meta_filter' ), 10, 2 );
		add_filter( 'wpf_meta_field_groups', array( $this, 'add_meta_field_group' ), 15 );
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ) );
		add_filter( 'wpf_watched_meta_fields', array( $this, 'watch_meta_fields' ) );

		add_filter( 'wpf_configure_sections', array( $this, 'configure_sections' ), 10, 2 );
		add_filter( 'wpf_configure_settings', array( $this, 'register_settings' ), 15, 2 );

		add_action( 'wpf_user_created', array( $this, 'after_registration' ), 10, 3 );
		add_action( 'ws_plugin__s2member_after_configure_user_registration', array( $this, 'post_s2_registration' ) );
		add_action( 'set_user_role', array( $this, 'after_change_user_role' ), 10, 2 );

		// Batch operations
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_action( 'wpf_batch_s2member_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_s2member', array( $this, 'batch_step' ) );

	}

	/**
	 * Triggered after registration, gets user membership level and triggers configured tags
	 *
	 * @access public
	 * @return void
	 */

	public function after_registration( $user_id, $contact_id, $post_data ) {

		$level = $_POST['s2_level'];
		$tags  = wp_fusion()->settings->get( 's2m_level_' . $level . '_apply_tags' );
		wp_fusion()->user->apply_tags( $tags, $user_id );

	}

	/**
	 * Stores member level in POST so we can use it to apply tags after registration
	 *
	 * @access public
	 * @return void
	 */

	public function post_s2_registration( $defined_vars ) {

		$_POST['s2_level'] = $defined_vars['level'];

	}

	/**
	 * Apply additional tags if a member is changed to a new role
	 *
	 * @access public
	 * @return void
	 */

	public function after_change_user_role( $user_id, $role ) {

		if ( false !== strpos( $role, 's2member_level' ) ) {

			$level = str_replace( 's2member_level', '', $role );
			$tags  = wp_fusion()->settings->get( 's2m_level_' . $level . '_apply_tags' );

			if ( ! empty( $tags ) ) {
				wp_fusion()->user->apply_tags( $tags, $user_id );
			}
		}

	}

	/**
	 * Adds Integrations tab if not already present
	 *
	 * @access public
	 * @return void
	 */

	public function configure_sections( $page, $options ) {

		if ( ! isset( $page['sections']['integrations'] ) ) {
			$page['sections'] = wp_fusion()->settings->insert_setting_after( 'contact-fields', $page['sections'], array( 'integrations' => __( 'Integrations', 'wp-fusion' ) ) );
		}

		return $page;

	}

	/**
	 * Registers additional s2member settings
	 *
	 * @access  public
	 * @return  array Settings
	 */

	public function register_settings( $settings, $options ) {

		$settings['s2m_header'] = array(
			'title'   => __( 's2Member Integration', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'integrations',
		);

		$settings['s2m_desc'] = array(
			'std'     => 0,
			'type'    => 'paragraph',
			'section' => 'integrations',
			'desc'    => __( 'For each membership level you can configure tags to be applied to the user at registration or when their membership level is changed.', 'wp-fusion' ),
		);

		for ( $n = 0; $n <= $GLOBALS['WS_PLUGIN__']['s2member']['c']['levels']; $n ++ ) {

			$settings[ 's2m_level_' . $n . '_apply_tags' ] = array(
				'title'   => __( 'Level', 'wp-fusion' ) . ' ' . $n . ': ' . format_to_edit( $GLOBALS['WS_PLUGIN__']['s2member']['o'][ 'level' . $n . '_label' ] ),
				'desc'    => '',
				'std'     => 0,
				'type'    => 'assign_tags',
				'section' => 'integrations',
			);

		}

		return $settings;

	}

	/**
	 * Filters user meta at registration / or on profile updates
	 *
	 * @access  public
	 * @return  array Post Data
	 */

	public function user_meta_filter( $post_data, $user_id ) {

		if ( isset( $post_data['wp_s2member_custom_fields'] ) && is_array( $post_data['wp_s2member_custom_fields'] ) ) {
			$post_data = array_merge( $post_data, $post_data['wp_s2member_custom_fields'] );
		}

		if ( isset( $post_data['s2member_pro_authnet_checkout'] ) && is_array( $post_data['s2member_pro_authnet_checkout'] ) ) {
			$post_data = array_merge( $post_data, $post_data['s2member_pro_authnet_checkout'] );
		}

		if ( isset( $post_data['s2member_pro_paypal_checkout'] ) && is_array( $post_data['s2member_pro_paypal_checkout'] ) ) {
			$post_data = array_merge( $post_data, $post_data['s2member_pro_paypal_checkout'] );
		}

		$field_map = array(
			'email'     => 'user_email',
			'username'  => 'user_login',
			'password1' => 'user_pass',
			'street'    => 'billing_address_1',
			'city'      => 'billing_city',
			'state'     => 'billing_state',
			'zip'       => 'billing_postcode',
			'country'   => 'billing_country',
		);

		$post_data = $this->map_meta_fields( $post_data, $field_map );

		foreach ( $post_data as $key => $value ) {
			if ( strpos( $key, 'ws_plugin__s2member_custom_reg_field_' ) !== false ) {
				$key               = str_replace( 'ws_plugin__s2member_custom_reg_field_', '', $key );
				$post_data[ $key ] = $value;
			} elseif ( strpos( $key, 'ws_plugin__s2member_profile_' ) !== false ) {
				$key               = str_replace( 'ws_plugin__s2member_profile_', '', $key );
				$post_data[ $key ] = $value;
			}
		}

		if ( isset( $post_data['wp_s2member_access_cap_times'] ) && is_array( $post_data['wp_s2member_access_cap_times'] ) ) {
			$post_data['wp_s2member_access_cap_times'] = end( $post_data['wp_s2member_access_cap_times'] );
		}

		return $post_data;

	}

	/**
	 * Adds S2M field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */

	public function add_meta_field_group( $field_groups ) {

		$field_groups['s2m'] = array(
			'title'  => 's2Member',
			'fields' => array(),
		);

		return $field_groups;

	}

	/**
	 * Adds S2M meta fields to WPF contact fields list
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$fields = json_decode( $GLOBALS['WS_PLUGIN__']['s2member']['o']['custom_reg_fields'] );

		if ( empty( $fields ) ) {
			return $meta_fields;
		}

		foreach ( $fields as $field ) {
			$meta_fields[ $field->id ] = array(
				'label' => $field->label,
				'type'  => $field->type,
				'group' => 's2m',
			);
		}

		$meta_fields['wp_s2member_subscr_id'] = array(
			'label' => 'Subscriber ID',
			'type'  => 'text',
			'group' => 's2m',
		);

		$meta_fields['wp_s2member_subscr_gateway'] = array(
			'label' => 'Subscriber Gateway',
			'type'  => 'text',
			'group' => 's2m',
		);

		$meta_fields['wp_s2member_subscr_notes'] = array(
			'label' => 'Subscriber Notes',
			'type'  => 'text',
			'group' => 's2m',
		);

		$meta_fields['wp_s2member_auto_eot_time'] = array(
			'label' => 'Account End-of-Term',
			'type'  => 'date',
			'group' => 's2m',
		);

		$meta_fields['s2_level'] = array(
			'label' => 'Membership Level',
			'type'  => 'text',
			'group' => 's2m',
		);

		return $meta_fields;

	}

	/**
	 * Sync EOT field when it's modified
	 *
	 * @access  public
	 * @return  array Meta Fields
	 */

	public function watch_meta_fields( $meta_fields ) {

		$meta_fields[] = 'wp_s2member_auto_eot_time';

		return $meta_fields;

	}

	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds PMPro checkbox to available export options
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['s2member'] = array(
			'label'   => 's2Member membership levels',
			'title'   => 'members',
			'tooltip' => 'Applies configured tags for all members based on membership level',
		);

		return $options;

	}

	/**
	 * Counts total number of members to be processed
	 *
	 * @access public
	 * @return array Members
	 */

	public function batch_init() {

		$args = array(
			'fields'     => 'ID',
			'meta_query' => array(
				array(
					'key'     => 'wp_s2member_access_cap_times',
					'compare' => 'EXISTS',
				),
			),
		);

		$users = get_users( $args );

		wpf_log( 'info', 0, 'Beginning s2Member batch operation on ' . count( $users ) . ' members', array( 'source' => 's2member' ) );

		return $users;

	}

	/**
	 * Processes member actions in batches
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $user_id ) {

		$levels = get_user_meta( $user_id, 'wp_s2member_access_cap_times', true );
		$level  = end( $levels );

		$level = str_replace( 'level', '', $level );
		$tags  = wp_fusion()->settings->get( 's2m_level_' . $level . '_apply_tags' );

		if ( ! empty( $tags ) ) {

			wp_fusion()->user->apply_tags( $tags, $user_id );

		}

	}

}

new WPF_S2Member();
