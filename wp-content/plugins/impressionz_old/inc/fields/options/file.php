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
 //print_r($args);
$field = $args->name;
// Get the value of this setting
$value = get_option($field, $args->value);
// echo !empty( $value) ? "<label id='{$the_id}'>{$value}</label><br/>": '';
echo GOX\Form::file("$field", $value);
echo '<small class="howto" id="new-tag-post_tag-desc">'.$args->desc.'</small>';
