<?php

/*
 *
 * Plugin Name: Back In Stock Notifier for WooCommerce | WooCommerce Waitlist Pro
 * Plugin URI: https://codewoogeek.online/shop/free-plugins/back-in-stock-notifier/
 * Description: Notify subscribed buyers when products back in stock
 * Version: 1.9.9
 * Author: codewoogeek
 * Author URI: https://codewoogeek.online
 * Text Domain: cwginstocknotifier
 * Domain Path: /languages
 * WC requires at least: 2.2.0
 * WC tested up to: 4.1
 *
 * @package     cwginstocknotifier
 * @author      codewoogeek
 * @copyright   2020 CodeWooGeek eCommerce Solutions
 * @license     GPL-3.0+
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * @icons used from https://www.flaticon.com/authors/roundicons
 */

if (!defined('ABSPATH')) {
    exit; // avoid direct access to the file
}

if (isset($_GET['post_type']) && $_GET['post_type'] == 'cwginstocknotifier') {
    require('includes/library/WP_Persistent_Notices.php');
}
require_once 'includes/library/wp-async-request.php';
require_once 'includes/library/wp-background-process.php';

if (!class_exists('CWG_Instock_Notifier')) {

    class CWG_Instock_Notifier {

        /**
         *
         * @var string Version
         */
        public $version = '1.9.9';

        /**
         *
         * @var instance object
         */
        protected static $_instance = null;

        /**
         * @see CWG_Instock_Notifier()
         * @return object
         */
        public static function instance() {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * @return error when this function called as it is private method
         */
        private function __wakeup() {
            
        }

        /**
         * @return error when this function called as it is a private method, so clonning will be forbidden
         */
        private function __clone() {
            
        }

        /**
         * construct the class
         */
        public function __construct() {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            $this->avoid_header_sent();
            $this->define_constant();
            $this->initialize();
            $this->include_files();
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 999);
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            add_filter('woocommerce_screen_ids', array($this, 'add_screen_ids_to_woocommerce'));
            add_filter('admin_head', array($this, 'remove_help_tab_context'));
            add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
        }

        /**
         * Avoid Header already sent issue
         */
        public function avoid_header_sent() {
            ob_start();
        }

        /**
         * Include necessary files to load
         */
        public function include_files() {
            include('includes/admin/class-post-type.php');
            include('includes/frontend/class-product.php');
            include('includes/class-ajax.php');
            include('includes/class-core.php');
            include('includes/class-api.php');
            include('includes/admin/class-settings.php');
            include('includes/class-instock-mailer.php');
            include('includes/class-subscribe-mailer.php');
            include('includes/class-logger.php');
            include('includes/class-privacy.php');
            include('includes/admin/class-extra.php');
            include('includes/class-google-recaptcha.php');
            include('includes/class-troubleshoot.php');
            include('includes/class-privacy-checkbox.php');
            include('includes/class-upgrade.php');
        }

        public function initialize() {
            require_once('includes/class-background-mail-process.php');
        }

        public function define_constant() {
            $this->define('CWGINSTOCK_PLUGINURL', plugins_url('/', __FILE__));
            $this->define('CWGINSTOCK_DIRNAME', basename(dirname(__FILE__)));
            $this->define('CWGINSTOCK_FILE', __FILE__);
            $this->define('CWGSTOCKPLUGINBASENAME', plugin_basename(__FILE__));
        }

        private function define($name, $value) {
            if (!defined($name)) {
                define($name, $value);
            }
        }

        public function check_script_is_already_loaded($handle, $list = 'enqueued') {
            return wp_script_is($handle, $list);
        }

        public function enqueue_scripts() {
            wp_register_script('cwginstock_jquery_validation', CWGINSTOCK_PLUGINURL . 'assets/js/jquery.validate.js', array('jquery'), $this->version, true);
            $check_already_enqueued = $this->check_script_is_already_loaded('jquery-blockui');
            if (!$check_already_enqueued) {
                wp_register_script('jquery-blockui', CWGINSTOCK_PLUGINURL . 'assets/js/jquery.blockUI.js', array('jquery'), $this->version, true);
            }
            wp_register_script('cwginstock_js', CWGINSTOCK_PLUGINURL . 'assets/js/frontend.min.js', array('jquery', 'jquery-blockui'), $this->version, true);

            wp_register_style('cwginstock_frontend_css', CWGINSTOCK_PLUGINURL . 'assets/css/frontend.min.css', array(), $this->version, false);
            wp_register_style('cwginstock_bootstrap', CWGINSTOCK_PLUGINURL . 'assets/css/bootstrap.min.css', array(), $this->version, false);

            $get_option = get_option('cwginstocksettings');
            $check_visibility = isset($get_option['hide_form_guests']) && $get_option['hide_form_guests'] != '' && !is_user_logged_in() ? false : true;
            if ($check_visibility) {
                wp_enqueue_script('jquery');
                wp_enqueue_script('jquery-blockui');
                wp_enqueue_style('cwginstock_frontend_css');
                wp_enqueue_style('cwginstock_bootstrap');

                $get_empty_msg = isset($get_option['empty_error_message']) && $get_option['empty_error_message'] != '' ? $get_option['empty_error_message'] : __('Email Address cannot be empty', 'cwginstocknotifier');
                $invalid_msg = isset($get_option['invalid_email_error']) && $get_option['invalid_email_error'] != '' ? $get_option['invalid_email_error'] : __('Please Enter Valid Email Address', 'cwginstocknotifier');
                $translation_array = apply_filters('cwginstock_localization_array', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'user_id' => get_current_user_id(),
                    'security_error' => __("Something went wrong, please try after sometime", 'cwginstocknotifier'),
                    'empty_email' => $get_empty_msg,
                    'invalid_email' => $invalid_msg,
                ));
                wp_localize_script('cwginstock_js', 'cwginstock', $translation_array);
                wp_enqueue_script('cwginstock_js');
            }
        }

        public function admin_enqueue_scripts() {
            $screen = get_current_screen();
            if (isset($screen->id) && (($screen->id == 'cwginstocknotifier_page_cwg-instock-mailer') || ($screen->id == 'edit-cwginstocknotifier'))) {
                wp_enqueue_style('cwginstock_admin_css', CWGINSTOCK_PLUGINURL . '/assets/css/admin.css', array(), $this->version);
                wp_register_script('cwginstock_admin_js', CWGINSTOCK_PLUGINURL . '/assets/js/admin.js', array('jquery', 'wc-enhanced-select'), $this->version);
                wp_localize_script('cwginstock_admin_js', 'cwg_enhanced_selected_params', array('search_tags_nonce' => wp_create_nonce('search-tags')));
                wp_enqueue_script('cwginstock_admin_js');
            }
        }

        public function load_plugin_textdomain() {
            $domain = 'cwginstocknotifier';
            $dir = untrailingslashit(WP_LANG_DIR);
            $locale = apply_filters('plugin_locale', get_locale(), $domain);
            if ($exists = load_textdomain($domain, $dir . '/plugins/' . $domain . '-' . $locale . '.mo')) {
                return $exists;
            } else {
                load_plugin_textdomain($domain, FALSE, basename(dirname(__FILE__)) . '/languages/');
            }
        }

        public function add_screen_ids_to_woocommerce($screen_ids) {
            $screen_ids[] = 'edit-cwginstocknotifier';
            $screen_ids[] = 'cwginstocknotifier_page_cwg-instock-mailer';
            return $screen_ids;
        }

        // hide help context tab

        public function remove_help_tab_context() {
            $screen = get_current_screen();
            if ($screen->id == 'edit-cwginstocknotifier' || $screen->id == 'cwginstocknotifier_page_cwg-instock-mailer') {
                $screen->remove_help_tabs();
            }
        }

    }

    /**
     * @since 1.0
     * @return object
     */
    function CWG_Instock_Notifier() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        if (cwg_is_woocommerce_activated()) {
            return CWG_Instock_Notifier::instance();
        }
    }

    if (!function_exists('cwg_is_woocommerce_activated')) {

        function cwg_is_woocommerce_activated() {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            if (is_plugin_active('woocommerce/woocommerce.php')) {
                return true;
            } elseif (is_plugin_active_for_network('woocommerce/woocommerce.php')) {
                return true;
            } else {
                return false;
            }
        }

    }

    CWG_Instock_Notifier();
}
