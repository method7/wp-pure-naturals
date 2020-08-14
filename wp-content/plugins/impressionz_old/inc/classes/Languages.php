<?php

namespace IMP;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'IMP\Plugin_Languages' ) ) :

/**
*
* Include translate files form /language/
*
* https://wordpress.stackexchange.com/questions/137503/how-to-load-theme-textdomain-from-plugin
*	https://code.tutsplus.com/articles/how-to-internationalize-wordpress-themes-and-plugins--wp-22779
*
*
*/
class Plugin_Languages{

	function __construct(){

		//load_plugin_textdomain
		add_action( 'plugins_loaded',  array($this, 'load_plugin_textdomain') );

	}

	/**
	 * Load plugin textdomain.
	 *
	 * @since 1.0.0
	 */
	function load_plugin_textdomain() {

	  	load_plugin_textdomain( 'gox', false,  Plugin::path().'/languages' );
	}


}

new Plugin_Languages;

endif; // class_exists check
