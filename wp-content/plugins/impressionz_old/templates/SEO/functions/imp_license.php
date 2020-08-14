<?php

 if (!defined('ABSPATH')) exit; // Exit if accessed directly

 function imp_license(){


  $args = array(
   'headers' =>
    array(
     // 'If-Modified-Since: Sat, 29 Oct 1994 19:43:31 GMT',
     // 'Cache-Control: max-age=315360',
     "Cache-Control: no-cache"
    ),
   'timeout' => 45,
   'sslverify'=>FALSE
  );

  $license_key = get_option('impressionz_license_key', '');

   if ( $license_key!="" ) :

      $site_url = site_url();
      $doamin   = parse_url ( $site_url ) ;
      $host = sanitize_title( $doamin[ 'host' ] );
      //echo $host;
      // echo 'https://impressionz.io/wp-json/license/v1/site/'.$license_key.'/'. $host.'/';
      $response = wp_remote_get( esc_url_raw( 'https://impressionz.io/wp-json/license/v1/site/'.$license_key.'/'. $host.'/') , $args );
      // echo "<pre>";
      // print_r( $response );
      //   echo "</pre>";
     if ( $response['response']['code'] ==200) :
       //print_R( $response);
       $data = $response['body'];
       $data = json_decode( $data );
       //print_r( $data);
       update_option('imp_status',  $data->status);
       update_option('imp_message',  $data->message);
     else:
       update_option('imp_status', 'error');
       update_option('imp_message', 'Please check your <a href="'.admin_url().'/admin.php?page=impressionz_license">license</a>.');
     endif;

  else:
    update_option('imp_status', 'error');
    update_option('imp_message', 'Please check your <a href="'.admin_url().'/admin.php?page=impressionz_license">license</a>.');
  endif;


 }
