<?php
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	/**
	 * Textarea field for meta box
	 *
	 * This file will be include in admin/classes/Register_Post_Meta_Boxes.php as filed
	 *
	 * @author Goran Petrovic
	 * @since 1.0
	 *
	 * @return html
	 **/

 	echo !empty($field->title) ? "<label>{$field->title}</label>": '';
 	echo GOX\Form::textarea("meta[{$field->name}]", $field->value, $field->attr); //helper
		echo '<small class="howto" id="new-tag-post_tag-desc">'.$field->desc.'</small>'; 
