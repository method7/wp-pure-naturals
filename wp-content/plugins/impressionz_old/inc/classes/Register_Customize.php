<?php

namespace IMP;

	use WP_Customize_Image_Control;
	use User_Select_Custom_Control;
	use WP_Customize_Media_Control;
	use WP_Customize_Color_Control;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'IMP\Register_Plugin_Customize' ) ) :

/**
 * 	Customize Option Builder
 *  auto open button https://gist.github.com/ericandrewlewis/2310fd6d7dabf0696965
 *
 * @package     WordPress
 * @subpackage  GOX Framework
 * @since 			1.0.0
 *
 * @version 		1.0.0
 *
 *
 **/

class Register_Plugin_Customize{

	public $parts;
	public $slug;
	public $SC_config;
	public $customize = array();
	public $data = array();

	function __construct( $customize = array() ){

		global $config; //include global data

		$this->slug 		 = Plugin::slug();
		$this->customize = $customize;
		//var_dump( get_config() ); //debug

 	  //register Customize
 	  add_action('customize_register', array(&$this, 'register_customize_options'));

	}


	// function __destruct( ){
	// 	global $SC_config;
	// 	unset( $SC_config['customize'] );
	// }


	// /**
	//  * 	Set customize options array from all parts
	//  *
	//  * @author
	//  * @since 1.0
	//  *
	//  **/

	// function set_part_customize_options($part, $array = array()){

	// 		$this->customize[ $part ] = $array ;
	// 		return 	$this->customize;
	// }



	/**
	 * 	Render customize options from global $SC_config['customize'] = array()
	 *
	 * @author
	 * @since 1.0
	 * @var $wp_customize
	 *
	 **/
	 function register_customize_options( $wp_customize ){




			// Include the Multi Color Picker.
			//require_once( dirname( __FILE__ ) . '/multi-color-picker/multi-color-picker.php' );


	 	global $config; //include global data

	 	$this->slug = Plugin::slug();

	 	// echo get_config('slug');
	 	//var_dump( get_config('customize') ); //debug

		//panels
		if ( $this->customize ) :
		foreach ( $this->customize as $key => $panel ) :
			//print_R($panel);
			$panel_priority = !empty( $panel[ 'priority' ] ) 	? $panel['priority']  	: 10;
			$panel_desc	    = !empty( $panel[ 'desc' ] ) 			? $panel['desc']  		  : '';
			$panel_ID	    	= !empty( $panel[ 'ID' ] ) 				? $panel['ID']  		  	: 'options';
			$panel_ID	    	= !empty( $panel[ 'slug' ] ) 			? $panel['slug']  			:	$panel_ID	;

			//add panel
			$this->add_panel( $wp_customize, $this->slug.'_'.$panel_ID ,$panel['title'], $panel_desc, $panel_priority );

				//sections
				foreach ( $panel[ 'sections' ]  as $key => $section) :

					//default values
					$priority 	= !empty( $section['priority'] ) 	? $section['priority']  : 10;
					$title 	  	= !empty( $section['title'] ) 	? $section['title'] 	: '';
					$slug 	  	= !empty( $section['slug'] ) 	? $section['slug'] 	: '';
					$section_ID = !empty( $section['ID'] ) 		? $section['ID'] 		: $section['slug'];
					$desc 	  	= !empty( $section['desc'] ) 		? $section['desc'] 		: '';

					//add section
					$this->add_section($wp_customize, $this->slug.'_'.$panel_ID, $this->slug.'_'.$panel_ID.'_'.$section_ID, $section['title'], $desc, $priority);

						//fields
						foreach ($section['fileds']  as $key => $filed) :

							//default values
							$type 	   = !empty( $filed['type'] ) 		? $filed['type'] 	  : 'text';
							$default   = !empty( $filed['default'] ) 	? $filed['default']   : '';
							$title 	   = !empty( $filed['title'] ) 		? $filed['title'] 	  : '';
							$desc 	   = !empty( $filed['desc'] ) 		? $filed['desc'] 	  : '';
							$choices   = !empty( $filed['choices'] ) 	? $filed['choices']   : array();
							$transport = !empty( $filed['transport'] ) 	? $filed['transport'] : 'refresh';  //postMessage
							$filed_name = !empty( $filed['name'] )      ? $panel_ID.'_'.$section_ID.'_'.$filed['name'] 	  : '';

							//add filed
							if ( method_exists(	'CSS\Register_Customize', $type) ):
								$this->{$type}($wp_customize, $this->slug.'_'.$panel_ID, $this->slug.'_'.$panel_ID.'_'.$section_ID, $filed_name , $default, $title, $desc, $transport, $choices );
							endif;

						endforeach; //fields

				endforeach; //sections

			endforeach; //panels
			endif;
	}


