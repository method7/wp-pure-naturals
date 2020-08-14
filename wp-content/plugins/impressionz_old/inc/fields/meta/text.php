<?php

	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	/**
	* 	Text field for meta box
	*
	* @author Goran Petrovic
	* @since 1.0
	*
	* @return html
	**/

	//debug args
	//print_r( $field );

 	echo !empty($field->title) ? "<label id='{$field->name}'>{$field->title}</label>": '';
 	echo GOX\Form::input("meta[{$field->name}]", $field->value, $field->attr); //helper 
 	echo '<small class="howto" id="new-tag-post_tag-desc">'.$field->desc.'</small>';
