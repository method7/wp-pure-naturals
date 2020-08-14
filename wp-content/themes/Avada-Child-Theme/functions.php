<?php

function theme_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'avada-stylesheet' ) );
}
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );

function avada_lang_setup() {
	$lang = get_stylesheet_directory() . '/languages';
	load_child_theme_textdomain( 'Avada', $lang );
}
add_action( 'after_setup_theme', 'avada_lang_setup' );

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

if ( !function_exists( 'chld_thm_cfg_parent_css' ) ):
    function chld_thm_cfg_parent_css() {
        wp_enqueue_style( 'chld_thm_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array( 'avada-stylesheet' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'chld_thm_cfg_parent_css', 10 );

// END ENQUEUE PARENT ACTION

add_filter( 'wc_order_statuses', 'wc_renaming_order_status' );
function wc_renaming_order_status( $order_statuses ) {
    foreach ( $order_statuses as $key => $status ) {
        if ( 'wc-completed' === $key ) 
            $order_statuses['wc-completed'] = _x( 'Shipped', 'Order status', 'woocommerce' );
    }
    return $order_statuses;
}


/**
 * Allow HTML in term (category, tag) descriptions
 */
foreach ( array( 'pre_term_description' ) as $filter ) {
	remove_filter( $filter, 'wp_filter_kses' );
	if ( ! current_user_can( 'unfiltered_html' ) ) {
		add_filter( $filter, 'wp_filter_post_kses' );
	}
}
 
foreach ( array( 'term_description' ) as $filter ) {
	remove_filter( $filter, 'wp_kses_data' );
}

/**
 * @snippet       Show Regular/Sale Price @ WooCommerce Cart Table
 * @how-to        Watch tutorial @ https://businessbloomer.com/?p=19055
 * @sourcecode    https://businessbloomer.com/?p=20478
 * @author        Rodolfo Melogli
 * @testedwith    WooCommerce 3.4.3
 */
 
add_filter( 'woocommerce_cart_item_price', 'bbloomer_change_cart_table_price_display', 30, 3 );
 
function bbloomer_change_cart_table_price_display( $price, $values, $cart_item_key ) {
$slashed_price = $values['data']->get_price_html();
$is_on_sale = $values['data']->is_on_sale();
if ( $is_on_sale ) {
 $price = $slashed_price;
}
return $price;
}



add_filter( 'loop_shop_per_page', 'new_loop_shop_per_page', 20 );
function new_loop_shop_per_page( $cols ) {
  // $cols contains the current number of products per page based on the value stored on Options -> Reading
  // Return the number of products you wanna show per page.
  $cols = 1000;
  return $cols;
}


add_action( 'woocommerce_check_cart_items', 'mandatory_coupon_for_specific_items' );
function mandatory_coupon_for_specific_items() {
    $targeted_ids   = array(23128); // The targeted product ids (in this array)
    $coupon_code    = 'rose23b'; // The required coupon code

    $coupon_applied = in_array( strtolower($coupon_code), WC()->cart->get_applied_coupons() );

    // Loop through cart items
    foreach(WC()->cart->get_cart() as $cart_item ) {
        // Check cart item for defined product Ids and applied coupon
        if( in_array( $cart_item['product_id'], $targeted_ids ) && ! $coupon_applied ) {
            wc_clear_notices(); // Clear all other notices

            // Avoid checkout displaying an error notice
            wc_add_notice( sprintf( 'The product"%s" requires a coupon for checkout.', $cart_item['data']->get_name() ), 'error' );
            break; // stop the loop
        }
    }
}


/**
 * @snippet       Hide one shipping option in one zone when Free Shipping is available
 * @how-to        Get CustomizeWoo.com FREE
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 3.6.3
 * @donate $9     https://businessbloomer.com/bloomer-armada/
 */
  
add_filter( 'woocommerce_package_rates', 'bbloomer_unset_shipping_when_free_is_available_in_zone', 10, 2 );
   
function bbloomer_unset_shipping_when_free_is_available_in_zone( $rates, $package ) {
      
// Only unset rates if free_shipping is available
if ( isset( $rates['free_shipping:1'] ) ) {
     unset( $rates['flat_rate:3'] );
}     
     
return $rates;
  
}
function admsort_function() {
	if(is_front_page()){
     return ' | <a href="https://absolute.digital/seo/">Essex SEO Agency</a> Absolute Digital Media';
	}
}
add_shortcode('admsc', 'admsort_function');

//PASSWORD FORMS

add_filter( 'the_password_form', 'custom_password_form' );
function custom_password_form() {
global $post;
$o = '<form action="' . esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ) . '" method="post" class="post-password-form pp">
        <p>' . __( "This post is password protected. To view it please enter your password below:" ) . '</p>
        <label for="password">' . __( "Code:" ) . ' 
            <input name="post_password" id="password" type="password" size="20" required/>
        </label>
        <input type="submit" name="Submit" value="' . esc_attr__( "Submit" ) . '" class="fusion-button button-default fusion-button-default-size"/>
    </form>


    <form action="/password-request-thank-you/" method="post" class="wpcf7-form request-pass-form" novalidate="novalidate">
        <p style="margin-bottom: 0;"><b>To purchase this high strength product, you will need an access code. This is a manufacturer requirement.</b></p>
        <p style="margin-bottom: 0; margin-top: 0"><b>Please fill in a short form below. </b></p>
        <p style="margin-top: 0;"><b>Pricing will show once you entered a correct access code. </b></p>
        <div style="display: none;">
            <input type="hidden" name="_wpcf7" value="23315">
            <input type="hidden" name="_wpcf7_version" value="5.1.6">
            <input type="hidden" name="_wpcf7_locale" value="en_US">
            <input type="hidden" name="_wpcf7_unit_tag" value="wpcf7-f23315-p23316-o1">
            <input type="hidden" name="_wpcf7_container_post" value="23316">
        </div>
        <p>
            <label> Your Name (required)
                <br>
                <span class="wpcf7-form-control-wrap your-name"><input type="text" name="your-name" value="" size="40" class="wpcf7-form-control wpcf7-text wpcf7-validates-as-required" aria-required="true" aria-invalid="false"></span> </label>
        </p>
        <p>
            <label> Your Email (required)
                <br>
                <span class="wpcf7-form-control-wrap your-email"><input type="email" name="your-email" value="" size="40" class="wpcf7-form-control wpcf7-text wpcf7-email wpcf7-validates-as-required wpcf7-validates-as-email" aria-required="true" aria-invalid="false"></span> </label>
        </p>
        <p>
            <input type="submit" value="Send" class="wpcf7-form-control wpcf7-submit">
            <div class="fusion-slider-loading" style="display: none;"></div>
        </p>
        <div class="fusion-alert alert custom alert-custom fusion-alert-center fusion-alert-capitalize alert-dismissable wpcf7-response-output wpcf7-display-none" style="border-width:1px;">
            <button style="color:;border-color:;" type="button" class="close toggle-alert" data-dismiss="alert" aria-hidden="true">×</button>
            <div class="fusion-alert-content-wrapper"><span class="fusion-alert-content"></span></div>
        </div>
    </form>

';
return $o;
}

// Add custom metabox to product category pages
add_action('product_cat_add_form_fields', 'adm_taxonomy_add_new_meta_field', 10, 1);
add_action('product_cat_edit_form_fields', 'adm_taxonomy_edit_meta_field', 10, 1);
//Product Cat Create page
function adm_taxonomy_add_new_meta_field() {
    ?>
    <div class="form-field">
        <label for="adm_additional_content"><?php _e('Meta Description', 'Avada'); ?></label>
        <textarea name="adm_additional_content" id="adm_additional_content"></textarea>
        <p class="description"><?php _e('Enter a meta description, <= 160 character', 'Avada'); ?></p>
    </div>
    <?php
}

//Product Cat Edit page
function adm_taxonomy_edit_meta_field($term) {

    //getting term ID
    $term_id = $term->term_id;

    // retrieve the existing value(s) for this meta field.
    $adm_additional_content = get_term_meta($term_id, 'adm_additional_content', true);
    ?>
    <table class="form-table">
        <tr class="form-field">
            <th scope="row" valign="top"><label for="adm_additional_content"><?php _e('Additional Information', 'Avada'); ?></label></th>
            <td>
                <textarea name="adm_additional_content" id="adm_additional_content"><?php echo esc_attr($adm_additional_content) ? esc_attr($adm_additional_content) : ''; ?></textarea>
                <p class="description"><?php _e('Enter a additional information', 'Avada'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}


add_action('edited_product_cat', 'adm_save_taxonomy_custom_meta', 10, 1);
add_action('create_product_cat', 'adm_save_taxonomy_custom_meta', 10, 1);

// Save extra taxonomy fields callback function.
function adm_save_taxonomy_custom_meta($term_id)
{

    $adm_additional_content = filter_input(INPUT_POST, 'adm_additional_content');

    update_term_meta($term_id, 'adm_additional_content', $adm_additional_content);
}


add_action('avada_after_main_content', 'product_category_additional_information');
function product_category_additional_information(){
	if (is_product_category()) {
		$productCatAdditionalContent = get_term_meta(get_queried_object()->term_id, 'adm_additional_content', true);
		if (!empty($productCatAdditionalContent)) {
			echo '<div class="container">' . $productCatAdditionalContent .'</div>';
		}
	}
};

/**
 * Woo Cart Dropdown for Main Nav or Top Nav.
 *
 * @param string $position The cart position.
 * @return string HTML of Dropdown
 */
function avada_nav_woo_cart( $position = 'main' ) {

    if ( ! class_exists( 'WooCommerce' ) ) {
        return '';
    }
    

    $woo_cart_page_link       = wc_get_cart_url();
    $cart_link_active_class   = '';
    $cart_link_active_text    = '';
    $is_enabled               = false;
    $main_cart_class          = '';
    $cart_link_inactive_class = '';
    $cart_link_inactive_text  = '';
    $items                    = '';
    $cart_contents_count      = WC()->cart->get_cart_contents_count();

    if ( 'main' === $position ) {
        $is_enabled               = Avada()->settings->get( 'woocommerce_cart_link_main_nav' );
        $main_cart_class          = ' fusion-main-menu-cart';
        $cart_link_active_class   = 'fusion-main-menu-icon fusion-main-menu-icon-active';
        $cart_link_inactive_class = 'fusion-main-menu-icon';
        if ( Avada()->settings->get( 'woocommerce_cart_counter' ) ) {
            if ( $cart_contents_count ) {
                $cart_link_active_text = '<span class="fusion-widget-cart-number">' . $cart_contents_count . '</span>';
            }
            $main_cart_class .= ' fusion-widget-cart-counter';
        } elseif ( $cart_contents_count ) {
            // If we're here, then ( Avada()->settings->get( 'woocommerce_cart_counter' ) ) is not true.
            $main_cart_class .= ' fusion-active-cart-icons';
        }
    } elseif ( 'secondary' === $position ) {
        $is_enabled             = Avada()->settings->get( 'woocommerce_cart_link_top_nav' );
        $main_cart_class        = ' fusion-secondary-menu-cart';
        $cart_link_active_class = 'fusion-secondary-menu-icon';
        /* translators: Number of items. */
        $cart_link_active_text    = sprintf( esc_html__( '%s Item(s)', 'Avada' ), $cart_contents_count ) . ' <span class="fusion-woo-cart-separator">-</span> ' . WC()->cart->get_cart_subtotal();
        $cart_link_inactive_class = $cart_link_active_class;
        $cart_link_inactive_text  = esc_html__( 'Cart', 'Avada' );
    }

    $highlight_class = '';
    if ( 'bar' === Avada()->settings->get( 'menu_highlight_style' ) ) {
        $highlight_class = ' fusion-bar-highlight';
    }
    $cart_link_markup = '<a class="' . $cart_link_active_class . $highlight_class . '" href="' . $woo_cart_page_link . '"><span class="menu-text" aria-label="' . esc_html__( 'View Cart', 'Avada' ) . '">' . $cart_link_active_text . '</span></a>';
    if ( $is_enabled ) {
        if ( is_cart() ) {
            $main_cart_class .= ' current-menu-item current_page_item';
        }

        $items = '<li class="fusion-custom-menu-item fusion-menu-cart' . $main_cart_class . '">';
        if ( $cart_contents_count ) {
            $checkout_link = wc_get_checkout_url();

            $items .= $cart_link_markup;
            $items .= '<div class="fusion-custom-menu-item-contents fusion-menu-cart-items">';
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                $_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                $product_link = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                $thumbnail_id = ( $cart_item['variation_id'] && has_post_thumbnail( $cart_item['variation_id'] ) ) ? $cart_item['variation_id'] : $cart_item['product_id'];

                if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                    $items .= '<div class="fusion-menu-cart-item">';
                    $items .= '<a href="' . $product_link . '">';
                    $items .= get_the_post_thumbnail( $thumbnail_id, 'recent-works-thumbnail' );

                    // Check needed for pre Woo 2.7 versions only.
                    $item_name = method_exists( $_product, 'get_name' ) ? $_product->get_name() : $cart_item['data']->post->post_title;

                    $items .= '<div class="fusion-menu-cart-item-details">';
                    $items .= '<span class="fusion-menu-cart-item-title">' . $item_name . '</span>';

                    $product_price = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
                    if ( '' !== $product_price ) {
                        $product_price = ' x ' . $product_price;
                    }
                    $items .= '<span class="fusion-menu-cart-item-quantity">' . $cart_item['quantity'] . $product_price . '</span>';
                    $items .= '</div>';
                    $items .= '</a>';
                    $items .= '</div>';
                }
            }
            $items .= '<div class="fusion-menu-cart-checkout">';
            $items .= '<div class="fusion-menu-cart-link"><a href="' . $woo_cart_page_link . '">' . esc_html__( 'View Cart', 'Avada' ) . '</a></div>';
            $items .= '<div class="fusion-menu-cart-checkout-link"><a href="' . $checkout_link . '">' . esc_html__( 'Checkout', 'Avada' ) . '</a></div>';
            $items .= '</div>';
            $items .= '</div>';
        } else {
            // Batch 1 2.4.3 start
            $items .= '<a class="' . $cart_link_inactive_class . $highlight_class . '" href="' . $woo_cart_page_link . '"><span class="menu-text" aria-label="' . esc_html__( 'View Cart', 'Avada' ) . '">' . $cart_link_inactive_text . '</span></a>';
            $items .= '<div class="fusion-custom-menu-item-contents fusion-menu-cart-items">';
            $items .= '<p class="cart-empty woocommerce-info">' . wp_kses_post( apply_filters( 'wc_empty_cart_message', __( 'Your cart is currently empty.', 'woocommerce' ) ) ) . '</p>';
            $items .= '<div class="fusion-menu-cart-checkout">';
            $items .= '<div class="fusion-menu-cart-link"><a href="' . $woo_cart_page_link . '">' . esc_html__( 'View Cart', 'Avada' ) . '</a></div>';
            $items .= '</div>';
            $items .= '</div>';
            // Batch 1 2.4.3 end
        }
        $items .= '</li>';
    }
    return $items;
}

/**
 * Simple helper to debug to the console
 *
 * @param $data object, array, string $data
 * @param $context string  Optional a description.
 *
 * @return string
 */
function debug_to_console($data, $context = 'Debug in Console') {

    // Buffering to solve problems frameworks, like header() in this and not a solid return.
    ob_start();

    $output  = 'console.info(\'' . $context . ':\');';
    $output .= 'console.log(' . json_encode($data) . ');';
    $output  = sprintf('<script>%s</script>', $output);

    echo $output;
}