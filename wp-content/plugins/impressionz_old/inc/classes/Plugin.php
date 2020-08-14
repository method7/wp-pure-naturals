<?php

namespace IMP;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'IMP\Plugin' ) ) :

global $config;

/**
 * Global Plugin Config
 *
 * Config::get('key');
 * Config::set('key', 'value');
 * Config::set('key', array('values'));
 * Config::remove('key', 'id');
 *
 * @author
 * @package     WordPress
 * @subpackage  GOX
 * @since 			GOX  1.0.0
 *
 * @version 	1.0.0
 *
 *
 **/
class Plugin{

  static $config;
  static $slug;

	function __construct(){

		global $config;

		// self::$slug = $slug;
		self::$config[ __NAMESPACE__ ][ 'namespace' ]     = __NAMESPACE__;
		self::$config[ __NAMESPACE__ ][ 'slug' ] 		 	     = self::slug();
		self::$config[ __NAMESPACE__ ][ 'name' ] 		 	     = self::name();
		self::$config[ __NAMESPACE__ ][ 'texdomain' ]     = self::name();
		self::$config[ __NAMESPACE__ ][ 'url' ] 		        = self::url();
		self::$config[ __NAMESPACE__ ][ 'path' ] 		       = self::path();
		self::$config[ __NAMESPACE__ ][ 'img' ] 		        = self::img();
  self::$config[ __NAMESPACE__ ][ 'thumb' ] 		      = self::img();
		self::$config[ __NAMESPACE__ ][ 'templates' ]     = self::template();
		self::$config[ __NAMESPACE__ ][ 'enqueue_scripts_deps' ] = 99;
		self::$config[ __NAMESPACE__ ][ 'post-type' ]     =  array(); //global register post types in Register_Post_Types()
		self::$config[ __NAMESPACE__ ][ 'meta-box' ]      = array();
		self::$config[ __NAMESPACE__ ][ 'meta-term' ]     = array();
		self::$config[ __NAMESPACE__ ][ 'customize' ]     = array();
		self::$config[ __NAMESPACE__ ][ 'options' ]       = array();
		self::$config[ __NAMESPACE__ ][ 'meta-user' ]     = array();
		self::$config[ __NAMESPACE__ ][ 'form' ] 			      = array();
		self::$config[ __NAMESPACE__ ][ 'media-queries' ] = array();

		$config = self::$config;
		return self::$config;
	}

	/**
	 * Get config data
	 *
	 * @since 1.0.0
	 */
	static function get( $key = null, $type = array() ) {
		global $config;
		if( $key ) :
	 		 return self::$config[__NAMESPACE__][$key];
		else:
			 return self::$config[__NAMESPACE__];
		endif;

	}

	/**
	 * Set config data.
	 *
	 * @since 1.0.1
	 */

	static function set( $key, $values = null ) {
		global $config;
		//if values in array
		if ( is_array( $values ) ) :

			$id = ( key( $values ) == 0 || key( $values ) == '' ) ? sha1( json_encode( key( $values ) ) ) : key( $values ) ;

			self::$config[ __NAMESPACE__ ][ $key ][	$id ]	= current( $values );
		else:
	 		self::$config[ __NAMESPACE__ ][ $key ]	= $values;
	 	endif;

	  return self::$config;
	}


	// static function set( $key, $values = null ) {
	// 	global $config;
	// 	//if values in array
	// 	if ( is_array( $values ) ) :
	// 		self::$config[ __NAMESPACE__ ][ $key ][ key( $values ) ]	= current( $values );
	// 	else:
	//  		self::$config[ __NAMESPACE__ ][ $key ]	= $values;
	//  	endif;

	//   return self::$config;
	// }

	/**
	 * filter config data.
	 *
	 * @since 1.0.0
	 */
	static function filter( $key, $values = null ) {
		global $config;
		//if values in array
		if ( is_array( $values ) ) :
			self::$config[ __NAMESPACE__ ][ $key ]	=  $values ;
		else:
	 		self::$config[ __NAMESPACE__ ][ $key ]	= $values;
	 	endif;

	  return self::$config;
	}