	/**
	 * 	Add Customize Panel
	 *
	 * @author
	 * @since 1.0
	 *
	 * @var obj $wp_customize -  WP Object
	 * @var str $title - Panel Title in Customize view
	 * @var str $description - Panel Description in Customize view
	 * @var int $priority - Position order in customize view
	 * @var str $capability - edit_theme_options
	 * @var str $theme_supports
	 *
	 * @return void
	 **/
	 function add_panel($wp_customize, $ID, $title = "Theme Options", $description = "", $priority = 10, $capability = "edit_theme_options", $theme_supports = ''){

		$wp_customize->add_panel(
			self::sanitize_name( $ID ),
			array(
		   	 	'priority'       => $priority,
			    'capability'     => $capability,
			    'theme_supports' => $theme_supports,
			    'title'          => $title,
			    'description'    => $description,
			)
		);

	}

	/**
	 * 	Add Section in Customize Panel
	 *
	 * @author
	 * @since 1.0
	 *
	 * @var obj $wp_customize -  WP Object
	 * @var str $id - Section ID must be unique
	 * @var str $title - Section Title in Customize view
	 * @var str $description - Section Description in Customize view
	 * @var str $panel - Panel ID, parent
	 * @var ing $priority - Position order in customize view
	 *
	 * @return void
	 **/
	  function add_section($wp_customize, $panel_ID = 'Theme Options', $ID = "setting", $title = "Setting", $description = '', $priority = 10){

	   $wp_customize->add_section(
			self::sanitize_name( $ID ),
			array(
	     	  'title'    		=> $title,
		      'priority' 		=> $priority,
			  	'description'	=> $description,
			  	'panel'  			=> self::sanitize_name( $panel_ID ),
	   		));

	}


	/**
	 * 	Add Text filed in Customize Section
	 *
	 * @author
	 * @since 1.0
	 *
	 * @var obj $wp_customize -  WP Object
	 * @var str $panel - Panel ID, parent panel
	 * @var str $section - Section ID, parent section
	 * @var str $name - Filed name, must be uniqu whit - or _ filed_name
	 * @var str $default - Default value
	 * @var str $title - Label title in view
	 * @var str $desc - Filed description in view
	 * @var str $transport - refresh, postMessage
	 *
	 * @return void
	 **/
	  function text($wp_customize, $panel = "Theme Options", $section = "Setting", $name = 'text', $default = '', $title = '', $desc = '', $transport = 'refresh' ){



		$wp_customize->add_setting(
			self::sanitize_name( $panel ).'['.$name.']',
			array(
	 		   'default'        	   		=> $default ,
		       'capability'     	   => 'edit_theme_options',
		       'type'           	   => 'option',
			   'transport'   		  		 => $transport, //refresh , postMessage
			   'sanitize_callback' 	   => 'esc_attr',
			   'sanitize_js_callback'  => 'esc_js'
		   	   ));


		$wp_customize->add_control(
			$name,
			array(
	   		 	'label'   	  => $title,
			    'section' 	  => self::sanitize_name( $section ),
			    'settings'    => self::sanitize_name( $panel ).'['.$name.']',
			    'type'        => 'text',
				'description' => $desc,
				));

		$wp_customize->selective_refresh->add_partial(
			$name,
			array(
		  	  'selector' => '.edit_option_'.$name,
			  	'settings' => self::sanitize_name( $panel ).'['.$name.']',
			) );
	 }


