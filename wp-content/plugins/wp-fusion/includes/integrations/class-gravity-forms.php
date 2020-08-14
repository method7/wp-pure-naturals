<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

GFForms::include_feed_addon_framework();

class WPF_GForms_Integration extends GFFeedAddOn {

	protected $_version                  = WP_FUSION_VERSION;
	protected $_min_gravityforms_version = '1.7.9999';
	protected $_slug                     = 'wpfgforms';
	protected $_full_path                = __FILE__;
	protected $_title                    = 'CRM Integration';
	protected $_short_title              = 'WP Fusion';
	protected $postvars                  = array();
	public $feed_lists;

	protected $_capabilities_settings_page = array( 'manage_options' );
	protected $_capabilities_form_settings = array( 'manage_options' );
	protected $_capabilities_plugin_page   = array( 'manage_options' );
	protected $_capabilities_app_menu      = array( 'manage_options' );
	protected $_capabilities_app_settings  = array( 'manage_options' );
	protected $_capabilities_uninstall     = array( 'manage_options' );


	/**
	 * Get parent running
	 *
	 * @access  public
	 * @return  void
	 */

	public function init() {

		$this->slug                                  = 'gravity-forms';
		wp_fusion()->integrations->{'gravity-forms'} = $this;

		parent::init();

		// Batch operations
		add_filter( 'wpf_export_options', array( $this, 'export_options' ) );
		add_filter( 'wpf_batch_gravity_forms_init', array( $this, 'batch_init' ) );
		add_action( 'wpf_batch_gravity_forms', array( $this, 'batch_step' ) );

		// Payments
		add_action( 'gform_post_payment_status', array( $this, 'paypay_payment_received' ), 10, 8 );
		add_action( 'gform_stripe_fulfillment', array( $this, 'stripe_payment_received' ), 10, 8 );

		// User registration
		add_action( 'gform_user_registered', array( $this, 'user_registered' ), 10, 4 );
		add_action( 'gform_user_updated', array( $this, 'user_registered' ), 10, 4 );
		add_filter( 'gform_user_registration_update_user_id', array( $this, 'update_user_id' ) );

		// Merge tag
		add_action( 'gform_admin_pre_render', array( $this, 'add_merge_tags' ) );
		add_filter( 'gform_replace_merge_tags', array( $this, 'replace_merge_tags' ), 10, 7 );

	}


	/**
	 * Triggered when form is submitted
	 *
	 * @access  public
	 * @return  void
	 */

