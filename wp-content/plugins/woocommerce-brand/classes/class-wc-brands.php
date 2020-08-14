<?php

/**
 * WC_Brands class.
 */
class pw_woocommerc_brans_Wc_Brands
{
    const Error_BRANDS = 115;
    var $template_url;
    var $plugin_path;

    public function __construct()
    {
        $this->template_url = apply_filters('woocommerce_template_url', 'woocommerce/');
        add_filter('template_include', array($this, 'template_loader'));
        add_filter('parse_query', array($this, 'convert_id_to_term_in_query'));
        add_filter('post_type_link', array($this, 'post_type_link'), 11, 2);
        add_action('woocommerce_archive_description', array($this, 'brand_description'));
        add_action('woocommerce_after_shop_loop_item', array($this, 'brand_show_title'));
        remove_action('woocommerce_product_meta_end', array($this, 'pw_woocommerc_show_brand'));
        add_action('init', array($this, 'pw_woocommerce_brand_single_position'));


    }

    function pw_woocommerce_brand_single_position()
    {
        $position = get_option('pw_woocommerce_brands_position_single_brand', "default");
        if ($position == 'default') {
            add_action('woocommerce_product_meta_end', array($this, 'pw_woocommerc_show_brand'), 10);
        } elseif ($position == '1') {
            add_action('woocommerce_single_product_summary', array($this, 'pw_woocommerc_show_brand'), 3);
        } elseif ($position == '2') {
            add_action('woocommerce_single_product_summary', array($this, 'pw_woocommerc_show_brand'), 7);
        } elseif ($position == '3') {
            add_action('woocommerce_single_product_summary', array($this, 'pw_woocommerc_show_brand'), 15);
        } elseif ($position == '4') {
            add_action('woocommerce_single_product_summary', array($this, 'pw_woocommerc_show_brand'), 25);
        } elseif ($position == '5') {
            add_action('woocommerce_single_product_summary', array($this, 'pw_woocommerc_show_brand'), 35);
        } elseif ($position == '6') {
            add_action('woocommerce_single_product_summary', array($this, 'pw_woocommerc_show_brand'), 55);
        }
    }

    public function post_type_link($permalink, $post)
    {
        // Abort if post is not a product
        if ($post->post_type !== 'product')
            return $permalink;

        // Abort early if the placeholder rewrite tag isn't in the generated URL
        if (false === strpos($permalink, '%'))
            return $permalink;

        // Get the custom taxonomy terms in use by this post
        $terms = get_the_terms($post->ID, 'product_brand');

        if (empty($terms)) {
            // If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
            $product_brand = _x('uncategorized', 'slug', 'wc_brands');
        } else {
            // Replace the placeholder rewrite tag with the first term's slug
            $first_term = array_shift($terms);
            $product_brand = $first_term->slug;
        }

        $find = array(
            '%product_brand%'
        );

        $replace = array(
            $product_brand
        );

        $replace = array_map('sanitize_title', $replace);

        $permalink = str_replace($find, $replace, $permalink);

        return $permalink;
    } // End post_type_link()