	/**
	 * 	Add Text filed in Customize Section
	 *
	 * @author
	 * @since 1.0
	 *
	 * @var obj $wp_customize -  WP Object
	 * @var str $panel - Panel ID, parent panel
	 * @var str $section - Section ID, parent section
	 * @var str $name - Filed name, must be uniqu whit - or _ filed_name
	 * @var str $default - Default value
	 * @var str $title - Label title in view
	 * @var str $desc - Filed description in view
	 * @var str $transport - refresh, postMessage
	 *
	 * @return void
	 **/
	  function number($wp_customize, $panel = "Theme Options", $section = "Setting", $name = 'text', $default = '', $title = '', $desc = '', $transport = 'refresh' ){



		$wp_customize->add_setting(
			self::sanitize_name( $panel ).'['.$name.']',
			array(
	 		   'default'        	   => $default ,
		       'capability'     	   => 'edit_theme_options',
		       'type'           	   => 'option',
			   'transport'   		   => $transport, //refresh , postMessage
			   'sanitize_callback' 	   => 'esc_attr',
			   'sanitize_js_callback'  => 'esc_js'
		   	   ));


		$wp_customize->add_control(
			$name,
			array(
	   		 	'label'   	  => $title,
			    'section' 	  => self::sanitize_name( $section ),
			    'settings'    => self::sanitize_name( $panel ).'['.$name.']',
			    'type'        => 'number',
				'description' => $desc,
				));

		$wp_customize->selective_refresh->add_partial(
			$name,
			array(
		  	  'selector' => '.edit_option_'.$name,
			  'settings' => self::sanitize_name( $panel ).'['.$name.']',
			) );
	 }


	/**
	 * 	Add Image filed in Customize Section
	 *
	 * @author
	 * @since 1.0
	 *
	 * @var obj $wp_customize -  WP Object
	 * @var str $panel - Panel ID, parent panel and get_option( panel name )
	 * @var str $section - Section ID, parent section
	 * @var str $name - Filed name, must be uniqu whit - or _ filed_name
	 * @var str $default - Default value
	 * @var str $title - Label title in view
	 * @var str $desc - Filed description in view
	 * @var str $transport - refresh, postMessage
	 *
	 * @return void
	 **/
	 function image($wp_customize, $panel = "Theme Options", $section = "Setting", $name = 'image', $default = NULL, $title = 'My Label', $desc = 'Desc', $transport = 'refresh' ){


		$wp_customize->add_setting(
	  		self::sanitize_name( $panel ).'['.$name.']',
	  		array(
	      	   'default'        	   => $default ,
	  	       'capability'     	   => 'edit_theme_options',
	  	       'type'           	   => 'option',
	  		   'transport'   		   => $transport,
	  		   'sanitize_callback' 	   => 'esc_url',
	  		   'sanitize_js_callback'  => 'esc_js'
	  	   	   ));


	  	$wp_customize->add_control( new WP_Customize_Image_Control(
	  		$wp_customize,
	  		$name,
	  		array(
				'label'   	  => $title,
	  		    'section' 	  => self::sanitize_name( $section ),
	  		    'settings'    => self::sanitize_name( $panel ).'['.$name.']',
	  		    'type'        => 'image',
				'description' => $desc,
	  			)));

		$wp_customize->selective_refresh->add_partial(
			$name,
			array(
		  	  'selector' => '.edit_option_'.$name,
				'settings' => self::sanitize_name( $panel ).'['.$name.']',
			) );
	}


	/**
	 * 	Add Image filed in Customize Section
	 *
	 * @author
	 * @since 1.0
	 *
	 * @var obj $wp_customize -  WP Object
	 * @var str $panel - Panel ID, parent panel and get_option( panel name )
	 * @var str $section - Section ID, parent section
	 * @var str $name - Filed name, must be uniqu whit - or _ filed_name
	 * @var str $default - Default value
	 * @var str $title - Label title in view
	 * @var str $desc - Filed description in view
	 * @var str $transport - refresh, postMessage
	 *
	 * @return void
	 **/
	 function media($wp_customize, $panel = "Theme Options", $section = "Setting", $name = 'image', $default = NULL, $title = 'My Label', $desc = 'Desc', $transport = 'refresh' ){


		$wp_customize->add_setting(
	  		self::sanitize_name( $panel ).'['.$name.']',
	  		array(
	      	   'default'        	   => $default ,
	  	       'capability'     	   => 'edit_theme_options',
	  	       'type'           	   => 'option',
	  		   'transport'   		   => $transport,
	  		   'sanitize_callback' 	   => '',
	  		   'sanitize_js_callback'  => ''
	  	   	   ));


	  	$wp_customize->add_control( new WP_Customize_Media_Control(
	  		$wp_customize,
	  		$name,
	  		array(
				'label'   	  => $title,
	  		    'section' 	  => self::sanitize_name( $section ),
	  		    'settings'    => self::sanitize_name( $panel ).'['.$name.']',
	  		    'type'        => 'media',
				'description' => $desc,
	  			)));

		$wp_customize->selective_refresh->add_partial(
			$name,
			array(
		  	  'selector' => '.edit_option_'.$name,
				'settings' => self::sanitize_name( $panel ).'['.$name.']',
			) );
	}