	public function process_feed( $feed, $entry, $form ) {

		gform_update_meta( $entry['id'], 'wpf_complete', false );

		$update_data   = array();
		$email_address = '';

		// Check payment status
		if ( isset( $feed['meta']['payment_status'] ) && 'always' != $feed['meta']['payment_status'] ) {

			$paid_statuses = array( 'Paid', 'Approved', 'Processing' );

			if ( 'paid_only' == $feed['meta']['payment_status'] ) {

				if ( empty( $entry['payment_status'] ) || ! in_array( $entry['payment_status'], $paid_statuses ) ) {
					// Form is set to Paid Only and payment status is not paid
					return;
				}
			} elseif ( 'fail_only' == $feed['meta']['payment_status'] ) {

				if ( ! empty( $entry['payment_status'] ) && in_array( $entry['payment_status'], $paid_statuses ) ) {
					// Form is set to Fail Only and payment status is not failed
					return;

				}
			}
		}

		// Combine multiselects where appropriate
		foreach ( $entry as $field_id => $value ) {

			if ( strpos( $field_id, '.' ) !== false && ! empty( $value ) ) {

				$field_id = explode( '.', $field_id );

				if ( ! isset( $entry[ $field_id[0] ] ) ) {
					$entry[ $field_id[0] ] = array();
				}

				$entry[ $field_id[0] ][ $field_id[1] ] = $value;

			}
		}

		// Prepare update array
		foreach ( $feed['meta']['wpf_fields'] as $id => $data ) {

			// Convert dashes back into points for isset
			$id = str_replace( '-', '.', $id );

			if ( isset( $entry[ $id ] ) && ( ! empty( $entry[ $id ] ) || $entry[ $id ] == 0 ) && ! empty( $data['crm_field'] ) ) {

				if ( 'multiselect' == $data['type'] && 0 === strpos( $entry[ $id ], '[' ) ) {

					// Convert multiselects into array format
					$entry[ $id ] = str_replace( '"', '', $entry[ $id ] );
					$entry[ $id ] = str_replace( '[', '', $entry[ $id ] );
					$entry[ $id ] = str_replace( ']', '', $entry[ $id ] );
					$entry[ $id ] = explode( ',', $entry[ $id ] );

				}

				$value = apply_filters( 'wpf_format_field_value', $entry[ $id ], $data['type'], $data['crm_field'] );

				if ( ! empty( $value ) || $value == 0 ) {

					if ( $data['type'] == 'fileupload' ) {
						$value = stripslashes( $value );
					}

					$update_data[ $data['crm_field'] ] = $value;

					if ( $data['type'] == 'email' ) {
						$email_address = $entry[ $id ];
					}
				}
			}
		}

		// Possibly deal with lists if the CRM supports it
		if ( isset( $feed['meta']['wpf_lists'] ) && ! empty( $feed['meta']['wpf_lists'] ) ) {

			$this->feed_lists = $feed['meta']['wpf_lists'];

			add_filter( 'wpf_add_contact_lists', array( $this, 'filter_lists' ) );
			add_filter( 'wpf_update_contact_lists', array( $this, 'filter_lists' ) );

		}

		if ( ! isset( $feed['meta']['wpf_tags'] ) ) {
			$feed['meta']['wpf_tags'] = array();
		}

		$args = array(
			'email_address'    => $email_address,
			'update_data'      => $update_data,
			'apply_tags'       => $feed['meta']['wpf_tags'],
			'integration_slug' => 'gform',
			'integration_name' => 'Gravity Forms',
			'form_id'          => $form['id'],
			'form_title'       => $form['title'],
			'form_edit_link'   => admin_url( 'admin.php?page=gf_edit_forms&id=' . $form['id'] ),
		);

		require_once WPF_DIR_PATH . 'includes/integrations/class-forms-helper.php';

		$contact_id = WPF_Forms_Helper::process_form_data( $args );

		if ( is_wp_error( $contact_id ) ) {

			$this->add_feed_error( $contact_id->get_error_message(), $feed, $entry, $form );

		} else {

			gform_update_meta( $entry['id'], 'wpf_complete', true );

			gform_update_meta( $entry['id'], 'wpf_contact_id', $contact_id );

			$this->add_note( $entry['id'], 'Entry synced to ' . wp_fusion()->crm->name . ' (contact ID ' . $contact_id . ')' );

		}

	}

	/**
	 * Triggered when PayPal payment is received
	 *
	 * @access  public
	 * @return  void
	 */

	public function paypay_payment_received( $feed, $entry, $status, $transaction_id, $subscriber_id, $amount, $pending_reason, $reason ) {

		if ( 'Paid' == $status || 'Completed' == $status ) {

			$form  = GFAPI::get_form( $entry['form_id'] );
			$feeds = $this->get_feeds( $entry['form_id'] );

			foreach ( $feeds as $feed ) {

				if ( 'wpfgforms' == $feed['addon_slug'] && isset( $feed['meta']['payment_status'] ) && 'always' != $feed['meta']['payment_status'] ) {

					$this->process_feed( $feed, $entry, $form );

				}
			}
		}

	}

	/**
	 * Triggered when Stripe payment is received
	 *
	 * @access  public
	 * @return  void
	 */

	public function stripe_payment_received( $session, $entry, $feed, $form ) {

		$feeds = $this->get_feeds( $entry['form_id'] );

		foreach ( $feeds as $feed ) {

			if ( 'wpfgforms' == $feed['addon_slug'] && isset( $feed['meta']['payment_status'] ) && 'always' != $feed['meta']['payment_status'] ) {

				$this->process_feed( $feed, $entry, $form );

			}
		}

	}

	/**
	 * Displays table for mapping fields
	 *
	 * @access  public
	 * @return  void
	 */

