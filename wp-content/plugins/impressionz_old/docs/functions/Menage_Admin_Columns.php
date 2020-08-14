<?php

namespace IMP;

if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'IMP\Menage_Admin_SEO_Columns' ) ) :

  class Menage_Admin_SEO_Columns{

     var $prefix = '';

     function __construct(){

        $this->prefix  = prefix();

        //manage_posts_columns
        add_filter('manage_posts_columns', array($this, 'manage_posts_columns'), 999, 2);
        add_filter('manage_page_posts_columns', array($this, 'manage_posts_columns'), 999, 2);

        //manage_posts_custom_column
       //  add_action('manage_posts_custom_column', array($this, 'manage_posts_custom_column'), 10, 2);
         // add_action('manage_page_posts_custom_column', array($this, 'manage_posts_custom_column'), 10, 2);

        //https://wordpress.stackexchange.com/questions/240576/manage-edit-post-type-sortable-columns-sorts-but-wrong
        add_action( 'pre_get_posts', array($this, 'sortable_orderby' ) );

        //run
        add_action( 'init', array($this, 'init' ), 999 );
     }


     function init(){
       $args = array(
          'public'   => true,
       );
       $post_types = get_post_types(    $args );
       foreach( $post_types as $post_type ){
          // echo $post_type;
           // add_filter('manage_' . $post_type . '_columns', array($this, 'manage_posts_columns'), 999, 2);
           add_action('manage_' . $post_type . '_posts_custom_column', array($this, 'manage_posts_custom_column'), 999, 2);
           add_filter( 'manage_edit-' . $post_type . '_sortable_columns', array($this, 'manage_edit_sortable_columns'), 999, 2 );
       }

     }

     /**
      * sortable_orderby
      */
     function sortable_orderby( $query ) {
        if( ! is_admin() )
            return;

        $orderby = $query->get( 'orderby');

        if( 'impressionz' == $orderby ) {
            $query->set('meta_key', prefix().'gsc_new_kw');
            $query->set('orderby','meta_value_num');
        } elseif('imp_gsc_set_kw' == $orderby) {
            $query->set('meta_key', prefix().'gsc_set_kw');
            $query->set('orderby','meta_value_num');
        }
     }

     /**
      * make column sortable
      */
     function manage_edit_sortable_columns($columns){
         $columns['impressionz'] = 'impressionz';
         // $columns['organization'] 			= 'organization';
         // $columns['taxonomy-testimonials'] 			= 'taxonomy-testimonials';
         return $columns;
     }

     /**
      * manage custom post type admin columns
      *
      * @return array
      */
     function manage_posts_columns($columns){
         $columns['impressionz'] = "Impressionz";
         return $columns;
     }


     /**
      * manage custom post type admin columns values
      *
      * @return array
      */
     function manage_posts_custom_column($column, $post_id){
         switch ($column) :
            case "impressionz":
                  echo '<div id="count_keywords-'.$post_id.'">';
                  echo (get_post_meta($post_id, 'imp_gsc_new_kw', true) > 0) ? '<span style="color:red;">'.get_post_meta($post_id, 'imp_gsc_new_kw', true).' new</span>' : '';
                  echo (get_post_meta($post_id, 'imp_gsc_set_kw', true) > 0) ? '<br /><span style="color:green;">'.get_post_meta($post_id,'imp_gsc_set_kw', true).' optimized</span>' : '';
                  echo '</div>';
                break;
             default;
                 echo "-";
                 break;
         endswitch;
     }

 }

 //run
 new Menage_Admin_SEO_Columns;

endif;
