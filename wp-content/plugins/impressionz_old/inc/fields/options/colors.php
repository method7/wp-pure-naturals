<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly

	 // Get the field name from the $args array
  $field = $args->name;
  // Get the value of this setting
  $value = get_option($field, $args->value);
  // echo a proper input type="text"

  // $type = isset( $args['type'] );

  $placeholder =  isset( $args->placeholder ) ? $args->placeholder : 'Entry';
		echo '<div class="gox-option-colors">';
  echo sprintf('<input type="color" name="colors[]" id="%s" value="%s" placeholder="%s"/>', $field, $field, $value, $placeholder);

  echo sprintf('<input type="color" name="colors[]" id="%s" value="%s" placeholder="%s"/>', $field, $field, $value, $placeholder);
  echo sprintf('<input type="color" name="colors[]" id="%s" value="%s" placeholder="%s"/>', $field, $field, $value, $placeholder);
  echo sprintf('<input type="color" name="colors[]" id="%s" value="%s" placeholder="%s"/>', $field, $field, $value, $placeholder);
  echo sprintf('<input type="color" name="colors[]" id="%s" value="%s" placeholder="%s"/>', $field, $field, $value, $placeholder);
  echo sprintf('<input type="color" name="colors[]" id="%s" value="%s" placeholder="%s"/>', $field, $field, $value, $placeholder);
  echo sprintf('<input type="color" name="colors[]" id="%s" value="%s" placeholder="%s"/>', $field, $field, $value, $placeholder);

  echo ($args->desc ) ? '<p><i><small>'.$args->desc.'</small></i></p>' : '';




  ?>


</div>