	public function settings_wpf_fields( $field ) {

		$form = $this->get_current_form();

		// Quiz Handling
		$quiz_fields = GFAPI::get_fields_by_type( $form, array( 'quiz' ) );

		if ( ! empty( $quiz_fields ) ) {

			$quiz_fields = array(
				'gquiz_score'   => 'Quiz Score Total',
				'gquiz_percent' => 'Quiz Score Percentage',
				'gquiz_grade'   => 'Quiz Grade',
				'gquiz_is_pass' => 'Quiz Pass/Fail',
			);

		}

		do_action( 'wpf_gform_settings_before_table', $form );

		echo '<table class="settings-field-map-table wpf-field-map" cellspacing="0" cellpadding="0">';

		echo '<tbody>';

		$email_found = false;

		foreach ( $form['fields'] as $field ) {

			if ( $field['type'] == 'html' || $field['type'] == 'page' || $field['type'] == 'section' ) {
				continue;
			}

			if ( $field->inputs == null ) {

				// Handing for simple fields (no subfields)
				if ( $field->type == 'email' ) {
					$email_found = true;
				}

				$label = $field->label;

				if ( empty( $label ) ) {
					$label = '<em>(Field ID ' . $field->id . ' - ' . ucwords( $field->type ) . ')</em>';
				}

				echo '<tr>';
				echo '<td><label>' . $label . '<label></td>';
				echo '<td><i class="fa fa-angle-double-right"></i></td>';
				echo '<td>';
				wpf_render_crm_field_select( $this->get_setting( 'wpf_fields[' . $field->id . '][crm_field]' ), '_gaddon_setting_wpf_fields', $field->id );

				$this->settings_hidden(
					array(
						'label'         => '',
						'name'          => 'wpf_fields[' . $field->id . '][type]',
						'default_value' => $field->type,
					)
				);

				echo '</td>';
				echo '</tr>';

			} else {

				// Fields with subfields (Name, Address, etc.)
				$label = $field->label;

				if ( empty( $label ) ) {
					$label = '<em>(Field ID ' . $field->id . ' - ' . ucwords( $field->type ) . ')</em>';
				}

				// For multi-check checkboxes allow either the whole field or just the subfields
				if ( $field->type == 'checkbox' && count( $field->inputs ) > 1 ) {

					echo '<tr>';
					echo '<td><label>' . $label . '<label></td>';
					echo '<td><i class="fa fa-angle-double-right"></i></td>';
					echo '<td>';
					wpf_render_crm_field_select( $this->get_setting( 'wpf_fields[' . $field->id . '][crm_field]' ), '_gaddon_setting_wpf_fields', $field->id );

					$this->settings_hidden(
						array(
							'label'         => '',
							'name'          => 'wpf_fields[' . $field->id . '][type]',
							'default_value' => 'multiselect',
						)
					);

					echo '</td>';
					echo '</tr>';

				}

				foreach ( $field->inputs as $input ) {

					if ( ! isset( $input['isHidden'] ) || $input['isHidden'] == false ) {

						if ( $field->type == 'email' ) {
							$email_found = true;
						}

						if ( $input['label'] == 'First' ) {
							$std  = 'First Name';
							$name = 'FirstName';
						} elseif ( $input['label'] == 'Last' ) {
							$std  = 'Last Name';
							$name = 'LastName';
						} else {
							$std  = '';
							$name = '';
						}

						echo '<tr>';
						echo '<td><label>' . $label . ' - ' . $input['label'] . '<label></td>';
						echo '<td><i class="fa fa-angle-double-right"></i></td>';

						echo '<td>';
						wpf_render_crm_field_select( $this->get_setting( 'wpf_fields[' . str_replace( '.', '-', $input['id'] ) . '][crm_field]' ), '_gaddon_setting_wpf_fields', str_replace( '.', '-', $input['id'] ) );

						$this->settings_hidden(
							array(
								'label'         => '',
								'name'          => 'wpf_fields[' . str_replace( '.', '-', $input['id'] ) . '][type]',
								'default_value' => $field->type,
							)
						);

						echo '</td>';
						echo '</tr>';

					}
				}
			}
		}

		if ( ! empty( $quiz_fields ) ) {

			echo '<tr><td colspan="2"><strong><br />Quiz Fields</strong></td></tr>';

			foreach ( $quiz_fields as $id => $label ) {

				echo '<tr>';
				echo '<td><label>' . $label . '<label></td>';
				echo '<td><i class="fa fa-angle-double-right"></i></td>';
				echo '<td>';
				wpf_render_crm_field_select( $this->get_setting( 'wpf_fields[' . $id . '][crm_field]' ), '_gaddon_setting_wpf_fields', $id );

				$this->settings_hidden(
					array(
						'label'         => '',
						'name'          => 'wpf_fields[' . $id . '][type]',
						'default_value' => 'text',
					)
				);

				echo '</td>';
				echo '</tr>';

			}
		}

		echo '</tbody>';
		echo '</table>';

		if ( $email_found == false ) {
			echo '<div class="alert danger"><strong>Warning:</strong> No <i>email</i> type field found on this form. Entries from guest users will not be sent to ' . wp_fusion()->crm->name . '.</div>';
		}

		do_action( 'wpf_gform_settings_after_table', $form );

	}

