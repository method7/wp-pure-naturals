<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_filter('cron_schedules', 'imp_add_cron_interval');

function imp_add_cron_interval($schedules)
{
    $schedules['two_minutes'] = array(
        'interval' => 120,
        'display'  => esc_html__('Two minutes'),
    );

    $schedules['three_minutes'] = array(
        'interval' => 180,
        'display'  => esc_html__('Three minutes'),
    );

    $schedules['ten_seconds'] = array(
        'interval' => 10,
        'display'  => esc_html__('Ten seconds'),
    );
    $schedules['three_days'] = array(
        'interval' => 259200,
        'display'  => esc_html__('Every 3 Days'),
    );



    return $schedules;
}

function imp_cron(){

    set_time_limit(60);
    //db prefix
    $prefix = IMP\prefix();
    $cron_status = get_option('imp_count_cron_status', 'stop');

    $resultarray = get_option( $prefix.'gsc_data', array() );

    if (  !empty( $resultarray ) ) :
     $limit = get_option('imp_gsc_limit', 5);
     $offset = get_option('imp_gsc_offset', 0);
     $newoffset = $offset + $limit;
     update_option('imp_gsc_offset', $newoffset);
     //update_option('imp_gsc_offset', $newoffset);
     // $urlmain = get_site_url();  // WORKS! promiti site_url
     //
     // if ($_SERVER['HTTP_HOST'] == 'localhost') {
     //     $urlmain = 'https://rs.youstuff.org';
     //     // $permalink = str_replace( get_site_url() , $urlmain, $permalink);
     // }

     //get data
     // $resultarray = get_option( $prefix.'gsc_data', array() );
     // $resultarray = current($resultarray);
     $keys        = array_keys($resultarray);
    // $arraySize = count($resultarray);
     $to = $offset + $limit;
     // if( isset( $keys[ $to ] ) ) {
     for ( $i = $offset; $i < $to; $i++ ) {
        if ( isset( $keys[ $i ] ) ) {
           $urlone = $keys[$i];
           $url_data = IMP\url_to_data( $urlone );
           imp_count_keywords( $url_data );
        }else{
          update_option('imp_gsc_offset', 0);
          update_option('imp_count_cron_status', 'stop');
          update_option('imp_count_kw_status', 'stop');
        }
     }
    // }else{
        // update_option('imp_gsc_offset', 0);
        // update_option('imp_count_cron_status', 'stop');
        //update_option('imp_gsc_offset_conunt', '');
    // }
   endif;
}
//add_action('wp_loaded','imp_cron');

add_action('imp_cron_hook', 'imp_cron');



function imp_cron_two(){

    set_time_limit(60);
    //db prefix
    $prefix = IMP\prefix();
    $cron_status = get_option('imp_count_cron_status', 'stop');

    $resultarray = get_option( $prefix.'gsc_data', array() );

    if (  !empty( $resultarray ) ) :
     $limit = get_option('imp_gsc_limit', 5);
     $offset = get_option('imp_gsc_offset', 0);
     $newoffset = $offset + $limit;
     update_option('imp_gsc_offset', $newoffset);
     //update_option('imp_gsc_offset', $newoffset);
     // $urlmain = get_site_url();  // WORKS! promiti site_url
     //
     // if ($_SERVER['HTTP_HOST'] == 'localhost') {
     //     $urlmain = 'https://rs.youstuff.org';
     //     // $permalink = str_replace( get_site_url() , $urlmain, $permalink);
     // }

     //get data
     // $resultarray = get_option( $prefix.'gsc_data', array() );
     // $resultarray = current($resultarray);
     $keys        = array_keys($resultarray);
    // $arraySize = count($resultarray);
     $to = $offset + $limit;
     // if( isset( $keys[ $to ] ) ) {
     for ( $i = $offset; $i < $to; $i++ ) {
        if ( isset( $keys[ $i ] ) ) {
           $urlone = $keys[$i];
           $url_data = IMP\url_to_data( $urlone );
           imp_count_keywords( $url_data );
        }else{
          update_option('imp_gsc_offset', 0);
          update_option('imp_count_cron_status', 'stop');
          update_option('imp_count_kw_status', 'stop');
        }
     }
    // }else{
        // update_option('imp_gsc_offset', 0);
        // update_option('imp_count_cron_status', 'stop');
        //update_option('imp_gsc_offset_conunt', '');
    // }
   endif;
}
//add_action('wp_loaded','imp_cron');

