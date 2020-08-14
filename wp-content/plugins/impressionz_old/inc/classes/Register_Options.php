<?php

namespace IMP;

use \GOX\Form;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'IMP\Register_Plugin_Options' ) ) :

/**
*
* https://developer.wordpress.org/reference/functions/add_menu_page/
*/

class Register_Plugin_Options {

	// $totalSuplly =
	public $options;

	function __construct() {


		add_action( 'admin_init', array(&$this, 'admin_init' ) );
		add_action( 'admin_menu', array(&$this, 'admin_menu' ) );
	}

  /**
   * hook into WP's admin_init action hook
   * https://codex.wordpress.org/Function_Reference/add_options_page
   * https://codex.wordpress.org/Function_Reference/add_settings_section
   */
  public function admin_init(){
				$the_page = isset($_GET['page']) ? $_GET['page'] : 'null';
				if( $the_page =="impressionz_settings"
						or $the_page =="impressionz_cannibalization"
						or $the_page =="impressionz") :
					$imp_staus = get_option('imp_status', 'error');
					if(	$imp_staus == 'error') :
						$msg = get_option('imp_message', 'Please check your <a href="%imp_license%">License</a>.');
							$THE_URL = admin_url().'admin.php?page=impressionz_license';
						$msg = str_replace('%imp_license%', $THE_URL ,$msg);
				 	wp_die($msg);
					endif;
			endif;
		//options
		// print_r(Plugin::get('options'));
		foreach ( Plugin::get('options') as $key => $option) : $option =  (object) $option;
			$page   = __NAMESPACE__.'_'.$option->page;
			foreach ($option->sections as $key => $section) : $section = (object) $section;
				$sectionName = 	$page.'_'. $section->slug;
	       add_settings_section(
          $sectionName, //section
          $section->title,
          array(&$this, 'settings_section_header'),
          $page //page
				);
				foreach ($section->fileds as $key => $filed) :

					$filed = (object) $filed;
					$filedName = $filed->name;
					$filedID 	 = $sectionName.'_'.$filedName;

					/**
					* https://developer.wordpress.org/reference/functions/register_setting/
					*/
					register_setting($page.'_group',
					$filed->name
					//, array(
					// 	 'type'							=>'string',
					// 	// 'sanitize_callback'=>'',
					// 	'description'		=> 'asdasds',
					// 	// 'show_in_rest'	=>
					// 	 'default' =>'22',
					// )

					);
				//	echo $page.'_group';
				//	echo $filedID.' , ';
					add_settings_field(
      	 $filedID, 			//filed ID
        $filed->title, //filed title
        array(&$this, 'include_filed'), //callback
        	$page,  			//page
         $sectionName, //section
         array(				//args
             'name' 		=> $filed->name,
             'type' 		=> $filed->type,
		 										'value' 		=> !empty( $filed->value ) ? $filed->value : NULL,
													'choices'	=> !empty( $filed->choices ) ? $filed->choices : array(),
													'desc'				=> !empty( $filed->desc ) ? $filed->desc : NULL,
													'label_for' => 'myprefix_setting-id',
													'attr'			=> !empty( $filed->attr ) ? $filed->attr : NULL,
         )
     );
				endforeach; //fileds
			endforeach; //section
		endforeach; //options
  }


		// include filed file
    public function include_filed( $args ){ $args = (object) $args;
    	include( Plugin::path().'inc/fields/options/'.$args->type.'.php');
    }

   //section description
   public function settings_section_header($args){
   			foreach ( Plugin::get('options') as $key => $option) : $option =  (object) $option;
    			$page   = __NAMESPACE__.'_'.$option->page;
					foreach ($option->sections as $key => $section) : $section = (object) $section;
						$sectionName = 	$page.'_'. $section->slug;
						if ($args['id'] == 	$sectionName ) :
							echo $section->desc;
						endif;
					endforeach; //section
    		endforeach; //options
   	 	// print_R($args);
   	// echo '</pre>';
				//echo "Asd";
   }


	function  display() { ?>
		<div class="wrap">
			<?php foreach ( Plugin::get('options') as $key => $option) : $option =  (object) $option; ?>
				<?php if ($_GET['page'] == $option->page) : ?>
					 <h2><?php echo $option->title ?></h2>
					 <?php if ($option->menu!="") : ?>
					 <?php $this->menu(); ?>
				<?php endif; ?>

				<?php  $form = isset($option->form) ? $option->form : true;
				 if ( $form!==false ) :?>
				 <form method="post" action="options.php">
			        <?php @settings_fields(__NAMESPACE__.'_'.$option->page.'_group'); ?>
			        <?php //@do_settings_fields(__NAMESPACE__.'_'.$option->page.'_group'); ?>
			        <br />
			        <?php do_settings_sections(__NAMESPACE__.'_'.$option->page); ?>
			        <?php @submit_button(); ?>
			 		   </form>
			 		 <?php else: ?>
							<form method="post">
								<?php include( Plugin::path().'inc/fields/options/'.$option->callback.'.php'); ?>
						</form>
	 			<?php endif; ?>
	  <?php endif; ?>
		<?php endforeach; ?>
	</div>

		<?php
	}


  //MENUS

	//https://developer.wordpress.org/reference/functions/add_submenu_page/
	//https://stackoverflow.com/questions/24978982/how-to-hide-wordpress-submenu-page

	function admin_menu() {

		$icon = 'https://api.iconify.design/whh:seo.svg?color=%23fff&height=18&inline=true';

		add_menu_page(
			'Content',
			'Content',
			'manage_options',
			'impressionz',
			array( $this, 'display' ),
			$icon ,
			999, 34 );

		add_submenu_page('impressionz',
	    __( 'Cannibalization', 'imp' ),
	    __( 'Cannibalization', 'imp' ),
	    'manage_options',
	    'impressionz_cannibalization',
	     array( $this, 'display' )
	   );

		add_submenu_page('impressionz',
					__( 'Settings', 'imp' ),
					__( 'Settings', 'imp' ),
					'manage_options',
					'impressionz_settings',
						array( $this, 'display' )
				);

			add_submenu_page('impressionz',
						__( 'License', 'imp' ),
						__( 'License', 'imp' ),
						'manage_options',
						'impressionz_license',
							array( $this, 'display' )
					);


	}


	function  menu( $args = array() ){
		$pageSlug = $_GET['page'];
		foreach (Plugin::get('options') as $i => $option) : $option = (object) $option;
				$current_page_menu = $option->menu;
				$menus[$option->menu][] = array(
							'page'	=>  $option->page,
							'title'	=>  isset( $option->menu_title ) ? $option->menu_title : $option->title,
							'slug'	=>  $option->page
				);
		endforeach;

		//	$class = isset($args['class']) ? $args['class']  : '';
		echo '<nav class="nav-tab-wrapper settings-nav-tab-wrapper">';
			foreach ( $menus['main'] as $key => $menu ) {
				$menu = (object) $menu;
				$active_class = (	$pageSlug == $menu->page) ? 'nav-tab-active' : '';
				echo '<a href="'.admin_url('admin.php').'?page='.$menu->page.'" class="nav-tab '.$active_class.'">'.$menu->title.'</a>';
			}
		echo "</nav>";
	}

}
endif;
