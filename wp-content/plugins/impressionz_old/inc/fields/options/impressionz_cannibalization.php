<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly
 $array = get_option('imp_gsc_data', array() ) ;
 if( $array ) :
 $array = $array;
// print_R( $array );

  foreach ($array as $url => $kws) {

    //echo  $url.'<br />';
    //print_R($value);
    foreach ($kws as $kw => $value) {
     // code...
      $array_results[$kw][$url] = $value;
    }
   // code...
  }
   //print_R($array_results);


  ?>

  <p style="margin-top:4px; margin-bottom:12px; padding-left:8px;"><?php echo get_option('imp_gsc_date', 28) ?> days / <?php echo get_option('imp_gsc_impression', 100) ?> Impressions </p>

    <?php $i=1; foreach ($array_results as $kw => $urls) :
      if( count($array_results[$kw]) > 1  ) :
     ?>
     <table class="table wp-list-table widefat  striped posts" style="margin-bottom:20px;">
     <thead>
     <tr>
        <th colspan="1" class="manage-column " scope="col" style="padding:12px 0px 12px 12px; ">
          <?php echo '<small style="color:gray;">'.$i.'.</small> '.$kw ?>
        </th>

        <th style="text-align:center; background-color:#4285f4; color:#fff; padding:12px 0px 0px 0px; " title="Clicks">C</th>
        <th style="text-align:center; background-color:#5e35b1; color:#fff; padding:12px 0px 0px 0px;" title="Impressions">I</th>
        <th style="text-align:center; background-color:#00897b; color:#fff; padding:12px 0px 0px 0px;" title="CTR">CTR</th>
        <th style="text-align:center; background-color:#e8710a; color:#fff; padding:12px 0px 0px 0px;" title="Position">P</th>
     </tr>
    </thead>
    <tbody id="the-list">

         <?php
          //impressions clicks position ctr

          foreach ($urls as $url => $value) :
            echo ' <tr>';
            echo '<td width="80%;"><a href="'.$url.'" target="_blank">' .$url.'</a></td>';
            echo '<td title="Clicks" style="width:60px; color:#4285f4; text-align:center;" width="">'.$value['clicks'].'</td>';
            echo '<td title="Impressions"  style="width:60px; color:#5e35b1; text-align:center;">'.$value['impressions'].'</td>';
            echo '<td title="CTR"  style="width:60px; color:#00897b; text-align:center;">'.number_format(round($value['ctr'], 2 ), 2).'%</td>';
            echo '<td title="Position"  style="width:60px; color:#e8710a; text-align:center;">'.round($value['position'], 1).'</td>';
            echo '</tr></td>';

          endforeach;
         ?>

    </tbody>
      </table>
     <?php $i++; endif; endforeach;

    else:
      echo '<p>No data. Please adjust <a href="'.admin_url().'admin.php?page=impressionz_settings">the settings</a>.</p>';
    endif;

     ?>
