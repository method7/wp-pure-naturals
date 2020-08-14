<?php

namespace IMP;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'IMP\Register_Plugin_Post_Meta_Boxes' ) ) :
 /**
   * Meta Boxes
   *
   * register meta boxes and fileds from global config
   * create custom in /inc/fildes/
   *
   * https://codex.wordpress.org/Function_Reference/add_meta_box
   * https://developer.wordpress.org/reference/functions/add_meta_box/
   *
   * @package    WordPress
   * @subpackage GOX Framework
   * @since 	 	 1.0.0
   *
   * @version 		1.1.0
   *
   */
	class Register_Plugin_Post_Meta_Boxes{

		var $config;
		var $meta_boxes;

		function __construct( $meta_boxes = array() ){

			global $config;

			$this->meta_boxes = $meta_boxes ;

			//save meta[] fileds
			add_action( 'save_post', array( &$this, 'save_metabox' ) );

			//include meta boxes scripts
			add_action( 'admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts' ) );

			//init add_meta_boxes
			add_action( 'add_meta_boxes', array( &$this, 'render_meta_boxes' ) );

		}

		/**
		 * 	Save custom meta
		 *
		 * @since 1.0
		 *
		 * @return void
		 **/
		 function save_metabox( $post_id ){

			// Check if our nonce is set.
			if ( ! isset( $_POST['gox_custom_box_nonce'] ) )
				return $post_id;

				$nonce = $_POST['gox_custom_box_nonce'];

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $nonce, 'gox_custom_box' ) )
				return $post_id;

			// If this is an autosave, our form has not been submitted,
	    //so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return $post_id;

			// Check the user's permissions.
			if ( 'page' == $_POST['post_type'] ) {

				if ( ! current_user_can( 'edit_page', $post_id ) )
					return $post_id;

			} else {

				if ( ! current_user_can( 'edit_post', $post_id ) )
					return $post_id;
			}

			/* OK, its safe for us to save the data now. */
			if ( !empty( $_POST['meta'] ) ) :

				//debug
				//print_R( $_POST['meta'] );

				//update all meta in json format
				update_post_meta( $post_id,	'json', json_encode( $_POST['meta']) );
				//insert meta value by fileds name
				foreach ( $_POST['meta'] as $name => $value) :
					update_post_meta( $post_id, $name, $value );
					// update_post_meta( $post_id, $this->slug.'_'.$key, $value );
				endforeach;

				//save locations
				if ( !empty( $_POST[ 'meta' ][ 'locations' ] )  ) :
					delete_post_meta( $post_id,'location' );
					foreach ( $_POST[ 'meta' ][ 'locations'] as $name => $value) :
						add_post_meta( $post_id, 'location', $value );
						// update_post_meta( $post_id, $this->slug.'_'.$key, $value );
					endforeach;
				else:
					delete_post_meta( $post_id, 'location' );
					add_post_meta( $post_id, 'location', 'front' );
					add_post_meta( $post_id, 'locations', array( 'front' ) );
				endif;

				//save media_query
				if ( !empty( $_POST[ 'meta' ][ 'media_queries' ] )  ) :
					delete_post_meta( $post_id, 'media_query' );
					foreach ( $_POST[ 'meta' ][ 'media_queries' ] as $name => $value) :
						add_post_meta( $post_id, 'media_query', $value );
						// update_post_meta( $post_id, $this->slug.'_'.$key, $value );
					endforeach;
				endif;

			endif;
		}

		/**
		 * 	Load boostrap columnes and forms styles and srcipts in adnim panel
		 *
		 * @since 1.0
		 *
		 * @return void
		 **/
		function admin_enqueue_scripts() {
			global $pagenow;
			global $config;
			if (( $pagenow == 'post.php' ) or ( $pagenow == 'post-new.php' ) ) {
 				//&& ($_GET['post_type'] == 'page')
			  // editing a page
		    wp_register_style( 'bootstrap-columnes',  plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/meta-fileds-columnes.css', false, '1.0.0' );
		    wp_enqueue_style( 'bootstrap-columnes' );
			  	wp_register_style( 'bootstrap-forms',   plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/meta-fileds-forms.css', false, '1.0.0' );
		    wp_enqueue_style( 'bootstrap-forms' );
 					wp_enqueue_style('jquery-ui-css',   plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/jquery-ui.css', false, '1.0.0' );

			}
		}

		/**
		 * 	render_meta_boxes
		 *
		 * @since 1.0
		 *
		 * @return void
		 **/
		 function render_meta_boxes( $post_type ){

		 	//debug
		 	//print_r(config::get('meta-box'));

		 	global $config;
			 if ( $this->meta_boxes ) :
					foreach ( $this->meta_boxes as $value) :
						 $context  = !empty( $value[ 'context' ] )  ? $value[ 'context' ]  : 'normal' ;
						 $priority = !empty( $value[ 'priority' ] ) ? $value[ 'priority' ] : 'default';
						 $slug 		 =  isset( $value[ 'slug' ] ) ? $value[ 'slug' ] : $value[ 'id' ];
						 self::add_meta_box( $post_type, $this, $value[ 'post_types' ],  $slug , $value[ 'title' ], $context, $priority   );
					endforeach;
				endif;
		}


		/**
		 * add_meta_box
		 *
		 * @since 1.0
		 *
		 * @return html
		 **/
		static function add_meta_box( $post_type, $thiss, $post_types = null , $id = 'meta_box_content', $title = 'Setup', $context ='normal', $priority = 'default' ){
			 //limit meta box to certain post types
   	if ( in_array( $post_type, $post_types )) :
						add_meta_box(
							$id
							, $title
							, array( &$thiss, 'render_meta_boxes_content' )
							, $post_type
							, $context  //'normal', 'advanced', or 'side'
							, $priority //'high', 'core', 'default' or 'low')
						);
    endif;
		}

		/**
		 * 	Render fields
		 *
		 * @author Goran Petrovic
		 * @since 1.0
		 *
		 * @return void
		 **/
		 function render_meta_boxes_content( $post, $data ){
		 	global $config;


			 if ( $this->meta_boxes ) :
					foreach ( $this->meta_boxes as $value ) :
						if ( $data[ 'id' ] == $value[ 'id' ] ) :
							$postbox_id = $data[ 'id' ];
							//description
							$desc = !empty ( $value[ 'desc' ] ) ? $value[ 'desc' ] : NULL;
							$this->add_meta_box_content( $post, $postbox_id , $value[ 'fields' ], $desc );
						endif;
					endforeach;
				endif;
		}

		/**
		 * add_meta_box_content
		 *
		 * https://codex.wordpress.org/Validating_Sanitizing_and_Escaping_User_Data
		 * @since 1.0
		 *
		 * @return html
		 **/
	 	 function add_meta_box_content( $post, $postbox_id , $fields = array(), $desc = '' ){

			// Add an nonce field so we can check for it later.
			wp_nonce_field( 'gox_custom_box', 'gox_custom_box_nonce' );

			 	echo '<div id="meta_'.$postbox_id.'" class="row meta_post_row" style="">';
					echo '<div id="meta_form_group_'.$postbox_id.'-form-group" class="form-group" style="margin-top:0px;">';
						//description
						echo !empty( $desc ) ? '<div class="inside"><small class="howto" id="new-tag-post_tag-desc">' . $desc . '</small></div>' : NULL ;
							foreach ($fields as $key => $field) :
								//format data
								$type 			  		= isset( $field['type'] ) ? $field['type'] : 'text';
								$field['name']		= isset( $field['name'] ) ?  sanitize_key( $field['name'] ) : 'text';
								$field['ID']			= isset( $field['ID'] 	) ? sanitize_key( $field['title'] ) : '';
								$field['title']   = isset( $field['title']) ? $field['title']: '';
								$field['attr'] 	  = isset( $field['attr'] ) ? $field['attr']: '';
								// $field['attr']['id'] = isset( $field['ID'] 	) ? sanitize_key( $field['title'] ) : '';
								$field['default'] = isset( $field['default']) ? $field['default']: '';

								$field['desc']	  = (!empty( $field['desc'] )) ? $field['desc'] : '';
								$field['col']		  = (!empty( $field['col'] )) ? $field['col'] : 'col-12';
								$field['choices'] = (!empty( $field['choices'] )) ? $field['choices'] : array();

								//default value
								$field['default'] = isset( $field['default']) ? $field['default']: '';
								$field['value']	 	= isset( $field['value']) ? $field['default']: '';

								$field['fileds']	 	= isset( $field['fileds']) ? $field['fileds']: array();

								//get value
								$value 					 = get_post_meta($post->ID, $field['name'] , true);
								$field['value']  = isset( $value ) ? $value : $field['value'];
								$field 					 = (object) $field; //return object

								//debug
								//print_R(	$field);
								echo '<div id="filed-'.$field->name.'-col" class="'. $field->col .'" style="float:left; margin-bottom:10px;">';
									//include fileds from admin/fileds/meta/
									include( plugin_dir_path( dirname( __FILE__ ) ).'fields/meta/'.$type.'.php');
								echo '</div>';
							endforeach;
						echo "</div>";
				echo "</div>";

		}

	}
endif;
?>
