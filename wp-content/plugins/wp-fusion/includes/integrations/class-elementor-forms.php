<?php

use Elementor\Controls_Manager;
use Elementor\Settings;
use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Controls\Fields_Map;
use ElementorPro\Modules\Forms\Classes\Integration_Base;
use ElementorPro\Classes\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function wpf_add_form_actions() {
	\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( 'wpfusion', new WPF_Elementor_Forms() );
}

add_action( 'elementor_pro/init', 'wpf_add_form_actions' );

class WPF_Elementor_Forms extends ElementorPro\Modules\Forms\Classes\Integration_Base {

	/**
	 * @var string
	 */
	public $slug = 'elementor-forms';

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @since   1.0
	 * @return  void
	 */

	public function __construct() {

		wp_fusion()->integrations->{'elementor-forms'} = $this;

		add_filter( 'get_post_metadata', array( $this, 'update_saved_forms' ), 10, 4 );

	}

	/**
	 * Get action ID
	 *
	 * @access  public
	 * @return  string ID
	 */

	public function get_name() {
		return 'wpfusion';
	}

	/**
	 * Get action label
	 *
	 * @access  public
	 * @return  string Label
	 */

	public function get_label() {
		return 'WP Fusion';
	}

	/**
	 * Get CRM fields
	 *
	 * @access  public
	 * @return  array fields
	 */

	public function get_fields() {

		$fields = array();

		$fields_merged    = array();
		$available_fields = wp_fusion()->settings->get( 'crm_fields', array() );

		if ( isset( $available_fields['Standard Fields'] ) ) {

			$available_fields = array_merge( $available_fields['Standard Fields'], $available_fields['Custom Fields'] );

		}

		foreach ( $available_fields as $field_id => $field_label ) {

			$remote_required = false;

			if ( $field_label == 'Email' ) {
				$remote_required = true;
			}

			$fields[] = array(
				'remote_label'    => $field_label,
				'remote_type'     => 'text',
				'remote_id'       => $field_id,
				'remote_required' => $remote_required,
			);

		}

		// Add as tag
		if ( is_array( wp_fusion()->crm->supports ) && in_array( 'add_tags', wp_fusion()->crm->supports ) ) {

			$fields[] = array(
				'remote_label'    => '+ Create tag(s) from',
				'remote_type'     => 'text',
				'remote_id'       => 'add_tag_e',
				'remote_required' => false,
			);

		}

		return $fields;

	}

	/**
	 * Get available tags for select
	 *
	 * @access  public
	 * @return  array Tags
	 */

	public function get_tags() {

		$available_tags = wp_fusion()->settings->get( 'available_tags', array() );

		$data = array();

		foreach ( $available_tags as $id => $label ) {

			if ( is_array( $label ) ) {
				$label = $label['label'];
			}

			$data[ $id ] = $label;

		}

		return $data;

	}

	/**
	 * Registers settings
	 *
	 * @access  public
	 * @return  void
	 */

	public function register_settings_section( $widget ) {

		$widget->start_controls_section(
			'section_wpfusion',
			[
				'label'     => 'WP Fusion',
				'condition' => [
					'submit_actions' => $this->get_name(),
				],
			]
		);

		$widget->add_control(
			'wpf_apply_tags',
			[
				'label'       => __( 'Apply Tags', 'wp-fusion' ),
				'description' => sprintf( __( 'The selected tags will be applied in %s when the form is submitted.', 'wp-fusion' ), wp_fusion()->crm->name ),
				'type'        => Controls_Manager::SELECT2,
				'options'     => $this->get_tags(),
				'multiple'    => true,
				'label_block' => true,
				'show_label'  => true,
			]
		);

		$widget->add_control(
			'wpf_add_only',
			[
				'label'       => __( 'Add Only', 'wp-fusion' ),
				'description' => __( 'Only add new contacts, don\'t update existing ones.', 'wp-fusion' ),
				'type'        => Controls_Manager::SWITCHER,
				'label_block' => false,
				'show_label'  => true,
			]
		);

		$widget->add_control(
			'wpf_fields_map',
			[
				'label'     => sprintf( __( '%s Field Mapping', 'elementor-pro' ), wp_fusion()->crm->name ),
				'type'      => Fields_Map::CONTROL_TYPE,
				'separator' => 'before',
				'fields'    => [
					[
						'name' => 'remote_id',
						'type' => Controls_Manager::HIDDEN,
					],
					[
						'name' => 'local_id',
						'type' => Controls_Manager::SELECT,
					],
				],
				'default'   => $this->get_fields(),
			]
		);

		$widget->end_controls_section();

	}

