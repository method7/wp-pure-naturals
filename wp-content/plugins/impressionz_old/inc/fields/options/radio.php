<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly

 	// Get the field name from the $args array
          	$field = $args['field'];
						$desc = !empty( $args['desc'] ) ? $args['desc'] : NULL;
						$desc2 = !empty( $args['desc2'] ) ? $args['desc2'] : NULL;
           	// Get the value of this setting
           	$value = get_option('customize_style_active_options');
	 	  	echo '<label style="height:10px;"><input name="input_radio" id="' . $field . '" type="radio" value="' .$field . '" class="code" ' . checked( $field, $value , false ) . ' /> '.$desc.'<small>'.$desc2.'</small></label>';
