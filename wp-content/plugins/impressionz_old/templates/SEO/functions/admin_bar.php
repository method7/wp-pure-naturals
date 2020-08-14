<?php

// if ( current_user_can( 'manage_options' ) ) :
//https://developer.wordpress.org/reference/classes/wp_admin_bar/add_node/
add_action('admin_bar_menu', 'imp_add_toolbar_items', 9999);
function imp_add_toolbar_items($admin_bar){
 if ( current_user_can( 'manage_options' ) ) :
   $icon = '<img src="https://api.iconify.design/whh:seo.svg?color=%23fff&height=18&inline=true" style="width:14px; height:14px; margin-right:4px;"/>';
    $admin_bar->add_menu( array(
        'id'    => 'impressionz',
        'title' => $icon.' Impressionz',
        'href'  => admin_url().'admin.php?page=impressionz',
        'meta'  => array(
            'title' => __('Content'),
        ),
    ));
  endif;
   $admin_bar->add_menu( array(
       'id'    => 'impressionz_content',
       'parent' => 'impressionz',
       'title' => 'Content',
       'href'  => admin_url().'admin.php?page=impressionz',
       'meta'  => array(
           // 'title' => __('My Sub Menu Item'),
           // 'target' => '_blank',
           // 'class' => 'my_menu_item_class'
       ),
   ));

    $admin_bar->add_menu( array(
        'id'    => 'impressionz_cannibalization',
        'parent' => 'impressionz',
        'title' => 'Cannibalization',
        'href'  => admin_url().'admin.php?page=impressionz_cannibalization',
        'meta'  => array(
            // 'title' => __('My Sub Menu Item'),
            // 'target' => '_blank',
            // 'class' => 'my_menu_item_class'
        ),
    ));
    $admin_bar->add_menu( array(
        'id'    => 'impressionz_settings',
        'parent' => 'impressionz',
        'title' => 'Settings',
        'href'  => admin_url().'admin.php?page=impressionz_settings',
        'meta'  => array(
            // 'title' => __('My Second Sub Menu Item'),
            // 'target' => '_blank',
            // 'class' => 'my_menu_item_class'
        ),
    ));
}
// endif;
 ?>
