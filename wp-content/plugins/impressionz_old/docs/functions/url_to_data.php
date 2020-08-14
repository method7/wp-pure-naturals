<?php

namespace IMP;


 function url_to_data_old( $url ){

   $mainurl = get_option('imp_gsc_property', '');

   //replace url on dev location
   if ( $mainurl  !== get_site_url() ) :
       $the_url = get_site_url().'/';
       $replace = rtrim( $the_url , '/') ;
       $mainurl = rtrim( $mainurl , '/') ;
       $url     = str_replace($mainurl, $replace,  $url );
   endif;
	  //echo   $url ;
   $str_url     = str_replace(  $the_url , '',  $url );
   $explode_url = explode('/', $str_url);
   //print_R($explode_url);

   $args = array(
     'public'   => true,
     // '_builtin' => false

   );

   $taxonomies = get_taxonomies( $args, 'all');
   //print_R(  $taxonomies );
   if ( ! empty( $taxonomies ) ) :

           foreach ( $taxonomies as $taxonomy ) {
             $all_tax[ $taxonomy->rewrite['slug']  ] =  $taxonomy->name ;
             // print_R( $taxonomy);
              // echo '<li>' . $taxonomy . '</li>';
           }

    endif;

   // print_R( $all_tax);

    //start from posts and pages
   $post_id = url_to_postid( $url );
   // echo $explode_url[0];
   // print_R($all_tax );
   //if is post
   if ( $post_id !== 0 ) {
      // count_keywords($postidinsert);
       $return =[
        'type'  =>'pt',
        'name'  => get_post_type( $post_id ),
        'ID'    => $post_id ,
        'title' => get_the_title( $post_id ),
        'url'   => $url
       ];

    //if is autor
   }elseif( $explode_url[0] == "author" ){

    // $slug_key = count($explode_url)-2;
    // $slug = $explode_url[ $slug_key ] ;

    $return =[
     'type'  => 'author',
     'name'  => $explode_url[ 2 ],
     'ID'    => '',
     'title' => 'Author',
     'url'   => $url
    ];
   }elseif( in_array( $explode_url[0], array_flip( $all_tax ) ) ) {
    //taxonomy
    $tax = $all_tax[ $explode_url[0] ];

    //slug
    $slug_key = count($explode_url)-2;
    $slug = $explode_url[ $slug_key ] ;
     // echo $slug;
     // echo $tax ;

     //get term gy slug
      $term = get_term_by('slug', $slug , $tax );
      // print_R(  $term );


      $return =[
       'type'  => 'taxonomy',
       'name'  => $tax ,
       'ID'    => isset( $term->term_id ) ? $term->term_id : '',
       'title' => isset( $term->name ) ?  $term->name : '',
       'url'   => $url
      ];

   }else{

    //print_R($explode_url);
     $key = count($explode_url)-1;
    //print_R($explode_url);
     $urlone = $explode_url[ 0 ] ;
	 $urlone = $urlone.'/';
    //echo   $urlone;
     global $wpdb;
     $myquery = "SELECT post_id FROM $wpdb->postmeta  WHERE meta_key = 'custom_permalink' AND meta_value = '" . $urlone . "'";
     $thispage = $wpdb->get_row($myquery);

     global $wpdb;
     $my_term_query = "SELECT term_id FROM $wpdb->termmeta  WHERE meta_key = 'custom_permalink' AND meta_value = '" . $urlone . "'";
     $thisterm = $wpdb->get_row($my_term_query);


     if( $thispage ){

          $post_id = ($thispage->post_id);
           $return =[
            'type'  => 'pt',
            'name'  => get_post_type( $post_id  ),
            'ID'    => $post_id ,
            'title' => get_the_title( $post_id ),
            'url'   => $url
           ];

      }elseif( $thisterm ){
         $term_id = ( $thisterm->term_id );
         $return =[
          'type'  => 'taxonomy',
          'name'  => $term_id,
          'ID'    => $term_id ,
          'title' => $term_id,
          'url'   => $url
         ];
      }else{

       $return =[
        'type'  => 'other',
        'name'  => 'other',
        'ID'    => '' ,
        'title' => '-',
        'url'   => $url
       ];

      }

   }


   // print_R($return );
 return $return;
   // return [
   //  'type'  => '',
   //  'ID'    => '',
   //  'title' => '',
   //  'url'   => ''
   // ];

 }




  ?>
