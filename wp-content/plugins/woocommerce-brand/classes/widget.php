<?php 
/*
	WidGet For Woo Brand V3.0
*/
//Add Carousel Pro Brand`s
class pw_widget_carousel extends WP_Widget {
	function __construct() {
		parent::__construct(
			'pw_widget_carousel', // Base ID
			__('Woo Brand`s Carousel', 'woocommerce-brands'), // Name
			array( 'description' => __( 'Display a Carousel of your Brands on widget.', 'woocommerce-brands' ), ) // Args
		);
	}
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
		echo do_shortcode('[pw_brand_carousel
				pw_brand="'.$instance['pw_brand'].'" 
				pw_except_brand="'.$instance['pw_except_brand'].'" 
				pw_style="'.$instance['pw_style'].'" 
				pw_tooltip="'.$instance['pw_tooltip'].'" 
				pw_round_corner="'.$instance['pw_round_corner'].'" 
				pw_show_image="'.$instance['pw_show_image'].'" 
				pw_featured="'.$instance['pw_featured'].'" 
				pw_show_title="'.$instance['pw_show_title'].'" 
				pw_show_count="'.$instance['pw_show_count'].'" 
				pw_item_width="'.$instance['pw_item_width'].'" 
				pw_item_marrgin="'.$instance['pw_item_marrgin'].'" 
				pw_slide_direction="'.$instance['pw_slide_direction'].'" 
				pw_show_pagination="'.$instance['pw_show_pagination'].'" 
				pw_show_control="'.$instance['pw_show_control'].'" 
				pw_item_per_view="'.$instance['pw_item_per_view'].'" 
				pw_item_per_slide="'.$instance['pw_item_per_slide'].'" 
				pw_slide_speed="'.$instance['pw_slide_speed'].'" 
				pw_auto_play="'.$instance['pw_auto_play'].'"]');			
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('title:', 'woocommerce-brands') ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php if ( isset ( $instance['title'] ) ) echo esc_attr( $instance['title'] ); ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_brand' ); ?>"><?php _e('Brand`s:', 'woocommerce-brands') ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'pw_brand' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pw_brand' ) ); ?>" value="<?php if ( isset ( $instance['pw_brand'] ) ) echo esc_attr( $instance['pw_brand'] ); ?>" placeholder="<?php _e('All', 'woocommerce-brands'); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_except_brand' ); ?>"><?php _e('Except Brand`s:', 'woocommerce-brands') ?></label>
			<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'pw_except_brand' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pw_except_brand' ) ); ?>" value="<?php if ( isset ( $instance['pw_except_brand'] ) ) echo esc_attr( $instance['pw_except_brand'] ); ?>" placeholder="<?php _e('None', 'woocommerce-brands'); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_featured' ); ?>"><?php _e('Display Only Featured Brands:', 'woocommerce-brands') ?></label>
			<p><input id="rss-show-summary" name="<?php echo $this->get_field_name('pw_featured'); ?>" type="checkbox" value="yes" <?php checked( @$instance['pw_featured'], 'yes' ); ?> />				
			<label for="rss-show-summary"><?php echo _e('Display Only Featured Brands','woocommerce-brands'); ?></label></p>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_show_count' ); ?>"><?php _e('Display Count Of Brands:', 'woocommerce-brands') ?></label>
			<p><input id="rss-show-summary" name="<?php echo $this->get_field_name('pw_show_count'); ?>" type="checkbox" value="yes" <?php checked( @$instance['pw_show_count'], 'yes' ); ?> />				
			<label for="rss-show-summary"><?php echo _e('Display Count Of Brands','woocommerce-brands'); ?></label></p>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_show_image' ); ?>"><?php _e('Display Brand`s Image:', 'woocommerce-brands') ?></label>
			<p><input id="rss-show-summary" name="<?php echo $this->get_field_name('pw_show_image'); ?>" type="checkbox" value="yes" <?php checked( @$instance['pw_show_image'], 'yes' ); ?> />				
			<label for="rss-show-summary"><?php echo _e('Display Brand`s Image','woocommerce-brands'); ?></label></p>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_show_title' ); ?>"><?php _e('Display Brand`s Title:', 'woocommerce-brands') ?></label>
			<p><input id="rss-show-summary" name="<?php echo $this->get_field_name('pw_show_title'); ?>" type="checkbox" value="yes" <?php checked( @$instance['pw_show_title'], 'yes' ); ?> />				
			<label for="rss-show-summary"><?php echo _e('Display Brand`s Title','woocommerce-brands'); ?></label></p>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_tooltip' ); ?>"><?php _e('Show Tooltip:', 'woocommerce-brands') ?></label>
			<p><input id="rss-show-summary" name="<?php echo $this->get_field_name('pw_tooltip'); ?>" type="checkbox" value="yes" <?php checked( @$instance['pw_tooltip'], 'yes' ); ?> />				
			<label for="rss-show-summary"><?php echo _e('Show Tooltip','woocommerce-brands'); ?></label></p>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_style' ); ?>"><?php _e('Style:', 'woocommerce-brands') ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'pw_style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pw_style' ) ); ?>">
				<option value="wb-car-style1" <?php selected( @$instance['pw_style'], 'wb-car-style1' ,1) ?>><?php _e('Style 1', 'woocommerce-brands') ?></option>
				<option value="wb-car-style2" <?php selected( @$instance['pw_style'], 'wb-car-style2',1 ) ?>><?php _e('Style 2', 'woocommerce-brands') ?></option>
				<option value="wb-car-style3" <?php selected( @$instance['pw_style'], 'wb-car-style3',1 ) ?>><?php _e('Style 3', 'woocommerce-brands') ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_round_corner' ); ?>"><?php _e('Round Corner:', 'woocommerce-brands') ?></label>
			<p><input id="rss-show-summary" name="<?php echo $this->get_field_name('pw_round_corner'); ?>" type="checkbox" value="wb-car-round" <?php checked( @$instance['pw_round_corner'], 'wb-car-round' ); ?> />				
			<label for="rss-show-summary"><?php echo _e('Round Corner','woocommerce-brands'); ?></label></p>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_item_width' ); ?>"><?php _e('Item Width:', 'woocommerce-brands') ?></label>
			<input type="number" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'pw_item_width' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pw_item_width' ) ); ?>" value="<?php if ( isset ( $instance['pw_item_width'] ) ) echo esc_attr( $instance['pw_item_width'] ); ?>" placeholder="<?php _e('300', 'woocommerce-brands'); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_item_marrgin' ); ?>"><?php _e('Item Marrgin:', 'woocommerce-brands') ?></label>
			<input type="number" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'pw_item_width' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pw_item_marrgin' ) ); ?>" value="<?php if ( isset ( $instance['pw_item_marrgin'] ) ) echo esc_attr( $instance['pw_item_marrgin'] ); ?>" placeholder="<?php _e('300', 'woocommerce-brands'); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_slide_direction' ); ?>"><?php _e('Slide direction:', 'woocommerce-brands') ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'pw_slide_direction' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pw_slide_direction' ) ); ?>">
				<option value="vertical" <?php selected( @$instance['pw_slide_direction'], 'vertical',1) ;?>><?php _e('Vertical', 'woocommerce-brands') ?></option>
				<option value="horizontal" <?php selected( @$instance['pw_slide_direction'], 'horizontal',1); ?>><?php _e('Horizontal', 'woocommerce-brands') ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_show_pagination' ); ?>"><?php _e('Show Pagination:', 'woocommerce-brands') ?></label>
			<p><input id="rss-show-summary" name="<?php echo $this->get_field_name('pw_show_pagination'); ?>" type="checkbox" value="true" <?php checked( @$instance['pw_show_pagination'], 'true' ); ?> />				
			<label for="rss-show-summary"><?php echo _e('Show Pagination','woocommerce-brands'); ?></label></p>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_show_control' ); ?>"><?php _e('Show Control:', 'woocommerce-brands') ?></label>
			<p><input id="rss-show-summary" name="<?php echo $this->get_field_name('pw_show_control'); ?>" type="checkbox" value="true" <?php checked( @$instance['pw_show_control'], 'true' ); ?> />				
			<label for="rss-show-summary"><?php echo _e('Show Control','woocommerce-brands'); ?></label></p>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_item_per_view' ); ?>"><?php _e('Item Per View:', 'woocommerce-brands') ?></label>
			<input type="number" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'pw_item_per_view' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pw_item_per_view' ) ); ?>" value="<?php if ( isset ( $instance['pw_item_per_view'] ) ) echo esc_attr( $instance['pw_item_per_view'] ); ?>" placeholder="<?php _e('3', 'woocommerce-brands'); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_slide_speed' ); ?>"><?php _e('Slide Speed:', 'woocommerce-brands') ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'pw_slide_speed' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pw_slide_speed' ) ); ?>">
				<option value="1000" <?php selected( @$instance['pw_slide_speed'], '1000',1) ?>><?php _e('1 Sec', 'woocommerce-brands') ?></option>
				<option value="2000" <?php selected( @$instance['pw_slide_speed'], '2000',1 ) ?>><?php _e('2 Sec', 'woocommerce-brands') ?></option>
				<option value="3000" <?php selected( @$instance['pw_slide_speed'], '3000',1 ) ?>><?php _e('3 Sec', 'woocommerce-brands') ?></option>
				<option value="4000" <?php selected( @$instance['pw_slide_speed'], '4000',1 ) ?>><?php _e('4 Sec', 'woocommerce-brands') ?></option>
				<option value="5000" <?php selected( @$instance['pw_slide_speed'], '5000',1 ) ?>><?php _e('5 Sec', 'woocommerce-brands') ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'pw_auto_play' ); ?>"><?php _e('Auto play:', 'woocommerce-brands') ?></label>
			<p><input id="rss-show-summary" name="<?php echo $this->get_field_name('pw_auto_play'); ?>" type="checkbox" value="true" <?php checked( @$instance['pw_auto_play'], 'true' ); ?> />				
			<label for="rss-show-summary"><?php echo _e('Auto play','woocommerce-brands'); ?></label></p>
		</p>
		
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) :'';
		$instance['pw_brand']     = ( ! empty($new_instance['pw_brand'] )) ? $new_instance['pw_brand'] : '';
		$instance['pw_except_brand']     = isset($new_instance['pw_except_brand'] ) ? $new_instance['pw_except_brand'] : 'null';
		$instance['pw_style']     = isset($new_instance['pw_style'] ) ? $new_instance['pw_style'] : 'wb-car-style1';
		$instance['pw_tooltip']     = isset($new_instance['pw_tooltip'] ) ? $new_instance['pw_tooltip'] : 'no';
		$instance['pw_round_corner']     = isset($new_instance['pw_round_corner'] ) ? $new_instance['pw_round_corner'] : 'wb-car-no';
		$instance['pw_show_image']     = isset($new_instance['pw_show_image'] ) ? $new_instance['pw_show_image'] : 'no';
		$instance['pw_featured']     = isset($new_instance['pw_featured'] ) ? $new_instance['pw_featured'] : 'no';
		$instance['pw_show_title']     = isset($new_instance['pw_show_title'] ) ? $new_instance['pw_show_title'] : 'no';
		$instance['pw_show_count']     = isset($new_instance['pw_show_count'] ) ? $new_instance['pw_show_count'] : 'no';
		$instance['pw_item_width']     = isset($new_instance['pw_item_width'] ) ? $new_instance['pw_item_width'] : '300';
		$instance['pw_item_marrgin']     = isset($new_instance['pw_item_marrgin'] ) ? $new_instance['pw_item_marrgin'] : '10';
		$instance['pw_slide_direction']     = isset($new_instance['pw_slide_direction'] ) ? $new_instance['pw_slide_direction'] : 'vertical';
		$instance['pw_show_pagination']     = isset($new_instance['pw_show_pagination'] ) ? $new_instance['pw_show_pagination'] : 'false';
		$instance['pw_show_control']     = isset($new_instance['pw_show_control'] ) ? $new_instance['pw_show_control'] : 'false';
		$instance['pw_item_per_view']     = isset($new_instance['pw_item_per_view'] ) ? $new_instance['pw_item_per_view'] : '3';
		$instance['pw_item_per_slide']     = isset($new_instance['pw_item_per_slide'] ) ? $new_instance['pw_item_per_slide'] : '1';
		$instance['pw_slide_speed']     = isset($new_instance['pw_slide_speed'] ) ? $new_instance['pw_slide_speed'] : 'false';
		$instance['pw_auto_play']     = isset($new_instance['pw_auto_play'] ) ? $new_instance['pw_auto_play'] : 'false';
		return $instance;
	}
}
register_widget( 'pw_widget_carousel' );	

