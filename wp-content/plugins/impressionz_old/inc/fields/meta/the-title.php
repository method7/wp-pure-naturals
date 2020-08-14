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
	//print_r($post);

	$value = isset($post->post_title) ? $post->post_title : NULL;

 	echo !empty($field->title) ? "<label id='{$field->name}'>{$field->title}</label>": ''; 
 	echo CSS\Form::input("post_title", $value, $field->attr); //helper 
 	echo '<small class="howto" id="new-tag-post_tag-desc">'.$field->desc.'</small>';

