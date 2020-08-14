<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class pw_brans_WC_Admin_Taxonomies {

	/**
	 * Constructor
	 */
	public function __construct() {
		
		add_action( 'init', array( $this, 'create_taxonomies' ) );
		add_action( "delete_term", array( $this, 'delete_term' ), 5 );

		/* Add form */
		add_action( 'product_brand_add_form_fields', array( $this, 'add_brands_fields' ) );
		add_action( 'product_brand_edit_form_fields', array( $this, 'edit_brands_fields' ), 10, 2 );
		add_action( 'created_term', array( $this, 'save_brands_fields' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'save_brands_fields' ), 10, 3 );

		add_filter( 'manage_edit-product_brand_columns', array( $this, 'brands_columns' ) );
		add_filter( 'manage_product_brand_custom_column', array( $this, 'brands_column' ), 10, 3 );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

	


	}

	public function edit_columns_brands($defaults) {
		$defaults['product_brand']  = __( 'Brands', 'woocommerce-brands' );	
		return $defaults;
	}

	public function custom_columns_brands($column)
	{
		global $post, $woocommerce, $the_product;

		if ( empty( $the_product ) || $the_product->id != $post->ID ) {
			$the_product = get_product( $post );
		}
	
		switch ( $column ) {
			case 'product_brand' :
				if ( ! $terms = get_the_terms( $post->ID, $column ) ) {
					echo '<span class="na">&ndash;</span>';
				} else {
					foreach ( $terms as $term ) {
						$termlist[] = '<a href="' . admin_url( 'edit.php?' . $column . '=' . $term->slug . '&post_type=product' ) . ' ">' . $term->name . '</a>';
					}
					echo implode( ', ', $termlist );
				}
				break;	
			default :
				break;				
		}
	}
	
	//Callback to set up the metabox
	public function pw_woocommerc_brands_metabox( $post ) {
		//Get taxonomy and terms
		$taxonomy = 'product_brand';
	 
		//Set up the taxonomy object and get terms
		$tax = get_taxonomy($taxonomy);
		$terms = get_terms($taxonomy,array('hide_empty' => 0));
	 
		//Name of the form
		$name = 'tax_input[' . $taxonomy . ']';
	 
		//Get current and popular terms
		$popular = get_terms( $taxonomy, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );
		$postterms = get_the_terms( $post->ID,$taxonomy );
		$current = ($postterms ? array_pop($postterms) : false);
		$current = ($current ? $current->term_id : 0);
		?>
	 
		<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
	 
			<!-- Display tabs-->
			<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
				<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>
				<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Most Used','woocommerce-brands' ); ?></a></li>
			</ul>
	 
			<!-- Display taxonomy terms -->
			<div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
				<ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
					<?php   foreach($terms as $term){
						$id = $taxonomy.'-'.$term->term_id;
						echo "<li id='$id'><label class='selectit'>";
						echo "<input type='radio' id='in-$id' name='{$name}'".checked($current,$term->term_id,false)."value='$term->term_id' />$term->name<br />";
					   echo "</label></li>";
					}?>
			   </ul>
			</div>
	 
			<!-- Display popular taxonomy terms -->
			<div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
				<ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
					<?php   foreach($popular as $term){
						$id = 'popular-'.$taxonomy.'-'.$term->term_id;
						echo "<li id='$id'><label class='selectit'>";
						echo "<input type='radio' id='in-$id'".checked($current,$term->term_id,false)."value='$term->term_id' />$term->name<br />";
						echo "</label></li>";
					}?>
			   </ul>
		   </div>
	 
		</div>
		<?php
	}

// create two taxonomies, genres and writers for the post type "product"
	public function create_taxonomies() {
		$labels = array(
			'name' => __( 'Brands', 'woocommerce-brands' ),
			'singular_name' => __( 'Brand', 'woocommerce-brands' ),
			'search_items' =>  __( 'Search Brands' ,'woocommerce-brands'),
			'all_items' => __( 'All Brands' ,'woocommerce-brands'),
			'parent_item' => __( 'Parent Brand' ,'woocommerce-brands'),
			'parent_item_colon' => __( 'Parent Brands:' ,'woocommerce-brands'),
			'edit_item' => __( 'Edit Brands' ,'woocommerce-brands'),
			'update_item' => __( 'Update Brands' ,'woocommerce-brands'),
			'add_new_item' => __( 'Add New Brand' ,'woocommerce-brands'),
			'new_item_name' => __( 'New Brand Name' ,'woocommerce-brands'),
			'menu_name' => __( 'Brands' ,'woocommerce-brands'),
		);    

	    register_taxonomy("product_brand",
	     array("product"),
	     array(
		     'hierarchical' => true,
		     'labels' => $labels,
		   	 'show_ui' => true,
    		 'query_var' => true,
		     'rewrite' => array( 'slug' => __( 'brand', 'woocommerce-brands' ), 'with_front' => true ),
		     'show_admin_column' => true
	     ));
	}  

	public function delete_term( $term_id ) {

		$term_id = (int) $term_id;

		if ( ! $term_id )
			return;

		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->woocommerce_termmeta} WHERE `woocommerce_term_id` = " . $term_id );
	}

	public function admin_scripts() {
			wp_enqueue_media();
	}
	
	public function add_brands_fields() {
		if(get_option('pw_woocommerce_brands_default_image'))
			$image=wp_get_attachment_thumb_url(get_option('pw_woocommerce_brands_default_image'));
		else
			$image = WP_PLUGIN_URL.'/woo-brand/img/default.png';
		?>			
		<div class="form-field">
			<label><?php _e( 'Thumbnail', 'woocommerce-brands' ); ?></label>
			<div id="brands_thumbnail" style="float:left;margin-right:10px;"><img src="<?php echo $image; ?>" width="60px" height="60px" /></div>
			<div style="line-height:60px;">
				<input type="hidden" id="brands_thumbnail_id" name="brands_thumbnail_id" />
				<button type="button" class="upload_image_button button"><?php _e( 'Upload/Add image', 'woocommerce-brands' ); ?></button>
				<button type="button" class="remove_image_button button"><?php _e( 'Remove image', 'woocommerce-brands' ); ?></button>
			</div>
			<script type="text/javascript">
				 // Only show the "remove image" button when needed
				 if ( ! jQuery('#brands_thumbnail_id').val() )
					 jQuery('.remove_image_button').hide();

				// Uploading files
				var file_frame;

				jQuery(document).on( 'click', '.upload_image_button', function( event ){
					

					event.preventDefault();

					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						file_frame.open();
						return;
					}

					// Create the media frame.
					file_frame = wp.media.frames.downloadable_file = wp.media({
						title: '<?php _e( 'Choose an image', 'woocommerce-brands' ); ?>',
						button: {
							text: '<?php _e( 'Use image', 'woocommerce-brands' ); ?>',
						},
						multiple: false
					});

					// When an image is selected, run a callback.
					file_frame.on( 'select', function() {
						attachment = file_frame.state().get('selection').first().toJSON();

						jQuery('#brands_thumbnail_id').val( attachment.id );
						jQuery('#brands_thumbnail img').attr('src', attachment.url );
						jQuery('.remove_image_button').show();
					});

					// Finally, open the modal.
					file_frame.open();
				});

				jQuery(document).on( 'click', '.remove_image_button', function( event ){
					jQuery('#brands_thumbnail img').attr('src', '<?php echo wc_placeholder_img_src(); ?>');
					jQuery('#brands_thumbnail_id').val('');
					jQuery('.remove_image_button').hide();
					return false;
				});

			</script>
			<div class="clear"></div>
		</div>
		<?php
	}

	public function edit_brands_fields( $term, $taxonomy ) {
		$url	= get_woocommerce_term_meta( $term->term_id, 'url', true );
		$image 			= '';
		$thumbnail_id 	= absint( get_woocommerce_term_meta( $term->term_id, 'thumbnail_id', true ) );
		if ( $thumbnail_id )
			$image = wp_get_attachment_thumb_url( $thumbnail_id );
		else
		{
			$image = wc_placeholder_img_src();	
		}
		?>
		<tr class="form-field">
			<th scope="row" valign="top"><label><?php _e( 'Thumbnail', 'woocommerce-brands' ); ?></label></th>
			<td>
				<div id="brands_thumbnail" style="float:left;margin-right:10px;"><img src="<?php echo $image; ?>" width="60px" height="60px" /></div>
				<div style="line-height:60px;">
					<input type="hidden" id="brands_thumbnail_id" name="brands_thumbnail_id" value="<?php echo $thumbnail_id; ?>" />
					<button type="submit" class="upload_image_button button"><?php _e( 'Upload/Add image', 'woocommerce-brands' ); ?></button>
					<button type="submit" class="remove_image_button button"><?php _e( 'Remove image', 'woocommerce-brands' ); ?></button>
				</div>
				<script type="text/javascript">

					// Uploading files
					var file_frame;

					jQuery(document).on( 'click', '.upload_image_button', function( event ){

						event.preventDefault();

						// If the media frame already exists, reopen it.
						if ( file_frame ) {
							file_frame.open();
							return;
						}

						// Create the media frame.
						file_frame = wp.media.frames.downloadable_file = wp.media({
							title: '<?php _e( 'Choose an image', 'woocommerce-brands' ); ?>',
							button: {
								text: '<?php _e( 'Use image', 'woocommerce-brands' ); ?>',
							},
							multiple: false
						});

						// When an image is selected, run a callback.
						file_frame.on( 'select', function() {
							attachment = file_frame.state().get('selection').first().toJSON();

							jQuery('#brands_thumbnail_id').val( attachment.id );
							jQuery('#brands_thumbnail img').attr('src', attachment.url );
							jQuery('.remove_image_button').show();
						});

						// Finally, open the modal.
						file_frame.open();
					});

					jQuery(document).on( 'click', '.remove_image_button', function( event ){
						jQuery('#brands_thumbnail img').attr('src', '<?php echo wc_placeholder_img_src(); ?>');
						jQuery('#brands_thumbnail_id').val('');
						jQuery('.remove_image_button').hide();
						return false;
					});

				</script>
				<div class="clear"></div>
			</td>
		</tr>
		<?php
	}


	public function save_brands_fields( $term_id, $tt_id, $taxonomy ) {

		if ( isset( $_POST['brands_thumbnail_id'] ) )
			update_woocommerce_term_meta( $term_id, 'thumbnail_id', absint( $_POST['brands_thumbnail_id'] ) );
			
		delete_transient( 'wc_term_counts' );
	}

	public function brands_columns( $columns ) {
			
		$new_columns          = array();
		$new_columns['cb']    = $columns['cb'];
		$new_columns['thumb'] = __( 'Image', 'woocommerce-brands' );		
		$new_columns['name'] =__('Name','woocommerce-brands');
		unset( $columns['cb'] );

		return array_merge( $new_columns, $columns );
		
	}

	public function brands_column( $columns, $column, $id ) {

		if ( $column == 'thumb' ) {

			$image 			= '';
			$thumbnail_id 	= get_woocommerce_term_meta( $id, 'thumbnail_id', true );

			if ($thumbnail_id)
				$image = wp_get_attachment_thumb_url( $thumbnail_id );
			else
				$image = wc_placeholder_img_src();
			$image = str_replace( ' ', '%20', $image );

			$columns .= '<img src="' . esc_url( $image ) . '" alt="Thumbnail" class="wp-post-image" height="48" width="48" />';

		}
		
		return $columns;
	}
}
new pw_brans_WC_Admin_Taxonomies();
?>