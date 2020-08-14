<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

 /**
 * Get GSC results
 *
 *
 * @see https://developers.google.com/webmaster-tools/search-console-api-original/v3/searchanalytics/query
 */


 function cop_get_gsc_api_old( $offset = 0 ) {

  //db prefix
  $prefix = IMP\prefix();

  //
  $cop_gsc_result          = get_option( $prefix.'gsc_data', array() );

  //$cop_gsc_result          = get_option('cop_gsc', array() );
  $cop_gsc_homepage_result = get_option('cop_gsc_home', array() );


  $googledir = IMP\path() . '3rd-party/google-api-php-client-2.4.0/vendor/';
  //$googleservices = SEOCOP\Plugin::path(). '3rd-party/google-api-php-client-services-0.121/src/Google/Service/';
  require_once $googledir . 'autoload.php';
 // require_once $googleservices . 'Webmasters.php';
 // require_once $googleservices . '/Webmasters/SearchAnalyticsQueryRequest.php';
  //require_once SEOCOP\Plugin::path(). '3rd-party/localhost.php';

  //$jsonfile = SEOCOP\Plugin::path() . '3rd-party/youstuff-1573612768611-31b1841017a4.json';
  $jsonfile = IMP\path() . '3rd-party/google-api-php-client-2.4.0/plugin.json';
  //$jsonfile = SEOCOP\Plugin::path() . '3rd-party/youstuff-1573612768611-f3d61a1b880a.json';

  $impression = get_option('cop_gsc_impression', 100);
  $days_range = get_option('cop_gsc_date', 7);
  $StartDate  = date('Y-m-d', strtotime('-'.$days_range.' days'));
  $EndDate    = date("Y-m-d", strtotime('-1 day'));
  //API CALL (If JSON file exists)
    if (file_exists($jsonfile)):

       $niz = array();
       $nizcustomurla = array();
       $h =0;

        for ($i=0; $i < 100 ; $i++) :
         // code...
          $offset = $i * 25000;

          try {

            // print_r($jsonfile);
            //  $client_id = $jsonfile->web->client_id;
            //  $redirect_uri = $jsonfile->web->redirect_uris[1];
            //  $client_secret = $jsonfile->web->client_secret;
            //  echo  $client_id;
            //  echo  $client_secret ;
             //https://github.com/googleapis/google-api-php-client/blob/master/docs/oauth-web.md
             //https://stackoverflow.com/questions/43506062/call-to-undefined-method-google-clientfetchaccesstokenwithauthcode
               $client = new Google_Client();

               $authCode = get_option('imp_authorization_code');
               $authCode =  trim($authCode);

               $client->setAuthConfig($jsonfile);
               $client->setAccessType("offline");        // offline access
 	             $client->setIncludeGrantedScopes(true);   // incremental auth
               $client->setApprovalPrompt('force');
               $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');

               // $auth_url = $client->createAuthUrl();
               // echo '<a href="'.$auth_url.'" target="_blank">'.$auth_url.'</a>';

               $token = get_option('imp_gsc_token');

               if ( !$token ) :
                 $token =  $client->authenticate(  $authCode );
                 update_option('imp_count_cron_status', 'start');
               endif;
             //  print_R( $token);
               //$token = $client->fetchAccessTokenWithAuthCode($authCode);

               if ( isset( $token['error'] ) ) :
                 update_option('imp_count_cron_status', 'stop');
               else:
                 update_option('imp_gsc_token', $token );
               endif;

             //  print_R( $token );

             //  $fullToken = $client->fetchAccessTokenWithAuthCode('4/xQEF_M9qzNljxJqIUa93rlM1h2LNWNLCMyOAdz3A9-QyD9Wp5N17YbgDeFrJ2BsozmEfb3AGvz6A-zX689AD91w');
               //$client->refreshToken( $token );
               $client->setAccessToken($token);

               // $client->getAccessToken();
               // $client->getAccessToken($token);
               // print_R(   $token );
               //  $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
               // //$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
               // //$accessToken = $client->getAccessToken();
               //
               // echo '<pre>';
               //  print_R( $accessToken );
               // echo '</pre>';
              // $client->setAccessToken(    $token );
                 // $client->refreshToken();
               //print_R($client);
               //$client->setAccessToken('ya29.a0Adw1xeWmXtuDi7Q2A5lpE9OKUg8tGg4eXdXv9un4TX8-uN6UBzblERU_BlyBvNKBjPxY26bSt01mxHBKYwAkxO4NtrSTRO5TPfrhA869CD6UMxrDXO9Aw_qYfez_R_mtum6My78Zc8nuzlXifp_uQqntRLB8xYVxRE1M');

              // wp_die($auth_url);

             //  if ($client->getAccessToken()) {
             //  //  $userData = $objOAuthService->userinfo->get();
             //  //  if(!empty($userData)) {
             // 	// $objDBController = new DBController();
             // 	// $existing_member = $objDBController->getUserByOAuthId($userData->id);
             // 	// if(empty($existing_member)) {
             // 	// 	$objDBController->insertOAuthUser($userData);
             // 	// }
             //  //  }
             //  //  $_SESSION['access_token'] = $client->getAccessToken();
             // } else {
             //   $authUrl = $client->createAuthUrl();
             // }


              $webmastersService = new Google_Service_Webmasters($client);
              $searchanalytics = $webmastersService->searchanalytics;



              $urlmain = get_site_url().'/';
              if ($_SERVER['HTTP_HOST'] == 'localhost') {
                  $urlmain = 'https://rs.youstuff.org/';
              }
             // echo $urlmain ;
              //echo  $urlmain;
              $options = [];
              $homepage = get_site_url() . '/';
              //echo  $homepage;

              $urlmain = 'https://rs.youstuff.org/';
              $request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest;
              $request->setStartDate($StartDate);
              $request->setEndDate($EndDate);
              $request->setStartRow($offset);
              $request->setRowLimit(25000);
              $request->setDimensions(array('page',  'query')); //, 'query'
              $request->setSearchType('web');
              $request->setAggregationType('byPage');


              //https://stackoverflow.com/questions/33939113/dimensions-of-query-webmasters-tools-api
              // $filter = new Google_Service_Webmasters_ApiDimensionFilter();
              //  $filter->setDimension('page');
              // // $filter->setOperator(">");
              // // $filter->setExpression('100');
              // $filter->setExpression("https://www.funtastictoy.com/best-toys-gifts-for-5-year-old-girls/");
              // $filtergroup = new Google_Service_Webmasters_ApiDimensionFilterGroup();
              // $filtergroup->setFilters(array($filter));
              // $request->setDimensionFilterGroups(array($filtergroup));


              //$request->orderBy("Impressions > 100");

              // $request->setOperator('equals');
               //$request->setExpression("Impressions > 100");
               //$request->setOrderByImpressions('20');
              //$request->setOrderBy('impressions');
             // $request->setOperator('equals');
              // $request->searchType('Impressions');
              // getImpressions setImpressions
             // $request->getImpressions(200);
              //print_R( $request);
              $qsearch = $searchanalytics->query($urlmain, $request,  $options );
              $rows = $qsearch->getRows();

             // update_option('imp_count_cron_status', 'start');
          } catch (\Exception $e) { // <<<<<<<<<<< You must use the backslash

              $m = $e->getMessage();
              $m = json_decode($m);
              if (json_last_error() === JSON_ERROR_NONE) {
                // JSON is valid
                 $message = $m->error->message;
               }else {
                  $message = $e->getMessage();
               }
               //print_r($m);
              return array(
                  'result' => array(),
                  'status' => 'error',
                  'message' =>  $message ,
              );
          }
          // $i=0;
          // foreach ($rows as $row) :
          //
          //   $url = $row->keys[0];
          //   $niz[$i] =  $url;
          //  $i++;
          // endforeach;
          // $niz = array();
          // $nizcustomurla = array();

          // echo '<pre>';
          // print_R($rows);
          // echo '</pre>';

          //PAGE + QUERY
          $i=0;
          foreach ($rows as $row) :
            if ($row->impressions >= $impression) :
                $url = $row->keys[0];
                 // $i++;
                // if ($_SERVER['HTTP_HOST'] == 'localhost') {
                //     $url = str_replace($urlmain, LOCALHOSTTESTING.'/', $url);
                // }
                $hash     = explode('#',$url);
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

                endif;


           endif;
           $i++;
          endforeach;

         // echo $i;
          //  echo '<br />'.count($niz);
          // echo '<pre>';
          // print_R($niz);
          //  echo '</pre>';

          // $saving_by_date[date("d.m.Y")] = $niz;
          //
          // //save all array in update_option('cop_gsc');
          // update_option('cop_gsc', $saving_by_date);
         endfor;
         endif;
         //  echo '#'. count($h_niz);
         // echo 'unique pages'.count($niz);
         //
          //print '<pre>';
          // print_R($niz );
          // echo conut($niz );
         //
          //print_R($niz);
         // print '</pre>';
         $saving_by_date[current_time( 'mysql' )] = $niz;
         //save all array in update_option('cop_gsc');
         update_option($prefix.'gsc_data', $saving_by_date);

         //https://wordpress.stackexchange.com/questions/246684/remove-post-meta-keys
         //DELETE FROM `wp_postmeta` WHERE `meta_key` LIKE 'weather_%'

          return array(
           'result'  => $saving_by_date,
           'status'  => 'success',
           'message' =>'',
          );


 }