    public function brand_show_title()
    {
        global $post;
        $id = $post->ID;
        $brand = "";
        if (get_option('pw_woocommerce_image_brand_shop_page') == "yes") {
            $terms = get_the_terms($post->ID, 'product_brand');
            if (is_array($terms)) {
                $brand .= '<span class="pw_brand_product_list">';
            }
            if (get_option('pw_woocommerce_image_brand_shop_page') == "yes") {
                $product_cats = wp_get_post_terms($post->ID, 'product_brand', array("fields" => "ids"));

                $ratio = get_option('pw_woocommerce_image_brand_shop_page_image_size', "150:150");

                list($width, $height) = explode(':', $ratio);
                foreach ($product_cats as $cat) {
                    $thumbnail = get_woocommerce_term_meta($cat, 'thumbnail_id', true);
                    if ($thumbnail) {
                        $image = wp_get_attachment_thumb_url($thumbnail);
                        $brand .= '<img src="' . $image . '" style="width:' . $width . 'px;height:' . $height . 'px" />';
                    }
                }
            }
            $brand .= '</span>';
        }

        $position = get_option('pw_woocommerce_brands_position_product_list', "default");
        if ($position == 'default') {
            echo $brand;
        } elseif ($position == 'before_price') {
            echo "<script type='text/javascript'>
				jQuery(document).ready(function(){
					if(jQuery('li.post-" . ($id) . " .pw_brand_product_list').length < 1){
						jQuery('" . ($brand) . "').insertBefore('li.post-" . ($id) . " span.price');
					}
				});
			</script>";
        } elseif ($position == 'before_title') {
            echo "<script type='text/javascript'>
					jQuery(document).ready(function(){
						if(jQuery('li.post-" . ($id) . " .pw_brand_product_list').length < 1){
							jQuery('" . ($brand) . "').insertBefore('li.post-" . ($id) . " h2');
						}
					});
				</script>";
        } elseif ($position == 'after_title') {
            echo "<script type='text/javascript'>
					jQuery(document).ready(function(){
						if(jQuery('li.post-" . ($id) . " .pw_brand_product_list').length < 1){
							jQuery('" . ($brand) . "').insertAfter('li.post-" . ($id) . " h2');
						}
					});
				</script>";
        } elseif ($position == 'before_addcart') {
            echo "<script type='text/javascript'>
				jQuery(document).ready(function(){
					if(jQuery('li.post-" . ($id) . " .pw_brand_product_list').length < 1){
						jQuery('" . ($brand) . "').insertBefore('li.post-" . ($id) . " a.add_to_cart_button');
					}
				});
			</script>";
        } elseif ($position == 'after_addcart') {
            echo "<script type='text/javascript'>
				jQuery(document).ready(function(){
					if(jQuery('li.post-" . ($id) . " .pw_brand_product_list').length < 1){
						jQuery('" . ($brand) . "').insertAfter('li.post-" . ($id) . " a.add_to_cart_button');
					}
				});
			</script>";
        }
    }

    //jQuery('adsds').insertBefore(target);
    public function brand_description()
    {

        if (!is_tax('product_brand'))
            return;

        if (!get_query_var('term'))
            return;

        $thumbnail = '';
        $term = get_term_by('slug', get_query_var('term'), 'product_brand');
        $url = "";
        $url = esc_html(get_woocommerce_term_meta($term->term_id, 'url', true));
        if (version_compare(WC()->version, '3.0.0', '>=')) {
            $wc_get_3 = 'wc_get_template';
        } else {
            $wc_get_3 = 'woocommerce_get_template';
        }

        $wc_get_3('brand-description.php', array(
            'thumbnail' => $thumbnail,
            'name' => $term->name,
            'url' => $url
        ), 'woocommerce-brands', $this->plugin_path() . '/templates/');

    }

    public function get_brand_thumbnail_url($brand_id, $size = 'full')
    {
        $thumbnail_id = get_woocommerce_term_meta($brand_id, 'thumbnail_id', true);

        if ($thumbnail_id)
            return current(wp_get_attachment_image_src($thumbnail_id, $size));
    }

    public function pw_woocommerc_show_brand()
    {
        global $post;

        if (is_singular('product')) {

            if (get_option('pw_woocommerce_brands_show_categories') == "yes")
                $get_terms = "product_cat";
            else
                $get_terms = "product_brand";

            if (get_option('pw_woocommerce_brands_show_categories') == "no" && (get_option('pw_woocommerce_brands_text_single') == "yes" || get_option('pw_woocommerce_brands_image_single') == "yes")) {
                $taxonomy = get_taxonomy($get_terms);
                $labels = $taxonomy->labels;
                $tax = (get_option('pw_woocommerce_brands_text') == "" ? "Brand" : get_option('pw_woocommerce_brands_text'));
                if (get_option('pw_woocommerce_brands_text_single') == "yes")
                    echo $this->pw_woocommerc_get_brands($post->ID, ', ', ' <div class="wb-posted_in">' . $tax . ': ', '</div>');

                $brands = wp_get_post_terms($post->ID, $get_terms, array("fields" => "ids"));
                if (get_option('pw_woocommerce_brands_image_single') == "yes") {
                    $terms = get_the_terms($post->ID, $get_terms);
                    if ($terms) {
                        if (get_option('pw_woocommerce_brands_text_single') != "yes")
                            echo $tax . ':';
                        echo '<div class="wb-single-img-cnt" >';
                        foreach ($terms as $term) {
                            $url = "";
                            $url = esc_html(get_woocommerce_term_meta($term->term_id, 'url', true));

                            /*for crup
                            $thumbnail=get_woocommerce_term_meta($term->term_id,'thumbnail_id', true);
                            if($thumbnail){
                                $thumbnail = current( wp_get_attachment_image_src( $thumbnail, 'full' ));
                                    if($url!="")
                                    {
                                        echo '<a href="'.$url.'"><img src="'.$thumbnail.'"  alt="'. $term->name.'" /></a>';
                                    }else{
                                        echo '<a href="'.home_url() .'/?'.$get_terms.'=' . esc_html( $term->slug).'"><img src="'.$thumbnail.'"  alt="'. $labels->name.'" /></a>';
                                    }
                            }
                            */
                            $thumbnail = get_woocommerce_term_meta($term->term_id, 'thumbnail_id', true);
                            if ($thumbnail) {
                                $ratio = get_option('pw_woocommerce_brands_image_single_image_size', "150:150");
                                list($width, $height) = explode(':', $ratio);

                                //$image = wp_get_attachment_thumb_url( $thumbnail );
                                $image = current(wp_get_attachment_image_src($thumbnail, 'full'));
                                if ($url != "") {
                                    echo '<a href="' . $url . '"><img src="' . $image . '"  alt="' . $term->name . '" style="width:' . $width . 'px;height:' . $height . 'px" /></a>';
                                } else {
                                    echo '<a href="' . home_url() . '/?' . $get_terms . '=' . esc_html($term->slug) . '"><img src="' . $image . '"  alt="' . $labels->name . '" style="width:' . $width . 'px;height:' . $height . 'px" /></a>';
                                }
                            }
                        }
                        echo '</div>';
                    }
                }
            }
        }
    }

