<?php

namespace IMP;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

  new AJAX;

  class AJAX{

   function __construct(){

    //https://codex.wordpress.org/AJAX_in_Plugins
    add_action( 'wp_ajax_count_keywords', array( $this , 'count_keywords' ) );

   }

   function my_action_javascript() { ?>
    	<script type="text/javascript" >
    	jQuery(document).ready(function($) {

    		var data = {
    			'action': 'count_keywords',
    			'post_id': 1234
    		};

    		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
    		jQuery.post(ajaxurl, data, function(response) {
    			alert('Got this from the server: ' + response);
    		});
    	});
    	</script> <?php
    }

   //ajax callback
   function count_keywords() {
   	global $wpdb; // this is how you get access to the database

    	$post_id = intval( $_POST['post_id'] );
     //$post_id = intval( $_POST['post_id'] );

     $count = count_keywords( 	$post_id  ) ;

      if ($count['new'] > 0  ) :
        echo '<span style="color:red;">'.$count['new'].' new</span>';
        echo ( $count[ 'set' ] > 0 ) ? '<br /><span style="color:green;">'.$count['set'].' optimized</span>' :'';
      else:
         if( $count[ 'set' ] > 0  ) :
          echo '<span style="color:red;">&nbsp;</span>';
          echo '<br /><span style="color:green;">'.$count['set'].' optimized</span>';
         else:
           echo "-";
         endif;

      endif;

   	wp_die(); // this is required to terminate immediately and return a proper response
   }




  }


?>
