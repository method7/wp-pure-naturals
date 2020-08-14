<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
	/**
	 * 	Checkbox field for meta box
	 *
	 * @author Goran Petrovic
	 * @since 1.0
	 *
	 * @return html
	 **/		


		//debug args 
		//print_r( $field );

		echo '<input name="meta['.$field->name.']" type="hidden" value="0">
		<div class="form-check" style="margin-top:10px;"><label class="form-check-label"><input style="margin-top:-3px;" type="checkbox" name="meta['.$field->name.']" value="1"  '.checked(	$field->value , '1', false).'  > '.$field->title.'</label></div>';
		echo '<small class="howto" id="new-tag-post_tag-desc">'.$field->desc.'</small>';
	
