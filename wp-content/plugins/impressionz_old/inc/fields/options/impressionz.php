<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly

 $prefix = IMP\prefix();
 $array = get_option($prefix.'gsc_data', array() ) ;

 // echo '<pre>';
 //
 // print_R( $array);
 // echo '</pre>';
 if ( $array ) :
  $array_results = $array; ?>
   <p style="margin-top:4px; margin-bottom:12px; padding-left:8px;"><?php echo get_option('imp_gsc_date', 28) ?> days / <?php echo get_option('imp_gsc_impression', 100) ?> Impressions </p>
    <?php
    $allow_types = get_option( $prefix.'display', json_decode(IPM_DISPLAY) );

    $i=1;
    foreach ($array_results as $url => $kws) :

     $url_data = IMP\url_to_data($url);
     // print_R($url_data);
     $the_title = $url_data['title'];
     $type      = $url_data['type'];
     $name      = $url_data['name'];
     $type      = ( $type == "pt" )   ? 'post' :  $type ;
     $type      = ( $name == "post" ) ? 'post' :  $type ;
     $type      = ( $name == "page" ) ? 'page' :  $type ;
     $ID        = $url_data['ID'];

     if ( in_array( $type, $allow_types  )  ) :  ?>

     <table class="table wp-list-table widefat  striped posts" style="margin-bottom:20px;">
     <thead>
     <tr title="<?php echo  ucwords( str_replace('pt', '',  $type.' '.$name ) ) ?>">
        <th colspan="1" class="manage-column " scope="col" style="padding:6px 0px 0px 12px; ">
         <p style="padding-top:0px;"><small style="color:gray;"><?php echo $i ?>.</small>  <?php  echo $the_title ?></p>
         <a href="<?php echo $url ?>" target="_blank" style="font-weight:normal;"><?php echo $url ?></a>
        </th>
        <th style="text-align:center;" title="Mentions" style="padding:12px 0px 0px 0px; ">M</th>
        <th style="text-align:center; background-color:#4285f4; color:#fff; padding-right:0px;" title="Clicks">C</th>
        <th style="text-align:center; background-color:#5e35b1; color:#fff; padding-right:0px;" title="Impressions">I</th>
        <th style="text-align:center; background-color:#00897b; color:#fff; padding-right:0px;" title="CTR">CTR</th>
        <th style="text-align:center; background-color:#e8710a; color:#fff; padding-right:0px;" title="Position">P</th>
     </tr>
    </thead>
    <tbody id="the-list">
       <?php
        //impressions clicks position ctr
        $cop   = [];
        $ik = 0;
        foreach ( $kws as $kw => $value ) :
          if ( $ik <= IPM_KW_LIMIT ) :
          if ( $type == "taxonomy" ) :
             $cop  = get_term_meta( $ID, 'imp_gsc_data', true );
          else:
             $cop  = get_post_meta( $ID, 'imp_gsc_data', true );
          endif;
          //print_R(  $cop  );
          $count       = isset($cop[$kw]['count']) ? $cop[$kw]['count'] : '-';
          $count_tag   = ( $count > 1 )  ? 'strong' : 'span';
          $color       = ( $count >= 1 ) ? 'green' : 'gray';
          echo '<tr title="'.$the_title.'">';
          echo '<td width="80%;"><'. $count_tag.' style="color:'. $color .';">'.$kw.'</'. $count_tag.'></td>';
          echo '<td title="Mentions" style="width:60px; text-align:center;"><span style="color:'. $color .';">'. $count.'</span></td>';
          echo '<td title="Clicks" style="width:60px; color:#4285f4; text-align:center;" width="">'.$value['clicks'].'</td>';
          echo '<td title="Impressions"  style="width:60px; color:#5e35b1; text-align:center;">'.$value['impressions'].'</td>';
          echo '<td title="CTR"  style="width:60px; color:#00897b; text-align:center;">'.number_format(round($value['ctr'], 2 ), 2).'%</td>';
          echo '<td title="Position"  style="width:60px; color:#e8710a; text-align:center;">'.round($value['position'], 1).'</td>';
          echo '</tr></td>';
          $ik++;
          endif;
        endforeach;
       ?>
    </tbody>
    </table>
    <?php $i++;
    endif;
   endforeach;

   else:
    echo '<p>No data. Please adjust the <a href="'.admin_url().'admin.php?page=impressionz_settings">settings</a>.</p>';
   endif;?>
