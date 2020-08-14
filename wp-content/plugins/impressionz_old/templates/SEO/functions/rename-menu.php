<?php


if (!defined('ABSPATH')) exit; // Exit if accessed directly

function imp_rename_admin_menu() {

    global $menu;

    foreach($menu as $key => $item) {
      if ( $item[0] === 'Content' ) {
          $menu[$key][0] = __('Impressionz','textdomain');     //change name
          // $menu[$key][1] = __('Adressbuch','textdomain');   //does not work but should (needs another hook?)
          // $menu[$key][2] = __('dashicons-id','textdomain'); //change icon
      }
    }
   return false;
}
add_action( 'admin_menu', 'imp_rename_admin_menu', 999 ); ?>
