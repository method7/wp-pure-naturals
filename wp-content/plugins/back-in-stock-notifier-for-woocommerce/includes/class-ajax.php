<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CWG_Instock_Ajax')) {

    class CWG_Instock_Ajax {

        public function __construct() {
            add_action('wp_ajax_cwginstock_product_subscribe', array($this, 'ajax_subscription'));
            add_action('wp_ajax_nopriv_cwginstock_product_subscribe', array($this, 'ajax_subscription'));
            add_action('cwginstock_ajax_data', array($this, 'perform_action_on_ajax_data'));
            add_action('cwginstock_after_insert_subscriber', array($this, 'perform_action_after_insertion'), 10, 2);
            add_action('wp_ajax_woocommerce_json_search_tags', array($this, 'json_search_tags'));
        }

        public function ajax_subscription() {
            if (isset($_POST)) {
                $obj = new CWG_Instock_API();
                $post_data = $obj->post_data_validation($_POST);
                $product_id = $post_data['product_id'];
                $get_option = get_option('cwginstocksettings');
                $check_is_security = isset($post_data['security']) && $post_data['security'] != '' ? 'yes' : 'no';
                if ($check_is_security == 'no') {
                    //block ajax request as it may be a bot
                    wp_die(-1, 403);
                }
                $check_is_recaptcha_enabled = isset($get_option['enable_recaptcha']) && $get_option['enable_recaptcha'] == '1' ? '1' : '2';
                $check_recaptcha_server_verify = isset($get_option['enable_recaptcha_verify']) && $get_option['enable_recaptcha_verify'] == '1' ? '1' : '2';
                $check_secret_key = isset($get_option['recaptcha_secret_key']) && $get_option['recaptcha_secret_key'] != '' ? $get_option['recaptcha_secret_key'] : '2';
                //if it is recaptcha ignore nonce and try verify recaptcha from google(avoid something went wrong error cause because of mainly from cache)
                if ($check_is_recaptcha_enabled == '2' || ($check_is_recaptcha_enabled == '1' && $check_recaptcha_server_verify == '2')) {
                    check_ajax_referer('codewoogeek-product_id-' . $product_id, 'security');
                } elseif ($check_is_recaptcha_enabled == '1' && $check_recaptcha_server_verify == '1' && $check_secret_key != '2') {
                    $verify_gresponse = $this->verify_recaptcha_client_response($post_data, $get_option);
                    if (is_wp_error($verify_gresponse)) {
                        wp_die(-1, 403);
                    } else {
                        $gresponse_body = json_decode(wp_remote_retrieve_body($verify_gresponse));
                        $gresponse_status = $gresponse_body->success;
                        if (!$gresponse_status) {
                            wp_die(-1, 403);
                        }
                    }
                }
                //for success
                do_action('cwginstock_ajax_data', $post_data);
                $success_msg = __('You have successfully subscribed, we will inform you when this product back in stock', 'cwginstocknotifier');
                $success = isset($get_option['success_subscription']) && $get_option['success_subscription'] ? $get_option['success_subscription'] : $success_msg;
                echo "<div class='cwginstocksuccess' style='color:green;'>$success</div>";
            }
            die();
        }

        public function perform_action_on_ajax_data($post_data) {
            $get_email = $post_data['user_email'];
            $get_user_id = $post_data['user_id'];
            $product_id = $post_data['product_id'];
            $variation_id = $post_data['variation_id'];

            $obj = new CWG_Instock_API($product_id, $variation_id, $get_email, $get_user_id);

            $check_is_already_subscribed = $obj->is_already_subscribed();

            if (!$check_is_already_subscribed) {
                $id = $obj->insert_subscriber();
                if ($id) {
                    $obj->insert_data($id);
                    $get_count = $obj->get_subscribers_count($product_id, 'cwg_subscribed');
                    update_post_meta($product_id, 'cwg_total_subscribers', $get_count);
                    do_action('cwginstock_after_insert_subscriber', $id, $post_data);
                    //logger
                    $logger = new CWG_Instock_Logger('success', "Subscriber #$get_email successfully subscribed - #$id");
                    $logger->record_log();
                }
            } else {
                $get_option = get_option('cwginstocksettings');
                $already_sub_msg = __('Seems like you have already subscribed to this product', 'cwginstocknotifier');
                $error = isset($get_option['already_subscribed']) && $get_option['already_subscribed'] ? $get_option['already_subscribed'] : $already_sub_msg;
                echo "<div class='cwginstockerror' style='color:red;'>$error</div>";
                die();
            }
        }

        // perform some action after insertion of subscriber

        public function perform_action_after_insertion($id, $post_data) {
            // send mail
            // settings data
            $option = get_option('cwginstocksettings');
            $is_enabled = isset($option['enable_success_sub_mail']) ? $option['enable_success_sub_mail'] : 0;
            $get_email = $post_data['user_email'];
            if ($is_enabled == '1' || $is_enabled == 1) {
                $mailer = new CWG_Trigger_Subscribe_Mail($id);
                $mailer->send();
                $logger = new CWG_Instock_Logger('success', "Mail sent to #$get_email for successful subscription - #$id");
                $logger->record_log();
            }
        }

        private function verify_recaptcha_client_response($post, $options) {
            $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
            $site_key = $options['recaptcha_secret_key'];
            $gresponse = $post['security'];
            $args = array('body' => array('secret' => $site_key, 'response' => $gresponse));
            $response = wp_remote_post($verify_url, $args);
            return $response;
        }

        public static function json_search_tags() {
            ob_start();

            check_ajax_referer('search-tags', 'security');

            if (!current_user_can('edit_products')) {
                wp_die(-1);
            }

            $search_text = isset($_GET['term']) ? wc_clean(wp_unslash($_GET['term'])) : '';

            if (!$search_text) {
                wp_die();
            }

            $found_tags = array();
            $args = array(
                'taxonomy' => array('product_tag'),
                'orderby' => 'id',
                'order' => 'ASC',
                'hide_empty' => true,
                'fields' => 'all',
                'name__like' => $search_text,
            );

            $terms = get_terms($args);

            if ($terms) {
                foreach ($terms as $term) {
                    $term->formatted_name = '';
                    $term->formatted_name .= $term->name . ' (' . $term->count . ')';
                    $found_tags[$term->term_id] = $term;
                }
            }
            wp_send_json(apply_filters('woocommerce_json_search_found_tags', $found_tags));
        }

    }

    new CWG_Instock_Ajax();
}