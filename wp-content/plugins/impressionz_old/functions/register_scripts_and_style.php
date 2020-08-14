<?php

namespace IMP;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

//new Register_Scripts_and_Styles;

/**
*
*	Register custom scripts and styles
*
* https://developer.wordpress.org/reference/functions/wp_enqueue_style/
*/

class Register_Scripts_and_Styles{

	public $deps = 999;

	//init
	function __construct(){
		//wp hook
		add_action( 'wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'), $this->$deps );


		add_action('customize_controls_print_styles', array($this, 'customize_controls_print_styles' ));
	}

	/**
	 * Register global scripts and styles
	 *
	 * https://developer.wordpress.org/reference/functions/wp_enqueue_style/
	 *
	 * CDN
	 *	- boostrap  4.1.1
	 *	- jquery  	3.3.1
	 *	- popper		1.14.3
	 *	- font-awesome 5.2.0
	 *
	 * @since 1.0.0
	 */
	function wp_enqueue_scripts() {

		//boostrap 4.1.1
		wp_enqueue_style('bootstrap', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css');

		//wp_deregister_script( 'jquery' );	 https://codex.wordpress.org/Function_Reference/wp_deregister_script
		//jquery 3.3.1
  	wp_enqueue_script( 'jquery','https://code.jquery.com/jquery-3.3.1.slim.min.js', array( 'jquery' ),'3.3.1',true );
  	//popper 1.14.3
  	wp_enqueue_script( 'popper','https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js', array( 'jquery' ),'1.14.3',true );
  	//bootstrap 4.1.1
  	wp_enqueue_script( 'bootstrap','https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js', array( 'jquery' ),'4.1.1',true );
  	//fontawesome 5.2.0
  	wp_enqueue_style( 'font-awesome', 'https://use.fontawesome.com/releases/v5.2.0/css/all.css', array(), '5.2.0' );

	  //wp_deregister_script( 'jquery' );
	}


	function customize_controls_print_styles(){

			wp_enqueue_style('gox-customize', Plugin::url().'/customize_controls_print_styles.css');

	}

}
