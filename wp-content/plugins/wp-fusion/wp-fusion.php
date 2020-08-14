<?php

/**
 * Plugin Name: WP Fusion
 * Description: WP Fusion connects your website to your CRM, with support for several CRMs and dozens of plugins.
 * Plugin URI: https://wpfusion.com/
 * Version: 3.33.18
 * Author: Very Good Plugins
 * Author URI: https://verygoodplugins.com/
 * Text Domain: wp-fusion
 *
 * WC requires at least: 3.0
 * WC tested up to: 4.3.1
 */

/**
 * @copyright Copyright (c) 2018. All rights reserved.
 *
 * @license   Released under the GPL license http://www.opensource.org/licenses/gpl-license.php
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

define( 'WP_FUSION_VERSION', '3.33.18' );

// deny direct access
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


final class WP_Fusion {

	/** Singleton *************************************************************/

	/**
	 * @var WP_Fusion The one true WP_Fusion
	 * @since 1.0
	 */
	private static $instance;


	/**
	 * Contains all active integrations classes
	 *
	 * @since 3.0
	 */
	public $integrations;


	/**
	 * Manages configured CRMs
	 *
	 * @var WPF_CRM_Base
	 * @since 2.0
	 */
	public $crm_base;


	/**
	 * Access to the currently selected CRM
	 *
	 * @var crm
	 * @since 2.0
	 */
	public $crm;


	/**
	 * Handler for AJAX and and asynchronous functions
	 *
	 * @var crm
	 * @since 2.0
	 */
	public $ajax;


	/**
	 * Handler for batch processing
	 *
	 * @var batch
	 * @since 3.0
	 */
	public $batch;


	/**
	 * Logging and diagnostics class
	 *
	 * @var logger
	 * @since 3.0
	 */
	public $logger;


	/**
	 * User handler - registration, sync, and updates
	 *
	 * @var WPF_User
	 * @since 2.0
	 */
	public $user;


	/**
	 * Stores configured admin meta boxes and other admin interfaces
	 *
	 * @var WPF_Admin_Interfaces
	 * @since 2.0
	 */
	public $admin_interfaces;


	/**
	 * Handles restricted content and redirects
	 *
	 * @var WPF_Access_Control
	 * @since 3.12
	 */
	public $access;


	/**
	 * Handles auto login sessions
	 *
	 * @var WPF_Auto_Login
	 * @since 3.12
	 */
	public $auto_login;


	/**
	 * Handles lead source tracking
	 *
	 * @var WPF_Lead_Sources
	 * @since 3.30.4
	 */
	public $lead_source_tracking;


	/**
	 * The settings instance variable
	 *
	 * @var WPF_Settings
	 * @since 1.0
	 */
	public $settings;


	/**
	 * Main WP_Fusion Instance
	 *
	 * Insures that only one instance of WP_Fusion exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0
	 * @static
	 * @static var array $instance
	 * @return WP_Fusion The one true WP_Fusion
	 */

	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Fusion ) ) {

			self::$instance = new WP_Fusion();

			self::$instance->setup_constants();

			// If PHP version not met
			if ( ! self::$instance->check_install() ) {
				add_action( 'admin_notices', array( self::$instance, 'php_version_notice' ) );
			}

			self::$instance->init_includes();

			// Create settings
			self::$instance->settings = new WPF_Settings();

			// Load active CRM
			self::$instance->init_crm();

			// Only useful if a CRM is selected and valid
			if ( ! empty( self::$instance->crm ) ) {

				self::$instance->setup_crm_constants();
				self::$instance->includes();

				self::$instance->logger               = new WPF_Log_Handler();
				self::$instance->user                 = new WPF_User();
				self::$instance->lead_source_tracking = new WPF_Lead_Source_Tracking();
				self::$instance->access               = new WPF_Access_Control();
				self::$instance->auto_login           = new WPF_Auto_Login();
				self::$instance->ajax                 = new WPF_AJAX();
				self::$instance->batch                = new WPF_Batch();

				add_action( 'plugins_loaded', array( self::$instance, 'integrations_includes' ), 10 ); // This has to be 10 for Elementor
				add_action( 'after_setup_theme', array( self::$instance, 'integrations_includes_theme' ) );

				if ( self::$instance->is_full_version() ) {
					add_action( 'after_setup_theme', array( self::$instance, 'updater' ), 20 );
				}
			}

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

		}

		return self::$instance;

	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @access protected
	 * @return void
	 */

	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-fusion' ), WP_FUSION_VERSION );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access protected
	 * @return void
	 */

	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-fusion' ), WP_FUSION_VERSION );
	}

	/**
	 * Setup plugin constants
	 *
	 * @access private
	 * @return void
	 */

	private function setup_constants() {

		if ( ! defined( 'WPF_MIN_WP_VERSION' ) ) {
			define( 'WPF_MIN_WP_VERSION', '4.0' );
		}

		if ( ! defined( 'WPF_MIN_PHP_VERSION' ) ) {
			define( 'WPF_MIN_PHP_VERSION', '5.6' );
		}

		if ( ! defined( 'WPF_DIR_PATH' ) ) {
			define( 'WPF_DIR_PATH', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'WPF_PLUGIN_PATH' ) ) {
			define( 'WPF_PLUGIN_PATH', plugin_basename( __FILE__ ) );
		}

		if ( ! defined( 'WPF_DIR_URL' ) ) {
			define( 'WPF_DIR_URL', plugin_dir_url( __FILE__ ) );
		}

		if ( ! defined( 'WPF_STORE_URL' ) ) {
			define( 'WPF_STORE_URL', 'https://wpfusion.com' );
		}

	}

	/**
	 * Check min PHP version
	 *
	 * @access private
	 * @return bool
	 */

	private function check_install() {

		if ( version_compare( phpversion(), WPF_MIN_PHP_VERSION, '>=' ) ) {
			return true;
		} else {
			return false;
		}

	}


	/**
	 * Setup CRM related constants
	 *
	 * @access private
	 * @return void
	 */

	private function setup_crm_constants() {

		if ( ! defined( 'WPF_CRM_NAME' ) ) {
			define( 'WPF_CRM_NAME', self::$instance->crm->name );
		}

	}


	/**
	 * Defines default supported plugin integrations
	 *
	 * @access public
	 * @return array Integrations
	 */

	public function get_integrations() {

		return apply_filters(
			'wpf_integrations', array(
				'edd'                    => 'Easy_Digital_Downloads',
				'edd-recurring'          => 'EDD_Recurring',
				'gravity-forms'          => 'GFForms',
				'formidable-forms'       => 'FrmFormsController',
				'woocommerce'            => 'WooCommerce',
				'woo-subscriptions'      => 'WC_Subscriptions',
				'woo-memberships'        => 'WC_Memberships',
				'woo-bookings'           => 'WC_Bookings',
				'woo-coupons'            => 'WC_Smart_Coupons',
				'woo-deposits'           => 'WC_Deposits',
				'woo-addons'             => 'WC_Product_Addons',
				'ultimate-member-1x'     => 'UM_API',
				'ultimate-member'        => 'UM',
				'userpro'                => 'userpro_api',
				'acf'                    => 'ACF',
				'acf'                    => 'acf',
				'learndash'              => 'SFWD_LMS',
				'wpep'                   => 'WPEP\Controller',
				'sensei'                 => 'WooThemes_Sensei',
				'bbpress'                => 'bbPress',
				'contact-form-7'         => 'wpcf7',
				'membermouse'            => 'MemberMouse',
				'memberpress'            => 'MeprBaseCtrl',
				'buddypress'             => 'BuddyPress',
				'pmpro'                  => 'MemberOrder',
				'restrict-content-pro'   => 'RCP_Capabilities',
				'lifterlms'              => 'LifterLMS',
				's2member'               => 'c_ws_plugin__s2member_utilities',
				'affiliate-wp'           => 'Affiliate_WP',
				'thrive-apprentice'      => 'TVA_Const',
				'wp-job-manager'         => 'WP_Job_Manager',
				'user-meta'              => 'UserMeta\\SupportModel',
				'simple-membership'      => 'SimpleWpMembership',
				'badgeos'                => 'BadgeOS',
				'tribe-tickets'          => 'Tribe__Tickets__Main',
				'wishlist-member'        => 'WishListMember',
				'cred'                   => 'CRED_CRED',
				'mycred'                 => 'myCRED_Core',
				'learnpress'             => 'LearnPress',
				'courseware'             => 'WPCW_Requirements',
				'gamipress'              => 'GamiPress',
				'peepso'                 => 'PeepSo',
				'profilepress'           => 'ProfilePress_Dir',
				'beaver-builder'         => 'FLBuilder',
				'elementor'              => 'Elementor\\Frontend',
				'elementor-forms'        => 'ElementorPro\Modules\Forms\Classes\Integration_Base',
				'elementor-popups'       => 'ElementorPro\\Plugin',
				'wplms'                  => 'BP_Course_Component',
				'profile-builder'        => 'WPPB_Add_General_Notices',
				'accessally'             => 'AccessAlly',
				'wpml'                   => 'SitePress',
				'divi'                   => 'et_setup_theme',
				'divi'                   => 'ET_Builder_Plugin',
				'weglot'                 => 'WeglotWP\\Bootstrap_Weglot',
				'wp-complete'            => 'WPComplete',
				'wpforms'                => 'WPForms',
				'popup-maker'            => 'Popup_Maker',
				'wpforo'                 => 'wpForo',
				'give'                   => 'Give',
				'ninja-forms'            => 'NF_Abstracts_Action',
				'advanced-ads'           => 'Advanced_Ads',
				'clean-login'            => 'clean_login_show',
				'private-messages'       => 'Private_Messages',
				'coursepress'            => 'CoursePress',
				'event-espresso'         => 'EE_Base',
				'fooevents'              => 'FooEvents',
				'convert-pro'            => 'Cp_V2_Loader',
				'woo-memberships-teams'  => 'WC_Memberships_For_Teams_Loader',
				'woo-wholesale-lead'     => 'WooCommerce_Wholesale_Lead_Capture',
				'caldera-forms'          => 'Caldera_Forms',
				'wp-affiliate-manager'   => 'WPAM_Plugin',
				'wcff'                   => 'Wcff',
				'gtranslate'             => 'GTranslate',
				'tutor-lms'              => 'tutor_lms',
				'translatepress'         => 'TRP_Translate_Press',
				'edd-software-licensing' => 'EDD_Software_Licensing',
				'cartflows'              => 'Cartflows_Loader',
				'memberium'              => 'memberium',
				'uncanny-groups'         => 'uncanny_learndash_groups\\InitializePlugin',
				'salon-booking'          => 'SLN_Plugin',
				'cpt-ui'                 => 'cptui_load_ui_class',
				'ahoy'                   => 'Ahoy',
				'wppizza'                => 'WPPIZZA',
				'users-insights'          => 'USIN_Manager',
				'e-signature'            => 'WP_E_Digital_Signature',
				'fluent-forms'           => 'FluentForm\Framework\Foundation\Bootstrap',
				'toolset'                => 'Types_Autoloader',
				'wp-event-manager'       => 'WP_Event_Manager_Registrations',
				'gravityview'            => 'GravityView_Plugin',
				'facetwp'                => 'FacetWP',
				'share-logins-pro'       => 'codexpert\Share_Logins_Pro\Plugin',
				'bp-account-deactivator' => 'BP_Account_Deactivator',
				'wp-ultimo'              => 'WP_Ultimo',
				'edd-custom-prices'      => 'edd_cp_has_custom_pricing',
				'oxygen'                 => 'oxygen_vsb_register_condition',
				'woo-request-a-quote'    => 'Addify_Request_For_Quote',
				'wcs-att'                => 'WCS_ATT',
				'refer-a-friend'         => 'WPGens_RAF',
				'simple-pay'             => 'SimplePay\Core\SimplePay',
				'wcs-gifting'            => 'WCS_Gifting',
				'events-manager'         => 'EM_Object',
				'wp-members'             => 'wpmem_init',
				'woo-shipment-tracking'  => 'WC_Shipment_Tracking',
				'pods'                   => 'Pods',
				'beaver-themer'          => 'FLThemeBuilderLoader',
				'woo-appointments'       => 'WC_Appointments',
				'wp-crowdfunding'        => 'WPCF\Crowdfunding',
				'modern-events-calendar' => 'MEC',
			)
		);

	}

	/**
	 * Defines default supported theme integrations
	 *
	 * @access public
	 * @return array Integrations
	 */

	public function get_integrations_theme() {

		return apply_filters(
			'wpf_integrations_theme', array(
				'divi'      => 'et_setup_theme',
				'memberoni' => 'memberoni_llms_theme_support',
				'acf'       => 'acf', // For ACF bundled with Memberoni or other themes
			)
		);

	}

	/**
	 * Defines supported CRMs
	 *
	 * @access private
	 * @return array CRMS
	 */

	public function get_crms() {

		return apply_filters(
			'wpf_crms', array(
				'infusionsoft'   => 'WPF_Infusionsoft_iSDK',
				'activecampaign' => 'WPF_ActiveCampaign',
				'ontraport'      => 'WPF_Ontraport',
				'drip'           => 'WPF_Drip',
				'convertkit'     => 'WPF_ConvertKit',
				'agilecrm'       => 'WPF_AgileCRM',
				'salesforce'     => 'WPF_Salesforce',
				'mautic'         => 'WPF_Mautic',
				'intercom'       => 'WPF_Intercom',
				'aweber'         => 'WPF_AWeber',
				'mailerlite'     => 'WPF_MailerLite',
				'capsule'        => 'WPF_Capsule',
				'zoho'           => 'WPF_Zoho',
				'kartra'         => 'WPF_Kartra',
				'userengage'     => 'WPF_UserEngage',
				'convertfox'     => 'WPF_ConvertFox',
				'salesflare'     => 'WPF_Salesflare',
				//'vtiger'         => 'WPF_Vtiger',
				'flexie'         => 'WPF_Flexie',
				'tubular'        => 'WPF_Tubular',
				'maropost'       => 'WPF_Maropost',
				'mailchimp'      => 'WPF_MailChimp',
				'sendinblue'     => 'WPF_SendinBlue',
				'hubspot'        => 'WPF_HubSpot',
				'platformly'     => 'WPF_Platformly',
				'drift'          => 'WPF_Drift',
				'staging'        => 'WPF_Staging',
				'autopilot'      => 'WPF_Autopilot',
				'customerly'     => 'WPF_Customerly',
				'copper'         => 'WPF_Copper',
				'nationbuilder'  => 'WPF_NationBuilder',
				'groundhogg'     => 'WPF_Groundhogg',
				'mailjet'        => 'WPF_Mailjet',
				'sendlane'       => 'WPF_Sendlane',
				'getresponse'    => 'WPF_GetResponse',
				'mailpoet'       => 'WPF_MailPoet',
				'klaviyo'        => 'WPF_Klaviyo',
				'birdsend'       => 'WPF_BirdSend',
				'zerobscrm'      => 'WPF_ZeroBSCRM',
				'mailengine'     => 'WPF_MailEngine',
				'klick-tipp'     => 'WPF_KlickTipp',
				'sendfox'        => 'WPF_SendFox',
				'quentn'         => 'WPF_Quentn',
				'loopify'        => 'WPF_Loopify',
				'wp-erp'         => 'WPF_WP_ERP',
			)
		);

	}

	/**
	 * Include required files
	 *
	 * @access private
	 * @return void
	 */

	private function init_includes() {

		// Functions
		require_once WPF_DIR_PATH . 'includes/functions.php';

		// Settings
		require_once WPF_DIR_PATH . 'includes/admin/class-settings.php';

		// CRM base class
		require_once WPF_DIR_PATH . 'includes/crms/class-base.php';

		if ( is_admin() ) {

			require_once WPF_DIR_PATH . 'includes/admin/class-notices.php';
			require_once WPF_DIR_PATH . 'includes/admin/admin-functions.php';
			require_once WPF_DIR_PATH . 'includes/admin/class-admin-interfaces.php';

			self::$instance->admin_interfaces = new WPF_Admin_Interfaces();

		}

	}

	/**
	 * Includes classes applicable for after the connection is configured
	 *
	 * @access private
	 * @return void
	 */

	private function includes() {

		require_once WPF_DIR_PATH . 'includes/admin/logging/class-log-handler.php';
		require_once WPF_DIR_PATH . 'includes/class-user.php';
		require_once WPF_DIR_PATH . 'includes/class-lead-source-tracking.php';
		require_once WPF_DIR_PATH . 'includes/class-ajax.php';
		require_once WPF_DIR_PATH . 'includes/class-access-control.php';
		require_once WPF_DIR_PATH . 'includes/class-auto-login.php';
		require_once WPF_DIR_PATH . 'includes/admin/class-batch.php';
		require_once WPF_DIR_PATH . 'includes/admin/gutenberg/class-gutenberg.php';

		if ( is_admin() && $this->is_full_version() ) {

			require_once WPF_DIR_PATH . 'includes/admin/class-updater.php';

		} else {

			require_once WPF_DIR_PATH . 'includes/class-shortcodes.php';

		}

		// Admin bar tools
		if ( ! is_admin() && self::$instance->settings->get( 'enable_admin_bar', true ) == true ) {
			require_once WPF_DIR_PATH . 'includes/admin/class-admin-bar.php';
		}

		if ( $this->is_full_version() ) {

			require_once WPF_DIR_PATH . 'includes/class-api.php';

		}

	}

	/**
	 * Initialize the CRM object based on the currently configured options
	 *
	 * @access private
	 * @return object CRM Interface
	 */

	public function init_crm() {

		self::$instance->crm_base = new WPF_CRM_Base();
		self::$instance->crm      = self::$instance->crm_base->crm;

		return self::$instance->crm;

	}

	/**
	 * Includes plugin integrations after all plugins have loaded
	 *
	 * @access private
	 * @return void
	 */

	public function integrations_includes() {

		// Integrations base
		require_once WPF_DIR_PATH . 'includes/integrations/class-base.php';

		// Store integrations for public access
		self::$instance->integrations = new stdClass();

		// Integrations autoloader
		foreach ( wp_fusion()->get_integrations() as $filename => $dependency_class ) {

			if ( class_exists( $dependency_class ) || function_exists( $dependency_class ) ) {

				if ( file_exists( WPF_DIR_PATH . 'includes/integrations/class-' . $filename . '.php' ) ) {
					require_once WPF_DIR_PATH . 'includes/integrations/class-' . $filename . '.php';
				}
			}
		}

	}

	/**
	 * Includes theme integrations after all theme has loaded
	 *
	 * @access private
	 * @return void
	 */

	public function integrations_includes_theme() {

		// Integrations base
		require_once WPF_DIR_PATH . 'includes/integrations/class-base.php';

		// Integrations autoloader
		foreach ( wp_fusion()->get_integrations_theme() as $filename => $dependency_class ) {

			if ( class_exists( $dependency_class ) || function_exists( $dependency_class ) ) {

				if ( file_exists( WPF_DIR_PATH . 'includes/integrations/class-' . $filename . '.php' ) ) {
					require_once WPF_DIR_PATH . 'includes/integrations/class-' . $filename . '.php';
				}
			}
		}

	}

	/**
	 * Load internationalization files
	 *
	 * @access public
	 * @return void
	 */

	public function load_textdomain() {

		load_plugin_textdomain( 'wp-fusion', false, 'wp-fusion/languages' );

	}


	/**
	 * Check to see if this is WPF Lite or regular
	 *
	 * @access public
	 * @return bool
	 */

	public function is_full_version() {

		$integrations = $this->get_integrations();

		if ( ! empty( $integrations ) ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Set up EDD updater
	 *
	 * @access public
	 * @return void
	 */

	public function updater() {

		if ( ! is_admin() ) {
			return;
		}

		$license_key = $this->settings->get( 'license_key' );

		// Allow setting license key via wp-config.php

		if ( empty( $license_key ) && defined( 'WPF_LICENSE_KEY' ) ) {
			$license_key = WPF_LICENSE_KEY;
		}

		$license_status = $this->settings->edd_check_license( $license_key );

		if ( $license_status == 'valid' ) {

			// setup the updater
			$edd_updater = new WPF_Plugin_Updater(
				WPF_STORE_URL, __FILE__, array(
					'version'   => WP_FUSION_VERSION,      // current version number
					'license'   => $license_key,           // license key
					'item_name' => 'WP Fusion',            // name of this plugin
					'author'    => 'Very Good Plugins',    // author of this plugin
				)
			);

		} elseif ( $license_status == 'error' ) {

			global $pagenow;

			if ( 'plugins.php' === $pagenow ) {

				add_action( 'after_plugin_row_' . WPF_PLUGIN_PATH, array( self::$instance, 'wpf_update_message_error' ), 10, 3 );

			}

		} else {

			global $pagenow;

			if ( 'plugins.php' === $pagenow ) {

				add_action( 'after_plugin_row_' . WPF_PLUGIN_PATH, array( self::$instance, 'wpf_update_message' ), 10, 3 );

			}

		}

	}

	/**
	 * Call home when deactivated
	 *
	 * @access private
	 * @return void
	 */

	public static function deactivation() {

		$license_key = self::$instance->settings->get( 'license_key' );

		// Allow setting license key via wp-config.php

		if ( empty( $license_key ) && defined( 'WPF_LICENSE_KEY' ) ) {
			$license_key = WPF_LICENSE_KEY;
		}

		self::$instance->settings->edd_check_license( $license_key, 'plugin_deactivated' );

	}

	/**
	 * Display update message
	 *
	 * @access public
	 * @return void
	 */

	public function wpf_update_message( $plugin_file, $plugin_data, $status ) {

		echo '<tr class="plugin-update-tr active">';
		echo '<td colspan="3" class="plugin-update colspanchange">';
		echo '<div class="update-message notice inline notice-warning notice-alt">';
		echo '<p>Your WP Fusion License key is currently inactive or expired. <a href="' . get_admin_url() . './options-general.php?page=wpf-settings#setup">Activate your license key</a> or <a href="https://wpfusion.com/" target="_blank">purchase a license</a> to enable automatic updates and support.</p>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';

	}

	/**
	 * Display license check error message
	 *
	 * @access public
	 * @return void
	 */

	public function wpf_update_message_error( $plugin_file, $plugin_data, $status ) {

		echo '<tr class="plugin-update-tr active">';
		echo '<td colspan="3" class="plugin-update colspanchange">';
		echo '<div class="update-message notice inline notice-warning notice-alt">';
		echo '<p>WP Fusion is unable to contact the update servers. Your web host may be running outdated software. Please <a href="https://wpfusion.com/support/contact/" target="_blank">contact support</a> for additional assistance.</p>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';

	}

	/**
	 * Returns error message and deactivates plugin when error returned.
	 *
	 * @access public
	 * @return mixed error message.
	 */

	public function php_version_notice() {

		echo '<div class="notice notice-error">';
		echo '<p><strong>Warning:</strong> WP Fusion requires at least PHP version ' . WPF_MIN_PHP_VERSION . ' in order to function properly. You are currently using PHP version ' . phpversion() . '. Please update your version of PHP, or contact your web host for assistance.</p>';
		echo '</div>';

	}



}

// Activation / deactivation

register_deactivation_hook( __FILE__, array( 'WP_Fusion', 'deactivation' ) );


/**
 * The main function responsible for returning the one true WP Fusion
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $wpf = wp_fusion(); ?>
 *
 * @return object The one true WP Fusion Instance
 */

if ( ! function_exists( 'wp_fusion' ) ) {

	function wp_fusion() {
		return WP_Fusion::instance();
	}

	// Get WP Fusion Running
	wp_fusion();

}