	/**
	 * 	Add Image filed in Customize Section
	 *
	 * @author
	 * @since 1.0
	 *
	 * @var obj $wp_customize -  WP Object
	 * @var str $panel - Panel ID, parent panel and get_option( panel name )
	 * @var str $section - Section ID, parent section
	 * @var str $name - Filed name, must be uniqu whit - or _ filed_name
	 * @var str $default - Default value
	 * @var str $title - Label title in view
	 * @var str $desc - Filed description in view
	 * @var str $transport - refresh, postMessage
	 *
	 * @return void
	 **/
	 function color($wp_customize, $panel = "Theme Options", $section = "Setting", $name = 'image', $default = NULL, $title = 'My Label', $desc = 'Desc', $transport = 'refresh' ){


		$wp_customize->add_setting(
	  		self::sanitize_name( $panel ).'['.$name.']',
	  		array(
	      	   'default'        	   => $default ,
	  	       'capability'     	   => 'edit_theme_options',
	  	       'type'           	   => 'option',
	  		   'transport'   		   => $transport,
	  		   'sanitize_callback' 	   => 'esc_url',
	  		   'sanitize_js_callback'  => 'esc_js'
	  	   	   ));


	  	$wp_customize->add_control( new WP_Customize_Color_Control(
	  		$wp_customize,
	  		$name,
	  		array(
				'label'   	  => $title,
	  		    'section' 	  => self::sanitize_name( $section ),
	  		    'settings'    => self::sanitize_name( $panel ).'['.$name.']',
	  		     'type'        => 'color',
				'description' => $desc,
	  			)));

		$wp_customize->selective_refresh->add_partial(
			$name,
			array(
		  	  	'selector' => '.edit_option_'.$name,
				'settings' => self::sanitize_name( $panel ).'['.$name.']',
			) );
	}



	/**
	 * 	Add Texarea filed in Customize Section
	 *
	 * @author
	 * @since 1.0
	 *
	 * @var obj $wp_customize -  WP Object
	 * @var str $panel - Panel ID, parent panel and get_option( panel name )
	 * @var str $section - Section ID, parent section
	 * @var str $name - Filed name, must be uniqu whit - or _ filed_name
	 * @var str $default - Default value
	 * @var str $title - Label title in view
	 * @var str $desc - Filed description in view
	 * @var str $transport - refresh, postMessage
	 *
	 * @return void
	 **/
	 function textarea($wp_customize, $panel = "Theme Options", $section = "Setting", $name = 'image', $default = NULL, $title = '', $desc = '', $transport = 'refresh' ){

			$wp_customize->add_setting(
				self::sanitize_name( $panel ).'['.$name.']',
				array(
		 		   'default'        	   => $default ,
			       'capability'     	   => 'edit_theme_options',
			       'type'           	   => 'option',
				   'transport'   		   => $transport, //refresh , postMessage
				   'sanitize_callback' 	   => '',
				   'sanitize_js_callback'  => 'esc_js'
			   	   ));


			$wp_customize->add_control(
				$name,
				array(
		   		 	'label'   	  => $title,
				    'section' 	  => self::sanitize_name( $section ),
				    'settings'    => self::sanitize_name( $panel ).'['.$name.']',
				    'type'        => 'textarea',
					'description' => $desc,
					));

			$wp_customize->selective_refresh->add_partial(
				$name,
				array(
		  	  		'selector' => '.edit_option_'.$name,
					'settings' => self::sanitize_name( $panel ).'['.$name.']',
					));

	}

