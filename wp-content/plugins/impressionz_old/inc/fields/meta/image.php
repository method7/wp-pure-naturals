<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
*  Image field for meta box
*
* @author Goran Petrovic
* @since 1.0
*
* @return html
**/		

echo !empty($field->title) ? "<label>{$field->title}</label><br/>": ''; 
echo GOX\Form::wp_image("meta[$field->name]", $field->value, $field->attr); 
echo '<small class="howto" id="new-tag-post_tag-desc">'.$field->desc.'</small>';

