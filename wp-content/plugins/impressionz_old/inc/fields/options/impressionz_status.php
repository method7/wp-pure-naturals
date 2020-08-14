<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly
//imp_license();

 // $GSC_API = new IMP\GSC_API();
 // $GSC_API->getQuery();

$gsc_cronstatus  = get_option( 'imp_gsc_cron_status', 'error' );
$gsc_cron_all_offest  = get_option( 'imp_gsc_all_offset', '0' );
//
// echo $gsc_cronstatus ;
// echo $gsc_cron_all_offest;

echo '<table class="wp-list-table  fixed striped posts">';
//call za GSC API

//$gsc_api = cop_get_gsc_api();




$status  = get_option( 'imp_gsc_status', 'error' );
$message = get_option( 'imp_gsc_message', 'Request Authorization' );

if( $status == 'success' ) :
 //
 // $GSC_API = new IMP\GSC_API;
 // $GSC_API->getQuery();

 $GSC_data     =  get_option( 'imp_gsc_data', array() );
 $imp_gsc_date =  get_option('imp_gsc_datetime', current_time( 'mysql' ) );

  // echo '<pre>';
  //  //print_R( $GSC_data );
  //  echo '</pre>';
 $total = count( $GSC_data  );



  ?>
      <tr title='Last update from Google Search Console.'>
       <td>Last update</td>
       <td> <?php echo date(get_option( 'date_format' ).' '.get_option( 'time_format' ), strtotime($imp_gsc_date)); ?></td>
     </tr>
  <?php



 // echo '<br />';
 echo '<tr title="Total results from Google Search Console including posts, pages, taxonomies and author pages."">
    <td>Total results</td>
    <td> <a href="'.admin_url().'admin.php?page=impressionz" style="text-decoration:none;">'. count(  $GSC_data  ).'</a></td>
   </tr>';

 //update_option('cop_gsc_offset', 0);
 // $property = get_option('imp_gsc_property');
 // echo $property ;

 $offset = get_option('imp_gsc_offset');
 //'imp_count_cron_status'
 $imp_countstatus = get_option('imp_count_cron_status', 'start');
 $imp_countstatus_kw = get_option('imp_count_kw_status', 'start');
 // echo $imp_countstatus;
 // echo  $offset;
 ?>
 <tr title='Count keywords status.'>
   <td>Update</td>
   <td><?php

   if($gsc_cronstatus == 'stop' ) :?>

    <?php if( $imp_countstatus == "stop" ) : ?>
        <stong style="color:green;">Done!</stong>
    <?php else: ?>
        <stong style="color:orange;">In progress  <?php echo $offset; ?> / <?php echo $total;?></stong> &nbsp; <a style="text-decoration:none;" href="" title="Refresh">&#x21bb;</a>
       <br />
       <small style="color:gray;">Estimated time <?php echo round( ( $total- $offset) / 5, 0) ?> minutes.</small>
    <?php endif; ?>

   <?php else: ?>
       <stong style="color:orange;">In progress... &nbsp; <a style="text-decoration:none;" href="" title="Refresh">&#x21bb;</a>
    <?php endif; ?>


   </td>
 <tr/>

<?php
else:
  echo '<tr><td><b>ERROR:</b></td><td><span style="color:red;">'.$message.'</span></td></tr>';
endif;

?>
</table>
