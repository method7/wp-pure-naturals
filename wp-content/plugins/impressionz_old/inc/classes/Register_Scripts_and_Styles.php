<?php

namespace IMP;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'IMP\Register_Plugin_Scripts_and_Styles' ) ) :

 /**
   * Register_Plugin_Scripts_and_Styles
   *
   * Registers a script to be enqueued later using the wp_enqueue_script() function.
   *
   * https://developer.wordpress.org/reference/functions/wp_register_script/
   * https://developer.wordpress.org/reference/functions/wp_enqueue_style/
   *
   * @package    	WordPress
   * @subpackage 	GOX Framework
   *
   * @version 		1.0.0
   *
   */

	class Register_Plugin_Scripts_and_Styles{

		//init
		function __construct(){
			add_action( 'wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'), Plugin::get('enqueue_scripts_deps') );
		}

		/**
		 * Register global scripts and styles
		 *
		 * @since 1.0.0
		 */
		function wp_enqueue_scripts() {

			//root/style.css
			wp_enqueue_style(__NAMESPACE__,  plugin_dir_url( dirname( dirname( __FILE__ ) ) ).'style.css');
			//root/scripts.js
			wp_enqueue_script( __NAMESPACE__ , plugin_dir_url( dirname( dirname( __FILE__ )  ) ).'scripts.js', array( 'jquery' ),'1.0.0', true );

		}


	}
	 new Register_Plugin_Scripts_and_Styles;

endif; // class_exists check
