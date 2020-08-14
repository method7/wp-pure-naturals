<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
	/**
	 * 	Checkbox field for meta box
	 *
	 * @author Goran Petrovic
	 * @since 1.0
	 *
	 * @return html
	 **/		

		//print_R($field->value);
		//debug args 
		//print_r( $field );
		//echo '<input name="meta['.$field->name.'][]" type="hidden" value="">';
	// print_R(get_editable_roles());

		foreach ( $field->choices as $key => $choice) {
			# code...
		$value = isset( $field->value[ $choice ] ) ? $field->value[ $choice ] : null;

		echo '<div class="form-check" style="margin-top:10px;"><label class="form-check-label"><input style="margin-top:-3px;" type="checkbox" name="meta['.$field->name.']['.$choice.']" value="'.$key.'"  '.checked(		$value  , $key, false).'  > &nbsp; '. $choice.'</label></div>';

	
		}

		echo '<small class="howto" id="new-tag-post_tag-desc">'.$field->desc.'</small>';