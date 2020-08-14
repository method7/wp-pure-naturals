<?php


 namespace IMP;

 use Google_Client;
 use Google_Service_Webmasters;
 use Google_Service_Webmasters_SearchAnalyticsQueryRequest;
 use Google_Service_Webmasters_SitesListResponse;
 class GSC_API{

   var $prefix ;
   var $client ;
   var $jsonfile;

  function __construct(){
    $this->prefix    = prefix();
    $this->jsonfile  = path() . '3rd-party/google-api-php-client-2.4.0/plugin.json';
    $googledir       = path() . '3rd-party/google-api-php-client-2.4.0/vendor/';
    require_once $googledir . 'autoload.php';
  }

  function Client( $authorization_code = null ){

    try {

     $client = new Google_Client();

     $authCode = ( $authorization_code ) ? $authorization_code : get_option('imp_authorization_code');
     $authCode = trim($authCode);

     $client->setAuthConfig( $this->jsonfile );
     $client->setAccessType("offline");        // offline access
     $client->setIncludeGrantedScopes(true);   // incremental auth
     $client->setApprovalPrompt('force');
     $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');

     // $auth_url = $client->createAuthUrl();
     // echo '<a href="'.$auth_url.'" target="_blank">'.$auth_url.'</a>';

     $token = get_option('imp_gsc_token', null);

     if ( !$token ) :
       $token =  $client->authenticate(  $authCode );
       //update_option( 'imp_count_cron_status', 'start');
       update_option( 'imp_gsc_status', 'error' );
       update_option('imp_count_cron_status', 'stop');
       update_option('imp_gsc_offset', 0);
     endif;
   //  print_R( $token);
     //$token = $client->fetchAccessTokenWithAuthCode($authCode);

     if ( isset( $token['error'] ) ) :
       update_option('imp_count_cron_status', 'stop');
     else:
       update_option('imp_gsc_offset', 0);
       update_option('imp_gsc_token', $token );
      // update_option( 'imp_gsc_property', get_site_url() );
     endif;

   //  print_R( $token );

   //  $fullToken = $client->fetchAccessTokenWithAuthCode('4/xQEF_M9qzNljxJqIUa93rlM1h2LNWNLCMyOAdz3A9-QyD9Wp5N17YbgDeFrJ2BsozmEfb3AGvz6A-zX689AD91w');
     //$client->refreshToken( $token );
     $client->setAccessToken($token);

     $this->client = $client;

     update_option( 'imp_gsc_status', 'success' );
     update_option( 'imp_gsc_message', 'Active' );

     //update_sites
     if( $authorization_code ) :
        $this->getSites();
     endif;


     return $this->client;
     // return array(
     //     'result' => array(),
     //     'status' => 'error',
     //     'message' =>  $message ,
     // );


    } catch (\Exception $e) { // <<<<<<<<<<< You must use the backslash

        $m = $e->getMessage();
        $m = json_decode($m);
        if (json_last_error() === JSON_ERROR_NONE) {
          // JSON is valid
           $message = $m->error->message;
         }else {
            $message = $e->getMessage();
         }

         update_option('imp_gsc_status', 'error' );
         update_option('imp_gsc_message', $message  );

         //print_r($m);
          return array();
        // return array(
        //     'result' => array(),
        //     'status' => 'error',
        //     'message' =>  $message ,
        // );
    }
  }


  function getQueryOffest( $offset ){

   try{
     //data
      $impression = get_option('imp_gsc_impression', 100);
      $days_range = get_option('imp_gsc_date', 28);
      $StartDate  = date('Y-m-d', strtotime('-'.$days_range.' days'));
      $EndDate    = date("Y-m-d", strtotime('-1 day'));
      $urlmain    = get_option('imp_gsc_property', get_site_url() );

      $webmastersService = new Google_Service_Webmasters( $this->Client() );
      $searchanalytics = $webmastersService->searchanalytics;




      //GSC API
      $request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest;
      $request->setStartDate($StartDate);
      $request->setEndDate($EndDate);
      $request->setStartRow($offset);
      $request->setRowLimit(25000);
      $request->setDimensions(array('page',  'query')); //, 'query'
      $request->setSearchType('web');
      $request->setAggregationType('byPage');
      $qsearch = $searchanalytics->query($urlmain, $request );
      $rows = $qsearch->getRows();

      $niz =  [];
      if( $rows  ) :
         foreach ( $rows as $row ) :
           if ( $row->impressions >= $impression ) :
               $url = $row->keys[0];
               // $kw  = trim($row->keys[1]);
                // $i++;
               // if ($_SERVER['HTTP_HOST'] == 'localhost') {
               //     $url = str_replace($urlmain, LOCALHOSTTESTING.'/', $url);
               // }
               $hash     = explode('#', $url);
               $url      = $hash[0];
               $the_hash = isset( $hash[1] ) ? ' #'.$hash[1] : 'none';

               //ako nema hahs tag
               if($the_hash == 'none') :
                  //echo   $url.'<br />' ;
                 $niz[$url][$row->keys[1]] = array(
                         'impressions'  => $row->impressions,
                         'clicks'       => $row->clicks,
                         'position'     => $row->position,
                         'ctr'          => $row->ctr,

                    );
                    // krsort($niz[$url][$row->keys[1]]);

                     // Sorty by impressions
                     $impressions = array_column($niz[$url], 'impressions');
                     array_multisort($impressions, SORT_DESC, $niz[$url]);
                     // $niz[$url] = array_slice($niz[$url], 0, 31, true);
                    // $price = array_column($inventory, 'price');
                    //
                    // array_multisort($price, SORT_DESC, $inventory);

                    // arsort($niz[$url][$row->keys[1]]);
               endif;
           endif;
        endforeach;
      else:
          //collecting stop
          update_option( 'imp_gsc_cron_status', 'stop');
          update_option( 'imp_gsc_all_offset', 0);

          //count start
          update_option( 'imp_gsc_offset', 0);
          update_option( 'imp_count_cron_status', 'start' );
          //break;
      endif;

     // endfor;

     $gsc_data_all = get_option('imp_gsc_data', array() );
     //return $rows;
     $all_data = array_merge_recursive( $gsc_data_all, $niz );

     $saving_by_date = $all_data ;

     update_option( 'imp_gsc_data',  $all_data );
     update_option( 'imp_gsc_datetime',  current_time( 'mysql' ) );
     update_option( 'imp_gsc_status', 'success' );
     update_option( 'imp_gsc_message', 'Active'  );
     // update_option( 'imp_gsc_offset', 0);
     // update_option( 'imp_count_cron_status', 'start' );

    } catch (\Exception $e) { // <<<<<<<<<<< You must use the backslash

        $m = $e->getMessage();
        $m = json_decode($m);
        if (json_last_error() === JSON_ERROR_NONE) {
          // JSON is valid
           $message = $m->error->message;
         }else {
            $message = $e->getMessage();
         }
         update_option('imp_gsc_cron_status', 'stop');
         update_option('imp_gsc_status', 'error' );
         update_option('imp_gsc_message', $message  );

         //print_r($m);
         //return array();
        // return array(
        //     'result' => array(),
        //     'status' => 'error',
        //     'message' =>  $message ,
        // );
    }
  }


  // function getQuery(){
  //
  //  try{
  //  //data
  //  $impression = get_option('imp_gsc_impression', 100);
  //  $days_range = get_option('imp_gsc_date', 28);
  //  $StartDate  = date('Y-m-d', strtotime('-'.$days_range.' days'));
  //  $EndDate    = date("Y-m-d", strtotime('-1 day'));
  //  $offset     = 0;
  //  //$def_site   = get_site_url();
  //  $urlmain    = get_option('imp_gsc_property', get_site_url() );
  //
  //  //$urlmain  = ( $urlmain!=""  ) ? $urlmain  : get_site_url();
  //
  //  $webmastersService = new Google_Service_Webmasters( $this->Client() );
  //  $searchanalytics = $webmastersService->searchanalytics;
  //
  //   // $ss = $webmastersService->sites->listSites()->getSiteEntry();
  //   //  print_R( $ss);
  //
  //  // $urlmain = get_site_url().'/';
  //  // if ($_SERVER['HTTP_HOST'] == 'localhost') {
  //  //     $urlmain = 'https://rs.youstuff.org/';
  //  // }
  //
  //  $options  = [];
  //  $homepage =  $urlmain;
  //
  //  //echo  $homepage;
  //
  //  $niz = array();
  //  $nizcustomurla = array();
  //  $h =0;
  //
  //   for ($i = 0; $i < 100 ; $i++) :
  //
  //
  //    // code...
  //     $offset = $i * 25000;
  //
  //     $request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest;
  //     $request->setStartDate($StartDate);
  //     $request->setEndDate($EndDate);
  //     $request->setStartRow($offset);
  //     $request->setRowLimit(25000);
  //     $request->setDimensions(array('page',  'query')); //, 'query'
  //     $request->setSearchType('web');
  //     $request->setAggregationType('byPage');
  //     $qsearch = $searchanalytics->query($urlmain, $request );
  //     $rows = $qsearch->getRows();
  //
  //     if(  $rows ) :
  //        foreach ($rows as $row) :
  //          if ($row->impressions >= $impression) :
  //              $url = $row->keys[0];
  //               // $i++;
  //              // if ($_SERVER['HTTP_HOST'] == 'localhost') {
  //              //     $url = str_replace($urlmain, LOCALHOSTTESTING.'/', $url);
  //              // }
  //              $hash     = explode('#', $url);
  //              $url      = $hash[0];
  //              $the_hash = isset( $hash[1] ) ? ' #'.$hash[1] : 'none';
  //
  //              //ako nema hahs tag
  //              if($the_hash == 'none') :
  //                 //echo   $url.'<br />' ;
  //                $niz[$url][$row->keys[1]] = array(
  //                        'impressions'  => $row->impressions,
  //                        'clicks'       => $row->clicks,
  //                        'position'     => $row->position,
  //                        'ctr'          => $row->ctr,
  //                   );
  //
  //              endif;
  //             endif;
  //          endforeach;
  //
  //
  //     else:
  //        break;
  //     endif;
  //
  //    endfor;
  //
  //
  //  //return $rows;
  //
  //    $saving_by_date[current_time( 'mysql' )] = $niz ;
  //
  //    update_option( 'imp_gsc_data',  $saving_by_date );
  //    update_option( 'imp_gsc_status', 'success' );
  //    update_option( 'imp_gsc_message', 'Active'  );
  //    update_option( 'imp_gsc_offset', 0);
  //    update_option( 'imp_count_cron_status', 'start' );
  //
  //   } catch (\Exception $e) { // <<<<<<<<<<< You must use the backslash
  //
  //       $m = $e->getMessage();
  //       $m = json_decode($m);
  //       if (json_last_error() === JSON_ERROR_NONE) {
  //         // JSON is valid
  //          $message = $m->error->message;
  //        }else {
  //           $message = $e->getMessage();
  //        }
  //
  //        update_option('imp_gsc_status', 'error' );
  //        update_option('imp_gsc_message', $message  );
  //
  //        //print_r($m);
  //        //return array();
  //       // return array(
  //       //     'result' => array(),
  //       //     'status' => 'error',
  //       //     'message' =>  $message ,
  //       // );
  //   }
  // }


  function getSites(){


   $webmastersService = new Google_Service_Webmasters($this->Client());
   $searchanalytics = $webmastersService->searchanalytics;

   $sites = $webmastersService->sites->listSites()->getSiteEntry();
    //print_R( $sites);
     $update = array( get_site_url() => get_site_url() );
    foreach ($sites as $key => $site) :
      if( strpos( $site->siteUrl, 'sc-domain:') === FALSE )  :
       $update[ $site->siteUrl ] = $site->siteUrl;
      endif;
    endforeach;

    // update_option( 'imp_gsc_property', get_site_url() );
    update_option( 'imp_gsc_sites', $update);
     //print_R($update);
   // return  $webmastersService->sites->listSites()->getSiteEntry();
    // print_R( $ss);

  }
 }
