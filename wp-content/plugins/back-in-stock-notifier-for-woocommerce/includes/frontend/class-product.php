<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CWG_Instock_Notifier_Product')) {

    class CWG_Instock_Notifier_Product {

        public function __construct() {
            add_action('woocommerce_simple_add_to_cart', array($this, 'display_in_simple_product'), 31);
            add_action('woocommerce_bundle_add_to_cart', array($this, 'display_in_simple_product'), 31);
            add_action('woocommerce_woosb_add_to_cart', array($this, 'display_in_simple_product'), 31);
            add_action('woocommerce_after_variations_form', array($this, 'display_in_no_variation_product'));
            add_action('woocommerce_grouped_add_to_cart', array($this, 'display_in_simple_product'), 32);
            add_filter('woocommerce_available_variation', array($this, 'display_in_variation'), 10, 3);
            //some theme variation disabled by default if it is out of stock so for that workaround solution
            add_filter('woocommerce_variation_is_active', array($this, 'enable_disabled_variation_dropdown'), 100, 2);
            //hide out of stock products from catalog is checked bypass to display variation dropdown instead of hide
            add_filter('option_woocommerce_hide_out_of_stock_items', array($this, 'display_out_of_stock_products_in_variable'), 999);
        }

        public function display_in_simple_product() {
            global $product;
            echo $this->display_subscribe_box($product);
        }

        public function display_in_no_variation_product() {
            global $product;
            $product_type = $product->get_type();
            // Get Available variations?
            if ($product_type == 'variable') {
                $get_variations = count($product->get_children()) <= apply_filters('woocommerce_ajax_variation_threshold', 30, $product);
                $get_variations = $get_variations ? $product->get_available_variations() : false;
                if (!$get_variations) {
                    echo $this->display_subscribe_box($product);
                }
            }
        }

        public function display_subscribe_box($product, $variation = array()) {
            $get_option = get_option('cwginstocksettings');
            $visibility_backorder = isset($get_option['show_on_backorders']) && $get_option['show_on_backorders'] == '1' ? true : false;
            if (!$variation && !$product->is_in_stock() || ((!$variation && (($product->managing_stock() && $product->backorders_allowed() && $product->is_on_backorder(1)) || $product->is_on_backorder(1)) && $visibility_backorder))) {
                return $this->html_subscribe_form($product);
            } elseif ($variation && !$variation->is_in_stock() || (($variation && (($variation->managing_stock() && $variation->backorders_allowed() && $variation->is_on_backorder(1)) || $variation->is_on_backorder(1)) && $visibility_backorder))) {
                return $this->html_subscribe_form($product, $variation);
            }
        }

        public function html_subscribe_form($product, $variation = array()) {
            $get_option = get_option('cwginstocksettings');
            $check_guest_visibility = isset($get_option['hide_form_guests']) && $get_option['hide_form_guests'] != '' && !is_user_logged_in() ? false : true;
            $check_member_visibility = isset($get_option['hide_form_members']) && $get_option['hide_form_members'] != '' && is_user_logged_in() ? false : true;
            $product_id = $product->get_id();
            $variation_class = '';
            if ($variation) {
                $variation_id = $variation->get_id();
                $variation_class = "cwginstock-subscribe-form-$variation_id";
            } else {
                $variation_id = 0;
            }
            if ($check_guest_visibility && $check_member_visibility && ($this->is_viewable($product_id, $variation_id) && $this->is_viewable_for_category($product_id)) && $this->visibility_on_regular_or_sale($product, $variation) && $this->is_viewable_for_product_tag($product_id)) {
                //wp_enqueue_script('cwginstock_jquery_validation');
                do_action('cwg_instock_before_subscribe_form');

                $security = wp_create_nonce('codewoogeek-product_id-' . $product_id);
                ob_start();
                $placeholder = isset($get_option['form_placeholder']) && $get_option['form_placeholder'] != '' ? $get_option['form_placeholder'] : __('Your Email Address', 'cwginstocknotifier');
                $button_label = isset($get_option['button_label']) && $get_option['button_label'] != '' ? $get_option['button_label'] : __('Subscribe Now', 'cwginstocknotifier');
                $instock_api = new CWG_Instock_API();

                $email = is_user_logged_in() ? $instock_api->get_user_email(get_current_user_id()) : '';
                ?>
                <section class="cwginstock-subscribe-form <?php echo $variation_class; ?>">
                    <div class="panel panel-primary cwginstock-panel-primary">
                        <div class="panel-heading cwginstock-panel-heading">
                            <h4 style="text-align: center;">
                                <?php
                                $form_title = esc_html__('Email when stock available', 'cwginstocksettings');
                                echo isset($get_option['form_title']) && $get_option['form_title'] != '' ? $instock_api->sanitize_text_field($get_option['form_title']) : $form_title;
                                ?>
                            </h4>
                        </div>
                        <div class="panel-body cwginstock-panel-body">
                            <?php
                            if (!isset($get_option['enable_troubleshoot']) || $get_option['enable_troubleshoot'] != '1') {
                                ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="col-md-12">
                                        <?php } ?>
                                        <div class="form-group center-block">
                                            <input type="email" style="width:100%; text-align:center;" class="cwgstock_email" name="cwgstock_email" placeholder="<?php echo $instock_api->sanitize_text_field($placeholder); ?>" value="<?php echo $email; ?>" />
                                        </div>
                                        <?php do_action('cwg_instock_after_email_field', $product_id, $variation_id); ?>
                                        <input type="hidden" class="cwg-product-id" name="cwg-product-id" value="<?php echo $product_id; ?>"/>
                                        <input type="hidden" class="cwg-variation-id" name="cwg-variation-id" value="<?php echo $variation_id; ?>"/>
                                        <input type="hidden" class="cwg-security" name="cwg-security" value="<?php echo $security; ?>"/>
                                        <div class="form-group center-block" style="text-align:center;">
                                            <input type="submit" name="cwgstock_submit" class="cwgstock_button" <?php echo apply_filters('cwgstock_submit_attr', '', $product_id, $variation_id); ?> value="<?php echo $instock_api->sanitize_text_field($button_label); ?>"/>
                                        </div>

                                        <div class="cwgstock_output"></div>
                                        <?php
                                        if (!isset($get_option['enable_troubleshoot']) || $get_option['enable_troubleshoot'] != '1') {
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <!-- End ROW -->
                        </div>
                    </div>
                </section>
                <?php
                return ob_get_clean();
            } else {
                return '';
            }
        }

        public function display_in_variation($atts, $product, $variation) {
            $get_stock = $atts['availability_html'];
            $atts['availability_html'] = $get_stock . $this->display_subscribe_box($product, $variation);
            return $atts;
        }

        public function enable_disabled_variation_dropdown($active, $variation) {
            $option = get_option('cwginstocksettings');
            $ignore_disabled_variation = isset($option['ignore_disabled_variation']) && $option['ignore_disabled_variation'] == '1' ? true : false;
            if (!$ignore_disabled_variation) {
                //if it is false then enable disabled out of stock variation from theme
                $active = true;
            }
            return $active;
        }

        public function is_viewable($product_id, $variation_id = 0) {
            $option = get_option('cwginstocksettings');
            $selected_products = isset($option['specific_products']) ? $option['specific_products'] : array();
            $product_visibility_mode = isset($option['specific_products_visibility']) ? $option['specific_products_visibility'] : '';
            if ((is_array($selected_products) && !empty($selected_products)) && $product_visibility_mode != '') {
                if ($variation_id > 0) {
                    //$product_visibility_mode 1 is for show and 2 is for hide
                    if ($product_visibility_mode == '1' && !in_array($variation_id, $selected_products)) {
                        return false;
                    } elseif ($product_visibility_mode == '2' && in_array($variation_id, $selected_products)) {
                        return false;
                    }
                } else {
                    if ($product_visibility_mode == '1' && !in_array($product_id, $selected_products)) {
                        return false;
                    } elseif ($product_visibility_mode == '2' && in_array($product_id, $selected_products)) {
                        return false;
                    }
                }
            }
            return true;
        }

        public function is_viewable_for_category($product_id) {
            $option = get_option('cwginstocksettings');
            $selected_categories = isset($option['specific_categories']) ? $option['specific_categories'] : array();
            $categories_visibility_mode = isset($option['specific_categories_visibility']) ? $option['specific_categories_visibility'] : '';

            if ((is_array($selected_categories) && !empty($selected_categories)) && $categories_visibility_mode != '') {
                $terms = wp_get_post_terms($product_id, array('product_cat'), array('fields' => 'slugs'));
                if ($terms) {
                    //if any value matched with settings then it will return matched values if not it will return only empty value
                    $intersect = array_intersect($terms, $selected_categories);
                    //$categories_visibility_mode 1 is for show and 2 is for hide
                    if ($categories_visibility_mode == '1' && empty($intersect)) {
                        return false;
                    } elseif ($categories_visibility_mode == '2' && !empty($intersect)) {
                        return false;
                    }
                }
            }
            return true;
        }

        public function is_viewable_for_product_tag($product_id) {
            $option = get_option('cwginstocksettings');
            $selected_tags = isset($option['specific_tags']) ? $option['specific_tags'] : array();
            $tags_visibility_mode = isset($option['specific_tags_visibility']) ? $option['specific_tags_visibility'] : '';

            if ((is_array($selected_tags) && !empty($selected_tags)) && $tags_visibility_mode != '') {
                $terms = wp_get_post_terms($product_id, array('product_tag'), array('fields' => 'slugs'));
                if ($terms) {
                    //if any value matched with settings then it will return matched values if not it will return only empty value
                    $intersect = array_intersect($terms, $selected_tags);
                    //$categories_visibility_mode 1 is for show and 2 is for hide
                    if ($tags_visibility_mode == '1' && empty($intersect)) {
                        return false;
                    } elseif ($tags_visibility_mode == '2' && !empty($intersect)) {
                        return false;
                    }
                } elseif (empty($terms) && $tags_visibility_mode == '1') {
                    //somewhere settings configured and set the visibility to show then hide it in current product
                    return false;
                }
            }
            return true;
        }

        public function visibility_on_regular_or_sale($product, $variation) {
            $option = get_option('cwginstocksettings');
            $hide_on_regular = isset($option['hide_on_regular']) && $option['hide_on_regular'] == '1' ? true : false;
            $hide_on_sale = isset($option['hide_on_sale']) && $option['hide_on_sale'] == '1' ? true : false;
            $check_is_on_sale = $variation ? $variation->is_on_sale() : $product->is_on_sale();
            $visibility = (($hide_on_regular && !$check_is_on_sale) || ($hide_on_sale && $check_is_on_sale)) ? false : true;
            return $visibility;
        }

        public function display_out_of_stock_products_in_variable($value) {
            $option = get_option('cwginstocksettings');
            $ignore_wc_visibility = isset($option['ignore_wc_visibility']) && $option['ignore_wc_visibility'] == '1' ? true : false;
            if (!class_exists('WooCommerce')) {
                //to avoid fatal error is_product conflict with other plugins like boost sales etc
                return $value;
            }
            if (is_product() && $ignore_wc_visibility) {
                //remove restriction only on single product page and followed by our settings page
                return 'no';
            }
            return $value;
        }

    }

    new CWG_Instock_Notifier_Product();
}