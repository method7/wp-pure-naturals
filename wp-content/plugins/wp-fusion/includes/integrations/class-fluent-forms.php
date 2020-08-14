<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use FluentForm\App\Services\Integrations\IntegrationManager;
use FluentForm\Framework\Foundation\Application;
use FluentForm\Framework\Helpers\ArrayHelper;

class WPF_FluentForms extends IntegrationManager {

	public $slug;

	public function __construct() {
		parent::__construct(
			false,
			'WP Fusion',
			'wpfusion',
			'_fluentform_wpfusion_settings',
			'fluentform_wpfusion_feed',
			16
		);

		$this->logo = WPF_DIR_URL . 'assets/img/logo-wide-color.png';

		$this->description = sprintf( __( 'WP Fusion syncs your Fluent Forms entries to %s.', 'wp-fusion' ), wp_fusion()->crm->name );

		$this->registerAdminHooks();

		$this->slug                                 = 'fluent-forms';
		wp_fusion()->integrations->{'fluent-forms'} = $this;

		// add_filter('fluentform_notifying_async_wpfusion', '__return_false');
	}

	public function getGlobalFields( $fields ) {
		return [
			'logo'             => $this->logo,
			'menu_title'       => __( 'WP Fusion Settings', 'wp-fusion' ),
			'menu_description' => sprintf( __( 'Fluent Forms is already connected to %s by WP Fusion, there\'s nothing to configure here. You can set up WP Fusion your individual forms under Settings &raquo; Marketing &amp; CRM Integrations. For more information <a href="https://wpfusion.com/documentation/lead-generation/fluent-forms/" target="_blank">see the documentation</a>.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'valid_message'    => __( 'Your Mailchimp API Key is valid', 'fluentform' ),
			'invalid_message'  => ' ',
			'save_button_text' => ' ',
		];
	}

	/**
	 * Set integration to configured
	 *
	 * @access public
	 * @return bool Configured
	 */

	public function isConfigured() {
		return true;
	}

	/**
	 * Register the integration
	 *
	 * @access public
	 * @return array Integrations
	 */

	public function pushIntegration( $integrations, $form_id ) {

		$integrations[ $this->integrationKey ] = [
			'title'                 => $this->title . ' Integration',
			'logo'                  => $this->logo,
			'is_active'             => true,
			'configure_title'       => 'Configration required!',
			'global_configure_url'  => admin_url( 'admin.php?page=fluent_forms_settings#general-wpfusion-settings' ),
			'configure_message'     => 'WP Fusion is not configured yet! Please configure your WP Fusion API first',
			'configure_button_text' => 'Set WP Fusion API',
		];

		return $integrations;

	}

	/**
	 * Get integration defaults
	 *
	 * @access public
	 * @return array Defaults
	 */

	public function getIntegrationDefaults( $settings, $form_id ) {

		return [
			'name'                    => '',
			'fieldEmailAddress'       => '',
			'custom_field_mappings'   => (object) [],
			'default_fields'          => (object) [],
			'note'                    => '',
			'tags'                    => '',
			'conditionals'            => [
				'conditions' => [],
				'status'     => false,
				'type'       => 'all',
			],
			'instant_responders'      => false,
			'last_broadcast_campaign' => false,
			'enabled'                 => true,
		];
	}

	/**
	 * Get settings fields
	 *
	 * @access public
	 * @return array Settings
	 */

	public function getSettingsFields( $settings, $form_id ) {
		return [
			'fields'              => [
				[
					'key'         => 'name',
					'label'       => 'Name',
					'required'    => true,
					'placeholder' => 'Your Feed Name',
					'component'   => 'text',
				],
				[
					'key'                => 'custom_field_mappings',
					'require_list'       => false,
					'label'              => 'Map Fields',
					'tips'               => 'Select which Fluent Form fields pair with their respective ' . wp_fusion()->crm->name . ' fields.',
					'component'          => 'map_fields',
					'field_label_remote' => wp_fusion()->crm->name . ' Field',
					'field_label_local'  => 'Form Field',
					'default_fields'     => $this->getMergeFields( false, false, $form_id ),
				],
				[
					'key'          => 'tags',
					'require_list' => false,
					'label'        => __( 'Tags', 'wp-fusion' ),
					'tips'         => __( 'Associate tags to your contacts with a comma separated list (e.g. new lead, FluentForms, web source).', 'wp-fusion' ),
					'component'    => 'value_text',
					'inline_tip'   => __( 'Enter tag names or tag IDs, separated by commas', 'wp-fusion' ),
				],
				[
					'require_list' => false,
					'key'          => 'conditionals',
					'label'        => 'Conditional Logics',
					'tips'         => __( 'Allow WP Fusion integration conditionally based on your submission values', 'wp-fusion' ),
					'component'    => 'conditional_block',
				],
				[
					'require_list'    => false,
					'key'             => 'enabled',
					'label'           => 'Status',
					'component'       => 'checkbox-single',
					'checkobox_label' => 'Enable This feed',
				],
			],
			'button_require_list' => false,
			'integration_title'   => $this->title,
		];
	}

	/**
	 * Get CRM fields
	 *
	 * @access public
	 * @return array Fields
	 */

	public function getMergeFields( $list, $list_id, $form_id ) {

		$fields = array();

		$available_fields = wp_fusion()->settings->get( 'crm_fields', array() );

		if ( isset( $available_fields['Standard Fields'] ) ) {

			$available_fields = array_merge( $available_fields['Standard Fields'], $available_fields['Custom Fields'] );

		}

		asort( $available_fields );

		foreach ( $available_fields as $field_id => $field_label ) {

			$remote_required = false;

			if ( $field_label == 'Email' ) {
				$remote_required = true;
			}

			$fields[] = array(
				'name'     => $field_id,
				'label'    => $field_label,
				'required' => $remote_required,
			);

		}

		return $fields;

	}


	/**
	 * Handle form submission
	 *
	 * @access public
	 * @return void
	 */

	public function notify( $feed, $form_data, $entry, $form ) {

		$email_address = false;

		$update_data = $feed['processedValues']['default_fields'];

		foreach ( $update_data as $field => $value ) {

			$update_data[ $field ] = apply_filters( 'wpf_format_field_value', $value, 'text', $field );

			if ( $email_address == false && is_email( $value ) ) {
				$email_address = $value;
			}
		}

		$input_tags = explode( ',', $feed['processedValues']['tags'] );

		$apply_tags = array();

		// Get tags to apply
		foreach ( $input_tags as $tag ) {

			$tag_id = wp_fusion()->user->get_tag_id( $tag );

			if ( false === $tag_id ) {

				wpf_log( 'notice', 0, 'Warning: ' . $tag . ' is not a valid tag name or ID.' );
				continue;

			}

			$apply_tags[] = $tag_id;

		}

		$args = array(
			'email_address'    => $email_address,
			'update_data'      => $update_data,
			'apply_tags'       => $apply_tags,
			'add_only'         => false,
			'integration_slug' => 'fluent_forms',
			'integration_name' => 'Fluent Forms',
			'form_id'          => $form->id,
			'form_title'       => $form->title,
			'form_edit_link'   => admin_url( 'admin.php?page=fluent_forms&route=editor&form_id=' . $form->id ),
		);

		require_once WPF_DIR_PATH . 'includes/integrations/class-forms-helper.php';

		$contact_id = WPF_Forms_Helper::process_form_data( $args );

		if ( is_wp_error( $contact_id ) ) {
			do_action( 'ff_integration_action_result', $feed, 'failed', $contact_id->get_error_message() );
		} else {
			do_action( 'ff_integration_action_result', $feed, 'success', 'Entry synced to ' . wp_fusion()->crm->name . ' (contact ID ' . $contact_id . ')' );
		}

	}

}

new WPF_FluentForms();
