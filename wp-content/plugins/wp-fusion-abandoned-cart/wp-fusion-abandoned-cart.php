<?php

/**
 * Plugin Name: WP Fusion - Abandoned Cart Addon
 * Description: Tracks abandoned carts and adds customer data to your CRM before checkout is complete.
 * Plugin URI: https://wpfusionplugin.com/
 * Version: 1.6.2
 * Author: Very Good Plugins
 * Author URI: http://verygoodplugins.com/
 * Text Domain: wp-fusion

 * WC requires at least: 3.0
 * WC tested up to: 4.0.1

*/

/**
 * @copyright Copyright (c) 2016. All rights reserved.
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

define( 'WPF_ABANDONED_CART_VERSION', '1.6.2' );

// deny direct access
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


final class WP_Fusion_Abandoned_Cart {

	/** Singleton *************************************************************/

	/**
	 * @var WP_Fusion_Abandoned_Cart The one true WP_Fusion_Abandoned_Cart
	 * @since 1.0
	 */
	private static $instance;

	/**
	 * The integrations handler instance variable
	 *
	 * @var WPF_Integrations
	 * @since 2.0
	 */
	public $integrations;


	/**
	 * The settings instance variable
	 *
	 * @var WP_Fusion_Settings
	 * @since 1.0
	 */
	public $settings;

	/**
	 * Manages configured CRMs
	 *
	 * @var WPF_CRMS
	 * @since 1.0
	 */

	public $crm_base;


	/**
	 * Access to the currently selected CRM
	 *
	 * @var crm
	 * @since 1.0
	 */

	public $crm;


	/**
	 * Main WP_Fusion_Abandoned_Cart Instance
	 *
	 * Insures that only one instance of WP_Fusion_Abandoned_Cart exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.0
	 * @static
	 * @staticvar array $instance
	 * @return The one true WP_Fusion_Abandoned_Cart
	 */

	public static function instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WP_Fusion_Abandoned_Cart ) ) {

			self::$instance = new WP_Fusion_Abandoned_Cart();
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->integrations_includes();

			self::$instance->crm_base = new WPF_Abandoned_Cart_CRM_Base();
			self::$instance->crm      = self::$instance->crm_base->crm;

			if ( is_admin() ) {
				self::$instance->settings = new WPF_Abandoned_Cart_Settings();
			}

			self::$instance->updater();

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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-fusion' ), '1.6' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @access protected
	 * @return void
	 */

	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-fusion' ), '1.6' );
	}

	/**
	 * Setup plugin constants
	 *
	 * @access private
	 * @return void
	 */

	private function setup_constants() {

		if ( ! defined( 'WPF_ABANDONED_CART_DIR_PATH' ) ) {
			define( 'WPF_ABANDONED_CART_DIR_PATH', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'WPF_ABANDONED_CART_PLUGIN_PATH' ) ) {
			define( 'WPF_ABANDONED_CART_PLUGIN_PATH', plugin_basename( __FILE__ ) );
		}

		if ( ! defined( 'WPF_ABANDONED_CART_DIR_URL' ) ) {
			define( 'WPF_ABANDONED_CART_DIR_URL', plugin_dir_url( __FILE__ ) );
		}

	}


	/**
	 * Defines default supported plugin integrations
	 *
	 * @access private
	 * @return array Integrations
	 */

	public function get_integrations() {

		return apply_filters(
			'wpf_abandoned_cart_integrations', array(
				'edd'         => 'Easy_Digital_Downloads',
				'woocommerce' => 'WooCommerce',
				'memberpress' => 'MeprBaseCtrl',
				'lifterlms'   => 'LifterLMS',
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
			'wpf_abanadoned_cart_crms', array(
				'drip'           => 'WPF_Abandoned_Cart_Drip',
				'activecampaign' => 'WPF_Abandoned_Cart_ActiveCampaign',
			)
		);

	}

	/**
	 * Include required files
	 *
	 * @access private
	 * @return void
	 */

	private function includes() {

		// Autoload CRMs
		require_once WPF_ABANDONED_CART_DIR_PATH . 'includes/crms/class-base.php';

		foreach ( $this->get_crms() as $filename => $integration ) {
			if ( file_exists( WPF_ABANDONED_CART_DIR_PATH . 'includes/crms/class-' . $filename . '.php' ) ) {
				require_once WPF_ABANDONED_CART_DIR_PATH . 'includes/crms/class-' . $filename . '.php';
			}
		}

		if ( is_admin() ) {
			require_once WPF_ABANDONED_CART_DIR_PATH . 'includes/admin/class-settings.php';
		}

	}

	/**
	 * Includes classes applicable for after the connection is configured
	 *
	 * @access private
	 * @return void
	 */

	private function integrations_includes() {

		// Autoload integrations
		require_once WPF_ABANDONED_CART_DIR_PATH . 'includes/integrations/class-base.php';

		foreach ( $this->get_integrations() as $filename => $dependency ) {

			if ( class_exists( $dependency ) && file_exists( WPF_ABANDONED_CART_DIR_PATH . 'includes/integrations/class-' . $filename . '.php' ) ) {
				require_once WPF_ABANDONED_CART_DIR_PATH . 'includes/integrations/class-' . $filename . '.php';
			}
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

		$license_status = wp_fusion()->settings->get( 'license_status' );
		$license_key    = wp_fusion()->settings->get( 'license_key' );

		if ( $license_status == 'valid' ) {

			// setup the updater
			$edd_updater = new WPF_Plugin_Updater(
				WPF_STORE_URL, __FILE__, array(
					'version' => WPF_ABANDONED_CART_VERSION,
					'license' => $license_key,
					'item_id' => 2707,
				)
			);

		} else {

			global $pagenow;

			if ( 'plugins.php' === $pagenow ) {
				add_action( 'after_plugin_row_' . WPF_ABANDONED_CART_PLUGIN_PATH, array( wp_fusion(), 'wpf_update_message' ), 10, 3 );
			}
		}

	}


}


/**
 * The main function responsible for returning the one true WP Fusion Abandoned Cart
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $wpf_ac = wp_fusion_abandoned_cart(); ?>
 *
 * @return object The one true WP Fusion Abandoned Cart Instance
 */

function wp_fusion_abandoned_cart() {

	if ( ! function_exists( 'wp_fusion' ) || wp_fusion()->settings->get( 'connection_configured' ) == false ) {
		return;
	}

	return WP_Fusion_Abandoned_Cart::instance();

}

add_action( 'plugins_loaded', 'wp_fusion_abandoned_cart', 100 );


