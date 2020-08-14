<?php

 if (!defined('ABSPATH')) exit; // Exit if accessed directly

 function imp_count_keywords( $data = [] ){

  // set_time_limit(60);

  $type      = $data['type'];
  $the_ID    = $data['ID'];
  $permalink = $data['url'];

  $prefix = IMP\prefix();
  $new    = 0;
  $set    = 0;
  $body   = '';

  if (  $type  == 'pt' ) :
   $cont_data =  get_post_meta($the_ID, $prefix.'gsc_data');
   //delete old data
   // delete_post_meta($the_ID, $prefix.'gsc_data');
   // delete_post_meta($the_ID, $prefix.'gsc_new_kw');
   // delete_post_meta($the_ID, $prefix.'gsc_set_kw');
  elseif( $type  == 'taxonomy'  ) :
   $cont_data =  get_term_meta($the_ID, $prefix.'gsc_data');
   // delete_term_meta($the_ID, $prefix.'gsc_data');
   // delete_term_meta($the_ID, $prefix.'gsc_new_kw');
   // delete_term_meta($the_ID, $prefix.'gsc_set_kw');
  else:
     $cont_data = array('done');
  endif;

  if ( empty( $cont_data ) ) :
   //get data
   $imp_gsc_data = get_option( $prefix.'gsc_data', array() );

   //has GSC data
   if( isset( $imp_gsc_data[ $permalink ] ) ) :

     $postdata_from_google = $imp_gsc_data[ $permalink ];
     //https://codex.wordpress.org/Function_Reference/wp_remote_get
     //https://codex.wordpress.org/Function_Reference/wp_remote_post
      $args = array(
       'headers' =>
        array(
         // 'If-Modified-Since: Sat, 29 Oct 1994 19:43:31 GMT',
         // 'Cache-Control: max-age=315360',
         "Cache-Control: no-cache"
        ),
       'timeout' => 15,
       'sslverify'=>FALSE
      );
      //get URL text
      $response = '';
      $response = wp_remote_get( esc_url_raw( $permalink.'?cron=t'.mt_rand().'t') , $args );
      if ( is_array( $response ) ) {
         $body =  wp_remote_retrieve_body( $response );
         $body = strip_tags( mb_strtolower(  $body ) );
         $body = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "",  $body );
         $body = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "",  $body );
          //mb_strtolower( )
        }

      // $cont = $body;
      // $cnt =  $body;
      $niz = array();
      //print_R($postdata_from_google);
      foreach( $postdata_from_google as $k => $d ){
          $count = substr_count($body, $k);
          $niz[$k] = array(
              'impressions' => $d['impressions'],
              'clicks'      => $d['clicks'],
              'position'    => $d['position'],
              'ctr'         => $d['ctr'],
              'count'       => $count
          );

          if ( $count == '0' ) :
             $new++;
          else:
             $set++;
          endif;
      }

      //update post meta
      if ( $type  == 'pt' ) :
        update_post_meta($the_ID, $prefix.'gsc_data', $niz);
        update_post_meta( $the_ID, $prefix.'gsc_new_kw', $new );
        update_post_meta( $the_ID, $prefix.'gsc_set_kw', $set );
      elseif( $type  == 'taxonomy'  ) :
        update_term_meta($the_ID, $prefix.'gsc_data', $niz);
        update_term_meta( $the_ID, $prefix.'gsc_new_kw', $new );
        update_term_meta( $the_ID, $prefix.'gsc_set_kw', $set );
      endif;



         // if ( $type  == 'pt' ) :
         //  update_post_meta( $the_ID, $prefix.'gsc_new_kw', $new );
         //  update_post_meta( $the_ID, $prefix.'gsc_set_kw', $set );
         // elseif( $type  == 'taxonomy'  ) :
         //  update_term_meta( $the_ID, $prefix.'gsc_new_kw', $new );
         //  update_term_meta( $the_ID, $prefix.'gsc_set_kw', $set );
         // endif;

       // else:
       //    if ( $type  == 'pt' ) :
       //      update_post_meta($the_ID, $prefix.'gsc_new_kw', '0');
       //    elseif( $type  == 'taxonomy'  ) :
       //      update_term_meta($the_ID, $prefix.'gsc_new_kw', '0');
       //    endif;
       // endif;

      else:
          if ( $type  == 'pt' ) :
            update_post_meta($the_ID, $prefix.'gsc_new_kw', '0');
          elseif( $type  == 'taxonomy'  ) :
            update_term_meta($the_ID, $prefix.'gsc_new_kw', '0');
         endif;
      endif;

      // return array('new'=>$new, 'set'=>$set, 'body'=> $body);
     endif; //in empty

 }



  function count_keywords( $post_id ){

   $prefix = IMP\prefix();
   $new    = 0;
   $set    = 0;
   $body   = '';

   //delete old data
   delete_post_meta($post_id, $prefix.'gsc_data');
   delete_post_meta($post_id, $prefix.'gsc_new_kw');
   delete_post_meta($post_id, $prefix.'gsc_set_kw');

   //delete_post_meta($post_id, 'cop_gsc');
   //update_post_meta($post_id, 'cop_gsc_new', '0');
   $permalink = get_permalink($post_id);

   $urlmain = get_site_url();  // WORKS! promiti site_url

   if ($_SERVER['HTTP_HOST'] == 'localhost') {
       $urlmain = 'https://rs.youstuff.org';
       $permalink = str_replace( get_site_url() , $urlmain, $permalink);
   }

   global $wpdb;
   $myquery = "SELECT meta_value FROM $wpdb->postmeta  WHERE meta_key = 'custom_permalink' AND post_id = '" . $post_id . "'";
   $thispage = $wpdb->get_row($myquery);

   if($thispage){
       $permalink = ($thispage->meta_value);
       $permalink = $urlmain.'/'.$permalink;
   }


   $cop_gsc_main = get_option($prefix.'gsc_data', array() );
   //var_dump($cop_gsc_main);
   $cop_gsc_main = current($cop_gsc_main);

   //has GSC data
   if(isset($cop_gsc_main[$permalink])) :

    $postdata_from_google = $cop_gsc_main[$permalink];

     //https://codex.wordpress.org/Function_Reference/wp_remote_get
     //https://codex.wordpress.org/Function_Reference/wp_remote_post


     // $response_args = array(
     //   // 'body' => $body,
     //    'timeout' => '1',
     //    'redirection' => '5',
     //    'httpversion' => '1.0',
     //    'blocking' => true,
     //    'headers' => array(),
     //    'cookies' => array()
     // );
      //$response = file_get_contents(  $permalink.'/?'.mt_rand()  );
      // echo $permalink.'?cron='.mt_rand();

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

      if ($_SERVER['HTTP_HOST'] == 'localhost') {
       $permalink = str_replace($urlmain,  get_site_url(), $permalink);
       //echo $get_permalink;
      }
     // echo $permalink;
      $response = wp_remote_get( esc_url_raw( $permalink.'?cron=t'.mt_rand().'t') , $args );

       //$response =   wp_remote_retrieve_body( wp_remote_get( $permalink.'/?'.mt_rand()  ) );
      if ( is_array( $response ) ) {
         //$header = $response['headers']; // array of http header lines
        // $body = preg_replace("/(<[A-Z][A-Z0-9]*[^>]*)($html_events)([\s]*=[\s]*)('[^>]*'|\"[^>]*\")([^>]*>)/i", "", $response[ 'body' ]);
        //$body =  strip_tags( $body ); // use the content
          $body = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "",  $response[ 'body' ]);

          $body =  strip_tags( mb_strtolower( $body ) );

         $response =  wp_remote_retrieve_body( $response[ 'response' ] );
      }
      // print_r( $response );
      //print_r(  $body );


   $cont = $body ;
    //$cont =  '';
   //$cont =   $response  ;
   //$cnt = str_replace(array('.', ',', '!', '?', '"', "'", '(', ')', "“", "”", "?”", ':', ";"), array(' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '), $cont);
  // $cnt = strtolower($cnt);
   $cnt =  $body;
  // print_r(  $cnt );
   $niz = array();
   //print_R($postdata_from_google);
   foreach($postdata_from_google as $k => $d){
       $count = substr_count($cnt, $k);
       //echo $count;
       $niz[$k] = array(
           'impressions' => $d['impressions'],
           'clicks'      => $d['clicks'],
           'position'    => $d['position'],
           'ctr'         => $d['ctr'],
           'count'       => $count
       );
 //                        $cop_gsc_main[$permalink][$k]['count'] = $count;
   }

   update_post_meta($post_id, $prefix.'gsc_data', $niz);

   $cop_gsc = $niz;
   if ($cop_gsc) :
       $set = 0;
       $new = 0;
       foreach ($cop_gsc  as $key => $value) {
         //echo $value['count'] ;
         if( $value['count'] == '0') :
            $new++;
         else:
            $set++;
         endif;

       }
       update_post_meta( $post_id, $prefix.'gsc_new_kw', $new );
       update_post_meta( $post_id, $prefix.'gsc_set_kw', $set );
         //echo get_post_meta($post_id,'cop_gsc_new', true);
       //echo $new;
       //print_R( $cop_gsc ) ;
       $number = count( $cop_gsc );
       // echo '<span style="color:red;">'. $new . ' new</span>   <br />';
       // echo ($set>0) ? '<span style="color:green;">'.$set.' optimized</span> ': '';

   else:
       // echo "-";
       // echo '0';
        update_post_meta($post_id, $prefix.'gsc_new_kw', '0');
        //update_post_meta($post_id, 'cop_gsc_new', 0);
     endif;

    else:
       update_post_meta($post_id, $prefix.'gsc_new_kw', '0');
     // echo get_post_meta($post_id,'cop_gsc_new', true);
     // echo '-';
    endif;

    return array('new'=>$new, 'set'=>$set, 'body'=> $body);
  }