	/**
	 * Saves settings
	 *
	 * @access  public
	 * @return  array Settings
	 */

	public function save_wpf_fields( $field, $setting ) {

		foreach ( $setting as $index => $fields ) {

			if ( ! empty( $fields['crm_field'] ) ) {
				$setting[ $index ]['crm_field'] = $setting[ $index ]['crm_field'];
			} else {
				unset( $setting[ $index ] );
			}
		}

		return $setting;

	}


	/**
	 * Renders tag multi select field
	 *
	 * @access  public
	 * @return  void
	 */

	public function settings_wpf_tags( $field ) {

		wpf_render_tag_multiselect(
			array(
				'setting'   => $this->get_setting( $field['name'] ),
				'meta_name' => '_gaddon_setting_' . $field['name'],
			)
		);

	}

	/**
	 * Renders tag multi select field
	 *
	 * @access  public
	 * @return  void
	 */

	public function settings_wpf_lists( $field ) {

		echo '<select multiple="" class="select4 select4-hidden-accessible" name="_gaddon_setting_wpf_lists[]" data-placeholder="Select lists" tabindex="-1" aria-hidden="true">';

		$lists     = wp_fusion()->settings->get( 'available_lists', array() );
		$selection = $this->get_setting( 'wpf_lists' );

		if ( empty( $selection ) ) {
			$selection = array();
		}

		foreach ( $lists as $list_id => $label ) {
			echo '<option ' . selected( true, in_array( $list_id, $selection ), false ) . ' value="' . $list_id . '">' . $label . '</option>';
		}

		echo '</select>';

	}

	/**
	 * Overrides the default lists with those present on the form, if applicable
	 *
	 * @access  public
	 * @return  array Lists
	 */

	public function filter_lists( $lists ) {

		return $this->feed_lists;

	}

	/**
	 * Defines settings for the feed
	 *
	 * @access  public
	 * @return  array Feed settings
	 */