	/**
	 * 	Add Checkbox filed in Customize Section
	 *
	 * @author
	 * @since 1.0
	 *
	 * @var obj $wp_customize -  WP Object
	 * @var str $panel - Panel ID, parent panel and get_option( panel name )
	 * @var str $section - Section ID, parent section
	 * @var str $name - Filed name, must be uniqu whit - or _ filed_name
	 * @var str $default - Default value
	 * @var str $title - Label title in view
	 * @var str $desc - Filed description in view
	 * @var str $transport - refresh, postMessage
	 *
	 * @return void
	 **/
	 function checkbox($wp_customize,  $panel = "Theme Options", $section = "Setting", $name = 'image', $default = NULL, $title = '', $desc = '', $transport = 'refresh' ){


		$wp_customize->add_setting(
			self::sanitize_name( $panel ).'['.$name.']',
			array(
			   'default'        	   => $default ,
		       'capability'     	   => 'edit_theme_options',
		       'type'           	   => 'option',
			   'transport'   		   => $transport, //refresh , postMessage
			   'sanitize_callback' 	   => 'esc_attr',
			   'sanitize_js_callback'  => 'esc_js'
		   	   ));

		$wp_customize->add_control(
			$name,
			array(
			 	'label'   	  => $title,
			    'section' 	  => self::sanitize_name( $section ),
			    'settings'    => self::sanitize_name( $panel ).'['.$name.']',
			    'type'        => 'checkbox',
				'description' => $desc,
				'std'         => '1',
				));

		$wp_customize->selective_refresh->add_partial(
			$name,
			array(
	  	  		'selector' => '.edit_option_'.$name,
				'settings' => self::sanitize_name( $panel ).'['.$name.']',
				));

	}

	/**
	 * 	Add Select box filed in Customize Section
	 *
	 * @author
	 * @since 1.0
	 *
	 * @var obj $wp_customize -  WP Object
	 * @var str $panel - Panel ID, parent panel and get_option( panel name )
	 * @var str $section - Section ID, parent section
	 * @var str $name - Filed name, must be uniqu whit - or _ filed_name
	 * @var str $default - Default value
	 * @var str $title - Label title in view
	 * @var str $desc - Filed description in view
	 * @var str $transport - refresh, postMessage
	 *
	 * @return void
	 **/
	 function select($wp_customize, $panel = "Theme Options",  $section = "Setting", $name = 'image', $default = NULL, $title = '', $desc = '', $transport = 'refresh', $choices = array() ){


	//if (is_page()) :
	 //echo "Asdas";
	//endif;

	    $wp_customize->add_setting(
			self::sanitize_name( $panel ).'['.$name.']',
			array(
	     	   'default'        	  => $default ,
		       'capability'     	  => 'edit_theme_options',
		       'type'           	  => 'option',
			   'transport'   		  => $transport, //postMessage,refresh
			   'sanitize_callback' 	  => 'esc_attr',
			   'sanitize_js_callback' => 'esc_js'
	   		)
		);

	   $wp_customize->add_control(
			$name,
			array(
	      	   'label'   	  => $title,
			   'description'  => $desc,
			   'section' 	  => self::sanitize_name( $section ),
		   	   'settings'     => self::sanitize_name( $panel ).'['.$name.']',
		       'type'    	  => 'select',
		       'choices' 	  => $choices,
	   		)
		);


		$wp_customize->selective_refresh->add_partial(
			$name,
			array(
	  	  		'selector' => '.edit_option_'.$name,
						'settings' => self::sanitize_name( $panel ).'['.$name.']',

				));

	}



	/**
	 * 	Sanitize filed name
	 *
	 *
	 * @author
	 * @since 1.0
	 *
	 * @return string
	 **/
	static function sanitize_name( $name ){

		return str_replace('-', '_', sanitize_title( strtolower( $name ) ) );

	}



	/**
	 * 	Set options from all parts
	 *
	 * @author
	 * @since 1.0
	 *
	 **/
	function get_customize_options_value(){

		global $SC_config;

		//Parts
		foreach ($this->parts as $key => $part) :
				//part options
				if ( !empty( $this->customize[ $key ] ) ) :
					//panels
					foreach ($this->customize[ $key ]  as $panel_key => $panel) :
							//sections
							foreach ($panel['sections']  as $section_key => $section) :
									$key 		   = self::sanitize_name( $section['title'] );
								 	$option        = get_option( strtolower($this->slug).'options', '');
								/*	$this->options = get_option( Plugin::$slug.'options', '');	*/
								//fields
								foreach ($section['fileds']  as $filed_key => $filed) :

									$name 	 					= self::sanitize_name( $filed['name'] );

									$SC_config['fields'][ $name ]   	= isset( $option[ $name ] ) ? $option[ $name ]  : (  isset( $filed['default'] ) ? $filed['default']  : '' );

									$this->option[ $name ] 		= isset( $option[ $name ] ) ? $option[ $name ]  : ( isset( $filed['default'] ) ? $filed['default']  : '' );

								endforeach;

							endforeach;
						endforeach;
				endif;
			endforeach;

			// set_config('dadasdasdasdasdsadsa',	$SC_config['fields']);

		if ( isset( $this->option ) )
	 			return $this->option;


	}


	}
endif;
?>