	//issues
	static function remove( $key, $id = null ) {
		unset(self::$config[__NAMESPACE__][$key][$id]);
	}


 /**
	* Get Plugin path
	*
	*/
	static function slug(){
		return mb_strtolower( __NAMESPACE__ );
	}

	 /**
	* Get Plugin path
	*
	*/
	static function img(){
		return plugin_dir_url( dirname( dirname( __FILE__ ) ) ).'assets/img/img.png';
	}


 /**
	* Get Plugin path
	*
	*/
	static function path(){
		return plugin_dir_path(dirname( dirname(__FILE__) ) );
	}

 /**
	* Get Plugin url
	*
	*/
	static function url(){
		return plugin_dir_url(dirname( dirname(__FILE__) ) );
	}

	/**
	 * Get Plugin name
	 *
	 */
 	static function name(){
 		return plugin_basename( dirname( dirname( dirname( __FILE__ ) ) ) );
 	}

	/**
	 * Get All Plugin Parts
	 *
	 */
	static function template(){
		return array_diff( scandir( plugin_dir_path( dirname( dirname( __FILE__) ) ).'/templates' ), array('.', '..', '.DS_Store', 'index.php')  );
	}

 static function template_part( $template = null , $part = null ) {
 	include( plugin_dir_path( dirname( __DIR__) ).'templates/'.$template.'/part/'.$part.'.php');
 }

	/**
	 *  customize options controller
	 *
	 * @var $key (string) customize_id
	 * @var  $args (array) values
	 * @since 1.0.0
	 * @return array()
	 */

	static function customize( $key = null ,  $args = null ) {
		global $config;
		//set values
		if ( $args ) :
			self::$config[  __NAMESPACE__ ][ 'customize' ][ $key ]	= $args ;
		//return value
		elseif( $key ) :
			return self::$config[ __NAMESPACE__ ][ 'customize' ][ $key ];
	 	endif;
	 	//return all customize options
	  return self::$config[ __NAMESPACE__ ][ 'customize' ];
	}

	/**
	 *  customize options controller
	 *
	 * @var $key (string) customize_id
	 * @var  $args (array) values
	 * @since 1.0.0
	 * @return array()
	 */

	static function posttype( $key = null ,  $args = null ) {
		global $config;
		//set values
		if ( $args ) :
			self::$config[  __NAMESPACE__ ][ 'post-type' ][ $key ]	= $args ;
		//return value
		elseif( $key ) :
			return self::$config[ __NAMESPACE__ ][ 'post-type' ][ $key ];
	 	endif;
	 	//return all customize options
	  return self::$config[ __NAMESPACE__ ][ 'post-type' ];
	}

	/**
	 *  customize options controller
	 *
	 * @var $key (string) customize_id
	 * @var  $args (array) values
	 * @since 1.0.0
	 * @return array()
	 */

	static function metabox( $key = null ,  $args = null ) {
		global $config;
		//set values
		if ( $args ) :
			self::$config[  __NAMESPACE__ ][ 'meta-box' ][ $key ]	= $args ;
		//return value
		elseif( $key ) :
			return self::$config[ __NAMESPACE__ ][ 'meta-box' ][ $key ];
	 	endif;
	 	//return all customize options
	  return self::$config[ __NAMESPACE__ ][ 'meta-box' ];
	}


}

 //HELPERS
 //get all data
 function config(){
    return Plugin::get();
 }

 //get plugin path
 function path(){
    return Plugin::path();
 }

 //get plugin url
 function url(){
    return Plugin::url();
 }

 //get plugin slug
 function slug(){
    return Plugin::slug();
 }

 //basename
 function basename(){
   	return plugin_basename( dirname( dirname( dirname( __FILE__ ) ) ) ).'/index.php';
 }

 //register_options
 function register_options( $array ){
   return Plugin::set('options', $array);
 }

 //register_custom_fields posts type
 function register_custom_fields( $array ){
   return Plugin::set('meta-box', $array);
 }

 //db save prefix
 function prefix(){
   return Plugin::slug().'_';
 }


endif; // class_exists check