add_action('imp_cron_hook_two', 'imp_cron_two');

function imp_cron_all(){

   $prefix = IMP\prefix();

    global $wpdb;
    $table = $wpdb->prefix.'postmeta';
    $wpdb->delete ($table, array('meta_key' => $prefix.'gsc_data'));
    $wpdb->delete ($table, array('meta_key' => $prefix.'gsc_new_kw'));
    $wpdb->delete ($table, array('meta_key' => $prefix.'gsc_set_kw'));

    //old plugin data
    $wpdb->delete ($table, array('meta_key' => 'cop_gsc'));
    $wpdb->delete ($table, array('meta_key' => 'cop_gsc_new'));
    $wpdb->delete ($table, array('meta_key' => 'cop_gsc_set'));

    //delete term meta
    global $wpdb;
    $table = $wpdb->prefix.'termmeta';
    $wpdb->delete ($table, array('meta_key' => $prefix.'gsc_data'));
    $wpdb->delete ($table, array('meta_key' => $prefix.'gsc_new_kw'));
    $wpdb->delete ($table, array('meta_key' => $prefix.'gsc_set_kw'));

    delete_option('imp_gsc_data');

    update_option( 'imp_gsc_all_offset', 0 );
    update_option( 'imp_gsc_cron_status', 'start' );

    update_option('imp_gsc_offset', 0);
    update_option('imp_count_cron_status', 'stop');

}
add_action('imp_cron_hook_all', 'imp_cron_all');


 /**
 *  Collect GSC results
 *
 *
 *
 *
 */
 function imp_gsc_cron_callback(){


  $prefix = IMP\prefix();
  $cron_status = get_option('imp_gsc_cron_status', 'stop');

  if (  $cron_status == 'start' ) :

    $limit     = get_option('imp_gsc_all_limit', 25000);
    $offset    = get_option('imp_gsc_all_offset', 0);
    $newoffset = $offset + $limit;
    update_option('imp_gsc_all_offset', $newoffset);

     $GSC_API = new IMP\GSC_API;
     $GSC_API->getQueryOffest(  $offset );

  endif;

   //update_option('imp_gsc_cron_z', '1');
 }

 add_action('imp_gsc_data', 'imp_gsc_cron_callback');

//
// Schedule Cron Job Event
function imp_cron_job(){

   //ten_seconds
     $count_cron = get_option( 'imp_gsc_cron_status', 'stop' );
     if ( $count_cron== 'start' ) :
      if(!wp_next_scheduled('imp_gsc_data')){
         wp_schedule_event(time(), 'ten_seconds', 'imp_gsc_data');
      }
     endif;;
     // $resultarray = get_option( 'imp_gsc_data', array() );
    // $cron_status = get_option('imp_count_cron_status', 'stop');
    // if( $cron_status  == "start") :
     if (!wp_next_scheduled('imp_cron_hook')) {
         wp_schedule_event(time(), 'two_minutes', 'imp_cron_hook');
     }
     if (!wp_next_scheduled('imp_cron_hook_two')) {
         wp_schedule_event(time(), 'three_minutes', 'imp_cron_hook_two');
     }
    // endif;

    // if( $count_cron  == "stop") :
     if(!wp_next_scheduled('imp_cron_hook_all')){
         wp_schedule_event(time(), 'three_days', 'imp_cron_hook_all');
     }
    // endif;
    //imp_cron();
}

//

add_action('init', 'imp_cron_job');
