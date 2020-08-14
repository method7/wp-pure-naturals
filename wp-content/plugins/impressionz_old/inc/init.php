<?php

namespace IMP;

// use GOX\Plugin;
// use GOX\Register_Plugin_Post_Types  as Register_Plugin_Post_Types;
// use GOX\Register_Plugin_Post_Meta_Boxes  as Register_Plugin_Post_Meta_Boxes;
// use GOX\Register_Plugin_Options  as Register_Plugin_Options;
// use GOX\Register_Plugin_Customize  as Register_Plugin_Customize;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'IMP\Init' ) ) :

/**
 * The Plugin Init
 *
 *
 *
 * @author 			GOX Press <support@GOXPress.io>
 * @link 				https://GOXPress.io
 * @package     WordPress
 * @subpackage  GOX
 *
 * @version 		1.0.0
 *
 **/

class Init {

	public $config;

	private static $_instance = null;

	//first instance
  public static function run(){
      if ( self::$_instance == null ) :
           self::$_instance = new self;
      endif;
      return self::$_instance;
  }

  //init
	public function __construct(){

		global $config;

		//first include global config class
		include_once( plugin_dir_path( __FILE__ ).'classes/Plugin.php');

		//run config
		new Plugin( plugin_basename( dirname(  dirname( __FILE__ ) ) ) );

		//include root config
		include( Plugin::path().'config.php');

		//include all parts files from admin, functions, shortcodes and widgets folders
		foreach ( Plugin::get('templates') as $template_id => $template) {
			//include files from the part admin folder
			foreach (glob( Plugin::get('path').'/templates/'.$template.'/admin/*', GLOB_NOSORT ) as $dir_path) :
		 		include_once( $dir_path );
			endforeach;
			 	//include files from the part functions folder
	 		foreach (glob( Plugin::get('path').'/templates/'.$template.'/functions/*', GLOB_NOSORT ) as $dir_path) :
		 		include_once( $dir_path );
			endforeach;
			//include files from the part shortcodes folder
			foreach (glob( Plugin::get('path').'/templates/'.$template.'/shortcodes/*', GLOB_NOSORT ) as $dir_path) :
		 		include_once( $dir_path );
			endforeach;
			 	//include files from the part widgets folder
			foreach (glob( Plugin::get('path').'/templates/'.$template.'/widgets/*', GLOB_NOSORT ) as $dir_path) :
		 		include_once( $dir_path );
			endforeach;
		}

		//include helpers
		foreach (glob( plugin_dir_path( __FILE__ ).'helpers/*', GLOB_NOSORT ) as $dir_path) :
			include_once( $dir_path );
		endforeach;

		//include global functions
		foreach (glob( plugin_dir_path( __FILE__ ).'functions/*', GLOB_NOSORT ) as $dir_path) :
			include_once( $dir_path );
		endforeach;

	  //incude classes
		foreach (glob(  plugin_dir_path( __FILE__ ).'classes/*', GLOB_NOSORT ) as $dir_path) :
			//print $dir_path ;
		  include_once( $dir_path );
		endforeach;

		//run global custom post type register
		 new Register_Plugin_Post_Types( Plugin::posttype() );


		 new Register_Plugin_Options( Plugin::get('options') );

		//run wp init
		add_action( 'init', array( $this, 'init' ) );

	}

	//global init
	public function init(){



		if ( is_admin() ) :
			//run global meta boxes register
			new Register_Plugin_Post_Meta_Boxes( Plugin::metabox() );
			//run global options register
			//new Register_Plugin_Options();
		endif;
		if ( is_customize_preview() ) :

				//new Register_Customize_CSS();

			//run global customize options register
		//	new Register_Plugin_Customize( Plugin::customize() );
		endif;
	}

}
//Run Plugin init
Init::run();

endif;
