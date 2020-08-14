<?php
//For Other Languge
/*
*/

function version_check_wocoommerce_brand( $version = '3.0' ) {
	if ( class_exists( 'WooCommerce' ) ) {
		global $woocommerce;
		if ( version_compare( $woocommerce->version, $version, ">=" ) ) {
			return true;
		}
	}
	return false;
}
add_shortcode( 'pw_brand_all_views', 'pw_brand_all_views_func' );
function pw_brand_all_views_func( $atts, $content = null ) {
	$pw_list_view_layout=$pw_show_count=$pw_featured=$pw_hide_empty_brands=$pw_show_image=$pw_show_title=$pw_columns=$pw_tablet_columns=$pw_mobile_columns=$pw_style=$pw_tooltip=$pw_adv1_category=$pw_adv2_category=$pw_filter_style=$ret="";


	extract(shortcode_atts( array(
		'type' => 'simple',
		'pw_show_count' => '',
		'pw_featured' => '',
		'pw_hide_empty_brands' => '',
		'pw_show_image' => '',
		'pw_show_title' => '',
		'pw_columns'=>'wb-col-md-3',
		'pw_tablet_columns'=>'wb-col-sm-6',
		'pw_mobile_columns'=>'wb-col-xs-12',
		'pw_style' => '',
		'pw_tooltip' => '',
		'pw_adv1_category'=> '',
		'pw_filter_style'=> '',
		'pw_adv2_category'=> '',
	),$atts));
	//print_r($atts);
	switch ($type) {
		case 'simple':
			require plugin_dirname_pw_woo_brand.'/includes/brand_lists/simple.php';
		break;
	}
	
	return $ret;	
}

