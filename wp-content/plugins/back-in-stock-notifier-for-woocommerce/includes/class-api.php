<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CWG_Instock_API')) {

    class CWG_Instock_API {

        public function __construct($product_id = 0, $variation_id = 0, $user_email = '', $user_id = 0, $language = 'en_US') {
            $this->product_id = $product_id;
            $this->variation_id = $variation_id;
            $this->subscriber_email = $user_email;
            $this->user_id = $user_id;
            $this->language = $language;
        }

        public function get_list_of_subscribers() {
            $args = array(
                'post_type' => 'cwginstocknotifier',
                'fields' => 'ids',
                'posts_per_page' => -1,
                'post_status' => 'cwg_subscribed',
            );
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'key' => 'cwginstock_product_id',
                    'value' => ($this->product_id > '0' || $this->product_id) ? $this->product_id : 'no_data_found',
                ),
                array(
                    'key' => 'cwginstock_variation_id',
                    'value' => ($this->variation_id > '0' || $this->variation_id > 0) ? $this->variation_id : 'no_data_found',
                ),
            );

            $args['meta_query'] = apply_filters("cwginstock_metaquery", $meta_query);
            $get_posts = get_posts($args);

            return $get_posts;
        }

        public function insert_subscriber() {
            $args = array(
                'post_title' => $this->subscriber_email,
                'post_type' => 'cwginstocknotifier',
                'post_status' => 'cwg_subscribed',
            );

            $id = wp_insert_post($args);
            if (!is_wp_error($id)) {
                return $id;
            } else {
                return false;
            }
        }

        public function insert_data($id) {
            $default_data = array(
                'cwginstock_product_id' => $this->product_id,
                'cwginstock_variation_id' => $this->variation_id,
                'cwginstock_subscriber_email' => $this->subscriber_email,
                'cwginstock_user_id' => $this->user_id,
                'cwginstock_language' => $this->language,
                'cwginstock_pid' => $this->variation_id > '0' || $this->variation_id > 0 ? $this->variation_id : $this->product_id,
            );
            foreach ($default_data as $key => $value) {
                update_post_meta($id, $key, $value);
            }
        }

        public function is_already_subscribed() {
            $args = array(
                'post_type' => 'cwginstocknotifier',
                'fields' => 'ids',
                'posts_per_page' => -1,
                'post_status' => 'cwg_subscribed',
            );
            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key' => 'cwginstock_pid',
                    'value' => $this->variation_id > '0' || $this->variation_id > 0 ? $this->variation_id : $this->product_id,
                ),
                array(
                    'key' => 'cwginstock_subscriber_email',
                    'value' => $this->subscriber_email,
                ),
            );
            $args['meta_query'] = $meta_query;
            $get_posts = get_posts($args);
            return $get_posts;
        }

        public function subscriber_subscribed($subscribe_id) {
            $args = array(
                'ID' => $subscribe_id,
                'post_type' => 'cwginstocknotifier',
                'post_status' => 'cwg_subscribed',
            );
            $id = wp_update_post($args);
            return $id;
        }

        public function subscriber_unsubscribed($subscribe_id) {
            $args = array(
                'ID' => $subscribe_id,
                'post_type' => 'cwginstocknotifier',
                'post_status' => 'cwg_unsubscribed',
            );
            $id = wp_update_post($args);
            return $id;
        }

        public function mail_sent_status($subscribe_id) {
            $args = array(
                'ID' => $subscribe_id,
                'post_type' => 'cwginstocknotifier',
                'post_status' => 'cwg_mailsent',
            );
            $id = wp_update_post($args);
            return $id;
        }

        public function mail_not_sent_status($subscribe_id) {
            $args = array(
                'ID' => $subscribe_id,
                'post_type' => 'cwginstocknotifier',
                'post_status' => 'cwg_mailnotsent',
            );
            $id = wp_update_post($args);
            return $id;
        }

        public function display_product_name($id) {
            $variation_id = get_post_meta($id, 'cwginstock_variation_id', true);
            $product_id = get_post_meta($id, 'cwginstock_product_id', true);
            if ($product_id) {
                $val = intval($variation_id);
                if ($val > 0) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        $formatted_name = $variation->get_formatted_name();
                        return $formatted_name;
                    }
                } else {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        return $product->get_formatted_name();
                    }
                }
                return false;
            }
        }

        public function display_product_link($id) {
            $variation_id = get_post_meta($id, 'cwginstock_variation_id', true);
            $product_id = get_post_meta($id, 'cwginstock_product_id', true);
            if ($product_id) {
                $val = intval($variation_id);
                if ($val > 0) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        $link = $variation->get_permalink();
                        return $link;
                    }
                } else {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        return $product->get_permalink();
                    }
                }
            }
            return '';
        }

        public function display_only_product_name($id) {
            $variation_id = get_post_meta($id, 'cwginstock_variation_id', true);
            $product_id = get_post_meta($id, 'cwginstock_product_id', true);
            if ($product_id) {
                $val = intval($variation_id);
                if ($val > 0) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        $formatted_name = $variation->get_name();
                        return $formatted_name;
                    }
                } else {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        return $product->get_name();
                    }
                }
                return false;
            }
        }

        public function get_product_sku($id) {
            $variation_id = get_post_meta($id, 'cwginstock_variation_id', true);
            $product_id = get_post_meta($id, 'cwginstock_product_id', true);
            if ($product_id) {
                $val = intval($variation_id);
                if ($val > 0) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        $formatted_name = $variation->get_sku();
                        return $formatted_name;
                    }
                } else {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        return $product->get_sku();
                    }
                }
                return false;
            }
        }

        public function get_product_image($id, $size = 'woocommerce_thumbnail') {
            $variation_id = get_post_meta($id, 'cwginstock_variation_id', true);
            $product_id = get_post_meta($id, 'cwginstock_product_id', true);
            if ($product_id) {
                $val = intval($variation_id);
                if ($val > 0) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        return $variation->get_image($size);
                    }
                } else {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        return $product->get_image($size);
                    }
                }
                return false;
            }
        }

        public function get_match_based_on_prefix_suffix($string, $prefix = '{', $suffix = '}') {
            $prefix = preg_quote($prefix);
            $suffix = preg_quote($suffix);
            if (preg_match_all("!$prefix(.*?)$suffix!", $string, $matches)) {
                return $matches[1];
            }
            return array();
        }

        public function get_cart_link($id) {
            $pid = get_post_meta($id, 'cwginstock_pid', true);
            if ($pid) {
                $object = wc_get_product($pid);
                if ($object) {
                    $url = $object->add_to_cart_url();
                }
            }
            if ($product_id) {
                $product_id = intval($product_id);
                $val = intval($variation_id);
                if ($val > 0) {
                    $variation = wc_get_product($variation_id);
                    if ($variation) {
                        $cart_url = $variation->add_to_cart_url();
                        return apply_filters('cwginstock_variation_cart_link', $cart_url, $variation_id, $id);
                    }
                } else {
                    $product = wc_get_product($product_id);
                    if ($product) {
                        $cart_url = $product->add_to_cart_url();
                        return apply_filters('cwginstock_cart_link', $cart_url, $product_id, $id);
                    }
                }
                return false;
            }
        }

        public function is_user_exists() {
            $get_user = get_user_by('email', $this->subscriber_email);
            if ($get_user) {
                return true;
            } else {
                return false;
            }
        }

        public function post_data_validation($post) {

            $post_data = array();
            if (is_array($post) && !empty($post)) {
                foreach ($post as $key => $value) {
                    if (is_array($value) && !empty($value)) {
                        foreach ($value as $newkey => $newvalue) {
                            $post_data[$key][$newkey] = $this->format_field($newkey, $newvalue);
                        }
                    } else {
                        $post_data[$key] = $this->format_field($key, $value);
                    }
                }
            }
            return $post_data;
        }

        public function format_field($key, $value) {
            $list_of_fields = array(
                'product_id' => intval(sanitize_text_field($value)),
                'variation_id' => intval(sanitize_text_field($value)),
                'user_id' => intval(sanitize_text_field($value)),
                'user_email' => sanitize_email($value),
            );
            if (isset($list_of_fields[$key])) {
                return $list_of_fields[$key];
            } else {
                return sanitize_text_field($value);
            }
        }

        public function get_user_email($user_id) {
            // user email
            if ($user_id > 0 || $user_id > '0') {
                $get_user = get_user_by('id', $user_id);
                if ($get_user) {
                    return $get_user->user_email;
                }
            }
            return '';
        }

        public function get_subscribers_count($product_id, $status = 'any') {
            $args = array(
                'post_type' => 'cwginstocknotifier',
                'post_status' => $status,
                'meta_query' => array(
                    array(
                        'key' => 'cwginstock_product_id',
                        'value' => array($product_id),
                        'compare' => 'IN',
                    )),
                'numberposts' => -1,
            );
            $query = get_posts($args);
            return count($query);
        }

        public function get_meta_values($key = '', $type = 'post', $status = 'cwg_subscribed') {
            global $wpdb;
            if (empty($key)) {
                return;
            }
            $meta_value = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = %s AND p.post_status = %s AND p.post_type = %s", $key, $status, $type));
            return $meta_value;
        }

        public function sanitize_text_field($value) {
            return sanitize_text_field($value);
        }

        public function sanitize_textarea_field($value) {
            $value = wp_kses($value, array(
                'a' => array(
                    'href' => array(),
                    'title' => array(),
                    'class' => array(),
                    'id' => array(),
                    'style' => array(),
                    'target' => array(),
                ),
                'br' => array(),
                'em' => array(),
                'strong' => array(
                    'class' => array(),
                    'id' => array(),
                    'style' => array(),
                ),
                'h1' => array(
                    'class' => array(),
                    'id' => array(),
                    'style' => array(),
                ),
                'h2' => array(
                    'class' => array(),
                    'id' => array(),
                    'style' => array(),
                ),
                'h3' => array(
                    'class' => array(),
                    'id' => array(),
                    'style' => array(),
                ),
                'h4' => array(
                    'class' => array(),
                    'id' => array(),
                    'style' => array(),
                ),
                'h5' => array(
                    'class' => array(),
                    'id' => array(),
                    'style' => array(),
                ),
                'h6' => array(
                    'class' => array(),
                    'id' => array(),
                    'style' => array(),
                ),
                'img' => array(
                    'class' => array(),
                    'id' => array(),
                    'style' => array(),
                    'src' => array(),
                    'alt' => array(),
                    'height' => array(),
                    'width' => array(),
                ),
                'label' => array(
                    'for' => array(),
                ),
                'ul' => array(
                    'id' => array(),
                    'class' => array(),
                    'style' => array(),
                ),
                'li' => array(
                    'id' => array(),
                    'class' => array(),
                    'style' => array(),
                ),
                'ol' => array(
                    'id' => array(),
                    'class' => array(),
                    'style' => array(),
                ),
                'p' => array(
                    'id' => array(),
                    'class' => array(),
                    'style' => array(),
                ),
                'b' => array(
                    'id' => array(),
                    'class' => array(),
                    'style' => array(),
                ),
                'table' => array(
                    'align' => array(),
                    'bgcolor' => array(),
                    'border' => array(),
                    'cellpadding' => array(),
                    'cellspacing' => array(),
                    'class' => array(),
                    'dir' => array(),
                    'frame' => array(),
                    'id' => array(),
                    'rules' => array(),
                    'style' => array(),
                    'width' => array(),
                ),
                'td' => array(
                    'abbr' => array(),
                    'align' => array(),
                    'bgcolor' => array(),
                    'class' => array(),
                    'colspan' => array(),
                    'dir' => array(),
                    'height' => array(),
                    'id' => array(),
                    'lang' => array(),
                    'rowspan' => array(),
                    'scope' => array(),
                    'style' => array(),
                    'valign' => array(),
                    'width' => array(),
                ),
                'th' => array(
                    'abbr' => array(),
                    'align' => array(),
                    'background' => array(),
                    'bgcolor' => array(),
                    'class' => array(),
                    'colspan' => array(),
                    'dir' => array(),
                    'height' => array(),
                    'id' => array(),
                    'lang' => array(),
                    'scope' => array(),
                    'style' => array(),
                    'valign' => array(),
                    'width' => array(),
                ),
                'tr' => array(
                    'align' => array(),
                    'bgcolor' => array(),
                    'class' => array(),
                    'dir' => array(),
                    'id' => array(),
                    'style' => array(),
                    'valign' => array(),
                ),
                'div' => array(
                    'id' => array(),
                    'class' => array(),
                    'style' => array(),
                ),
            ));
            return $value;
        }

    }

}