	public function feed_settings_fields() {

		$fields = array();

		$fields[] = array(
			'label'   => 'Feed name',
			'type'    => 'text',
			'name'    => 'feedName',
			'tooltip' => 'Enter a name to remember this feed by.',
			'class'   => 'small',
		);

		$fields[] = array(
			'name'          => 'wpf_fields',
			'label'         => 'Map Fields',
			'type'          => 'wpf_fields',
			'tooltip'       => 'Select a CRM field from the dropdown, or leave blank to disable sync',
			'save_callback' => array( $this, 'save_wpf_fields' ),
		);

		$fields[] = array(
			'name'    => 'wpf_tags',
			'label'   => __( 'Apply Tags', 'wp-fusion' ),
			'type'    => 'wpf_tags',
			'tooltip' => __( 'Select tags to be applied when a user submits this form.', 'wp-fusion' ),
		);

		if ( is_array( wp_fusion()->crm->supports ) && in_array( 'add_lists', wp_fusion()->crm->supports ) ) {

			$fields[] = array(
				'name'    => 'wpf_lists',
				'label'   => 'Add to Lists',
				'type'    => 'wpf_lists',
				'tooltip' => 'Select ActiveCampaign lists to add new contacts to.',
			);
		}

		// Maybe add payment fields
		$feeds        = GFAPI::get_feeds( null, $_GET['id'] );
		$has_payments = false;

		foreach ( $feeds as $feed ) {
			if ( isset( $feed['addon_slug'] ) && $feed['addon_slug'] == 'gravityformsstripe' || $feed['addon_slug'] == 'gravityformspaypal' ) {
				$has_payments = true;
				break;
			}
		}

		if ( $has_payments ) {

			$fields[] = array(
				'name'          => 'payment_status',
				'label'         => 'Payment Status',
				'type'          => 'radio',
				'default_value' => 'always',
				'choices'       => array(
					array(
						'label' => esc_html__( 'Process this feed regardless of payment status', 'wp-fusion' ),
						'value' => 'always',
					),
					array(
						'label' => esc_html__( 'Process this feed only if the payment is successful', 'wp-fusion' ),
						'value' => 'paid_only',
					),
					array(
						'label' => esc_html__( 'Process this feed only if the payment fails', 'wp-fusion' ),
						'value' => 'fail_only',
					),
				),
			);
		}

		$fields[] = array(
			'type'           => 'feed_condition',
			'name'           => 'condition',
			'label'          => 'Opt-In Condition',
			'checkbox_label' => 'Enable Condition',
			'instructions'   => 'Process this feed if',
		);

		$fields = apply_filters( 'wpf_gform_settings_fields', $fields );

		return array(
			array(
				'title'  => WPF_CRM_NAME . ' Integration',
				'fields' => $fields,
			),
		);
	}

	/**
	 * Creates columns for feed
	 *
	 * @access  public
	 * @return  array Feed settings
	 */

	public function feed_list_columns() {
		return array(
			'feedName' => __( 'Name', 'wp-fusion' ),
			'gftags'   => __( 'Applies Tags', 'wp-fusion' ),
		);
	}

	/**
	 * Override this function to allow the feed to being duplicated.
	 *
	 * @access public
	 * @param int|array $id The ID of the feed to be duplicated or the feed object when duplicating a form.
	 * @return boolean|true
	 */
	public function can_duplicate_feed( $id ) {
		return true;
	}

	/**
	 * Displays tags in custom column
	 *
	 * @access  public
	 * @return  string Configured tags
	 */


	public function get_column_value_gftags( $feed ) {

		$tags = rgars( $feed, 'meta/wpf_tags' );

		if ( empty( $tags ) ) {
			return '<em>-none-</em>';
		}

		$tag_labels = array();
		foreach ( (array) $tags as $tag ) {
			$tag_labels[] = wp_fusion()->user->get_tag_label( $tag );
		}

		return '<b>' . implode( ', ', $tag_labels ) . '</b>';
	}

	/**
	 * Loads stylesheets
	 *
	 * @access  public
	 * @return  array Styles
	 */