add_shortcode( 'pw_brand_carousel', 'pw_brand_carousel_func' );
function pw_brand_carousel_func( $atts, $content = null ) {
	//Add BxSlider
	wp_enqueue_style('woob-bxslider-style');	
	wp_enqueue_script('woob-bxslider-script');
	$pw_brand=$pw_show_image=$pw_tooltip=$pw_except_brand=$pw_featured=$pw_show_title=$pw_show_count=$pw_style=$pw_carousel_style=$pw_carousel_skin_style=$pw_round_corner=
	$pw_item_width=$pw_item_marrgin=$pw_slide_direction=$pw_show_pagination=$pw_show_control
	=$pw_item_per_view=$pw_item_per_slide=$pw_slide_speed=$pw_auto_play="";
	extract(shortcode_atts( array(
		'pw_brand' => '',
		'pw_except_brand' => '',
		'pw_style' => '',
		'pw_carousel_style' => '',
		'pw_carousel_skin_style' => '',
		'pw_tooltip' => '',
		'pw_round_corner' => '',
		'pw_show_image' => '',
		'pw_show_image_size' => '',
		'pw_featured' => '',
		'pw_show_title' => '',
		'pw_show_count' => '',
		'pw_item_width' => '300',
		'pw_item_marrgin' => '10',
		'pw_slide_direction' => '',
		'pw_show_pagination' => '',
		'pw_show_control' => '',
		'pw_item_per_view' => '3',
		'pw_item_per_slide' => '1',
		'pw_slide_speed' => '1',
		'pw_auto_play' => '',
	),$atts));
	//print_r($atts);
	if(get_option('pw_woocommerce_brands_show_categories')=="yes")
		$get_terms="product_cat";
	else
		$get_terms="product_brand";
	
	$exclude = array_map( 'intval', explode( ',', $pw_except_brand ) );
	//$exclude=$pw_except_brand;
	if($pw_except_brand =="null" || $pw_except_brand=="all"|| $pw_except_brand=="")
		$exclude="";
	
	$include = array_map( 'intval', explode( ',', $pw_brand ) );
	//$include=$pw_brand;
	if($pw_brand =="null" || $pw_brand=="all" || $pw_brand=="")
		$include="";
	$args = array(
		'orderby'           => 'name', 
		'order'             => 'ASC',
		'hide_empty'        => false,
		'exclude'           => $exclude, 
		'exclude_tree'      => array(), 
		'include'           => $include,
		'number'            => '', 
		'fields'            => 'all', 
		'slug'              => '',
		'name'              => '',
		'parent'            => '',
		'hierarchical'      => true, 
		'child_of'          => 0, 
		'get'               => '', 
		'name__like'        => '',
		'description__like' => '',
		'pad_counts'        => false, 
		'offset'            => '', 
		'search'            => '', 
		'cache_domain'      => 'core'
	);	
	$categories = get_terms( $get_terms, $args);
	$ret="";
	$did=rand(0,1000);
	$count='';
	$pw_slide_speed = (trim($pw_slide_speed)=='')?1:$pw_slide_speed;
	$pw_item_width = (trim($pw_item_width)=='')?300:$pw_item_width;
	$pw_item_marrgin = (trim($pw_item_marrgin)=='')?10:$pw_item_marrgin;
	$pw_item_per_view = (trim($pw_item_per_view)=='')?3:$pw_item_per_view;
	$pw_item_per_slide = (trim($pw_item_per_slide)=='')?1:$pw_item_per_slide;
	$pw_auto_play = ((trim($pw_auto_play)=='') || (!isset($pw_auto_play)))?'false':$pw_auto_play;
	
	$ret .= '<ul class="wb-bxslider wb-car-car  wb-carousel-layout wb-car-cnt " id="slider_'.$did.'"  style="visibility:hidden" >';
	foreach( (array) $categories as $term ) {
		$display_featured	= get_woocommerce_term_meta( $term->term_id, 'featured', true );
		
		$url= esc_html(get_woocommerce_term_meta( $term->term_id, 'url', true ));
		if($url=="")
			$url= get_term_link( $term->slug, $get_terms );

		$image= '';
		if($pw_show_count=="yes") $count=' ('.esc_html( $term->count).')';
		if($pw_show_image=="yes")
		{
			
			$thumbnail_id = get_woocommerce_term_meta( $term->term_id, 'thumbnail_id', true );			

			if ( $thumbnail_id )
			{
				if($pw_show_image_size=="full")
				{
					$image = current( wp_get_attachment_image_src( $thumbnail_id, 'full' ) );
				}
				else
				{
					$image = wp_get_attachment_thumb_url( $thumbnail_id );
				}
			}
			else
			{
				if(get_option('pw_woocommerce_brands_default_image'))
					$image=wp_get_attachment_thumb_url(get_option('pw_woocommerce_brands_default_image'));
				else
					$image = WP_PLUGIN_URL.'/woo-brand/img/default.png';
			}
		}
			if($pw_featured=="yes" && $display_featured==1)
			{
				$ret .='<li>
					<div class="wb-car-item-cnt" rel="tipsy" title="'.$term->name.$count.'">';
				if ($image!='')	{  
					$ret .='<a href="'.$url.'">'.'<img src="'.$image.'" >'.'</a>';
				}
				if ($pw_show_title=='yes'){
					$ret.='<div class="wb-car-title"><a  href="'.$url.'" title="'.$term->name.'" >'.$term->name.'</a>'.$count.'</div>';
				}
				$ret .='</div>
				</li>';
			}
			elseif($pw_featured=="no")
			{

				$ret .='<li>
					<div class="wb-car-item-cnt" rel="tipsy" title="'.$term->name.$count.'">';
				if ($image!='')	{  
					$ret .='<a href="'.$url.'" >'.'<img src="'.$image.'" >'.'</a>';
				}
				if ($pw_show_title=='yes'){
					$ret.='<div class="wb-car-title"><a  href="'.$url.'" title="'.$term->name.'" >'.$term->name.'</a>'.$count.'</div>';
				}
				$ret .='</div>
				</li>';
			}
		
		
	}
	$ret .='</ul>';
	if ( $pw_tooltip=='yes' ){
		$ret .="
		<script type='text/javascript'>
			jQuery(function() {
			   jQuery('#slider_" . $did ." div[rel=tipsy]').tipsy({ gravity: 's',live: true,fade:true});
			   jQuery('#slider_" . $did ."').css('visibility','visible');
			});
		</script>";
	}
	$ret .="<script type='text/javascript'>
                jQuery(document).ready(function() {
					jQuery('#slider_" . $did ."').css('visibility','visible');
                    slider" . $did ." =
					 jQuery('#slider_" . $did ."').bxSlider({ 
						  mode : '".($pw_slide_direction=='vertical' ? 'vertical' : 'horizontal' )."' ,
						  touchEnabled : true ,
						  adaptiveHeight : true ,
						  slideMargin : ".$pw_item_marrgin." , 
						  wrapperClass : 'wb-bx-wrapper wb-car-car wb-car-cnt ".$pw_style." ".$pw_round_corner." ".$pw_carousel_style." ".$pw_carousel_skin_style."',
						  infiniteLoop:true,
						  pager:".($pw_show_pagination =='true'?'true':'false').",
						  controls:".($pw_show_control=='true'?'true':'false').",".
						  ($pw_slide_direction=='horizontal' ? 'slideWidth:'.$pw_item_width.',' : 'slideWidth:5000,' )."
						  minSlides: ". $pw_item_per_view.",
						  maxSlides: ". $pw_item_per_view.",
						  moveSlides: ".$pw_item_per_slide.",
						  auto: ". $pw_auto_play.",
						  pause : ". $pw_slide_speed .",
						  autoHover  : true , 
 						  autoStart: true,
						  responsive:false,
					 });";
					 if ($pw_auto_play=='true'){
					 $ret.="
						 jQuery('.wb-bx-wrapper .wb-bx-controls-direction a').click(function(){
							  slider" . $did .".startAuto();
						 });
						 jQuery('.wb-bx-pager a').click(function(){
							 var i = jQuery(this).data('slide-index');
							 slider" . $did .".goToSlide(i);
							 slider" . $did .".stopAuto();
							 restart=setTimeout(function(){
								slider" . $did .".startAuto();
								},1000);
							 return false;
						 });";
					 }
               $ret.=" });	
            </script>";
	return $ret;
}
?>