    /**
     * get_brands function.
     *
     * @access public
     * @param int $post_id (default: 0)
     * @param string $sep (default: ')
     * @param mixed '
     * @param string $before (default: '')
     * @param string $after (default: '')
     * @return void
     */
    public function pw_woocommerc_get_brands($post_id = 0, $sep = ', ', $before = '', $after = '')
    {
        global $post;

        if ($post_id)
            $post_id = $post->ID;

        return get_the_term_list($post_id, 'product_brand', $before, $sep, $after);
    }

    /**
     * Get the plugin path
     */
    public function plugin_path()
    {
        if ($this->plugin_path) return $this->plugin_path;

        return $this->plugin_path = untrailingslashit(plugin_dir_path(dirname(__FILE__)));
    }

    /**
     * template_loader
     *
     * Handles template usage so that we can use our own templates instead of the themes.
     *
     * Templates are in the 'templates' folder. woocommerce looks for theme
     * overides in /theme/woocommerce/ by default
     *
     * For beginners, it also looks for a woocommerce.php template first. If the user adds
     * this to the theme (containing a woocommerce() inside) this will be used for all
     * woocommerce templates.
     */

    public function woocommerce_catalog()
    {
        global $post, $product;
        //	print_r($product);
        $tax = (get_option('pw_woocommerce_brands_text') == "" ? "Brand" : get_option('pw_woocommerce_brands_text'));
        echo $this->pw_woocommerc_get_brands($post->ID, ', ', ' <div class="wb-posted_in">' . $tax . ': ', '</div>');
    }

    public function template_loader($template)
    {

        $find = array('woocommerce.php');
        $file = '';
        //if(get_option('display_brand_in_product_listing')=="yes"){
        //	if ( is_tax( 'product_cat' ) )
        //	{
        //	add_action('woocommerce_after_shop_loop_item_title',array( $this, 'woocommerce_catalog'));
        //	}
        //}
        if (is_tax('product_brand')) {
            $term = get_queried_object();
            $file = 'taxonomy-' . $term->taxonomy . '.php';
            $find[] = 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
            $find[] = $this->template_url . 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
            $find[] = $file;
            $find[] = $this->template_url . $file;
        }

        if ($file) {
            $template = locate_template($find);
            if (!$template) $template = $this->plugin_path() . '/templates/' . $file;
        }
        return $template;
    }
    public function convert_id_to_term_in_query($query)
    {
        global $pagenow;
        $post_type = 'product'; // change HERE
        $taxonomy = 'product_brand'; // change HERE
        $q_vars = &$query->query_vars;
        if ($pagenow == 'edit.php' && isset($q_vars['post_type']) && $q_vars['post_type'] == $post_type && isset($q_vars[$taxonomy]) && is_numeric($q_vars[$taxonomy]) && $q_vars[$taxonomy] != 0) {
            $term = get_term_by('id', $q_vars[$taxonomy], $taxonomy);
            $q_vars[$taxonomy] = $term->slug;
        }
    }


}

new pw_woocommerc_brans_Wc_Brands();
?>