	public function styles() {

		if ( ! is_admin() ) {
			return parent::styles();
		}

		$styles = array(
			array(
				'handle'  => 'wpf_gforms_css',
				'src'     => WPF_DIR_URL . 'assets/css/wpf-gforms.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'tab' => 'wpfgforms' ),
				),
			),
			array(
				'handle'  => 'select4',
				'src'     => WPF_DIR_URL . 'includes/admin/options/lib/select2/select4.min.css',
				'version' => '4.0.1',
				'enqueue' => array(
					array( 'tab' => 'wpfgforms' ),
				),
			),
			array(
				'handle'  => 'wpf-admin',
				'src'     => WPF_DIR_URL . 'assets/css/wpf-admin.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'tab' => 'wpfgforms' ),
				),
			),
		);

		return array_merge( parent::styles(), $styles );
	}

	/**
	 * Loads scripts
	 *
	 * @access  public
	 * @return  array Scripts
	 */

	public function scripts() {
		$scripts = array(
			array(
				'handle'  => 'select4',
				'src'     => WPF_DIR_URL . 'includes/admin/options/lib/select2/select4.min.js',
				'version' => '4.0.1',
				'deps'    => array( 'jquery' ),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'wpfgforms',
					),
				),
			),
			array(
				'handle'  => 'wpf-admin',
				'src'     => WPF_DIR_URL . 'assets/js/wpf-admin.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery', 'select4' ),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'wpfgforms',
					),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}


	/**
	 * Push updated meta data after user registration
	 *
	 * @access  public
	 * @return  void
	 */

	public function user_registered( $user_id, $feed, $entry, $password ) {

		wp_fusion()->user->push_user_meta( $user_id );

		if ( ! empty( $password ) ) {
			wp_fusion()->user->push_user_meta( $user_id, array( 'user_pass' => $password ) );
		}

	}


	/**
	 * Disable user updating during auto login with GForms user registration
	 *
	 * @access  public
	 * @return  int User ID
	 */

	public function update_user_id( $user_id ) {

		if ( defined( 'DOING_WPF_AUTO_LOGIN' ) ) {
			$user_id = false;
		}

		return $user_id;

	}


	/**
	 * Add contact ID merge tag to dropdown
	 *
	 * @access  public
	 * @return  object Form
	 */

	public function add_merge_tags( $form ) {

		if ( ! headers_sent() ) {
			return $form;
		}

		?>
		<script type="text/javascript">

			gform.addFilter('gform_merge_tags', 'wpf_add_merge_tags');

			function wpf_add_merge_tags(mergeTags, elementId, hideAllFields, excludeFieldTypes, isPrepop, option){
				mergeTags["other"].tags.push({ tag: '{contact_id}', label: 'Contact ID' });
	 
				return mergeTags;
			}
		</script>

		<?php

		// return the form object from the php hook
		return $form;

	}


	/**
	 * Add contact ID merge tag to dropdown
	 *
	 * @access  public
	 * @return  object Form
	 */

	public function replace_merge_tags( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {

		$custom_merge_tag = '{contact_id}';

		if ( strpos( $text, $custom_merge_tag ) === false ) {
			return $text;
		}

		$contact_id = gform_get_meta( $entry['id'], 'wpf_contact_id' );
		$text       = str_replace( $custom_merge_tag, $contact_id, $text );

		return $text;

	}

	/**
	 * //
	 * // BATCH TOOLS
	 * //
	 **/

	/**
	 * Adds Woo Subscriptions checkbox to available export options
	 *
	 * @access public
	 * @return array Options
	 */

	public function export_options( $options ) {

		$options['gravity_forms'] = array(
			'label'   => 'Gravity Forms entries',
			'title'   => 'Entries',
			'tooltip' => 'Find Gravity Forms entries that have not been successfully processed by WP Fusion and syncs them to ' . wp_fusion()->crm->name . ' based on their configured feeds.',
		);

		return $options;

	}

	/**
	 * Gets total list of entries to be processed
	 *
	 * @access public
	 * @return array Subscriptions
	 */

	public function batch_init() {

		$entry_ids = array();

		$feeds = GFAPI::get_feeds( null, null, 'wpfgforms' );

		if ( empty( $feeds ) ) {
			return $entry_ids;
		}

		$form_ids = array();

		foreach ( $feeds as $feed ) {
			$form_ids[] = $feed['form_id'];
		}

		$search_criteria = array(
			'field_filters' => array(
				array(
					'key'      => 'wpf_complete',
					'value'    => '1',
					'operator' => '!=',
				),
			),
		);

		$entry_ids = GFAPI::get_entry_ids( $form_ids, $search_criteria );

		wpf_log( 'info', 0, 'Beginning <strong>Gravity Forms</strong> batch operation on ' . count( $entry_ids ) . ' entries', array( 'source' => 'batch-process' ) );

		return $entry_ids;

	}

	/**
	 * Processes entry feeds
	 *
	 * @access public
	 * @return void
	 */

	public function batch_step( $entry_id ) {

		$entry = GFAPI::get_entry( $entry_id );
		$form  = GFAPI::get_form( $entry['form_id'] );
		$feeds = $this->get_feeds( $entry['form_id'] );

		foreach ( $feeds as $feed ) {

			if ( $feed['addon_slug'] == 'wpfgforms' ) {

				$this->process_feed( $feed, $entry, $form );

			}
		}

	}

}

new WPF_GForms_Integration();
