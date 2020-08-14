<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_WPForms extends WPForms_Provider {

	/**
	 * Gets things started
	 *
	 * @access  public
	 * @return  void
	 */

	public function init() {

		wp_fusion()->integrations->wpforms = $this;

		$this->version  = WP_FUSION_VERSION;
		$this->name     = 'WP Fusion';
		$this->slug     = 'wp-fusion';
		$this->priority = 50;
		$this->icon     = WPF_DIR_URL . 'assets/img/logo.png';

		add_action( 'wp_ajax_wpforms_save_form', array( $this, 'save_form' ), 5 );

	}

	/**
	 * Process and submit entry to CRM
	 *
	 * @param array $fields WPForms form array of fields.
	 * @param array $entry
	 * @param array $form_data
	 * @param int $entry_id
	 */

	public function process_entry( $fields, $entry, $form_data, $entry_id = 0 ) {

		// Only run if this form has connections for this provider.
		if ( empty( $form_data['providers'][ $this->slug ] ) ) {
			return;
		}

		// Fire for each connection. ------------------------------------------//

		foreach ( $form_data['providers'][ $this->slug ] as $connection ) {

			// Check for conditional logic.
			$pass = $this->process_conditionals( $fields, $entry, $form_data, $connection );

			if ( ! $pass ) {

				wpforms_log(
					__( 'WP Fusion feed stopped by conditional logic', 'wp-fusion' ),
					$fields,
					array(
						'type'    => array( 'provider', 'conditional_logic' ),
						'parent'  => $entry_id,
						'form_id' => $form_data['id'],
					)
				);

				continue;

			}

			$email_address = false;
			$update_data = array();

			// Format fields

			foreach ( $fields as $i => $field ) {

				if( $field['type'] == 'email' && is_email( $field['value'] ) ) {

					$email_address = $field['value'];

				} elseif ( $field['type'] == 'name' ) {

					$field_first          = $field;
					$field_first['id']    = $field['id'] . '-first';
					$field_first['value'] = $field['first'];

					$fields[] = $field_first;

					$field_last          = $field;
					$field_last['id']    = $field['id'] . '-last';
					$field_last['value'] = $field['last'];

					$fields[] = $field_last;

				} elseif ( $field['type'] == 'checkbox' ) {

					$fields[ $i ]['value'] = explode( PHP_EOL, $field['value_raw'] );

				}

			}

			// Map fields

			foreach ( $fields as $field ) {

				if ( isset( $connection['fields'][ $field['id'] ] ) && ! empty( $connection['fields'][ $field['id'] ]['crm_field'] ) ) {

					$crm_field = $connection['fields'][ $field['id'] ]['crm_field'];

					if ( 'checkbox' == $field['type'] ) {
						$field['type'] = 'checkboxes';
					}

					$update_data[ $crm_field ] = apply_filters( 'wpf_format_field_value', $field['value'], $field['type'], $crm_field );

				}
			}

			if( ! isset( $connection['options']['apply_tags'] ) ) {
				$connection['options']['apply_tags'] = array();
			}

			$args = array(
				'email_address'		=> $email_address,
				'update_data'		=> $update_data,
				'apply_tags'		=> $connection['options']['apply_tags'],
				'integration_slug'	=> 'wpforms',
				'integration_name'	=> 'WPForms',
				'form_id'			=> $form_data['id'],
				'form_title'		=> $form_data['settings']['form_title'],
				'form_edit_link'	=> admin_url( 'admin.php?page=wpforms-builder&view=providers&form_id=' . $form_data['id'] )
			);

			require_once WPF_DIR_PATH . 'includes/integrations/class-forms-helper.php';

			$contact_id = WPF_Forms_Helper::process_form_data( $args );

		}

	}


	/**
	 * Fix WPForms issue with saving WPF tags multiselect data
	 *
	 * @return void
	 */

	public function save_form() {

		$form_post = json_decode( stripslashes( $_POST['data'] ) );

		$i = 0;

		foreach( $form_post as $n => $post_item ) {

			if( strpos( $post_item->name, 'apply_tags' ) !== false ) {
				$form_post[$n]->name = str_replace('[]', '[' . $i . ']', $post_item->name);
				$i++;
			}

		}

		$_POST['data'] = addslashes( json_encode( $form_post ) );

	}


	/**
	 * Add integration
	 *
	 * @return void
	 */

	public function output_auth() {

		$providers = get_option( 'wpforms_providers', array() );

		$providers[ $this->slug ]['wp-fusion'] = array(
			'label' => 'wp-fusion',
			'date'  => time(),
		);

		update_option( 'wpforms_providers', $providers );

	}


	/**
	 * Provider account select HTML.
	 *
	 * @param string $connection_id Unique connection ID.
	 * @param array  $connection Array of connection data.
	 *
	 * @return string
	 */

	public function output_accounts( $connection_id = '', $connection = array() ) {

		return '<input type="hidden" name="providers[' . $this->slug . '][' . $connection_id . '][account_id]" value="wp-fusion" />';

	}


	/**
	 * Provider account lists HTML.
	 *
	 * @param string $connection_id
	 * @param array $connection
	 *
	 * @return WP_Error|string
	 */
	public function output_lists( $connection_id = '', $connection = array() ) {

		return '';

	}


	/**
	 * Provider account list fields HTML.
	 *
	 * @param string $connection_id
	 * @param array $connection
	 * @param mixed $form
	 *
	 * @return WP_Error|string
	 */

	public function output_fields( $connection_id = '', $connection = array(), $form = '' ) {

		if ( empty( $connection_id ) || empty( $form ) ) {
			return '';
		}

		if( !isset( $connection['fields'] ) ) {
			$connection['fields'] = array();
		}

		$form_fields     = $this->get_form_fields( $form );

		// Create separate fields from Firs / Last name field

		foreach ( $form_fields as $i => $form_field ) {

			if ( isset( $form_field['format'] ) && $form_field['format'] == 'first-last' ) {

				$form_field_first          = $form_field;
				$form_field_first['id']    = $form_field['id'] . '-first';
				$form_field_first['label'] = $form_field['label'] . ' - First';

				array_splice( $form_fields, $i + 1, 0, array( $form_field_first ) );

				$form_field_last          = $form_field;
				$form_field_last['id']    = $form_field['id'] . '-last';
				$form_field_last['label'] = $form_field['label'] . ' - Last';

				array_splice( $form_fields, $i + 2, 0, array( $form_field_last ) );

			}

		}

		$output = '<div class="wpforms-provider-fields wpforms-connection-block">';

		$output .= '<h4>Fields</h4>';

		$output .= '<table>';

		$output .= sprintf( '<thead><tr><th>%s</th><th>%s</th></thead>', esc_html__( 'Available Form Fields', 'wpforms' ), esc_html__( 'CRM Field', 'wpforms' ) );

		$output .= '<tbody>';

		if ( ! empty( $form_fields ) ) {

			foreach ( $form_fields as $form_field ) {

				$output .= '<tr>';

				$output .= '<td>';

				$output .= esc_html( $form_field['label'] );

				$output .= '<td>';

				if( ! isset( $connection['fields'][ $form_field['id'] ] ) ) {
					$connection['fields'][ $form_field['id'] ] = array( 'crm_field' => false );
				}

				$setting = $connection['fields'][ $form_field['id'] ]['crm_field'];

				// CRM field

				$output .= '<select class="select4-crm-field" name="providers[' . $this->slug .'][' . $connection_id . '][fields][' . $form_field['id'] . '][crm_field]" data-placeholder="Select a field">';

					$output .= '<option></option>';

					$crm_fields = wp_fusion()->settings->get( 'crm_fields' );

					if ( ! empty( $crm_fields ) ) {

						foreach ( $crm_fields as $group_header => $fields ) {

							// For CRMs with separate custom and built in fields
							if ( is_array( $fields ) ) {

								$output .= '<optgroup label="' . $group_header . '">';

								foreach ( $crm_fields[ $group_header ] as $field => $label ) {

									if ( is_array( $label ) ) {
										$label = $label['label'];
									}

									$output .= '<option ' . selected( esc_attr( $setting ), $field, false ) . ' value="' . esc_attr($field) . '">' . esc_html($label) . '</option>';
								}


								$output .= '</optgroup>';

							} else {

								$field = $group_header;
								$label = $fields;

								$output .= '<option ' . selected( esc_attr( $setting ), $field, false ) . ' value="' . esc_attr($field) . '">' . esc_html($label) . '</option>';


							}

						}

					}

					// Save custom added fields to the DB
					if ( is_array( wp_fusion()->crm->supports ) && in_array( 'add_fields', wp_fusion()->crm->supports ) ) {

						$field_check = array();

						// Collapse fields if they're grouped
						if( isset( $crm_fields['Custom Fields'] ) ) {

							foreach( $crm_fields as $field_group ) {

								foreach( $field_group as $field => $label ) {
									$field_check[ $field ] = $label;
								}

							}

						} else {

							$field_check = $crm_fields;
							
						}

						// Check to see if new custom fields have been added
						if ( ! empty( $setting ) && ! isset( $field_check[ $setting ] ) ) {

							// Lowercase and remove spaces (for Drip)
							if( in_array( 'safe_add_fields', wp_fusion()->crm->supports ) ) {

								$setting_value = strtolower( str_replace( ' ', '', $setting ) );

							} else {

								$setting_value = $setting;

							}

							$output .= '<option value="' . esc_attr($setting_value) . '" selected="selected">' . esc_html($setting) . '</option>';

							if( isset( $crm_fields['Custom Fields'] ) ) {

								$crm_fields['Custom Fields'][ $setting_value ] = $setting;

							} else {
								$crm_fields[ $setting_value ] = $setting;
							}


							wp_fusion()->settings->set( 'crm_fields', $crm_fields );

							// Save safe crm field to DB
							$contact_fields                               = wp_fusion()->settings->get( 'contact_fields' );
							$contact_fields[ $field_sub_id ]['crm_field'] = $setting_value;
							wp_fusion()->settings->set( 'contact_fields', $contact_fields );

						}

					}

					if ( is_array( wp_fusion()->crm->supports ) && in_array( 'add_tags', wp_fusion()->crm->supports ) ) {

						$output .= '<optgroup label="Tagging">';

							$output .= '<option ' . selected( esc_attr( $setting ), 'add_tag_' . $form_field['id'] ) . ' value="add_tag_' . $form_field['id'] . '">+ Create tag(s) from value</option>';

						$output .= '</optgroup>';

					}

					$output .= '</select>';

				$output .= '</td>';

				$output .= '</tr>';

			}

		}

		$output .= '</tbody>';

		$output .= '</table>';

		$output .= '</div>';

		return $output;

	}


	/**
	 * Render tag multiselect in WPForms format
	 *
	 * @return string
	 */

	public function wpforms_render_tag_multiselect( $args ) {

		$defaults = array(
			'setting' 		=> array(),
			'meta_name'		=> null,
			'field_id'		=> null,
			'field_sub_id' 	=> null,
			'disabled'		=> false,
			'placeholder'	=> 'Select tags',
			'limit'			=> null,
			'no_dupes'		=> array(),
			'prepend'		=> array(),
			'class'			=> '',
			'connection'	=> ''
		);

		$output = '';

		$args = wp_parse_args( $args, $defaults );

		$available_tags = wp_fusion()->settings->get( 'available_tags' );

		// If no tags, set a blank array
		if ( ! is_array( $available_tags ) ) {
			$available_tags = array();
		}

		if ( is_array( reset( $available_tags ) ) ) {

			// Handling for select with category groupings

			$tag_categories = array();
			foreach ( $available_tags as $value ) {
				$tag_categories[] = $value['category'];
			}

			$tag_categories = array_unique( $tag_categories );

			$output .= '<select ' . ( $args["disabled"] == true ? ' disabled' : '' ) . ' data-placeholder="' . $args["placeholder"] . '" multiple="multiple" ' . ( $args["limit"] != null ? ' data-limit="' . $args["limit"] . '"' : '' ) . ' class="select4-wpf-tags ' . $args['class'] . '" name="providers[' . $this->slug . '][' . $args["connection"] . '][options][apply_tags][]"' . ( ! empty( $args["no_dupes"] ) ? ' data-no-dupes="' . implode(',', $args["no_dupes"]) . '"' : '' ) . '>';

				if( ! empty( $args['prepend'] ) )  {

					foreach( $args['prepend'] as $id => $tag ) {
						$output .= '<option value="' . esc_attr( $id ) . '"' . ( is_null( $args["field_sub_id"] ) ? selected( true, in_array( $id, (array) $args["setting"] ), false ) : selected( true, in_array( $id, (array) $args["setting"][ $args["field_sub_id"] ] ), false ) ) . '>' . $tag . '</option>';
					}

				}

				foreach ( $tag_categories as $tag_category ) {

					$output .= '<optgroup label="' . $tag_category . '">';

					foreach ( $available_tags as $id => $field_data ) {

						if ( $field_data['category'] == $tag_category ) {
							$output .= '<option value="' . esc_attr( $id ) . '"' . ( is_null( $args["field_sub_id"] ) ? selected( true, in_array( $id, (array) $args["setting"] ), false ) : selected( true, in_array( $id, (array) $args["setting"] [ $args["field_sub_id"] ] ), false ) ) . '>' . esc_html($field_data['label']) . '</option>';
						}

					}
					$output .= '</optgroup>';
				}

			$output .= '</select>';

		} else {

			// Handling for single level select (no categories)

			$output .= '<select ' . ( $args["disabled"] == true ? ' disabled' : '' );
			$output .= ' data-placeholder="' . $args["placeholder"] . '" multiple="multiple" data-limit="' . $args["limit"] . '" class="select4-wpf-tags ' . $args['class'] . '" name="providers[' . $this->slug . '][' . $args["connection"] . '][options][apply_tags][]"' . ( ! empty( $args["no_dupes"] ) ? ' data-no-dupes="' . implode(',', $args["no_dupes"]) . '"' : '' ) . '>';

				if( ! empty( $args['prepend'] ) )  {
					
					foreach( $args['prepend'] as $id => $tag ) {
						$output .= '<option value="' . esc_attr( $id ) . '"' . ( is_null( $args["field_sub_id"] ) ? selected( true, in_array( $id, (array) $args["setting"] ), false ) : selected( true, in_array( $id, (array) $args["setting"][ $args["field_sub_id"] ] ), false ) ) . '>' . esc_html($tag) . '</option>';
					}
				}

				// Check to see if new custom tags have been added
				if ( is_array( wp_fusion()->crm->supports ) && in_array( 'add_tags', wp_fusion()->crm->supports ) ) {

					foreach ( (array) $args["setting"] as $i => $tag ) {

						// For settings with sub-ids (like Woo variations)
						if ( is_array( $tag ) ) {

							foreach ( $tag as $sub_tag ) {

								if ( ! in_array( $sub_tag, $available_tags ) && $i == $args['field_sub_id'] ) {

									$available_tags[ $sub_tag ] = $sub_tag;
									wp_fusion()->settings->set( 'available_tags', $available_tags );
								}

							}

						} elseif ( ! isset( $available_tags[ $tag ] ) && ! empty( $tag ) ) {

							$available_tags[ $tag ] = $tag;
							wp_fusion()->settings->set( 'available_tags', $available_tags );

						}

					}

				}

				foreach ( $available_tags as $id => $tag ) {

					// Fix for empty tags created by spaces etc
					if ( empty( $tag ) ) {
						continue;
					}

					$output .= '<option value="' . esc_attr( $id ) . '"' . ( is_null( $args["field_sub_id"] ) ? selected( true, in_array( $id, (array) $args["setting"] ), false ) : selected( true, in_array( $id, (array) $args["setting"][ $args["field_sub_id"] ] ), false ) ) . '>' . esc_html($tag) . '</option>';

				}


			$output .= '</select>';


		}

		return $output;

	}


	/**
	 * Output options
	 *
	 * @param string $connection_id
	 * @param array $connection
	 *
	 * @return string
	 */

	public function output_options( $connection_id = '', $connection = array() ) {

		if ( empty( $connection_id ) ) {
			return '';
		}

		$output = '<div class="wpforms-provider-options wpforms-connection-block">';

		$output .= '<h4>Apply Tags</h4>';

		if( empty( $connection['options'] ) ) {
			$connection['options'] = array( 'apply_tags' => array() );
		}

		if( ! isset( $connection['options']['apply_tags'] ) ) {
			$connection['options']['apply_tags'] = array();
		}

		$args = array(
			'connection'	=> $connection_id,
			'setting' 		=> $connection['options']['apply_tags']
		);

		$output .= $this->wpforms_render_tag_multiselect( $args );

		$output .= '<span class="description">The selected tags will be applied when the form is submitted</span>';

		$output .= '</div>';

		return $output;

	}


}

new WPF_WPForms;
