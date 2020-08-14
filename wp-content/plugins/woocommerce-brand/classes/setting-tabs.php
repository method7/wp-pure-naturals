<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class pw_woocommerc_brans_WC_Admin_Tabs {

	public $tab; 
	public $options; 
	
	/**
	 * Constructor
	 */
	public function __construct() {
/*	*/
		$this->options = $this->pw_woocommerce_brands_plugin_options();
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'pw_woocommerce_brands_add_tab_woocommerce' ) );
		
		add_filter( 'woocommerce_page_settings', array( $this, 'pw_woocommerce_brands_add_page_setting_woocommerce' ) );
		add_action( 'woocommerce_update_options_pw_woocommerce_brands', array( $this, 'pw_woocommerce_brands_update_options' ) );
		add_action( 'woocommerce_admin_field_addons', array( $this, 'admin_fields_addons' ) );
		
		// save custom field types
		add_action( 'init', array( $this, 'save_custom_field_types' ) );		
		
		//
		add_action( 'woocommerce_settings_tabs_pw_woocommerce_brands', array( $this, 'pw_woocommerce_brands_print_plugin_options' ) );
		
		
		// Add brands filtering to the coupon creation screens.
		add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'add_coupon_brands_fields' ) );
	}

	public function add_coupon_brands_fields () {
		global $post;
		// Brands
		if(!defined('plugin_dir_url_pw_woo_brand_coupon'))
		{
			?>
			<p class="form-field"><label for="product_brands"><?php _e( 'Product brands', 'woocommerce-brands' ); ?></label>
			<?php _e('Please BUY/activated woocommerce brand coupon Add-ons', 'woocommerce-brands'); ?> <a href="http://proword.net/product/brand-coupon-add-on/" >Click for Buy</a> <img class="help_tip" data-tip='<?php _e( 'A product must be associated with this brand for the coupon to remain valid or, for "Product Discounts", products with these brands will be discounted.', 'woocommerce-brands' ); ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
			<?php

			// Exclude Brands
			?>
			<p class="form-field"><label for="exclude_product_brands"><?php _e( 'Exclude brands', 'woocommerce-brands' ); ?></label>
			<?php _e('Please BUY/activated woocommerce brand coupon Add-ons', 'woocommerce-brands'); ?> <a href="http://proword.net/product/brand-coupon-add-on/" >Click for Buy</a>
			<img class="help_tip" data-tip='<?php _e( 'Product must not be associated with these brands for the coupon to remain valid or, for "Product Discounts", products associated with these brands will not be discounted.', 'woocommerce-brands' ) ?>' src="<?php echo WC()->plugin_url(); ?>/assets/images/help.png" height="16" width="16" /></p>
			<?php
		}
	} // End add_coupon_brands_fields()
		
	public function save_custom_field_types() {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.4.0', '>=' ) ) {
			add_filter( 'woocommerce_admin_settings_sanitize_option_pw_woocommerce_image_brand_shop_page_image_size', array( $this, 'save_size_field' ), 10, 3 );
		} else {
			add_action( 'woocommerce_update_option_size', array( $this, '_deprecated_save_size_field' ) );			
		}
	}

	public function _deprecated_save_size_field( $option ) {
		$value = $this->save_size_field( null, $option, null );
		update_option( $option['id'], $value );
	}
	
	public function save_size_field( $value, $option, $raw_value ) {
			
		if ( isset( $_POST[ $option['id'] . '_width' ] ) && ! empty( $_POST[ $option['id'] . '_height' ] ) )
			return wc_clean( $_POST[ $option['id'] . '_width' ] ) . ':' . wc_clean( $_POST[ $option['id'] . '_height' ] );
	}
	
	function pw_woocommerce_brands_add_tab_woocommerce($tabs){
		$tabs['pw_woocommerce_brands'] = __('Brands','woocommerce-brands'); // or whatever you fancy
		return $tabs;
	}
	
	/**
	 * Update plugin options.
	 * 
	 * @return void
	 * @since 1.0.0
	 */
	public function pw_woocommerce_brands_update_options() {
	global $wp_rewrite;
		foreach( $this->options as $option ) {
			woocommerce_update_options( $option );   
		}
		
	   	$wp_rewrite->flush_rules();		
	}
	
	/**
	 * Add the select for the Woocommerce Brands page in WooCommerce > Settings > Pages
	 * 
	 * @param array $settings
	 * @return array
	 * @since 1.0.0
	 */
	public function pw_woocommerce_brands_add_page_setting_woocommerce( $settings ) {
		unset( $settings[count( $settings ) - 1] );
		
		$settings[] = array(
			'name' => __( 'Wishlist Page', 'woocommerce-brands' ),
			'desc' 		=> __( 'Page contents: [pw_woocommerce_brands]', 'woocommerce-brands' ),
			'id' 		=> 'pw_woocommerce_brands_page_id',
			'type' 		=> 'single_select_page',
			'std' 		=> '',         // for woocommerce < 2.0
			'default' 	=> '',         // for woocommerce >= 2.0
			'class'		=> 'chosen_select_nostd',
			'css' 		=> 'min-width:300px;',
			'desc_tip'	=>  false,
		);
		
		$settings[] = array( 'type' => 'sectionend', 'id' => 'page_options');
		
		return $settings;
	}

	
	
	
	public function pw_woocommerce_brands_print_plugin_options() {

		?>
		<div class="subsubsub_section">
			<br class="clear" />
			<?php foreach( $this->options as $id => $tab ) : ?>
			<div class="section" id="pw_woocommerce_brands_<?php echo $id ?>">
				<?php woocommerce_admin_fields( $this->options[$id] ) ;?>

			</div>
			<?php endforeach;?>

		</div>
		<?php
	}
	
	private function pw_woocommerce_brands_plugin_options() {
		$options['general_settings'] = array(
			array( 'name' => __( 'General Settings', 'woocommerce-brands' ), 'type' => 'title', 'desc' => '', 'id' => 'pw_woocommerce_brands_general_settings' ),
			array(
				'title' => __( 'Customize "Brand"', 'woocommerce-brands' ),
				'desc' 		=> __( 'Change "Brand" Text', 'woocommerce-brands' ),
				'id' 		=> 'pw_woocommerce_brands_text',
				'css' 		=> 'width:150px;',
				'default'	=> 'Brands',
				'type' 		=> 'text',
				'desc_tip'	=>  true,
			),		
			array(
				'name'      => __( 'Single product brand position', 'woocommerce-brands' , 'woocommerce-brands'),
				'desc'      => __( 'Position for brand list', 'woocommerce-brands' ),
				'id'        => 'pw_woocommerce_brands_position_single_brand',
				'type'      => 'select',
				'class'		=> 'chosen_select',
				'css' 		=> 'min-width:300px;',
				'options'   => array(
					'default' => __( 'Default', 'woocommerce-brands' ),
					'1' => __( 'Above Product Title', 'woocommerce-brands' ),
					'2' => __( 'Below Product Title', 'woocommerce-brands' ),
					'3' => __( 'After product price', 'woocommerce-brands' ),
					'4' => __( 'After product excerpt', 'woocommerce-brands' ),
					'5' => __( 'After single Add to Cart', 'woocommerce-brands' ),
					'6' => __( 'After product meta', 'woocommerce-brands' ),
					'7' => __( 'After product share', 'woocommerce-brands' ),
				),
				'desc_tip'	=>  true
			),
			array(
				'name'      => __( 'Product Listing brand position', 'woocommerce-brands' , 'woocommerce-brands'),
				'desc'      => __( 'Position for brand in Product Listing', 'woocommerce-brands' ),
				'id'        => 'pw_woocommerce_brands_position_product_list',
				'type'      => 'select',
				'class'		=> 'chosen_select',
				'css' 		=> 'min-width:300px;',
				'options'   => array(
					'default' => __( 'Default', 'woocommerce-brands' ),
					'before_price' => __( 'Before price', 'woocommerce-brands' ),
					'before_title' => __( 'Before Title', 'woocommerce-brands' ),
					'after_title' => __( 'After Title', 'woocommerce-brands' ),
					'before_addcart' => __( 'Before Add to Cart', 'woocommerce-brands' ),
					'after_addcart' => __( 'After Add to Cart', 'woocommerce-brands' ),
				),
				'desc_tip'	=>  true
			),			
			/*array(
				'title' => __( 'Display From', 'woocommerce-brands' ),
				'id' 		=> 'pw_woocommerce_brands_show_categories',
				'default'	=> 'no',
				'type' 		=> 'radio',
				'desc_tip'	=>  __( 'This option is show Categories or Brands.', 'woocommerce-brands' ),
				'options'	=> array(
					'no' => __( 'Brands', 'woocommerce-brands' ),
					'yes' => __( 'Categories', 'woocommerce-brands' )
				),
			),*/
			array( 'type' => 'sectionend', 'id' => 'pw_woocommerce_brands_general_settings' )
		);

	$options['brands_settings'] = array(
			array( 'name' => __( 'Brand`s Image&Text Setting', 'woocommerce-brands' ), 'type' => 'title', 'desc' => '', 'id' => 'pw_woocommerce_brands_image_settings' ),

		/*	array(
				'name'      =>  '' ,
				'desc'      => __( 'Display Brand`s In Producut`s Page(shop page)', 'woocommerce-brands'), 
				'id'        => 'pw_woocommerce_brands_shop_page',
				'std' 		=> 'yes',         // for woocommerce < 2.0
				'default' 	=> 'no',         // for woocommerce >= 2.0
				'type'      => 'checkbox'
			),
		*/
			array(
				'name'      => __( 'Image Size', 'woocommerce-brands' ),
				'desc'      => __( 'This size is usually used in product listings', 'woocommerce-brands'), 
				'id'        => 'pw_woocommerce_image_brand_shop_page_image_size',
				'std' 		=> '',         // for woocommerce < 2.0
				'default' 	=> '150:150',         // for woocommerce >= 2.0
				'type'      => 'size'
			),			

			array(
				'name'      => __( 'Image Size', 'woocommerce-brands' ),
				'desc'      => __( 'This size is usually used in product listings', 'woocommerce-brands'), 
				'id'        => 'pw_woocommerce_brands_image_list_image_size',
				'std' 		=> '',         // for woocommerce < 2.0
				'default' 	=> '1:1',         // for woocommerce >= 2.0
				'type'      => 'size'
			),
			
			array(
				'name'      => __( 'Default Image', 'woocommerce-brands' ),
				'desc'      => __( 'Add Default Image', 'woocommerce-brands'), 
				'id'        => 'pw_woocommerce_brands_default_image',
				'std' 		=> '',         // for woocommerce < 2.0
				'default' 	=> '',         // for woocommerce >= 2.0
				'type'      => 'upload'
			),
		
			array(
				'name'      => __( 'Brand`s Add-ons', 'woocommerce-brands' ),
				'desc'      => '', 
				'id'        => 'pw_woocommerce_brands_addons',
				'std' 		=> '',         // for woocommerce < 2.0
				'default' 	=> '',         // for woocommerce >= 2.0
				'type'      => 'addons'
			),
			array( 'type' => 'sectionend', 'id' => 'pw_woocommerce_brands_image_settings' ),

			array( 'type' => 'sectionend', 'id' => 'pw_woocommerce_brands_position_settings' ),				
			array( 'type' => 'sectionend', 'id' => 'pw_woocommerce_brands_position_settings' ),	
						array(	'title' => __( 'Pro Version', 'woocommerce' ), 'type' => 'title', 
			'desc' => '<p style="color:red;">To Buy Pro Version Please <a href="http://codecanyon.net/item/woocommerce-brands/8039481?ref=proword?ref=proword" target="blank">Click Here</a></P>Pro Version Feature:<br/>
<ul>
            <li><strong>Clean Design</strong></li>
            <br>
            <li><strong>Responsive Layout</strong></li>
            <br>
            <li><strong>WPML Plugin Support</strong></li>
            <br>
            <li><strong>Multisite Support</strong></li>
            <br>
            <li><strong>Campatible with Visual Composer plugin</strong></li>
            <br>
            <li><strong>Assign Brands to Products</strong></li>
            <br>
            <li><strong>Create Your Custom Shortcode</strong></li>
            <br>
            <li><strong>10 different views</strong></li>
            <br>
            <li>
<strong>7 Awesome Shortcodes</strong>
                <ul>
                    <li>Display All Brands with A-Z Filter</li>
                    <li>Brands Thumbnail</li>
                    <li>Product by Brand With Ajax Filter</li>
                    <li>Display Vertical Carousel (Vertical Slider)</li>
                    <li>Display Horizontal Carousel (Horizaontal Slider)</li>
                    <li>Display All Brands in Text Mode</li>
                    <li>Display All Brands in Image Mode</li>
                </ul>
            </li>
            <br>
            <li>
<strong>Widgets</strong>
                <ul>
                    <li>Display All Brands with A-Z Filter</li>
                    <li>Brands Thumbnail</li>
                    <li>Products Brand Filter(List/Dropdown)</li>
                    <li>Display Vertical Carousel (Vertical Slider)</li>
                    <li>Display Horizontal Carousel (Horizaontal Slider)</li>
                    <li>Display All Brands in Dropdown</li>
                </ul>
            </li>
            <br>
            <li>
<strong>Extra Button</strong>
                <ul>
                    <li>Display Brands with A-Z Filter in Extra Button (Left/Right Silde)</li>
                </ul>
            </li>
            <br>
            <li>
<strong>Setting Page with Advanced Options</strong>
                <ul>
                    <li>Customize “Brand” : Enter your title for displayed instead Extra Button title.</li>
                    <li>Display item from Brands or Categories.</li>
                    <li>Enable/Disable Display Brands Extra Button</li>
                    <li>Choose Extra Button Position (Left/Right)</li>
                    <li>Enable/Disable Display Brand`s Description in Single Product Page</li>
                    <li>Enable/Disable Display Brand`s Description in Product List</li>
                    <li>Enable/Disable Display Brand`s Image in Single Product Page</li>
                    <li>Enable/Disable Display Brand`s Text in Single Product Page</li>
                    <li>Enable/Disable Display Brand`s Image in Product List</li>
                    <li>Enable/Disable Display Brand in Product List</li>
                    <li>Set custom structures for your brand URLs in Admin-&gt;Settings-&gt;Permalinks</li>
                    <li>Display brand in even item in category product list</li>
                    <li>Display brand in even item in category product list</li>
                    <li>Add Default Image for Brand</li>
                    <li>You can set External link for Brands.If you set the url, When visitor click on a brand name,
                        this url will be diplayed instead of brand page
                    </li>
                </ul>
            </li>
            <br>
            <li>
<strong>Other Options</strong>
                <ul>
                    <li>Enable/Disable Display Brand Title (Use in Carousel and Display All Brands in Image Mode)</li>
                    <li>Enable/Disable Display Number of Products Relate to Brand</li>
                    <li>You Can Add Featured Attribute to Brands (Products -&gt; Brands -&gt; Add new and Check Featured)</li>
                    <li>Enable/Disable Display Only Featured Brands</li>
                    <li>Enable/Disable Display Image (Use in Display All Brands in Image Mode)</li>
                    <li>You Can Enter Number of Item in Carousel</li>
                    <li>You Can Enter Number of Item per View in Carousel</li>
                    <li>Choose Position in Carousel (Left/Center/Right)</li>
                </ul>
            </li>
        </ul>
			'),	
		);
		
		return apply_filters( 'pw_woocommerce_brands_tab_options', $options );
	}
	
	/**
	 * Create new Woocommerce admin field: slider
	 * 
	 * @access public
	 * @param array $value
	 * @return void 
	 * @since 1.0.0
	 */

	public function admin_fields_addons( $value ) {
		if(!defined('plugin_dir_url_pw_woo_brand_coupon'))
		{
			?>
			<tr>
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo $value['name']; ?></label>
				</th>
				<td class="forminp">
					<div class="form-field">
                        <div >
							<a href="#" id="<?php echo esc_attr( $value['id'] ); ?>"><?php _e('Show/Hide Add-ons') ?> </a>

                        </div>
						<?php echo $value['desc'];?>
                        <div class="clear"></div>
						<div id="pw_brand_addons" style="display:none">
							<?php
								include("addons.php");
							?>
						</div>
                    </div>	
					
                </td>
			</tr>
			<script type="text/javascript">
				jQuery(document).ready(function(e) {
					jQuery('#<?php echo esc_attr( $value['id'] ); ?>').click(function(e){
						e.preventDefault();
						jQuery("#pw_brand_addons").toggle();
					});
				});
			</script>		
			<?php
		}
	}	
	
	/**
	* Save the admin field: slider
	*
	* @access public
	* @param mixed $value
	* @return void
	* @since 1.0.0
	*/
	public function admin_update_option($value) {
		update_option( $value['id'], woocommerce_clean($_POST[$value['id']]) );		
	}

	
}
new pw_woocommerc_brans_WC_Admin_Tabs();
?>