<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly

   // Get the field name from the $args array
   $field = $args->name;
   // Get the value of this setting
   $value = get_option($field, $args->value);
  // echo a proper input type="text"

  echo sprintf('<textarea type="text" name="%s" id="%s" class="form-controle" style="min-height:250px;">%s</textarea>', $field, $field, $value);
  echo ($args->desc ) ? '<p><i><small>'.$args->desc.'</small></i></p>' : '';
