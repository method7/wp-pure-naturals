<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Get the field name from the $args array
$field = $args['field'];
$desc = !empty( $args['desc'] ) ? $args['desc'] : NULL;
$desc2 = !empty( $args['desc2'] ) ? $args['desc2'] : NULL;
// Get the value of this setting
$value = get_option($field);
echo '<label style="display:block;"><input name="' . $field . '" id="' . $field . '" type="checkbox"  value="1" class="check_class" class="code" ' . checked( 1, $value , false ) . ' /> '.$desc.' <small>'.$desc2.'</small></label>';
