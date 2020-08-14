<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly

 /**
 *  Meta Field : Impressionz
 *
 * @API Key AIzaSyDelfGtEdYBTETpIoyGK8k-qquskCTwLnw
 * @see https://console.developers.google.com/flows/enableapi?apiid=webmasters&credential=client_key
 * @see https://console.developers.google.com/apis/credentials/wizard?api=webmasters.googleapis.com&project=united-axle-263017
 * @see https://developers.google.com/webmaster-tools/search-console-api-original/v3/prereqs
 * @see https://searchconsole.googleapis.com/$discovery/rest?version=v1
 * @see https://developers.google.com/webmaster-tools/search-console-api/v1/libraries#php
 * @see https://github.com/googleapis/google-api-php-client
 * @see https://developers.google.com/webmaster-tools/search-console-api/v1/libraries#php
 */
?>
<?php
//count kw in the page
imp_count_keywords([
 'ID'   => get_the_ID(),
 'url'  => get_the_permalink(),
 'type' => 'pt',
]);



$prefix = IMP\prefix();
$gsc_data = get_post_meta(get_the_ID(), $prefix.'gsc_data' );
 //print_R( $cop);
?>
<div style="width:100%; overflow:auto;">
<table class="table " style="width:100%;">
 <caption>
  <?php echo get_option('imp_gsc_date', 28) ?> days / <?php echo get_option('imp_gsc_impression', 100) ?> Imp.
  <a href="<?php admin_url();?>admin.php?page=impressionz_settings">Settings</a>
 </caption>
 <tr style="background:#F9F9F9;">
   <th style="text-align:left;"  style="width:50%;">Keywords</th>
   <th style="text-align:center;" title="Mentions">M</th>
   <th style="text-align:center; background-color:#4285f4; color:#fff;" title="Clicks">C</th>
   <th style="text-align:center; background-color:#5e35b1; color:#fff;" title="Impressions">I</th>
   <th style="text-align:center; background-color:#e8710a; color:#fff;" title="Position">P</th>
 </tr>
<?php if( $gsc_data ) :
  foreach (current($gsc_data) as $key => $value) :
    $color = ( $value['count'] > 0) ? 'green' : 'black'; ?>
  <tr>
   <td title="keyword: <?php echo $key ?>"><?php echo '<span style="color:'. $color.'">'.$key.'</span>'; ?></td>
   <td style="text-align:center; color: <?php echo $color?>;" title="<?php  echo $value['count'] ?> Mentions"><?php  echo $value['count'] ?></td>
   <td style="text-align:center; color:#4285f4;" title="<?php  echo $value['clicks'] ?> Clicks"><?php  echo $value['clicks'] ?></td>
   <td style="text-align:center; color:#5e35b1;" title="<?php  echo $value['impressions'] ?> Impressions"><?php  echo $value['impressions'] ?></td>
   <td style="text-align:center;  color:#e8710a;" title="<?php  echo round($value['position'], 2)  ?> Position"><?php  echo round($value['position'], 0) ?></td>
  </tr>
<?php  endforeach;
 endif;?>
</table>
</div>
