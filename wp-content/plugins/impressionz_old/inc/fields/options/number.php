<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly


 // Get the field name from the $args array
  $field = $args->name;
  // Get the value of this setting
  $value = get_option($field,  $args->value );
  // echo a proper input type="text"
  // $type = isset( $args['type'] );

  echo sprintf('<input type="number" name="%s" id="%s" value="%s" placeholder="" min="1" max="10000000" step="1" required/>', $field, $field, $value);
  echo ($args->desc ) ? '<p><i><small>'.$args->desc.'</small></i></p>' : '';
