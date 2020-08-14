<?php  if (!defined('ABSPATH')) exit; // Exit if accessed directly

	 echo !empty($field->title) ? "<label>{$field->title}</label>": '';

	 // echo $field->tax;
$args = array(
	'show_option_all'    => '',
	'show_option_none'   => '',
	'option_none_value'  => '-1',
	'orderby'            => 'ID',
	'order'              => 'ASC',
	'show_count'         => 0,
	'hide_empty'         => 0,
	'child_of'           => 0,
	'exclude'            => '',
	'include'            => '',
	'echo'               => 1,
	'selected'           => 0,
	'hierarchical'       => 1,
	'name'               => 'tax_input['.$field->tax.'][]',
	'id'                 => '',
	'class'              => 'postform form-control',
	'depth'              => 0,
	'tab_index'          => 0,
	'taxonomy'           => $field->tax,
	'hide_if_empty'      => false,
	'value_field'	       => 'term_id',
);


	wp_dropdown_categories( $args );

?>
