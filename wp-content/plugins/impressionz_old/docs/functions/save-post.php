<?php

 namespace IMP;

 if (!defined('ABSPATH')) exit; // Exit if accessed directly

 // new SAVE_POST;

  //update post meta on save post
  class SAVE_POST{


   function __construct(){
    //on save post
    add_action( 'save_post',  array( $this , 'save_post' ), 10,3 );

   }

   function save_post( $post_id, $post, $update ) {
     //count_keywords( 	$post_id  ) ;

   }

  }

?>