	/**
	 * Update saved form data when it's loaded from the DB to detect new form fields (because Elementor support has been useless at helping with this, see https://github.com/elementor/elementor/issues/8938)
	 *
	 * @access  public
	 * @return  null / array Value
	 */

	public function update_saved_forms( $value, $object_id, $meta_key, $single ) {

		if ( is_admin() && $meta_key == '_elementor_data' ) {

			// Prevent looping
			remove_filter( 'get_post_metadata', array( $this, 'update_saved_forms' ), 10, 4 );

			$settings = get_post_meta( $object_id, '_elementor_data', true );

			// Quit if the desired setting isn't found or if settings are already an array (no idea why it does that)
			if ( is_array( $settings ) || false === strpos( $settings, 'wpf_fields_map' ) ) {
				return $value;
			}

			$settings = json_decode( $settings, true );

			$settings = $this->parse_elements_for_form( $settings );

			$value = json_encode( $settings );

		}

		return $value;

	}

	/**
	 * Loop through saved elements, updating values as necessary
	 *
	 * @access  public
	 * @return  array Elements
	 */

	private function parse_elements_for_form( $elements ) {

		foreach ( $elements as $i => $element ) {

			if ( isset( $element['settings'] ) && isset( $element['settings']['wpf_fields_map'] ) ) {

				$new_settings = $this->get_fields();

				foreach ( $new_settings as $n => $setting ) {

					foreach ( $element['settings']['wpf_fields_map'] as $saved_value ) {

						if ( $saved_value['remote_id'] == $setting['remote_id'] ) {

							$new_settings[ $n ] = array_merge( $setting, $saved_value );

						}
					}
				}

				$elements[ $i ]['settings']['wpf_fields_map'] = $new_settings;

			}

			if ( ! empty( $element['elements'] ) ) {

				$elements[ $i ]['elements'] = $this->parse_elements_for_form( $element['elements'] );

			}
		}

		return $elements;

	}

	/**
	 * Unsets WPF settings on export
	 *
	 * @access  public
	 * @return  object Element
	 */

	public function on_export( $element ) {

		unset(
			$element['settings']['wpf_fields_map'],
			$element['settings']['wpf_apply_tags']
		);

		return $element;
	}

	/**
	 * Process form submission
	 *
	 * @access  public
	 * @return  void
	 */

	public function run( $record, $ajax_handler ) {

		$sent_data     = $record->get( 'sent_data' );
		$form_settings = $record->get( 'form_settings' );

		$update_data   = array();
		$email_address = false;

		foreach ( $form_settings['wpf_fields_map'] as $field ) {

			if ( ! empty( $sent_data[ $field['local_id'] ] ) ) {

				$update_data[ $field['remote_id'] ] = apply_filters( 'wpf_format_field_value', $sent_data[ $field['local_id'] ], 'text', $field['remote_id'] );

				if ( $email_address == false && is_email( $sent_data[ $field['local_id'] ] ) ) {
					$email_address = $sent_data[ $field['local_id'] ];
				}
			}
		}

		if ( isset( $form_settings['wpf_add_only'] ) && $form_settings['wpf_add_only'] == 'yes' ) {
			$add_only = true;
		} else {
			$add_only = false;
		}

		if ( empty ( $form_settings['wpf_apply_tags'] ) ) {
			$form_settings['wpf_apply_tags'] = array();
		}

		$args = array(
			'email_address'    => $email_address,
			'update_data'      => $update_data,
			'apply_tags'       => $form_settings['wpf_apply_tags'],
			'add_only'         => $add_only,
			'integration_slug' => 'elementor_forms',
			'integration_name' => 'Elementor Forms',
			'form_id'          => null,
			'form_title'       => null,
			'form_edit_link'   => null,
		);

		require_once WPF_DIR_PATH . 'includes/integrations/class-forms-helper.php';

		$contact_id = WPF_Forms_Helper::process_form_data( $args );

	}

	/**
	 * @param array $data
	 *
	 * @return void
	 */

	public function handle_panel_request( array $data ) { }


}
