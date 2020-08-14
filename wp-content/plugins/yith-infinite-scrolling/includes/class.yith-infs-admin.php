<?php
/*  Copyright 2013  Your Inspiration Themes  (email : plugins@yithemes.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Admin class
 *
 * @author  YITH
 * @package YITH Infinite Scrolling
 * @version 1.0.0
 */

if ( ! defined( 'YITH_INFS' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_INFS_Admin' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_INFS_Admin {

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 * @var \YITH_INFS_Admin
		 */
		protected static $instance;

		/**
		 * Plugin options
		 *
		 * @since  1.0.0
		 * @var array
		 * @access public
		 */
		public $options = array();

		/**
		 * Plugin version
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $version = YITH_INFS_VERSION;

		/**
		 * @var $_panel Panel Object
		 */
		protected $_panel;

		/**
		 * @var $_premium string Premium tab template file name
		 */
		protected $_premium = 'premium.php';

		/**
		 * @var string Premium version landing link
		 */
		protected $_premium_landing = 'https://yithemes.com/themes/plugins/yith-infinite-scrolling/';

		/**
		 * @var string Infinite Scrolling panel page
		 */
		protected $_panel_page = 'yith_infs_panel';

		/**
		 * Various links
		 *
		 * @since  1.0.0
		 * @var string
		 * @access public
		 */
		public $doc_url = 'https://yithemes.com/docs-plugins/yith-infinite-scrolling/';

		/**
		 * The name for the plugin options
		 *
		 * @access protected
		 * @since  1.0.0
		 */
		protected static $_plugin_options = YITH_INFS_OPTION_NAME;


		/**
		 * Returns single instance of the class
		 *
		 * @since 1.0.0
		 * @return \YITH_INFS_Admin
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct() {

			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_style' ) );

			//Add action links
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_INFS_DIR . '/' . basename( YITH_INFS_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );

			if ( ! ( defined( 'YITH_INFS_PREMIUM' ) && YITH_INFS_PREMIUM ) ) {
				add_action( 'yith_infinite_scrolling_premium', array( $this, 'premium_tab' ) );
			}

			// YITH INFS Loaded
			do_action( 'yith_infs_loaded' );
		}

		/**
		 * Enqueue style
		 *
		 * @since  1.0.0
		 * @author Francesco Licandro <francesco.licandro@yithems.com>
		 * @access public
		 */
		public function enqueue_style() {
			if ( isset( $_GET['page'] ) && $_GET['page'] == $this->_panel_page ) {
				wp_enqueue_style( 'yith-infs-admin', YITH_INFS_ASSETS_URL . '/css/admin.css' );
			}
		}

		/**
		 * Action Links
		 *
		 * add the action links to plugin admin page
		 *
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @param $links | links plugin array
		 *
		 * @return   mixed Array
		 * @return mixed
		 * @use      plugin_action_links_{$plugin_file_name}
		 */
		public function action_links( $links ) {
			$links = yith_add_action_links( $links, $this->_panel_page, false );
			return $links;
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use      /Yit_Plugin_Panel class
		 * @return   void
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {

			if ( ! empty( $this->_panel ) ) {
				return;
			}

			$admin_tabs = array(
				'general' => __( 'Settings', 'yith-infinite-scrolling' ),
			);

			if ( ! ( defined( 'YITH_INFS_PREMIUM' ) && YITH_INFS_PREMIUM ) ) {
				$admin_tabs['premium'] = __( 'Premium Version', 'yith-infinite-scrolling' );
			}

			$args = array(
				'create_menu_page' => true,
				'parent_slug'      => '',
				'page_title'       => __( 'Infinite Scrolling', 'yith-infinite-scrolling' ),
				'menu_title'       => __( 'Infinite Scrolling', 'yith-infinite-scrolling' ),
				'parent'           => 'infs',
				'parent_page'      => 'yith_plugin_panel',
				'plugin-url'       => YITH_INFS_URL,
				'page'             => $this->_panel_page,
				'admin-tabs'       => $admin_tabs,
				'options-path'     => YITH_INFS_DIR . 'plugin-options',
				'class'            => yith_set_wrapper_class(),
				'plugin_slug'      => YITH_INFS_SLUG,
			);

			/* === Fixed: not updated theme  === */
			if ( ! class_exists( 'YIT_Plugin_Panel' ) ) {
				require_once( YITH_INFS_DIR . '/plugin-fw/lib/yit-plugin-panel.php' );
			}

			$this->_panel = new Yit_Plugin_Panel( $args );
		}

		/**
		 * Premium Tab Template
		 *
		 * Load the premium tab template on admin page
		 *
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @return   void
		 * @return void
		 */
		public function premium_tab() {
			$premium_tab_template = YITH_INFS_TEMPLATE_PATH . '/admin/' . $this->_premium;
			if ( file_exists( $premium_tab_template ) ) {
				include_once( $premium_tab_template );
			}

		}

		/**
		 * plugin_row_meta
		 *
		 * add the action links to plugin admin page
		 *
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use      plugin_row_meta
		 * @param $plugin_data
		 * @param $status
		 *
		 * @param $plugin_meta
		 * @param $plugin_file
		 * @return   Array
		 */
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status ) {

			if ( defined( 'YITH_INFS_INIT' ) && YITH_INFS_INIT == $plugin_file ) {
				$new_row_meta_args['slug'] = YITH_INFS_SLUG;

				if ( defined( 'YITH_INFS_PREMIUM' ) ) {
					$new_row_meta_args['is_premium'] = true;
				}
			}

			return $new_row_meta_args;
		}

		/**
		 * Get the premium landing uri
		 *
		 * @since   1.0.0
		 * @author  Andrea Grillo <andrea.grillo@yithemes.com>
		 * @return  string The premium landing link
		 */
		public function get_premium_landing_uri() {
			return $this->_premium_landing;
		}

		/**
		 * Get options from db
		 *
		 * @access public
		 * @since  1.0.0
		 * @author Francesco Licandro <francesco.licandro@yithemes.com>
		 * @param $option  string
		 * @param $default mixed
		 * @return mixed
		 */
		public static function get_option( $option, $default = false ) {
			return yinfs_get_option( $option, $default );
		}
	}
}
/**
 * Unique access to instance of YITH_WCQV_Admin class
 *
 * @since 1.0.0
 * @return \YITH_INFS_Admin
 */
function YITH_INFS_Admin() {
	return YITH_INFS_Admin::get_instance();
}