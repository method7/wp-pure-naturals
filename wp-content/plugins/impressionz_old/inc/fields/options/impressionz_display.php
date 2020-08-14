<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Get the field name from the $args array
// print_R($args);
$field = $args->name;
//echo $field;
$desc = !empty( $args->desc ) ? $args->desc  : NULL;
$desc2 = !empty(  $args->desc  ) ?  $args->desc  : NULL;
// Get the value of this setting
$values = get_option($field, array('post', 'page'));
$values = ($values) ? $values : array('post', 'page');
// $values = array_fill_values($values);
$values = array_combine($values , $values );

//$types = array('post', 'page', 'taxonomy', 'author', 'other' );

$types =  json_decode(IPM_DISPLAY);
// print_r(IPM_DISPLAY);

foreach ($types as $key => $type) {
$selected = ( in_array($type, $values ) ) ? 'checked="checked"' : '';
echo '<label style="display:block; margin-bottom:10px;">
       <input name="' . $field . '[]" id="' . $field . '" type="checkbox"  value="'.$type.'" class="check_class" class="code" ' . $selected  . ' /> '.$type.'
      </label>';
}
