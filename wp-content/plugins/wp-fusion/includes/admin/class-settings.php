<?php

class WPF_Settings {

	/**
	 * Contains all plugin settings
	 */

	public $options;


	/**
	 * Make batch processing utility publicly accessible
	 */

	public $batch;


	/**
	 * Get things started
	 *
	 * @since 1.0
	 * @return void
	 */

	public function __construct() {

		if ( is_admin() ) {

			$this->options = get_option( 'wpf_options', array() ); // No longer loading this on the frontend

			$this->init();
		}

	}

	/**
	 * Fires up settings in admin
	 *
	 * @since 1.0
	 * @return void
	 */

	private function init() {

		$this->includes();

		// Compatibility check
		add_action( 'wpf_settings_after_page_title', array( $this, 'show_compatibility_notices' ) );
		add_action( 'wp_ajax_dismiss_wpf_notice', array( $this, 'dismiss_compatibility_notice' ) );

		// Custom fields
		add_action( 'show_field_contact_fields', array( $this, 'show_field_contact_fields' ), 10, 2 );
		add_action( 'show_field_contact_fields_begin', array( $this, 'show_field_contact_fields_begin' ), 10, 2 );
		add_action( 'show_field_assign_tags', array( $this, 'show_field_assign_tags' ), 10, 2 );
		add_action( 'validate_field_assign_tags', array( $this, 'validate_field_assign_tags' ), 10, 3 );
		add_action( 'show_field_import_users', array( $this, 'show_field_import_users' ), 10, 2 );
		add_action( 'show_field_import_users_end', array( $this, 'show_field_import_users_end' ), 10, 2 );
		add_action( 'show_field_import_groups', array( $this, 'show_field_import_groups' ), 10, 2 );
		add_action( 'show_field_export_options', array( $this, 'show_field_export_options' ), 10, 2 );
		add_action( 'show_field_test_webhooks', array( $this, 'show_field_test_webhooks' ), 10, 2 );
		add_action( 'show_field_crm_field', array( $this, 'show_field_crm_field' ), 10, 2 );

		// CRM setup layouts
		add_action( 'show_field_api_validate', array( $this, 'show_field_api_validate' ), 10, 2 );
		add_action( 'show_field_api_validate_end', array( $this, 'show_field_api_validate_end' ), 10, 2 );

		// Resync button at top

		add_action( 'wpf_settings_page_title', array( $this, 'header_resync_button' ) );

		// AJAX
		add_action( 'wp_ajax_sync_tags', array( $this, 'sync_tags' ) );
		add_action( 'wp_ajax_sync_custom_fields', array( $this, 'sync_custom_fields' ) );
		add_action( 'wp_ajax_import_users', array( $this, 'import_users' ) );
		add_action( 'wp_ajax_delete_import_group', array( $this, 'delete_import_group' ) );

		// Setup scripts and initialize
		add_filter( 'wpf_meta_fields', array( $this, 'prepare_meta_fields' ), 60 );
		add_filter( 'wpf_configure_settings', array( $this, 'configure_settings' ), 20, 2 );
		add_filter( 'wpf_initialize_options', array( $this, 'initialize_options' ) );
		add_action( 'wpf_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Set tag labels
		add_action( 'wpf_sync', array( $this, 'save_tag_labels' ) );
		add_filter( 'gettext', array( $this, 'set_tag_labels' ), 10, 3 );

		// Validation
		add_filter( 'validate_field_contact_fields', array( $this, 'validate_field_contact_fields' ), 10, 3 );

		// Plugin action links and messages
		add_filter( 'plugin_action_links_' . WPF_PLUGIN_PATH, array( $this, 'add_action_links' ) );

		if( wp_fusion()->is_full_version() ) {

			add_action( 'show_field_edd_license_begin', array( $this, 'show_field_edd_license_begin' ), 10, 2 );
			add_action( 'show_field_edd_license', array( $this, 'show_field_edd_license' ), 10, 2 );

			add_action( 'wp_ajax_edd_activate', array( $this, 'edd_activate' ) );
			add_action( 'wp_ajax_edd_deactivate', array( $this, 'edd_deactivate' ) );

		} else {

			add_action( 'show_field_users_header_begin', array( $this, 'upgrade_notice' ), 10, 2 );

		}

		add_filter( 'wpf_configure_settings', array( $this, 'get_settings' ), 5, 2 );

		// Fire up the options framework
		new WPF_Options( $this->get_setup(), array(), $this->get_sections() );

	}


	/**
	 * Display upgrade prompt in free version
	 *
	 * @since 1.0
	 * @return mixed
	 */

	public function upgrade_notice( $id, $field ) {

		?>

			</table>

			<div id="wpf-pro">
				<div id="wpf-pro-top">
					<img src="<?php echo WPF_DIR_URL ?>assets/img/logo-wide.png" />
				</div>

				<p>You're running the <strong>Lite</strong> version of WP Fusion. A paid license includes:</p>

				<ul>
					<li>80+ plugin integrations like <a href="https://wpfusion.com/documentation/ecommerce/woocommerce/?utm_campaign=free-plugin&utm_source=free-plugin" target="_blank">WooCommerce</a>, <a href="https://wpfusion.com/documentation/page-builders/elementor/?utm_campaign=free-plugin&utm_source=free-plugin" target="_blank">Elementor</a>, <a href="https://wpfusion.com/documentation/learning-management/learndash/?utm_campaign=free-plugin&utm_source=free-plugin" target="_blank">LearnDash</a> and more</li>
					<li>Hundreds of tag triggers</li>
					<li>Sync data back to WordPress via webhooks</li>
					<li>Premium support</li>
				</ul>

				<a class="button-primary" href="https://wpfusion.com/?utm_campaign=free-plugin&utm_source=free-plugin" target="_blank">Learn More</a>

				<hr />

				Happy with the free version? Consider <a href="https://wordpress.org/plugins/wp-fusion-lite/#reviews" target="_blank">leaving us a review</a>.

			</div>

		<?php

	}


	/**
	 * Include options and batch libraries
	 *
	 * @since 1.0
	 * @return void
	 */

	private function includes() {

		require_once WPF_DIR_PATH . 'includes/admin/options/class-options.php';

	}


	/**
	 * Get the value of a specific setting
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function get( $key, $default = false ) {

		if ( empty( $this->options ) ) {
			$this->options = get_option( 'wpf_options', array() );
		}

		$value = isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;

		return apply_filters( 'wpf_get_setting_' . $key, $value );
	}


	/**
	 * Set the value of a specific setting
	 *
	 * @since 1.0
	 * @return voic
	 */
	public function set( $key, $value ) {

		$this->options[ $key ] = $value;
		update_option( 'wpf_options', $this->options, false );

	}

	/**
	 * Get all settings
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_all() {
		return $this->options;
	}

	/**
	 * Allows setting global settings during runtime
	 *
	 * @since 1.0
	 * @return array
	 */
	public function set_all( $options ) {
		$this->options = $options;
		update_option( 'wpf_options', $options, false );
	}

	/**
	 * Gets available tags without categories
	 *
	 * @since 3.33.4
	 * @return array
	 */
	public function get_available_tags_flat() {

		$available_tags = $this->get( 'available_tags', array() );

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
	 * Utility function for adding a setting before an existing setting
	 *
	 * @since 1.0
	 * @return array
	 */

	public function insert_setting_before( $key, array $array, $new_key, $new_value = null ) {

		if ( array_key_exists( $key, $array ) ) {
			$new = array();
			foreach ( $array as $k => $value ) {
				if ( $k === $key ) {
					if ( is_array( $new_key ) && count( $new_key ) > 0 ) {
						$new = array_merge( $new, $new_key );
					} else {
						$new[ $new_key ] = $new_value;
					}
				}
				$new[ $k ] = $value;
			}

			return $new;
		}

		return $array;
	}


	/**
	 * Utility function for adding a setting after an existing setting
	 *
	 * @since 1.0
	 * @return array
	 */

	public function insert_setting_after( $key, array $array, $new_key, $new_value = null ) {

		if ( array_key_exists( $key, $array ) ) {
			$new = array();

			foreach ( $array as $k => $value ) {
				$new[ $k ] = $value;
				if ( $k === $key ) {
					if ( is_array( $new_key ) && count( $new_key ) > 0 ) {
						$new = array_merge( $new, $new_key );
					} else {
						$new[ $new_key ] = $new_value;
					}
				}
			}

			return $new;

		} else {

			$array = $array + $new_key;

		}

		return $array;
	}

	/**
	 * Add settings link to plugin page
	 *
	 * @access public
	 * @return array Links
	 */

	public function add_action_links( $links ) {

		if ( ! is_array( $links ) ) {
			return $links;
		}

		$links[] = '<a href="' . get_admin_url( null, 'options-general.php?page=wpf-settings' ) . '">Settings</a>';

		return $links;
	}

	/**
	 * Enqueue options page scripts
	 *
	 * @access public
	 * @return void
	 */

	public function enqueue_scripts() {

		// Style
		wp_enqueue_style( 'wpf-options', WPF_DIR_URL . 'assets/css/wpf-options.css', array(), WP_FUSION_VERSION );
		wp_enqueue_style( 'wpf-admin', WPF_DIR_URL . 'assets/css/wpf-admin.css', array(), WP_FUSION_VERSION );

		// Scripts
		wp_enqueue_script( 'wpf-options', WPF_DIR_URL . 'assets/js/wpf-options.js', array( 'jquery', 'select4' ), WP_FUSION_VERSION, true );
		wp_localize_script( 'wpf-options', 'wpf_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'tag_type' => $this->get( 'crm_tag_type' ) ) );

		wp_enqueue_script( 'wpf-admin', WPF_DIR_URL . 'assets/js/wpf-admin.js', array( 'jquery', 'select4' ), WP_FUSION_VERSION, true );
		wp_localize_script( 'wpf-admin', 'wpf', array( 'crm_supports' => wp_fusion()->crm->supports ) );

	}

	/**
	 * Saves any tag label overrides on initial sync
	 *
	 * @access public
	 * @return void
	 */

	public function save_tag_labels() {

		if( isset( wp_fusion()->crm_base->tag_type ) ) {
			$this->set( 'crm_tag_type', wp_fusion()->crm_base->tag_type );
		}

	}

	/**
	 * Allows text to be overridden for CRMs that use different segmentation labels (groups, lists, etc)
	 *
	 * @access public
	 * @return string Text
	 */

	public function set_tag_labels( $translation, $text, $domain ) {

		if( $domain == 'wp-fusion' ) {

			if( isset( $this->options['connection_configured'] ) && $this->options['connection_configured'] == true && isset( $this->options['crm_tag_type'] ) ) {

				if( strpos($translation, ' Tag') !== false ) {

					$translation = str_replace(' Tag', ' ' . $this->options['crm_tag_type'], $translation );

				} elseif( strpos($translation, ' tag') !== false ) {

					$translation = str_replace(' tag', ' ' . strtolower( $this->options['crm_tag_type'] ), $translation );

				}

			}

		}

		return $translation;

	}

	/**
	 * Shows compatibility notices with other plugins
	 *
	 * @since 3.33.4
	 * @return mixed HTML output
	 */

	public function show_compatibility_notices() {

		$notices = apply_filters( 'wpf_compatibility_notices', array() );

		foreach ( $notices as $id => $message ) {

			if ( true == $this->get( 'dismissed_' . $id ) ) {
				continue;
			}

			echo '<div id="' . $id . '-notice" data-notice="' . $id . '" class="notice notice-warning wpf-notice is-dismissible"><p>' . $message . '</p></div>';

		}

		if ( true == $this->options['staging_mode'] ) {

			echo '<div class="notice notice-warning wpf-notice"><p>' . sprintf( __( '<strong>Heads up:</strong> WP Fusion is currently in Staging Mode. No data will be sent to or loaded from %s.', 'wp-fusion' ), wp_fusion()->crm->name ) . '</p></div>';

		}

	}

	/**
	 * Runs when a compatibility notice is dismissed
	 *
	 * @since 3.33.4
	 * @return void
	 */

	public function dismiss_compatibility_notice() {

		$this->set( 'dismissed_' . $_POST['id'], true );

		wp_die();

	}

	/**
	 * Filters out internal WordPress fields from showing up in syncable meta fields list and sets labels and types for built in fields
	 *
	 * @since 1.0
	 * @return array
	 */

	public function prepare_meta_fields( $meta_fields ) {

		$exclude_fields = array(
			'contact_id',
			'wpf_tags',
			'rich_editing',
			'comment_shortcuts',
			'admin_color',
			'use_ssl',
			'show_admin_bar_front',
			'wp_user_level',
			'dismissed_wp_pointers',
			'show_welcome_panel',
			'session_tokens',
			'wp_dashboard_quick_press_last_post_id',
			'nav_menu_recently_edited',
			'managenav-menuscolumnshidden',
			'metaboxhidden_nav-menus',
			'unique_id',
			'profilepicture',
			'action',
			'group',
			'shortcode',
			'up_username'
		);

		foreach ( $exclude_fields as $field ) {

			if ( isset( $meta_fields[ $field ] ) ) {

				unset( $meta_fields[ $field ] );

			}
		}

		// Some fields we can exclude via partials

		$exclude_fields_partials = array(
			'metaboxhidden_',
			'meta-box-order_',
			'screen_layout_',
			'closedpostboxes_',
			'_contact_id',
			'_tags'
			);

		foreach( $exclude_fields_partials as $partial ) {

			foreach( $meta_fields as $field => $data ) {

				if ( strpos($field, $partial) !== false ) {

					unset( $meta_fields[ $field ] );

				}

			}

		}

		// Load the reference of standard WP field names and types
		include dirname( __FILE__ ) . '/wordpress-fields.php';

		// Sets field types and labels for all built in fields
		foreach ( $wp_fields as $key => $data ) {

			if( !isset( $data['group'] ) ) {
				$data['group'] = 'wordpress';
			}

			$meta_fields[ $key ] = array(
				'label' => $data['label'],
				'type'  => $data['type'],
				'group'	=> $data['group']
			);

		}

		return $meta_fields;

	}

	/**
	 * Sync tags
	 *
	 * @access public
	 * @return array New tags loaded from CRM
	 */

	public function sync_tags() {

		$available_tags = wp_fusion()->settings->get( 'available_tags' );
		$new_tags       = wp_fusion()->crm->sync_tags();

		foreach ( $new_tags as $id => $data ) {

			if ( ! isset( $available_tags[ $id ] ) ) {
				echo '<option value="' . $id . '">' . wp_fusion()->user->get_tag_label( $id ) . '</option>';
			}

		}

		die();
	}


	/**
	 * Sync custom fields
	 *
	 * @access public
	 * @return array New custom fields loaded from CRM
	 */

	public function sync_custom_fields() {

		$crm_fields = wp_fusion()->settings->get( 'crm_fields' );
		$new_fields = wp_fusion()->crm->sync_crm_fields();

		if ( is_wp_error( $new_fields ) ) {

			wpf_log( 'error', 0, 'Failed to load custom fields: ' . $new_fields->get_error_message() );
			die();

		}

		if ( isset( $new_fields['Custom Fields'] ) ) {

			// Optgroup fields

			$new_fields = $new_fields['Custom Fields'];

			foreach ( $new_fields as $id => $label ) {
				if ( ! isset( $crm_fields['Custom Fields'][ $id ] ) ) {
					echo '<option value="' . $id . '">' . $label . '</option>';
				}
			}
		} else {

			// Non optgroup

			foreach ( $new_fields as $id => $label ) {
				if ( ! isset( $crm_fields[ $id ] ) ) {
					echo '<option value="' . $id . '">' . $label . '</option>';
				}
			}

		}

		die();
	}

	/**
	 * Deletes a previously imported group of contacts
	 *
	 * @access public
	 * @return bool
	 */

	public function delete_import_group() {

		$import_group  = intval($_POST['group_id']);
		$import_groups = get_option( 'wpf_import_groups' );

		global $current_user;

		foreach ( $import_groups[ $import_group ]['user_ids'] as $user_id ) {

			// Don't delete admins
			if( ! user_can( $user_id, 'manage_options' ) ) {
				wp_delete_user( $user_id, $current_user->ID );
			}

		}

		unset( $import_groups[ $import_group ] );
		update_option( 'wpf_import_groups', $import_groups, false );
		wp_send_json_success();

		die();

	}


	/**
	 * Check EDD license
	 *
	 * @access public
	 * @return string License Status
	 */

	public function edd_check_license( $license_key, $action = false ) {

		$status = get_transient( 'wpf_license_check' );

		// Run the license check a maximum of once every 10 days
		if ( false === $status || false !== $action ) {

			$integrations = array();

			if ( ! empty( wp_fusion()->integrations ) ) {

				foreach ( wp_fusion()->integrations as $slug => $object ) {
					$integrations[] = $slug;
				}
			}

			// Addons

			$addons = array(
				'WP_Fusion_Ecommerce',
				'WP_Fusion_Abandoned_Cart',
				'WP_Fusion_Logins',
				'WP_Fusion_Downloads',
				'WP_Fusion_Webhooks',
				'WP_Fusion_Media_Tools',
			);

			foreach ( $addons as $class ) {

				if ( class_exists( $class ) ) {

					$slug = str_replace( 'WP_Fusion_', '', $class );
					$slug = str_replace( '_', '-', $slug );

					$integrations[] = strtolower( $slug );

				}
			}

			// data to send in our API request
			$api_params = array(
				'edd_action'   => 'check_license',
				'license'      => $license_key,
				'item_name'    => urlencode( 'WP Fusion' ),
				'author'       => 'Very Good Plugins',
				'url'          => home_url(),
				'crm'          => wp_fusion()->crm->name,
				'integrations' => $integrations,
				'version'      => WP_FUSION_VERSION,
				'action'       => $action,
			);

			// Call the custom API.
			$response = wp_remote_post( WPF_STORE_URL, array(
				'timeout'   => 20,
				'sslverify' => false,
				'body'      => $api_params,
			) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				set_transient( 'wpf_license_check', true, 60 * 60 * 24 * 3 );
				return 'error';
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			$this->set( 'license_status', $license_data->license );

			set_transient( 'wpf_license_check', true, 60 * 60 * 24 * 10 );

			return $license_data->license;

		} else {

			// Return stored license data
			return $this->get( 'license_status' );

		}

	}


	/**
	 * Activate EDD license
	 *
	 * @access public
	 * @return bool
	 */

	public function edd_activate() {

		$license_key = trim( $_POST['key'] );

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license_key,
			'item_name'  => urlencode( 'WP Fusion' ), // the name of our product in EDD
			'url'        => home_url(),
			'version'	 => WP_FUSION_VERSION
		);

		if( wp_fusion()->settings->get( 'connection_configured' ) == true ) {

			$integrations = array();

			foreach(wp_fusion()->integrations as $slug => $object) {
				$integrations[] = $slug;
			}

			$api_params['crm'] = wp_fusion()->crm->name;
			$api_params['integrations'] = $integrations;

		}

		// Call the custom API.
		$response = wp_remote_post( WPF_STORE_URL, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );

		if( wp_remote_retrieve_response_code( $response ) == 403 ) {
			wp_send_json_error( '403 response. Please <a href="https://wpfusion.com/support/contact/" target="_blank">contact support</a>. IP address: ' . $_SERVER['SERVER_ADDR'] );
		}

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() . '&ndash; Please <a href="https://wpfusion.com/support/contact/" target="_blank">contact support</a> for further assistance.' );
			die();
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "valid" or "invalid"

		// Store the options locally
		$this->set( 'license_status', $license_data->license );
		$this->set( 'license_key', $license_key );

		if ( $license_data->license == 'valid' ) {
			wp_send_json_success( 'activated' );
		} else {
			wp_send_json_error( '<pre>' . print_r( $license_data, true ) . '</pre>' );
		}

		die();
	}


	/**
	 * Deactivate EDD license
	 *
	 * @access public
	 * @return bool
	 */

	public function edd_deactivate() {

		$license_key = trim( $_POST['key'] );

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license_key,
			'item_name'  => urlencode( 'WP Fusion' ), // the name of our product in EDD
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( WPF_STORE_URL, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params
		) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
			die();
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( $license_data->license == 'deactivated' ) {
			$this->set( 'license_status', 'invalid' );
			wp_send_json_success( 'deactivated' );
		} else {
			wp_send_json_error( '<pre>' . print_r( $license_data, true ) . '</pre>' );
		}

		wp_die();
	}


	/**
	 * Set up options page
	 *
	 * @access public
	 * @return void
	 */

	private function get_setup() {
		return array(
			'project_name' => __( 'WP Fusion', 'wp-fusion' ),
			'project_slug' => 'wpf',
			'menu'         => 'settings',
			'page_title'   => __( 'WP Fusion Settings', 'wp-fusion' ),
			'menu_title'   => __( 'WP Fusion', 'wp-fusion' ),
			'capability'   => 'manage_options',
			'option_group' => 'wpf_options',
			'slug'         => 'wpf-settings',
			'page_icon'    => 'tools',
		);
	}


	/**
	 * Get options page sections
	 *
	 * @access private
	 * @return void
	 */

	private function get_sections() {

		$sections = array();

		$sections['wpf-settings']['main']           = __( 'General Settings', 'wp-fusion' );
		$sections['wpf-settings']['contact-fields'] = __( 'Contact Fields', 'wp-fusion' );

		$sections['wpf-settings']['import']        = __( 'Import Users', 'wp-fusion' );
		$sections['wpf-settings']['setup']         = __( 'Setup', 'wp-fusion' );
		$sections['wpf-settings']['advanced']      = __( 'Advanced', 'wp-fusion' );

		return $sections;

	}


	/**
	 * Initialize settings to default values
	 *
	 * @access public
	 * @return array
	 */

	public function initialize_options( $options ) {

		// Access Key
		if ( empty( $options['access_key'] ) ) {
			$options['access_key'] = substr( md5( microtime() . rand() ), 0, 8 );
		}

		// Contact fields
		global $current_user;
		$meta_fields = get_user_meta( $current_user->ID );

		// Manually add password/username/email/role fields
		$meta_fields["user_pass"]  = "";
		$meta_fields["user_login"] = "";
		$meta_fields["user_email"] = "";
		$meta_fields["user_role"] = "";

		// Remove hidden fields
		foreach ( $meta_fields as $key => $value ) {
			if ( substr( $key, 0, 1 ) === "_" || substr( $key, 0, 5 ) === "hide_" || substr( $key, 0, 3 ) === "wp_" ) {
				unset( $meta_fields[ $key ] );
			}
		}

		// These fields should be turned on by default
		if ( ! isset( $options['contact_fields']['first_name']['active'] ) ) {
			$options['contact_fields']['first_name']['active'] = true;
		}

		if ( ! isset( $options['contact_fields']['last_name']['active'] ) ) {
			$options['contact_fields']['last_name']['active'] = true;
		}

		if ( ! isset( $options['contact_fields']['user_email']['active'] ) ) {
			$options['contact_fields']['user_email']['active'] = true;
		}

		// Set defaults for other meta fields
		foreach ( $meta_fields as $key => $value ) {

			// If key is new
			if ( ! isset( $options['contact_fields'][ $key ] ) ) {
				$options['contact_fields'][ $key ] = array();
			}

			// If active is empty
			if ( ! isset( $options['contact_fields'][ $key ]['active'] ) ) {
				$options['contact_fields'][ $key ]['active'] = 0;
			}

		}

		// Reset table headers
		if( isset( $_POST['wpf_options'] ) ) {

			if( isset( $_POST['wpf_options']['table_headers'] )) {

				$table_headers = array();

				foreach( (array) $_POST['wpf_options']['table_headers'] as $section => $value ) {
					$table_headers[ $section ] = true;
				}

				$options['table_headers'] = $table_headers;

			} else {
				$options['table_headers'] = array();
			}
		}

		// Staging CRM
		if( isset( $options['crm'] ) && $options['crm'] == 'staging' ) {
			$options['connection_configured'] = true;
		}

		// Upgrade settings for v 3.24.4
		if( isset( $options['hide_archives'] ) && $options['hide_archives'] === true ) {
			$options['hide_archives'] = 'standard';
		}

		return $options;

	}


	/**
	 * Define all available settings
	 *
	 * @access private
	 * @return void
	 */

	public function get_settings( $settings, $options ) {

		$settings = array();

		$settings['general_desc'] = array(
			'desc'    => '<p class="description" style="font-size: 14px;">' . sprintf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/getting-started/general-settings/" target="_blank">', '</a>' ) . '</p>',
			'type'    => 'heading',
			'section' => 'main',
		);

		$settings['users_header'] = array(
			'title'   => __( 'Automatically Create Contact Records for New Users', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'main'
		);

		$settings['create_users'] = array(
			'title'   => __( 'Create Contacts', 'wp-fusion' ),
			'desc'    => sprintf( __( 'Create new contacts in %s when users register in WordPress.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'std'     => 1,
			'type'    => 'checkbox',
			'section' => 'main',
			'unlock'  => array( 'user_roles', 'wpf_options-assign_tags', 'opportunity_state' ),
			'tooltip' => sprintf( __( 'We <em>strongly</em> recommend leaving this setting enabled. If it\'s disabled only profile updates from existing users will be synced with %s. New users or customers will not be synced, and no tags will be applied to new users.', 'wp-fusion' ), wp_fusion()->crm->name ),
		);

		$settings['assign_tags'] = array(
			'title'   => __( 'Assign Tags', 'wp-fusion' ),
			'desc'    => __( 'The selected tags will be applied to anyone who registers an account in WordPress.', 'wp-fusion' ),
			'type'    => 'multi_select',
			'choices' => array(),
			'section' => 'main'
		);

		/*
		// CONTACT DATA SYNC
		*/

		$settings['contact_sync_header'] = array(
			'title'   => __( 'Synchronize Contact Data', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'main'
		);

		$settings['push'] = array(
			'title'   => __( 'Push', 'wp-fusion' ),
			'desc'    => sprintf( __( 'When a user profile is modified, update their contact record in %s to match.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'std'     => 1,
			'type'    => 'checkbox',
			'section' => 'main',
		);

		$settings['push_all_meta'] = array(
			'title'   => __( 'Push All', 'wp-fusion' ),
			'desc'    => __( 'Push meta data whenever a single "user_meta" entry is added or modified.', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'main',
			'tooltip' => __( 'This is useful if using non-supported plugins or manual user_meta updates, but may result in duplicate API calls and slower performance.', 'wp-fusion' ),
		);

		$settings['login_sync'] = array(
			'title'   => __( 'Login Tags Sync', 'wp-fusion' ),
			'desc'    => sprintf( __( 'Load the user\'s latest tags from %s on login.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'main',
			'tooltip' => sprintf( __( 'Note: this is only necessary if you are applying tags via automations in %s and haven\'t set up webhooks to send the data back. Any tags applied via WP Fusion are available in WordPress immediately.' ), wp_fusion()->crm->name ),
		);

		$settings['login_meta_sync'] = array(
			'title'   => __( 'Login Meta Sync', 'wp-fusion' ),
			'desc'    => sprintf( __( 'Load the user\'s latest meta data from %s on login.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'main',
			'tooltip' => sprintf( __( 'Note: this is only necessary if you are manually updating contact data in %s and haven\'t set up webhooks to send the data back.' ), wp_fusion()->crm->name ),
		);

		// $settings['profile_update_tags'] = array(
		// 	'title'   => __( 'Update Tag', 'wp-fusion' ),
		// 	'desc'    => __( 'Apply this tag when a contact record has been updated (useful for triggering data to be sent to other WP Fusion installs).', 'wp-fusion' ),
		// 	'std'     => false,
		// 	'type'    => 'assign_tags',
		// 	'section' => 'main'
		// ); // Removed in v3.3.3

		/*
		// RESTRICT PAGE ACCESS
		*/

		$settings['restrict_access_header'] = array(
			'title'   => __( 'Content Restriction', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'main'
		);

		$settings['hide_restricted'] = array(
			'title'   => __( 'Hide From Menus', 'wp-fusion' ),
			'desc'    => __( 'Content that the user cannot access will be removed from menus.', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'main'
		);

		$settings['hide_archives'] = array(
			'title'   => __( 'Filter Queries', 'wp-fusion' ),
			'desc'    => __( 'Content that the user cannot access will be <strong>completely hidden</strong> from all post listings, grids, archives, and course navigation. <strong>Use with caution</strong>.', 'wp-fusion' ),
			'std'     => 'off',
			'type'    => 'select',
			'section' => 'main',
			'choices' => array(
				'off'		=> __( 'Off', 'wp-fusion' ),
				'standard'	=> __( 'Standard', 'wp-fusion' ),
				'advanced'	=> __( 'Advanced (slower)', 'wp-fusion' ),
			)
		);

		$settings['exclude_admins'] = array(
			'title'   => __( 'Exclude Administrators', 'wp-fusion' ),
			'desc'    => __( 'Users with Administrator accounts will be able to view all content, regardless of restrictions.', 'wp-fusion' ),
			'std'     => 1,
			'type'    => 'checkbox',
			'section' => 'main'
		);

		$settings['default_redirect'] = array(
			'title'   => __( 'Default Redirect', 'wp-fusion' ),
			'desc'    => __( 'Default redirect URL for when access is denied. This can be overridden on a per-page basis. Leave blank to display error message below.', 'wp-fusion' ),
			'std'     => '',
			'type'    => 'text',
			'section' => 'main'
		);

		$settings['return_after_login'] = array(
			'title'   => __( 'Return After Login', 'wp-fusion' ),
			'desc'    => __( 'If a user has been redirected away from a restricted page, take them back to that page after logging in.', 'wp-fusion' ),
			'tooltip' => __( 'When a user attempts to access a restricted page a cookie will be set. When the user logs in they will be redirected to the most recent restricted page they tried to access.', 'wp-fusion' ),
			'std'     => 1,
			'type'    => 'checkbox',
			'section' => 'main',
			'unlock'  => array( 'return_after_login_priority' )
		);

		$settings['return_after_login_priority'] = array(
			'title'   => __( 'Return After Login Priority', 'wp-fusion' ),
			'desc'    => __( 'Priority for the login redirect. A lower number means the redirect happens sooner in the login process.', 'wp-fusion' ),
			'std'     => 10,
			'type'    => 'number',
			'section' => 'main',
		);

		$settings['restricted_message'] = array(
			'title'         => __( 'Default Restricted Content Message', 'wp-fusion' ),
			'desc'          => __( 'Restricted content message for when a redirect hasn\'t been specified.', 'wp-fusion' ),
			'std'           => __( '<h2 style="text-align:center">Oops!</h2><p style="text-align:center">You don\'t have permission to view this page! Make sure you\'re logged in and try again, or contact support.</p>', 'wp-fusion' ),
			'type'          => 'editor',
			'section'       => 'main',
			'textarea_rows' => 10
		);

		$settings['per_post_messages'] = array(
			'title'   => __( 'Per Post Messages', 'wp-fusion' ),
			'desc'    => __( 'Enable this setting to allow specifying a different restricted content message for each page or post.', 'wp-fusion' ),
			'tooltip' => __( 'With this setting enabled a new metabox will appear on all posts and pages where you can specify a unique content restriction message for that post. If no message is set the Default Restricted Content Message will be shown.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'section' => 'main',
			'std'	  => 0,
		);

		/*
		// SITE LOCKOUT
		*/

		$settings['site_lockout_header'] = array(
			'title'   => __( 'Site Lockout', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'main',
			'desc'    => __( 'Site lockout lets you restrict access to all pages on your site if a user has a specific tag (i.e. "Payment Failed"). The only page accessible will be the URL specified as the Lockout Redirect below.', 'wp-fusion' ),
		);

		$settings['lockout_tags'] = array(
			'title'   => __( 'Lockout Tags', 'wp-fusion' ),
			'desc'    => __( 'If the user has any of these tags the lockout will be activated.', 'wp-fusion' ),
			'type'    => 'assign_tags',
			'section' => 'main',
		);

		$settings['lockout_redirect'] = array(
			'title'   => __( 'Lockout Redirect', 'wp-fusion' ),
			'desc'    => __( 'URL to redirect to when lockout is active.', 'wp-fusion' ),
			'std'     => wp_login_url(),
			'type'    => 'text',
			'section' => 'main',
		);

		$settings['lockout_allowed_urls'] = array(
			'title'       => __( 'Allowed URLs', 'wp-fusion' ),
			'desc'        => __( 'The URLs listed here (one per line) will bypass the Site Lockout feature.', 'wp-fusion' ),
			'type'        => 'textarea',
			'section'     => 'main',
			'rows'        => 3,
			'placeholder' => home_url( '/example-page/' ),
		);

		/*
		// SEO
		*/

		$settings['seo_header'] = array(
			'title'   => __( 'SEO', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'main'
		);

		$settings['seo_enabled'] = array(
			'title'   => __( 'Show Excerpts', 'wp-fusion' ),
			'desc'    => __( 'Show an excerpt of your restricted content to search engine spiders.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'section' => 'main',
			'std'	  => 0,
			'unlock'  => array( 'seo_excerpt_length' )
		);

		$settings['seo_excerpt_length'] = array(
			'title'   => __( 'Excerpt Length', 'wp-fusion' ),
			'desc'    => __( 'Show the first X words of your content to search engines. Leave blank for default, which is usually 55 words.', 'wp-fusion' ),
			'type'    => 'number',
			'section' => 'main'
		);

		/*
		// ACCESS KEY
		*/

		$settings['access_key_header'] = array(
			'title'   => __( 'Webhooks', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'main'
		);

		if( wp_fusion()->is_full_version() ) {

			$settings['access_key_desc'] = array(
				'std'     => 0,
				'type'    => 'paragraph',
				'section' => 'main',
				'desc'    => sprintf( __( 'Webhooks allow you to send data from %s back to your website. See <a href="http://wpfusion.com/documentation/#webhooks" target="_blank">our documentation</a> for more information on creating webhooks.', 'wp-fusion' ), wp_fusion()->crm->name ),
			);

			$settings['access_key'] = array(
				'title'   => __( 'Access Key', 'wp-fusion' ),
				'desc'    => __( 'Use this key when sending data back to WP Fusion via a webhook or ThriveCart.', 'wp-fusion' ),
				'type'    => 'text',
				'section' => 'main'
			);

			$settings['test_webhooks'] = array(
				'title'   => __( 'Test Webhooks', 'wp-fusion' ),
				'desc'    => __( 'Click this button to test your site\'s ability to receive incoming webhooks.', 'wp-fusion' ),
				'type'    => 'text',
				'section' => 'main'
			);

		} else {

			$settings['webhooks_lite_notice'] = array(
				'type'    => 'paragraph',
				'desc'    => '<span style="display:inline-block; background: #fff; padding: 10px 15px; font-weight: bold;">' . sprintf( __( 'To sync data bi-directionally with WordPress using %s webhooks please <a href="https://wpfusion.com/pricing/?utm_campaign=free-plugin&utm_source=free-plugin">upgrade to the full version</a> of WP Fusion.', 'wp-fusion' ), wp_fusion()->crm->name ) . '</span>',
				'section' => 'main'
			);

		}

		$settings['return_password_header'] = array(
			'title'   => __( 'Imported Users', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'main',
			'desc'    => __( 'These settings apply to users imported either via a webhook, the Import Users tool, or from a ThriveCart success URL.', 'wp-fusion' ),
		);

		$settings['return_password'] = array(
			'title'   => __( 'Return Password', 'wp-fusion' ),
			'desc'    => sprintf( __( 'Send new users\' generated passwords back to %s after import.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'type'    => 'checkbox',
			'section' => 'main',
			'std'	  => 1
		);

		$settings['return_password_field'] = array(
				'title'   => __('Return Password Field', 'wp-fusion' ),
				'desc'    => sprintf( __('Select a field in %s where generated passwords will be stored for imported users.', 'wp-fusion' ), wp_fusion()->crm->name ),
				'std'     => false,
				'type'    => 'crm_field',
				'section' => 'main'
		);

		/*
		// CONTACT FIELDS
		*/

		$settings['contact_fields'] = array(
			'title'   => __( 'Contact Fields', 'wp-fusion' ),
			'std'     => array(),
			'type'    => 'contact-fields',
			'section' => 'contact-fields',
			'choices' => array()
		);

		/*
		// IMPORT USERS
		*/

		$settings['import_users_p'] = array(
			'desc'    => sprintf( __( 'This feature allows you to import %s contacts as new WordPress users. Any fields configured on the <strong>Contact Fields</strong> tab will be imported.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'type'    => 'paragraph',
			'section' => 'import'
		);

		$settings['import_users'] = array(
			'title'   => __( 'Import Users', 'wp-fusion' ),
			'desc'    => __( 'Contacts with the selected tags will be imported as new users.', 'wp-fusion' ),
			'type'    => 'multi_select',
			'choices' => array(),
			'section' => 'import'
		);

		$settings['email_notifications'] = array(
			'title'   => __( 'Enable Notifications', 'wp-fusion' ),
			'desc'    => __( 'Send a welcome email to new users containing their username and a password reset link.', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'checkbox',
			'section' => 'import'
		);

		$settings['import_groups'] = array(
			'title'   => __( 'Import Groups', 'wp-fusion' ),
			'type'    => 'import_groups',
			'section' => 'import'
		);


		/*
		// API CONFIGURATION
		*/

		$settings['api_heading'] = array(
			'title'   => __( 'API Configuration', 'wp-fusion' ),
			'section' => 'setup',
			'type'    => 'heading'
		);

		$settings['crm'] = array(
			'title'       => __( 'Select CRM', 'wp-fusion' ),
			'section'     => 'setup',
			'type'        => 'select',
			'placeholder' => __( 'Select your CRM', 'wp-fusion' ),
			'allow_null'  => false,
			'choices'     => array()
		);

		$settings['connection_configured'] = array(
			'std'     => false,
			'type'    => 'hidden',
			'section' => 'setup'
		);

		if( wp_fusion()->is_full_version() ) {

			$settings['license_heading'] = array(
				'title'   => __( 'WP Fusion License', 'wp-fusion' ),
				'section' => 'setup',
				'type'    => 'heading'
			);

			$settings['license_key'] = array(
				'title'          => __( 'License Key', 'wp-fusion' ),
				'std'            => '',
				'type'           => 'edd_license',
				'section'        => 'setup',
				'license_status' => 'invalid'
			);

			$settings['license_status'] = array(
				'type'    => 'hidden',
				'section' => 'setup',
				'std'     => 'invalid'
			);

		}

		/*
		// ADVANCED
		*/

		$settings['advanced_header'] = array(
			'title'   => __( 'Advanced Features', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'advanced'
		);

		$settings['user_roles'] = array(
			'title'       => __( 'Limit  User Roles', 'wp-fusion' ),
			'desc'        => __( 'You can choose to create new contacts <strong>only when users are added to a certain role</strong>. Leave blank for all roles.', 'wp-fusion' ),
			'type'        => 'multi_select',
			'choices'     => array(),
			'placeholder' => __( 'Select user roles', 'wp-fusion' ),
			'section'     => 'advanced'
		);

		$settings['deletion_tags'] = array(
			'title'   => __( 'Deletion Tags', 'wp-fusion' ),
			'desc'    => sprintf( __( 'These tags will be applied in %s when a user is deleted from the site, or when a user deletes their own account.' ), wp_fusion()->crm->name ),
			'std'     => array(),
			'type'    => 'assign_tags',
			'section' => 'advanced'
		);

		$settings['link_click_tracking'] = array(
			'title'   => __( 'Link Tracking', 'wp-fusion' ),
			'desc'    => __( 'Enqueue the scripts to handle link click tracking. See <a href="https://wpfusion.com/documentation/tutorials/link-click-tracking/" target="_blank">this tutorial</a> for more info.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => 0,
			'section' => 'advanced'
		);

		$settings['auto_login_header'] = array(
			'title'   => __( 'Auto Login / Tracking Links', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'advanced'
		);

		$settings['auto_login'] = array(
			'title'   => __( 'Allow URL Login', 'wp-fusion' ),
			'desc'    => __( 'Track user activity and unlock content by passing a Contact ID in a URL. See <a href="https://wpfusion.com/documentation/tutorials/auto-login-links/" target="_blank">this tutorial</a> for more info.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => 0,
			'section' => 'advanced',
		);

		$settings['auto_login_forms'] = array(
			'title'   => __( 'Form Auto Login', 'wp-fusion' ),
			'desc'    => __( 'Start an auto-login session whenever a visitor submits a form configured with WP Fusion.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => 0,
			'section' => 'advanced'
		);

		$settings['auto_login_thrivecart'] = array(
			'title'   => __( 'ThriveCart Auto Login', 'wp-fusion' ),
			'desc'    => __( 'Automatically log in new users with a ThriveCart success URL. See <a href="https://wpfusion.com/documentation/tutorials/thrivecart/" target="_blank">this tutorial</a> for more info.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => 0,
			'section' => 'advanced'
		);

		if ( isset( $this->options['auto_login'] ) && true == $this->options['auto_login'] ) {
			$std = 1;
		} else {
			$std = 0;
		}

		$settings['auto_login_current_user'] = array(
			'title'   => __( 'Set Current User', 'wp-fusion' ),
			'desc'    => __( 'Set\'s the <code>$current_user</code> global for the auto-login user. Makes auto-login work better with form plugins, but may cause other plugins to crash.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => $std,
			'section' => 'advanced'
		);

		$settings['system_header'] = array(
			'title'   => __( 'System Settings', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'advanced'
		);

		$settings['staging_mode'] = array(
			'title'   => __( 'Staging Mode', 'wp-fusion' ),
			'desc'    => sprintf( __( 'When staging mode is active, all normal WP Fusion features will be available, but no API calls will be made to %s.', 'wp-fusion' ), wp_fusion()->crm->name ),
			'type'    => 'checkbox',
			'std'     => 0,
			'section' => 'advanced',
		);

		$settings['performance_header'] = array(
			'title'   => __( 'Performance', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'advanced',
		);

		$settings['enable_queue'] = array(
			'title'   => __( 'Enable API Queue', 'wp-fusion' ),
			'desc'    => __( 'Combine redundant API calls to improve performance.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => 1,
			'section' => 'advanced',
			'tooltip' => __( 'It is <strong>strongly</strong> recommended to leave this on except for debugging purposes.', 'wp-fusion' ),
			'unlock'  => array( 'staging_mode' ),
		);

		$settings['prevent_reapply'] = array(
			'title'   => __( 'Prevent Reapplying Tags', 'wp-fusion' ),
			'desc'    => __( 'If a user already has a tag cached in WordPress, don\'t send an API call to re-apply the same tag.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => 1,
			'section' => 'advanced',
			'tooltip' => __( 'It\'s recommended to leave this on for performance reasons but it can be turned off for debugging.', 'wp-fusion' ),
		);

		$settings['logging_header'] = array(
			'title'   => __( 'Logging', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'advanced',
		);

		$settings['enable_logging'] = array(
			'title'   => __( 'Enable Logging', 'wp-fusion' ),
			'desc'    => __( 'Access detailed activity logs for WP Fusion.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => 1,
			'section' => 'advanced',
			'unlock'  => array( 'logging_errors_only', 'logging_http_api' ),
		);

		$settings['logging_errors_only'] = array(
			'title'   => __( 'Only Errors', 'wp-fusion' ),
			'desc'    => __( 'Only log errors (not all activity).', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => 0,
			'section' => 'advanced',
		);

		if ( isset( wp_fusion()->crm->params ) ) {

			$settings['logging_http_api'] = array(
				'title'   => __( 'HTTP API Logging', 'wp-fusion' ),
				'desc'    => __( 'Log the raw data sent over the WordPress HTTP API.', 'wp-fusion' ),
				'type'    => 'checkbox',
				'std'     => 0,
				'section' => 'advanced',
				'tooltip' => __( 'This will result in a lot of data being written to the logs so it\'s recommended to only enable temporarily for debugging purposes.', 'wp-fusion' ),
			);

		}

		$settings['interfaces_header'] = array(
			'title'   => __( 'Interfaces and Settings', 'wp-fusion' ),
			'type'    => 'heading',
			'section' => 'advanced',
		);

		$settings['hide_additional'] = array(
			'title'   => __( 'Hide Additional Fields', 'wp-fusion' ),
			'desc'    => __( 'Hide the <code>wp_usermeta</code> Fields section from the Contact Fields tab.', 'wp-fusion' ),
			'tooltip' => __( 'If you\'re not using any of the fields this can speed up performance and make the settings page load faster.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => 0,
			'section' => 'advanced',
		);

		$settings['enable_admin_bar'] = array(
			'title'   => __( 'Admin Bar', 'wp-fusion' ),
			'desc'    => __( 'Enable the "Preview With Tag" functionality on the admin bar.', 'wp-fusion' ),
			'tooltip' => __( 'If you have a lot of tags and aren\'t using the Preview With Tag feature disabling this can make your admin bar load faster.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => 1,
			'section' => 'advanced',
		);

		$settings['admin_permissions'] = array(
			'title'   => __( 'Admin Permissions', 'wp-fusion' ),
			'desc'    => __( 'Require the <code>manage_options</code> capability to see WP Fusion meta boxes in the admin.', 'wp-fusion' ),
			'tooltip' => __( 'Enable this setting if you have other user roles editing posts (for example Authors or Editors) and you don\'t want them to see WP Fusion\'s access control meta boxes.', 'wp-fusion' ),
			'type'    => 'checkbox',
			'std'     => 0,
			'section' => 'advanced',
		);

		$settings['export_header'] = array(
			'title'   => __( 'Batch Operations', 'wp-fusion' ),
			'desc'    => __( 'Hover over the tooltip icons to get a description of what each operation does and what data will be modified. For more information on batch operations, <a target="_blank" href="https://wpfusion.com/documentation/tutorials/exporting-data/">see our documentation</a>.', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'advanced'
		);

		$settings['export_options'] = array(
			'title'   => __( 'Operation', 'wp-fusion' ),
			'type'    => 'export_options',
			'choices' => array(),
			'section' => 'advanced'
		);

		$settings['reset_header'] = array(
			'title'   => __( 'Reset', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'heading',
			'section' => 'advanced'
		);

		$settings['reset_options'] = array(
			'title'   => __( 'Reset', 'wp-fusion' ),
			'desc'    => __( 'Check this box and click "Save Changes" below to reset all options to their defaults.', 'wp-fusion' ),
			'std'     => 0,
			'type'    => 'reset',
			'section' => 'advanced'
		);

		return $settings;

	}


	/**
	 * Configure available settings
	 *
	 * @access public
	 * @return array
	 */

	public function configure_settings( $settings, $options ) {

		// Lock the license field if the license is valid
		if ( isset( $options['license_status'] ) && $options['license_status'] == 'valid' && ! empty( $options['license_key'] ) ) {
			$settings['license_key']['license_status'] = 'valid';
			$settings['license_key']['disabled']       = true;
		}

		// Everything else can be done after the API connection is set up
		if ( isset( $options['connection_configured'] ) && $options['connection_configured'] == true ) {

			global $wp_roles;

			if( ! isset( $options['available_tags'] ) ) {
				$options['available_tags'] = array();
			}

			$settings['user_roles']['choices']  = $wp_roles->role_names;
			$settings['user_roles']['disabled'] = ( $options['create_users'] == 0 ? true : false );

			$settings['assign_tags']['choices']  = $options['available_tags'];
			$settings['assign_tags']['disabled'] = ( $options['create_users'] == 0 ? true : false );

			$settings['import_users']['choices'] = $options['available_tags'];

			if ( get_option( 'wpf_import_groups' ) == false ) {
				$settings['import_groups']['type'] = 'hidden';
			}

			// Disable CRM change after initial connection is configured
			$settings['crm']['disabled'] = true;
			$settings['crm']['desc']     = __( 'To change CRMs, please do a full reset of WP Fusion from the Advanced tab.', 'wp-fusion' );

			$settings['contact_fields']['choices'] = apply_filters( 'wpf_meta_fields', $options['contact_fields'] );

			// Fields that unlock other fields

			foreach ( $settings as $setting => $data ) {

				if ( isset( $data['unlock'] ) ) {

					foreach ( $data['unlock'] as $unlock_field ) {

						if ( ! empty( $settings[ $unlock_field ] ) && isset( $options[ $setting ] ) ) {

							$settings[ $unlock_field ]['disabled'] = ( $options[ $setting ] == 0 ? true : false );

						}
					}
				}
			}

		} else {

			// Enable CRM change after reset
			$settings['crm']['disabled'] = false;
			$settings['crm']['desc']     = '';

		}

		return $settings;

	}

	/**
	 * Shows API key field
	 *
	 * @access  public
	 * @since   1.0
	 */

	public function show_field_api_validate( $id, $field ) {

		if( isset( $field['password'] ) ) {
			$type = 'password';
		} else {
			$type = 'text';
		}

		echo '<input id="' . $id . '" class="form-control ' . $field['class'] . '" type="' . $type . '" name="wpf_options[' . $id . ']" value="' . esc_attr( $this->options[ $id ] ) . '">';

		if ( $this->options['connection_configured'] == true ) {

			echo '<a id="test-connection" data-post-fields="' . implode( ',', $field['post_fields'] ) . '" class="btn btn-success wpf-tip right" data-tip="' . sprintf( __( 'Refresh all custom fields and available tags from %s. Does not modify any user data or permissions.', 'wp-fusion'), wp_fusion()->crm->name ) . '">' . __( 'Resynchronize Available Tags &amp; Fields', 'wp-fusion' ) . '</a>';

		} else {

			echo '<a id="test-connection" data-post-fields="' . implode( ',', $field['post_fields'] ) . '" class="btn btn-default">' . __( 'Connect', 'wp-fusion' ) . '</a>';

		}

	}

	/**
	 * Close out API validate field
	 *
	 * @access  public
	 * @since   1.0
	 */

	public function show_field_api_validate_end( $id, $field ) {

		if ( ! empty( $field['desc'] ) ) {
			echo '<span class="description">' . $field['desc'] . '</span>';
		}
		echo '</td>';
		echo '</tr>';

		echo '</table><div id="connection-output"></div>';
		echo '</div>'; // close CRM div
		echo '<table class="form-table">';

	}

	/**
	 * Opens EDD license field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_edd_license_begin( $id, $field ) {
		echo '<tr valign="top">';
		echo '<th scope="row"><label for="' . $id . '">' . $field['title'] . '</label></th>';
		echo '<td>';
	}


	/**
	 * Displays EDD license field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_edd_license( $id, $field ) {

		if ( $field['license_status'] == "invalid" ) {
			$type = 'text';
		} else {
			$type = 'password';
		}

		echo '<input id="' . $id . '" class="form-control" ' . ( $field['license_status'] == 'valid' ? 'disabled' : '' ) . ' type="' . $type . '" name="wpf_options[' . $id . ']" placeholder="' . $field['std'] . '" value="' . esc_attr( $this->options[ $id ] ) . '">';

		if ( $field['license_status'] == "invalid" ) {
			echo '<a id="edd-license" data-action="edd_activate" class="btn btn-default">Activate License</a>';
		} else {
			echo '<a id="edd-license" data-action="edd_deactivate" class="btn btn-default">Deactivate License</a>';
		}
		echo '<span class="description">Enter your license key for automatic updates and support.</span>';
		echo '<div id="connection-output-edd"></div>';
	}

	/**
	 * Displays import users field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_import_users( $id, $field ) {

		if ( empty( $this->options[ $id ] ) ) {
			$this->options[ $id ] = array();
		}

		$args = array(
			'meta_name' 	=> 'wpf_options',
			'field_id'		=> $id,
			'placeholder'	=> __('Select Tag', 'wp-fusion'),
			'limit'			=> 1
		);

        wpf_render_tag_multiselect( $args );

		global $wp_roles;

		echo '<select class="select4" id="import_role" data-placeholder="Select a user role">';

		echo '<option></option>';

		foreach ( $wp_roles->role_names as $role => $name ) {

			// Set subscriber as default
			echo '<option ' . selected( 'Subscriber', $name, false ) . ' value="' . $role . '">' . $name . '</option>';

		}

		echo '</select>';

		echo '<a id="import-users-btn" class="btn btn-default">Import</a>';

	}

	/**
	 * Close import users field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_import_users_end() {

		echo '</td></tr></table>';
		echo '<div id="import-output"></div>';
		echo '<table class="form-table">';

	}

	/**
	 * Shows assign tags field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_assign_tags( $id, $field ) {

		if ( empty( $this->options[ $id ] ) ) {
			$this->options[ $id ] = array();
		}

		if( ! isset($field['placeholder'] ) ) {
			$field['placeholder'] = __( 'Select tags', 'wp-fusion' );
		}

		if( ! isset( $field['limit'] ) ) {
			$field['limit'] = null;
		}

		$args = array(
			'setting' 		=> $this->options[ $id ],
			'meta_name'		=> 'wpf_options',
			'field_id'		=> $id,
			'placeholder'	=> $field['placeholder'],
			'disabled'		=> false,
			'limit'			=> $field['limit']
		);

		wpf_render_tag_multiselect( $args );

	}

	/**
	 * Validate field assign tags
	 *
	 * @access public
	 * @return array Input
	 */

	public function validate_field_assign_tags( $input, $setting, $options ) {

		if( ! empty( $input ) ) {
			$input = stripslashes_deep( $input );
		}

		return $input;

	}


	/**
	 * Displays a single CRM field
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_crm_field( $id, $field ) {

		if ( empty( $this->options[ $id ] ) ) {
			$this->options[ $id ] = array( 'crm_field' => false );
		}

		wpf_render_crm_field_select( $this->options[ $id ]['crm_field'], 'wpf_options', $id );

	}

	/**
	 * Opening for contact fields table
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_contact_fields_begin( $id, $field ) {

		if( ! isset( $field['disabled'] ) ) {
			$field['disabled'] = false;
		}

		echo '<tr valign="top"' . ( $field['disabled'] ? ' class="disabled"' : '' ) . '>';
		echo '<td>';
	}


	/**
	 * Displays contact fields table
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_contact_fields( $id, $field ) {

		// Lets group contact fields by integration if we can

		$field_groups = array(
			'wordpress' => array(
				'title'		=> __( 'Standard WordPress Fields', 'wp-fusion' ),
				'fields'	=> array()
				),
			'leadsource' => array(
				'title'		=> __( 'Google Analytics and Lead Source Tracking', 'wp-fusion' ),
				'fields'	=> array()
				),
			);

		$field_groups = apply_filters( 'wpf_meta_field_groups', $field_groups );

		// Append ungrouped fields

		$field_groups['extra'] = array(
			'title'  => __( 'Additional <code>wp_usermeta</code> Table Fields (For Developers)', 'wp-fusion' ),
			'fields' => array(),
		);

		// Rebuild fields array into group structure
		foreach( $field['choices'] as $meta_key => $data ) {

			if( isset( $data['group'] ) && isset( $field_groups[ $data['group'] ] ) ) {

				$field_groups[ $data['group'] ]['fields'][$meta_key] = $data;

			} else {

				$field_groups['extra']['fields'][$meta_key] = $data;

			}

		}

		if( $this->options['hide_additional'] == true ) {

			foreach( $field_groups['extra']['fields'] as $key => $data ) {

				if( ! isset( $data['active'] ) || $data['active'] != true ) {
					unset( $field_groups['extra']['fields'][ $key ] );
				}

			}

		}

		$field_types = array(
			'text',
			'date',
			'multiselect',
			'capabilities',
			'checkbox',
			'state',
			'country',
			'int',
		);

		echo '<p class="description">' . sprintf( __( 'For more information on these settings, %1$ssee our documentation%2$s.', 'wp-fusion' ), '<a href="https://wpfusion.com/documentation/getting-started/syncing-contact-fields/" target="_blank">', '</a>' ) . '</p>';
		echo '<br />';

		// Display contact fields table

		echo '<table id="contact-fields-table" class="table table-hover">';

		echo '<thead>';
		echo '<tr>';
		echo '<th class="sync">' . __( 'Sync', 'wp-fusion' ) . '</th>';
		echo '<th>' . __( 'Name', 'wp-fusion' ) . '</th>';
		echo '<th>' . __( 'Meta Field', 'wp-fusion' ) . '</th>';
		echo '<th>' . __( 'Type', 'wp-fusion' ) . '</th>';
		echo '<th>' . sprintf( __( '%s Field', 'wp-fusion' ), wp_fusion()->crm->name ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		foreach( $field_groups as $group => $group_data ) {

			if ( empty( $group_data['fields'] ) && $group != 'extra' ) {
				continue;
			}

			// Output group section headers
			if( empty( $group_data['title'] ) ) {
				$group_data['title'] = 'none';
			}

			$group_slug = strtolower(str_replace(' ', '-', $group_data['title']));

			if( ! isset( $this->options['table_headers'][ $group_slug ] ) ) {
				$this->options['table_headers'][ $group_slug ] = false;
			}

			if ( 'standard-wordpress-fields' != $group_slug ) { // Skip the first one

				echo '<tbody class="labels">';
					echo '<tr class="group-header"><td colspan="5">';
						echo '<label for="' . $group_slug . '" class="group-header-title ' . ($this->options['table_headers'][ $group_slug ] == true ? "collapsed" : "") . '">' . $group_data['title'] . '<i class="fa fa-angle-down"></i><i class="fa fa-angle-up"></i></label><input type="checkbox" ' . checked( $this->options['table_headers'][ $group_slug ], true, false ) . ' name="wpf_options[table_headers][' . $group_slug . ']" id="' . $group_slug . '" data-toggle="toggle">';
					echo '</td></tr>';
				echo '</tbody>';

			}

			echo '<tbody class="table-collapse ' . ($this->options['table_headers'][ $group_slug ] == true ? "hide" : "") . '">';

			foreach ( $group_data['fields'] as $user_meta => $data ) {

				// Allow hiding for internal fields
				if ( isset( $data['hidden'] ) ) {
					continue;
				}

				if( empty($this->options[ $id ][ $user_meta ] ) || ! isset( $this->options[ $id ][ $user_meta ]['crm_field'] ) || ! isset( $this->options[ $id ][ $user_meta ]['active'] ) ) {
					$this->options[ $id ][ $user_meta ] = array('active' => false, 'crm_field' => false);
				}

				echo '<tr' . ( $this->options[ $id ][ $user_meta ]['active'] == true ? ' class="success" ' : '' ) . '>';
				echo '<td><input class="checkbox contact-fields-checkbox"' . ( empty( $this->options[ $id ][ $user_meta ]['crm_field'] ) ? ' disabled' : '' ) . ' type="checkbox" id="wpf_cb_' . $user_meta . '" name="wpf_options[' . $id . '][' . $user_meta . '][active]" value="1" ' . checked( $this->options[ $id ][ $user_meta ]['active'], 1, false ) . '/></td>';
				echo '<td class="wp_field_label">' . ( isset( $data['label'] ) ? $data['label'] : '' );

				if ( 'user_pass' == $user_meta ) {

					$pass_message  = 'It is <em>strongly</em> recommended to leave this field disabled from sync. If it\'s enabled: <br /><br />';
					$pass_message .= '1. Real user passwords will be synced in plain text to ' . wp_fusion()->crm->name . ' when a user registers or changes their password. This is a security issue and may be illegal in your jurisdiction.<br /><br />';
					$pass_message .= '2. User passwords will be loaded from ' . wp_fusion()->crm->name . ' when webhooks are received. If not set up correctly this could result in your users\' passwords being unexpectedly reset, and/or password reset links failing to work.<br /><br />';
					$pass_message .= 'If you are importing users from ' . wp_fusion()->crm->name . ' via a webhook and wish to store their auto-generated password in a custom field, it is sufficient to check the box for <strong>Return Password</strong> on the General settings tab. You can leave this field disabled from syncing.';

					echo ' <i class="fa fa-question-circle wpf-tip right" data-tip="' . $pass_message . '"></i>';
				}

				echo '</td>';
				echo '<td><span class="label label-default">' . $user_meta . '</span></td>';
				echo '<td class="wp_field_type">';

					if( ! isset( $data['type'] ) ) {
						$data['type'] = 'text';
					}

					// Allow overriding types via dropdown
					if ( ! empty( $this->options['contact_fields'][$user_meta]['type'] ) ) {
						$data['type'] = $this->options['contact_fields'][$user_meta]['type'];
					}

					if( ! in_array( $data['type'], $field_types ) ) {
						$field_types[] = $data['type'];
					}

					asort( $field_types );

					echo '<select class="wpf_type" name="wpf_options[' . $id . '][' . $user_meta . '][type]">';

					foreach( $field_types as $type ) {
						echo '<option value="' . $type . '" ' . selected( $data['type'], $type, false ) . '>' . $type . '</option>';
					}

				echo '<td>';

				wpf_render_crm_field_select( $this->options[ $id ][ $user_meta ]['crm_field'], 'wpf_options', 'contact_fields', $user_meta );

				echo '</td>';

				echo '</tr>';

			}

		}

		// Add new

		echo '<tr>';
		echo '<td><input class="checkbox contact-fields-checkbox" type="checkbox" disabled id="wpf_cb_new_field" name="wpf_options[contact_fields][new_field][active]" value="1" /></td>';
		echo '<td class="wp_field_label">Add new field</td>';
		echo '<td><input type="text" id="wpf-add-new-field" name="wpf_options[contact_fields][new_field][key]" placeholder="New Field Key" /></td>';
		echo '<td class="wp_field_type">';

		echo '<select class="wpf_type" name="wpf_options[contact_fields][new_field][type]">';

		foreach ( $field_types as $type ) {
			echo '<option value="' . $type . '" ' . selected( 'text', $type, false ) . '>' . $type . '</option>';
		}

		echo '<td>';

		wpf_render_crm_field_select( false, 'wpf_options', 'contact_fields', 'new_field' );

		echo '</td>';

		echo '</tr>';

		echo '</tbody>';

		echo '</table>';
	}


	/**
	 * Displays import groups table
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_import_groups( $id, $field ) {

		if( ! class_exists('WPF_User') ) {
			return;
		}

		$import_groups = get_option( 'wpf_import_groups' );

		if ( $import_groups == false ) {
			return;
		}

		echo '<table id="import-groups" class="table table-hover">';

		echo '<thead>';
		echo '<tr">';
		echo '<th class="import-date">Date</th>';
		echo '<th>Tag(s)</th>';
		echo '<th>Role</th>';
		echo '<th>Contacts</th>';
		echo '<th>Delete</th>';

		echo '</tr>';
		echo '</thead>';

		foreach ( $import_groups as $date => $data ) {

			// Fix for old import groups with dash in name
			if ( isset( $data['params']->{'import_users-tags'} ) ) {
				$tags = $data['params']->{'import_users-tags'};
			} elseif ( isset( $data['params']->{'import_users'} ) ) {
				$tags = $data['params']->{'import_users'};
			}

			if ( isset( $tags ) ) {

				$tag_labels = array();

				foreach ( $tags as $id ) {
					$tag_labels[] = wp_fusion()->user->get_tag_label( $id );
				}

			} else {
				$tags = 'All Contacts';
			}

			global $wp_roles;

			echo '<tr class="import-group-row">';
			echo '<td class="import-date">' . date( 'n/j/Y g:ia', $date ) . '</td>';
			echo '<td>' . implode( ', ', $tag_labels ) . '</td>';
			echo '<td>' . ( isset( $data['role'] ) && isset( $wp_roles->roles[ $data['role'] ] ) ? $wp_roles->roles[ $data['role'] ]['name'] : 'Unknown' ) . '</td>';
			echo '<td>' . count( $data['user_ids'] ) . '</td>';
			echo '<td>';
			echo '<a class="delete-import-group btn btn-default btn-sm" data-delete="' . $date . '">Delete</a>';
			echo '</td>';
			echo '</tr>';

		}

		echo '</table>';
	}


	/**
	 * Displays export options
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_export_options( $id, $field ) {

		$options = apply_filters( 'wpf_export_options', array() );

		foreach ( $options as $key => $data ) {

			echo '<input class="radio export-options" data-title="' . $data['title'] . '" type="radio" id="export_' . $key . '" name="export_options" value="' . $key . '" />';
			echo '<label for="export_' . $key . '">' . $data['label'];

			if(isset($data['tooltip'])) {
				echo '<i class="fa fa-question-circle wpf-tip right" data-tip="' . $data['tooltip'] . '"></i>';
			}

			echo '</label><br />';

		}

		echo '<br /><br /><a id="export-btn" class="btn btn-default">' . __( 'Create Background Task', 'wp-fusion' ) . '</a><br />';

	}


	/**
	 * Displays webhooks test button
	 *
	 * @access public
	 * @return mixed
	 */

	public function show_field_test_webhooks( $id, $field ) {

		echo '<a class="button" data-url="' . home_url() . '" id="test-webhooks-btn" href="#">Test Webhooks</a>';

	}

	/**
	 * Show resync button in header
	 *
	 * @access  public
	 * @since   3.33.17
	 */

	public function header_resync_button() {

		if ( $this->options['connection_configured'] == true ) {

			echo '<a id="header-resync" style="margin-left: 10px; position: relative;" class="btn btn-success wpf-tip right" data-tip="' . sprintf( __( 'Refresh all custom fields and available tags from %s. Does not modify any user data or permissions.', 'wp-fusion'), wp_fusion()->crm->name ) . '"><span class="dashicons dashicons-update-alt"></span></a>';

		}

	}

	/**
	 * Validation for contact field data
	 *
	 * @access public
	 * @return mixed
	 */

	public function validate_field_contact_fields( $input, $setting, $options_class ) {

		foreach ( $input as $field => $data ) {

			if ( ! isset( $data['active'] ) ) {
				$input[ $field ]['active'] = 0;
			}

		}

		if( ! empty( $input['user_email'] ) ) {

			if ( $input['user_email']['active'] != true || empty( $input['user_email']['crm_field'] ) ) {
				$options_class->errors[] = '<strong>Error:</strong> The field user_email must be enabled for sync, please enable it from the Contact Fields tab.';
			}

		}

		// New fields
		if ( ! empty( $input['new_field']['key'] ) ) {

			$input[ $input['new_field']['key'] ] = array(
				'active'    => true,
				'label'     => '(custom) ' . $input['new_field']['key'],
				'type'      => $input['new_field']['type'],
				'crm_field' => $input['new_field']['crm_field'],
			);

		}

		unset( $input['new_field'] );

		$input = apply_filters( 'wpf_contact_fields_save', $input );

		return $input;

	}

}