/*
	WidGet Brand V2 
*/
class pw_brands_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'pw_brands_Widget', // Base ID
			__('WooCommerce Brands', 'woocommerce-brands'), // Name
			array( 'description' => __( 'Display a list of your Brands on your site.', 'woocommerce-brands' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
		echo $args['before_widget'];
		if ( ! empty( $title ) )
			echo $args['before_title'] . $title . $args['after_title'];
			
		if(get_option('pw_woocommerce_brands_show_categories')=="yes")
			$get_terms="product_cat";
		else
			$get_terms="product_brand";
					
		$categories = get_terms( $get_terms, 'orderby=name&hide_empty=0' );
			
			if ( ! empty( $categories ) ) {
				
				if($instance['show']=="dropdown")
				{
					
					wp_enqueue_script('woob-dropdown-script');

					?>
					<script type='text/javascript'>
                    /* <![CDATA[ */                    
						function onbrandsChange(value) {
							if(value=="")
								return false;
							var val=Array();
							val=value.split('@');
							//confirm(val[1]);
							if(val[0]=='slug')
								window.location= "<?php echo home_url(); ?>/?<?php echo $get_terms;?>="+val[1];
							else
								window.location= val[1];
						}
						
						jQuery(document).ready(function() {
//							jQuery("#payments").msDropdown({visibleRows:4});
							jQuery(".tech").msDropdown();	
			//				jQuery( '#carouselhor' ).elastislide(
			//					{
			//					 minItems : parseInt(jQuery( '#carouselhor' ).attr('title')),
			//					}
			//				);
						});
						/* ]]> */
                     </script>                    
					<?php
					echo '<select name="tech" class="tech" onchange="onbrandsChange(this.value)" >';
					//if(get_option('pw_woocommerce_brands_show_categories')=="yes")
						 echo '<option value="">'. __('Please Select','woocommerce-brands').'</option>';
					//else
					//	 echo '<option value="">'.__('Select One','woocommerce-brands') .'</option>';
						 
					 foreach( (array) $categories as $term ) { 
					  $url	= esc_html(get_woocommerce_term_meta( $term->term_id, 'url', true ));
					  $display_type = get_woocommerce_term_meta( $term->term_id, 'featured', true );
					  $count="";
					  if($instance['post_counts']==1)
					   $count='( '. esc_html( $term->count ) .' )  ';
							 
					  if($instance['featured']==1 && $display_type==1)
					  {
						if($url!="")
							echo'<option value="url@'.esc_html( $url ).'">'.esc_html( $term->name ).$count.'</option>';
						else
							echo'<option value="slug@'.esc_html( $term->slug ).'" '.selected( esc_html ( get_query_var( 'product_brand' ) ) , esc_html( $term->slug ) , 1 ).'>'.esc_html( $term->name ).$count.'</option>';
						
					  }
					  elseif($instance['featured']==0)
					  {
						if($url!="")
							echo'<option value="url@'.esc_html( $url ).'">'.esc_html( $term->name ).$count.'</option>';
						else
							echo'<option value="slug@'.esc_html( $term->slug ).'" '.selected( esc_html ( get_query_var( 'product_brand' ) ) , esc_html( $term->slug ) , 1 ).'>'.esc_html( $term->name ).$count.'</option>';
					  }
					 }
					 echo '</select>';	
				}
			}
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','woocommerce-brands'); ?></label>
			<input type="text" class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php if (isset ( $instance['title'])) {echo esc_attr( $instance['title'] );} ?>" /></p>
            
		<p><label for="<?php echo $this->get_field_id('show'); ?>"><?php _e('Display Show:','woocommerce-brands'); ?></label>
            <select class='widefat' id="<?php echo $this->get_field_id('show'); ?>"
                    name="<?php echo $this->get_field_name('show'); ?>" type="text">
              <option value='dropdown' <?php selected( @$instance['show'] , "dropdown",1); ?>>
                Display DropDown
              </option>
              <option value='a-z' <?php selected( @$instance['show'] , "a-z",1); ?>>
                Display A-Z(only in pro)
              </option>
            </select>
        </p>

		<p><input id="rss-show-summary" name="<?php echo $this->get_field_name('featured'); ?>" type="checkbox" value="1" <?php checked( @$instance['featured'], 1 ); ?> />
		<label for="rss-show-summary"><?php echo _e('Display Only featured?','woocommerce-brands'); ?></label></p>		
		<p><input id="rss-show-summary" name="<?php echo $this->get_field_name('post_counts'); ?>" type="checkbox" value="1" <?php checked( @$instance['post_counts'], 1 ); ?> />
		<label for="rss-show-summary"><?php echo _e('Show post counts','woocommerce-brands'); ?></label></p>
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['show'] = $new_instance['show'];		
		$instance['featured']     = isset($new_instance['featured'] ) ? (int) $new_instance['featured'] : 0;
		$instance['post_counts']     = isset($new_instance['post_counts'] ) ? (int) $new_instance['post_counts'] : 0;				
		return $instance;
	}
}
register_widget( 'pw_brands_Widget' );
