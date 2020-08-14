<?php
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
		/**
		 * 	Radio field for meta box
		 *
		 * @author Goran Petrovic
		 * @since 1.0
		 *
		 * @return html
		 **/		
		global $post;


			foreach ( $field->fields as $key => $field ) :
		
					//format data
					$type 			  		= isset( $field['type'] ) ? $field['type'] : 'text';
					$field['name']		= isset( $field['name'] ) ?  sanitize_key( $field['name'] ) : 'text';
					$field['ID']			= isset( $field['ID'] 	) ? sanitize_key( $field['title'] ) : '';	
					$field['title']   = isset( $field['title']) ? $field['title']: '';
					$field['attr'] 	  = isset( $field['attr'] ) ? $field['attr']: '';
					$field['attr']['id'] = isset( $field['ID'] 	) ? sanitize_key( $field['title'] ) : '';
					$field['default'] = isset( $field['default']) ? $field['default']: '';

					$field['desc']	  = (!empty( $field['desc'] )) ? $field['desc'] : '';
					$field['col']		  = (!empty( $field['col'] )) ? $field['col'] : 'col-12';
					$field['choices'] = (!empty( $field['choices'] )) ? $field['choices'] : array();

					//default value
					$field['default'] = isset( $field['default']) ? $field['default']: '';
					$field['value']	 	= isset( $field['value']) ? $field['default']: '';

					//get value
					$value 					 = get_post_meta( $post->ID, $field['name'] , true);
					$field['value']  = isset( $value ) ? $value : $field['value'];
					$field 					 = (object) $field; //return object

					//debug
					//print_R(	$field);
					echo '<div id="filed-'.$field->name.'-col" class="'. $field->col .'" style="float:left; margin-bottom:10px;">';
						//include fileds from admin/fileds/meta/
						include( plugin_dir_path( dirname( dirname( __FILE__ ) ) ).'fields/meta/'.$type.'.php');
					echo '</div>';
			
			//	include( plugin_dir_path( dirname( __FILE__ ) ).'fields/meta/'.$field->type.'.php');
			 endforeach; 



			//debug args 
			//print_r( $field );
				
		 // 	echo !empty($field->title) ? "<label>{$field->title}</label>": ''; 

		 // 	echo '<br />
		 // 	 <label class="form-check-label" style="font-weight:normal;">
   //      <input type="radio" class="form-check-input" name="optionsRadios" id="optionsRadios2" value="option2" style="margin-top:-3px;" >
   //      Option two can be something else and selecting it will deselect option one
   //    </label><br />
   //     <label class="form-check-label" style="font-weight:normal;">
   //      <input type="radio" class="form-check-input" name="optionsRadios" id="optionsRadios2" value="option2" style="margin-top:-3px;" >
   //      Option two can be something else and selecting it will deselect option one
   //    </label><br />
   //     <label class="form-check-label" style="font-weight:normal;">
   //      <input type="radio" class="form-check-input" name="optionsRadios" id="optionsRadios2" value="option2" style="margin-top:-3px;" >
   //      Option two can be something else and selecting it will deselect option one
   //    </label>';

		 // 	// echo GOX\Form::select("meta[{$field->name}]", $field->value, $field->choices,  $field->attr); //helper 


			// echo '<small class="howto" id="new-tag-post_tag-desc">'.$field->desc.'</small>';
	
