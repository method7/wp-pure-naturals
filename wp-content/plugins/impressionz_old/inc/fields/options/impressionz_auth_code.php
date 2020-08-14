<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly

  $prefix = IMP\prefix();


 // Get the field name from the $args array
  $field = $args->name;
  // Get the value of this setting
  $value = get_option($field, $args->value);
  // echo a proper input type="text"

  // $type = isset( $args['type'] );

  //$token = get_option('imp_token', '');
  $token = get_option('imp_gsc_token', '');

  if  ( $token != "" ) :
    echo '<strong style="color:green;">Active</strong><br />';
    echo '<a href="'.admin_url().'/admin.php?page=impressionz_settings&imp_clear_cache=true" class="button button-secondary" style="margin-top:5px;">Clear Authorization</a>';
  else:
   $placeholder =  isset( $args->attr['placeholder'] ) ? $args->attr['placeholder'] : 'Entry';
   echo sprintf('<input type="text" name="%s" id="%s" value="%s" placeholder="%s" style="width:482px"/>', $field, $field, $value, $placeholder);
   echo ($args->desc ) ? '<p><i><small>'.$args->desc.'</small></i></p>' : '';
  endif;
