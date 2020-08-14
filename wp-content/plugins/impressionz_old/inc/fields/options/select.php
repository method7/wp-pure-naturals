<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly

   // Get the field name from the $args array
    $field = $args->name;
    // Get the value of this setting
    $value = get_option($field, $args->value);
    // echo a proper input type="text"

		/*choices*/
		echo \GOX\Form::select(  $field ,  $value ,  $args->choices );
		echo ($args->desc ) ? '<p><i><small>'.$args->desc.'</small></i></p>' : '';
