<?php

namespace IMP;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

  /**
  * Reset options on change settigns
  *
  *
  *
  */

  new Save_Options;
  class Save_Options{
   //https://wordpress.stackexchange.com/questions/177272/hook-if-somebody-saves-plugin-options
   function __construct(){

     //reset
     add_action( 'update_option_imp_authorization_code', array($this, 'imp_authorization_code' ), 10, 2 );

     //reboot
     add_action( 'update_option_imp_gsc_property',  array($this, 'update_option_imp_gsc_property' ), 10, 2 );

     //impressions
     add_action( 'update_option_imp_gsc_impression', array($this, 'update_option_imp_gsc_impression' ), 10, 2 );

     //date
     add_action( 'update_option_imp_gsc_date',        array($this, 'update_option_imp_gsc_date' ), 10, 2 );

     //add_action( 'save_option_SEOCOP_impressionz_settings_group',array($this, 'add_option_cop_gsc_impression'), 10, 2 );
     //add_action( 'update_option_cop_gsc_impression', array($this, 'add_option_cop_gsc_impression' ), 10, 2 );

      add_action('admin_init', [$this, 'clear']);
   }

   function clear(){
    if ( isset( $_GET[ 'imp_clear_cache'] ) ) :

       delete_option( prefix().'_token' );
       delete_option( prefix().'gsc_data' );
       delete_option( prefix().'gsc_token' );
       delete_option( prefix().'gsc_sites' );

       delete_option( prefix().'authorization_code' );
       update_option( prefix().'authorization_code', '');


       update_option( prefix().'gsc_offset', 0 );
       update_option( prefix().'count_cron_status', 'stop' );

       delete_option( prefix().'gsc_property' );
       update_option( prefix().'gsc_property', get_site_url() );

      // update_option( prefix().'gsc_property', get_site_url() );

       update_option( prefix().'gsc_status', 'error' );
       update_option( prefix().'gsc_message', 'Request Authorization' );
       $redirect = admin_url().'admin.php?page=impressionz_settings';
       wp_safe_redirect($redirect);
    endif;
   }


   function imp_authorization_code( $old_value, $new_value ) {
      //update_option('imp_count_cron_status', 'start');
      $GSC_API = new GSC_API();
      $GSC_API->Client( $new_value );
      //$GSC_API->getQuery();
   }

   function update_option_imp_gsc_property(){
     $this->delete_all_data();
   }

   function update_option_imp_gsc_impression(){
     $this->delete_all_data();
   }

   function update_option_imp_gsc_date(){
     $this->delete_all_data();
   }

    /**
    * Hook into options page after save.
    */
    function delete_all_data( ) {
      //plugin prefix
      $prefix =  prefix();

      //delete post meta
      global $wpdb;
      $table = $wpdb->prefix.'postmeta';
      $wpdb->delete ($table, array('meta_key' => prefix().'gsc_data'));
      $wpdb->delete ($table, array('meta_key' => prefix().'gsc_new_kw'));
      $wpdb->delete ($table, array('meta_key' => prefix().'gsc_set_kw'));

      //old plugin data
      $wpdb->delete ($table, array('meta_key' => 'cop_gsc'));
      $wpdb->delete ($table, array('meta_key' => 'cop_gsc_new'));
      $wpdb->delete ($table, array('meta_key' => 'cop_gsc_set'));

      //delete term meta
      global $wpdb;
      $table = $wpdb->prefix.'termmeta';
      $wpdb->delete ($table, array('meta_key' => prefix().'gsc_data'));
      $wpdb->delete ($table, array('meta_key' => prefix().'gsc_new_kw'));
      $wpdb->delete ($table, array('meta_key' => prefix().'gsc_set_kw'));

       //delete old data
      delete_option('cop_gsc');


      //update_option( prefix().'gsc_property', $new_value );

     // update_option( prefix().'gsc_offset', 0);
      //
      // update_option( prefix().'count_cron_status', 'start' );
      update_option( prefix().'gsc_offset', 0);
      update_option( prefix().'count_cron_status', 'stop' );


       //
      delete_option('imp_gsc_data');
      //main cron
      update_option( prefix().'gsc_all_offset', 0);
      update_option( prefix().'gsc_cron_status', 'start' );

      // $GSC_API = new GSC_API();
      // $GSC_API->getQuery();

   }

  }
