<?php

namespace IMP;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'IMP\Register_Plugin_Post_Types' ) ) :
 /**
   * Register Post Types from global config
   * https://v2.wp-api.org/extending/custom-content-types/
   *
   * @package    	WordPress
   * @subpackage 	GOX Framework
   * @since 			1.0.0
   *
   * @version 		1.0.0
   *
   */
	class Register_Plugin_Post_Types{

		var $return;
		var $post_types;

		function __construct( $post_types = array() ){



			//regiter post types
			$this->post_types = array_merge( $post_types , $this->get_custom_post_types() ) ;

			//$this->post_types = $this->get_custom_post_types();


			//register config post-type for all active parts
			add_action( 'init', array(&$this, 'post_types_registration' ) );

			/* Flush rewrite rules for custom post types. */
			register_deactivation_hook( plugin_dir_path( dirname( dirname( __FILE__ ) ) ), 'flush_rewrite_rules' );
			register_activation_hook( plugin_dir_path( dirname( dirname( __FILE__ ) ) ), array($this, 'plugin_flush_rewrites') );

			/* Flush rewrite rules for custom post types. */
			add_action( 'after_switch_theme', 'flush_rewrite_rules' );
		}


		/**
		* This is how you would flush rewrite rules when a plugin is activated or deactivated:
		* https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
		*
		*/
		function plugin_flush_rewrites() {

		  // ATTENTION: This is *only* done during plugin activation hook in this example!
	    // You should *NEVER EVER* do this on every page load!!
			flush_rewrite_rules();
		}


		function get_custom_post_types(){


			$args = array(
				'post_type'=> 'gox_post_type',
				'fields'=> 'ids'
			);

			$gox_post_type = get_posts( $args );

			$results = [];

			foreach ($gox_post_type as $key => $pt) {

				$results[ $pt[ $key  ] ] = array(

					 'slug' 				=> get_post_field( 'post_name' , $pt) ,
           'label' 				=> get_post_field( 'post_title' , $pt),
           'single-label' => get_post_field( 'title-single' , $pt),
           	'public' 			 			=> 0,
				    'publicly_queryable'=> 1,
				    'show_ui' 			 		=> true,
				    'show_in_menu' 		 	=> true, //edit.php?post_type=gox_css
				    'query_var' 			 	=> true,
				    'rewrite' 			 	 	=> 1,
				    'has_archive' 		 	=> 1,
				    'hierarchical' 		 	=> 1,
						'menu_position' 	 	=> get_post_field( 'menu_order' , $pt),
						'supports'					=> get_post_field( 'supports'),
						'taxonomies'				=> array(),
          );

			}
			// print_R( $posts );

			// print_r(	$this->post_types );
			//print_r($results );
			return $results;
		}

		/**
		 * Register Config::( post types ) and taxonomies
		 *
		 * https://codex.wordpress.org/Function_Reference/register_post_type
		 * https://developer.wordpress.org/reference/functions/register_post_type/
		 *
		 * @return void
		 */

		function post_types_registration() {

			//debug
			//print_R( config::get('post-type') );

			if( $this->post_types  ) :
				//register config post-type
				foreach ( $this->post_types  as $key => $value ) :
					if ( $key != 'wp' ) :
							register_post_type( $value['slug'], array(
										'labels' 			=> array(
											'name' 				 			=> $value['label'],
											'singular_name' 	 	=> $value['single-label'],
											'add_new' 			 		=> 'Add '. $value['single-label'],
											'add_new_item'  	 	=> 'Add New '. $value['single-label'],
											'edit' 				 			=> 'Edit',
											'edit_item' 		 		=> 'Edit '. $value['single-label'],
											'new_item' 			 		=> 'New '. $value['single-label'],
											'view' 				 			=> 'View '.  $value['single-label'],
											'view_item' 		 		=> 'View '. $value['single-label'],
											'search_items'  	 	=> 'Search '. $value['label'],
											'not_found' 		 		=> 'No ' . $value['single-label'] . ' found',
											'not_found_in_trash'=> 'No ' . $value['single-label'] . ' in Trash',
											'parent' 			 			=> 'Parent ' . $value['single-label'],
										),
										'public' 			 				=> isset( $value['public'] ) 			  ? $value['public'] : true,
								    'publicly_queryable' 	=> isset( $value['publicly_queryable'] ) ? $value['publicly_queryable'] : true,
								    'show_ui' 			 			=> isset( $value['show_ui'] ) 			  ? $value['show_ui'] : true,
								    'show_in_menu' 		 		=> isset( $value['show_in_menu'] ) 	  ? $value['show_in_menu'] : true,
								    'query_var' 					=> isset( $value['query_var'] ) 		  ? $value['query_var'] : true,
								    'rewrite' 			 	 		=> isset( $value['rewrite'] ) 			  ? $value['rewrite'] : true,
								    'has_archive' 		 		=> isset( $value['has_archive'] ) 		  ? $value['has_archive'] : false,
								    'hierarchical' 		 		=> isset( $value['hierarchical'] )       ? $value['hierarchical'] : false,
										'menu_position' 	 		=> isset( $value['menu_position'] )      ? $value['menu_position'] : 15,
										'supports' 				 		=> isset( $value['supports'] )      ? $value['supports'] : array(),
										'rewrite' 			 	 		=> array( 'slug' => $value['slug'], 'with_front' => true  ),
										'menu_icon'						=> isset( $value['menu_icon'] )      ? $value['menu_icon'] : null,
										//'capability_type' 		=>  array('post', 'oglas') ,
										// 'capabilities' 				=> array('create_posts' => 'edit_oglas'),
										//'map_meta_cap'    		=> true,
								)
							);


								// register post type taxonomies , make it hierarchical (like categories)
								foreach ($value['taxonomies'] as $taxonomy) :

									$labels = array(
							  	  'name' 							=> $taxonomy['single-label'],
								    'singular_name' 		=> $taxonomy['single-label'],
								    'search_items' 			=> 'Search'. $taxonomy['label'],
								    'all_items' 				=> 'All '.$taxonomy['label'],
								    'parent_item' 			=> 'Parent '.$taxonomy['single-label'],
								    'parent_item_colon' => 'Parent '.$taxonomy['single-label'].':',
								    'edit_item' 				=> 'Edit '.$taxonomy['single-label'],
								    'update_item' 			=> 'Update '.$taxonomy['single-label'],
								    'add_new_item' 			=> 'Add New '.$taxonomy['single-label'],
								    'new_item_name' 		=> 'New '.$taxonomy['single-label'].' Name',
								    'menu_name' 				=> $taxonomy['label'],
							  	);

									//multi post types in tax
									$post_types = ( isset( $taxonomy['post_types'] ) ) ? $taxonomy['post_types'] : $value['slug'];
									$slug 			= ( isset( $taxonomy['slug'] ) ) ? $taxonomy['slug'] : $taxonomy['id'];

									register_taxonomy( $slug, $post_types , array(
			 	            'hierarchical'			 => $taxonomy['hierarchical'],
				            'labels' 	  				 => $labels,
				            'show_ui' 	  			 => isset( $value['show_ui'] ) ? $value['show_ui'] : true,
				            'query_var'   			 => isset( $value['query_var'] ) ? $value['query_var'] : true,
										'show_admin_column'  => isset( $value['show_admin_column'] ) ? $value['show_admin_column'] : true,
										'public'				 		 => isset( $value['public'] ) ? $value['public'] : true,
										'show_in_quick_edit' => isset( $value['show_in_quick_edit'] ) ? $value['show_in_quick_edit'] : true,
										'publicly_queryable' => isset( $value['publicly_queryable'] ) ? $value['publicly_queryable'] : true,
										'show_in_rest'			 => isset( $value['show_in_rest'] ) ? $value['show_in_rest'] : true,
										'rest_base' 				 => isset( $value['rest_base'] ) ? $value['rest_base'] : true,
										// 'rest_controller_class' => isset( $value['rest_controller_class'] ) ? $value['rest_controller_class'] : true,
										//'update_count_callback' => '_update_post_term_count',
				            'rewrite' 	  			 => array( 'slug' =>  	$slug ),
				            //https://wordpress.stackexchange.com/questions/155629/custom-taxonomies-capabilities
				          //   'capabilites'       => array(
										    //     'manage_terms'  => 'manage_categories',
										    //     'edit_terms'    => 'manage_categories',
										    //     'delete_terms'  => 'manage_categories',
										    //     'assign_terms'  => 'edit_posts'
										    // )
										  //'description'=> 'asdasds',
		 			        	));

									endforeach;

					endif;
				endforeach;
			endif;
		}

	}
endif;
