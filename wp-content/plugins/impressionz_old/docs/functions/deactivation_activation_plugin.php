<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

 //activation
 register_activation_hook( 'impressionz/index.php', 'impressionz_activation_hook' );
 function impressionz_activation_hook(){
   update_option('imp_authorization_code', '');
   update_option('imp_gsc_property', get_site_url() );
   update_option('imp_gsc_date', '28');
   update_option('imp_gsc_impression', '100');
 }

 //deactivation
 register_deactivation_hook( IMP\basename(), 'impressionz_deactivation_hook' );
 function impressionz_deactivation_hook(){
   $prefix = IMP\prefix();

   $options  = [
    'gsc_data',
    'gsc_token',
    'gsc_status',
    'gsc_message',
    'gsc_property',
    'authorization_code',
    'gsc_date',
    'gsc_impression',
    'gsc_offset',
    'count_cron_status'


   ];

    $old_options  = [
     'cop_gsc',
     'impressionz_license',
     'impressionz_license_key',
     'impressionz_license_message'
    ];

    $old_post_meta  = [
     'cop_gsc',
     'cop_gsc_new',
     'cop_gsc_set'
    ];
   //detete options
   foreach ($options as $key => $option) {
    delete_option( $prefix.$option );

   }

   foreach ($old_options as $key => $option) {
    delete_option( $option );
   }

  }
  ?>
