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
	

		
			//debug args 
			//print_r( $field );
				
		 	echo !empty($field->title) ? "<label>{$field->title}</label>": ''; 

		 	echo '<br />
		 	 <label class="form-check-label" style="font-weight:normal;">
        <input type="radio" class="form-check-input" name="optionsRadios" id="optionsRadios2" value="option2" style="margin-top:-3px;" >
        Option two can be something else and selecting it will deselect option one
      </label><br />
       <label class="form-check-label" style="font-weight:normal;">
        <input type="radio" class="form-check-input" name="optionsRadios" id="optionsRadios2" value="option2" style="margin-top:-3px;" >
        Option two can be something else and selecting it will deselect option one
      </label><br />
       <label class="form-check-label" style="font-weight:normal;">
        <input type="radio" class="form-check-input" name="optionsRadios" id="optionsRadios2" value="option2" style="margin-top:-3px;" >
        Option two can be something else and selecting it will deselect option one
      </label>';

		 	// echo GOX\Form::select("meta[{$field->name}]", $field->value, $field->choices,  $field->attr); //helper 


			echo '<small class="howto" id="new-tag-post_tag-desc">'.$field->desc.'</small>';
	
