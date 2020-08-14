<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CWG_Instock_Settings')) {

    class CWG_Instock_Settings {

        public function __construct() {
            add_action('admin_menu', array($this, 'add_settings_menu'));
            add_action('admin_init', array($this, 'register_manage_settings'));
            add_action('admin_init', array($this, 'default_value'));
            $this->api = new CWG_Instock_API();
        }

        public function add_settings_menu() {
            add_submenu_page('edit.php?post_type=cwginstocknotifier', 'Settings', 'Settings', 'manage_woocommerce', 'cwg-instock-mailer', array($this, 'manage_settings'));
        }

        public function manage_settings() {
            echo "<div class='wrap'>";
            settings_errors();
            ?>
            <form action='options.php' method='post' id="cwginstocknotifier_settings">

                <h1><?php _e('Back In Stock Notifier for WooCommerce Settings', 'cwginstocknotifier'); ?></h1>
                <div class="notice">
                    <p>Browse our <a href="https://codewoogeek.online/product-category/back-in-stock-notifier/" target="_blank"><strong>Back In Stock Notifier Add-ons</strong></a> which cost you only <strong>$5.00(It help us to keep support this plugin) - Unlimited Site License and No Monthly Subscription</strong></p>
                </div>
                <?php
                settings_fields('cwginstocknotifier_settings');
                do_action('cwginstocksettings_before_section');
                do_settings_sections('cwginstocknotifier_settings');
                submit_button();
                ?>
            </form>
            <?php
            echo "</div>";
        }

        public function register_manage_settings() {
            register_setting('cwginstocknotifier_settings', 'cwginstocksettings', array($this, 'sanitize_data'));
            add_settings_section('cwginstock_section', __('Frontend Form', 'cwginstocknotifier'), array($this, 'section_heading'), 'cwginstocknotifier_settings');
            add_settings_field('cwg_instock_form_title', __('Title for Subscribe Form', 'cwginstocknotifier'), array($this, 'form_title'), 'cwginstocknotifier_settings', 'cwginstock_section');
            add_settings_field('cwg_instock_form_placeholder', __('Placeholder for Email Field', 'cwginstocknotifier'), array($this, 'form_email_placeholder'), 'cwginstocknotifier_settings', 'cwginstock_section');
            add_settings_field('cwg_instock_form_button', __('Button Label', 'cwginstocknotifier'), array($this, 'button_label'), 'cwginstocknotifier_settings', 'cwginstock_section');

            add_settings_section('cwginstock_section_visibility', __('Visibility Settings', 'cwginstocknotifier'), array($this, 'visibility_section_heading'), 'cwginstocknotifier_settings');
            add_settings_field('cwginstock_visibility_guest', __('Hide Subscribe Form for Guests', 'cwginstocknotifier'), array($this, 'hide_form_for_guest'), 'cwginstocknotifier_settings', 'cwginstock_section_visibility');
            // since version 1.7
            add_settings_field('cwginstock_visibility_member', __('Hide Subscribe Form for Members', 'cwginstocknotifier'), array($this, 'hide_form_for_member'), 'cwginstocknotifier_settings', 'cwginstock_section_visibility');

            add_settings_field('cwginstock_visibility_backorder', __('Show Subscribe Form on Backorders', 'cwginstocknotifier'), array($this, 'show_form_for_backorders'), 'cwginstocknotifier_settings', 'cwginstock_section_visibility');

            add_settings_field('cwginstock_visibility_products', __('Show/Hide Subscribe Form for specific products', 'cwginstocknotifier'), array($this, 'visibility_for_specific_products'), 'cwginstocknotifier_settings', 'cwginstock_section_visibility');
            add_settings_field('cwginstock_visibility_categories', __('Show/Hide Subscribe Form for specific categories', 'cwginstocknotifier'), array($this, 'visibility_for_specific_categories'), 'cwginstocknotifier_settings', 'cwginstock_section_visibility');
            add_settings_field('cwginstock_visibility_tags', __('Show/Hide Subscribe Form for specific tags', 'cwginstocknotifier'), array($this, 'visibility_for_specific_tags'), 'cwginstocknotifier_settings', 'cwginstock_section_visibility');

            add_settings_field('cwginstock_visibility_on_regular', __('Hide Subscribe Form on Regular Products out of stock', 'cwginstocknotifier'), array($this, 'visibility_settings_for_product_on_regular'), 'cwginstocknotifier_settings', 'cwginstock_section_visibility');
            add_settings_field('cwginstock_visibility_on_sale', __('Hide Subscribe Form on Sale Products out of stock', 'cwginstocknotifier'), array($this, 'visibility_settings_for_product_on_sale'), 'cwginstocknotifier_settings', 'cwginstock_section_visibility');

            add_settings_field('cwginstock_bypass_disabled_variation', __("Don't overwrite disabled out of stock variations from theme configuration", 'cwginstocknotifier'), array($this, 'disabled_variation_settings_option'), 'cwginstocknotifier_settings', 'cwginstock_section_visibility');
            add_settings_field('cwginstock_bypass_wc_visibility', __('Ignore WooCommerce Out of Stock Visibility Settings for Variation', 'cwginstocknotifier'), array($this, 'ignore_settings_for_wc_out_of_stock_visibility'), 'cwginstocknotifier_settings', 'cwginstock_section_visibility');

            add_settings_section('cwginstock_section_error', __('Message Settings', 'cwginstocknotifier'), array($this, 'error_section_heading'), 'cwginstocknotifier_settings');
            add_settings_field('cwg_instock_sub_success', __('Success Subscription Message', 'cwginstocknotifier'), array($this, 'success_subscription_message'), 'cwginstocknotifier_settings', 'cwginstock_section_error');
            add_settings_field('cwg_instock_already_exists', __('Email Already Subscribed Message', 'cwginstocknotifier'), array($this, 'email_already_subscribed'), 'cwginstocknotifier_settings', 'cwginstock_section_error');


            add_settings_field('cwg_instock_error_email_empty', __('Email Field Empty Error', 'cwginstocknotifier'), array($this, 'empty_email_address'), 'cwginstocknotifier_settings', 'cwginstock_section_error');
            add_settings_field('cwg_instock_error_email_invalid', __('Invalid Email Error', 'cwginstocknotifier'), array($this, 'invalid_email_address'), 'cwginstocknotifier_settings', 'cwginstock_section_error');


            add_settings_section('cwginstock_section_mail', __('Mail Settings', 'cwginstocknotifier'), array($this, 'mail_settings_heading'), 'cwginstocknotifier_settings');
            add_settings_field('cwg_instock_success_subscription_mail', __('Enable Success Subscription Mail', 'cwginstocknotifier'), array($this, 'success_subscription_mail'), 'cwginstocknotifier_settings', 'cwginstock_section_mail');
            add_settings_field('cwg_instock_success_subscription_subject', __('Success Subscription Mail Subject', 'cwginstocknotifier'), array($this, 'success_subscription_mail_subject'), 'cwginstocknotifier_settings', 'cwginstock_section_mail');
            add_settings_field('cwg_instock_success_subscription_message', __('Success Subscription Mail Message', 'cwginstocknotifier'), array($this, 'success_subscription_mail_message'), 'cwginstocknotifier_settings', 'cwginstock_section_mail');
            add_settings_field('cwg_instock_success_subscription_copy', __('Additionally Send this Subscription mail as a copy to specific email ids', 'cwginstocknotifier'), array($this, 'enable_copy_subscription'), 'cwginstocknotifier_settings', 'cwginstock_section_mail');
            add_settings_field('cwg_instock_success_subscription_copy_recipients', __('Enter Email Ids separated by commas that you want to receive subscription copy mail', 'cwginstocknotifier'), array($this, 'subscription_copy_recipients'), 'cwginstocknotifier_settings', 'cwginstock_section_mail');

            add_settings_field('cwg_instock_mail', __('Enable Instock Mail', 'cwginstocknotifier'), array($this, 'enable_instock_mail'), 'cwginstocknotifier_settings', 'cwginstock_section_mail');
            add_settings_field('cwg_instock_mail_subject', __('Instock Mail Subject', 'cwginstocknotifier'), array($this, 'instock_mail_subject'), 'cwginstocknotifier_settings', 'cwginstock_section_mail');
            add_settings_field('cwg_instock_mail_message', __('Instock Mail Message', 'cwginstocknotifier'), array($this, 'instock_mail_message'), 'cwginstocknotifier_settings', 'cwginstock_section_mail');

            add_settings_section('cwginstock_section_bgprocess', __('Background Process Engine - Advanced Settings', 'cwginstocknotifier'), array($this, 'background_process_heading'), 'cwginstocknotifier_settings');
            add_settings_field('cwginstock_bgp_selection', __('Background Process Engine', 'cwginstocknotifier'), array($this, 'bgp_engine'), 'cwginstocknotifier_settings', 'cwginstock_section_bgprocess');

            do_action('cwginstock_register_settings');
        }

        public function section_heading() {
            _e("Customize the Frontend Subscribe Form when Product become out of stock", 'cwginstocknotifier');
        }

        public function form_title() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='text' style='width: 400px;' name='cwginstocksettings[form_title]' value="<?php echo $this->api->sanitize_text_field($options['form_title']); ?>"/>
            <?php
        }

        public function form_email_placeholder() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='text' style='width: 400px;' name='cwginstocksettings[form_placeholder]' value="<?php echo $this->api->sanitize_text_field($options['form_placeholder']); ?>"/>
            <?php
        }

        public function button_label() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='text' style='width: 400px;' name='cwginstocksettings[button_label]' value="<?php echo $this->api->sanitize_text_field($options['button_label']); ?>"/>
            <?php
        }

        public function visibility_section_heading() {
            _e("Visibility Settings for Subscriber Form Frontend", 'cwginstocknotifier');
        }

        public function hide_form_for_guest() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='checkbox' name='cwginstocksettings[hide_form_guests]' <?php isset($options['hide_form_guests']) ? checked($options['hide_form_guests'], 1) : ''; ?> value="1"/>
            <p><i><?php _e("Hide Subscribe Form for non logged-in Users", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function hide_form_for_member() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='checkbox' name='cwginstocksettings[hide_form_members]' <?php isset($options['hide_form_members']) ? checked($options['hide_form_members'], 1) : ''; ?> value="1"/>
            <p><i><?php _e("Hide Subscribe Form for logged-in Users", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function show_form_for_backorders() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type="checkbox" name="cwginstocksettings[show_on_backorders]" <?php isset($options['show_on_backorders']) ? checked($options['show_on_backorders'], 1) : ''; ?> value="1"/>
            <p><i><?php _e("Display Subscribe Form for Back Order", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function visibility_for_specific_products() {
            $options = get_option('cwginstocksettings');
            ?>
            <select style="width:320px;" data-placeholder="<?php _e("Select Products", 'cwginstocknotifier'); ?>" data-allow_clear="true" tabindex="-1" aria-hidden="true" name="cwginstocksettings[specific_products][]" multiple="multiple" class="wc-product-search">
                <?php
                $current_v = isset($options['specific_products']) ? $options['specific_products'] : '';
                if (is_array($current_v) && !empty($current_v)) {
                    foreach ($current_v as $each_id) {
                        $product = wc_get_product($each_id);
                        if ($product) {
                            printf('<option value="%s"%s>%s</option>', $each_id, ' selected="selected"', wp_kses_post($product->get_formatted_name()));
                        }
                    }
                }
                ?>
            </select>
            <label><input type="radio" name="cwginstocksettings[specific_products_visibility]" <?php isset($options['specific_products_visibility']) ? checked($options['specific_products_visibility'], 1) : ''; ?> value="1"/> <?php _e('Show', 'cwginstocknotifier'); ?></label>
            <label><input type="radio" name="cwginstocksettings[specific_products_visibility]" <?php isset($options['specific_products_visibility']) ? checked($options['specific_products_visibility'], 2) : ''; ?> value="2"/> <?php _e('Hide', 'cwginstocknotifier'); ?></label>
            <p><i><?php _e("By Default this field will empty means subscribe form will shown to all out of stock products by default", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function visibility_for_specific_categories() {
            $options = get_option('cwginstocksettings');
            ?>
            <select style="width:320px;" data-placeholder="<?php _e("Select Categories", 'cwginstocknotifier'); ?>" data-allow_clear="true" name="cwginstocksettings[specific_categories][]" multiple="multiple" class="wc-category-search">
                <?php
                $current_v = isset($options['specific_categories']) ? $options['specific_categories'] : '';
                if (is_array($current_v) && !empty($current_v)) {
                    foreach ($current_v as $each_slug) {
                        $current_category = $each_slug ? get_term_by('slug', $each_slug, 'product_cat') : false;
                        if ($current_category) {
                            printf('<option value="%s"%s>%s</option>', $each_slug, ' selected="selected"', esc_html($current_category->name . "(" . $current_category->count . ")"));
                        }
                    }
                }
                ?>
            </select>
            <label><input type="radio" name="cwginstocksettings[specific_categories_visibility]" <?php isset($options['specific_categories_visibility']) ? checked($options['specific_categories_visibility'], 1) : ''; ?> value="1"/> <?php _e('Show', 'cwginstocknotifier'); ?></label>
            <label><input type="radio" name="cwginstocksettings[specific_categories_visibility]" <?php isset($options['specific_categories_visibility']) ? checked($options['specific_categories_visibility'], 2) : ''; ?> value="2"/> <?php _e('Hide', 'cwginstocknotifier'); ?></label>
            <p><i><?php _e("By Default this field will empty means subscribe form will shown to all out of stock products by default", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function visibility_for_specific_tags() {
            $options = get_option('cwginstocksettings');
            ?>
            <select style="width:320px;" data-placeholder="<?php _e("Select Product Tags", 'cwginstocknotifier'); ?>" data-allow_clear="true" name="cwginstocksettings[specific_tags][]" multiple="multiple" class="wc-tag-search">
                <?php
                $current_v = isset($options['specific_tags']) ? $options['specific_tags'] : '';
                if (is_array($current_v) && !empty($current_v)) {
                    foreach ($current_v as $each_slug) {
                        $current_category = $each_slug ? get_term_by('slug', $each_slug, 'product_tag') : false;
                        if ($current_category) {
                            printf('<option value="%s"%s>%s</option>', $each_slug, ' selected="selected"', esc_html($current_category->name . "(" . $current_category->count . ")"));
                        }
                    }
                }
                ?>
            </select>
            <label><input type="radio" name="cwginstocksettings[specific_tags_visibility]" <?php isset($options['specific_tags_visibility']) ? checked($options['specific_tags_visibility'], 1) : ''; ?> value="1"/> <?php _e('Show', 'cwginstocknotifier'); ?></label>
            <label><input type="radio" name="cwginstocksettings[specific_tags_visibility]" <?php isset($options['specific_tags_visibility']) ? checked($options['specific_tags_visibility'], 2) : ''; ?> value="2"/> <?php _e('Hide', 'cwginstocknotifier'); ?></label>
            <p><i><?php _e("By Default this field will empty means subscribe form will shown to all out of stock products by default", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function visibility_settings_for_product_on_sale() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='checkbox' name='cwginstocksettings[hide_on_sale]' <?php isset($options['hide_on_sale']) ? checked($options['hide_on_sale'], 1) : ''; ?> value="1"/>
            <p><i><?php _e("Hide Subscribe Form on Sale Products out of stock", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function disabled_variation_settings_option() {
            $options = get_option('cwginstocksettings');
            ?>
            <p>Some themes disable variation out of stock by default and some by an option, when activate our plugin it overwrite theme configuration(disabled variation become selectable), so by enable this option our plugin settings will not overwrite theme configuration</p>
            <input type='checkbox' name='cwginstocksettings[ignore_disabled_variation]' <?php isset($options['ignore_disabled_variation']) ? checked($options['ignore_disabled_variation'], 1) : ''; ?> value="1"/>
            <p><i><?php _e("Enable this option to not overwrite disabled out of stock variation settings from themes(some themes)", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function ignore_settings_for_wc_out_of_stock_visibility() {
            $options = get_option('cwginstocksettings');
            ?>
            <p>WooCommerce has an option to hide out of stock products from catalog(WooCommerce->Products->Inventory->Out of stock visibililty),when you enable/enabled this option will hide out of stock products from shop page/category page, but this also hide out of stock variations from variation dropdown, for that we provide option to ignore that woocommerce out of stock visibility settings only for variable products</p>
            <input type='checkbox' name='cwginstocksettings[ignore_wc_visibility]' <?php isset($options['ignore_wc_visibility']) ? checked($options['ignore_wc_visibility'], 1) : ''; ?> value="1"/>
            <p><i><?php _e("Enable this option to ignore WooCommerce Out of stock Visibility Settings for Variations", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function visibility_settings_for_product_on_regular() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='checkbox' name='cwginstocksettings[hide_on_regular]' <?php isset($options['hide_on_regular']) ? checked($options['hide_on_regular'], 1) : ''; ?> value="1"/>
            <p><i><?php _e("Hide Subscribe Form on Regular Products out of stock", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function error_section_heading() {
            _e("Customize Error Message and its Visibility", 'cwginstocknotifier');
        }

        public function empty_email_address() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='text' style='width: 400px;' name='cwginstocksettings[empty_error_message]' value="<?php echo $this->api->sanitize_text_field($options['empty_error_message']); ?>"/>
            <?php
        }

        public function invalid_email_address() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='text' style='width: 400px;' name='cwginstocksettings[invalid_email_error]' value="<?php echo $this->api->sanitize_text_field($options['invalid_email_error']); ?>"/>
            <?php
        }

        public function mail_settings_heading() {
            _e('Customize Email Message and its corresponding settings', 'cwginstocknotifier');
            echo "<br> Available Shortcodes to be used for subject and message <br>";
            echo "<strong>{product_name}, {product_id}, {product_link}, {shopname}, {email_id}, {subscriber_email}, {cart_link}, {only_product_name}, {only_product_sku}, {product_image}</strong>";
            echo "<br> If you want to show the image with specified size then you can try something like this one <strong>{product_image=thumbnail}</strong>, (you can pass parameter like <strong>thumbnail/medium/large</strong>) it also accept any custom width and height by pass something like this one <strong>{product_image=100x100}</strong> (widthxheight)";
            echo "<br> <strong> When you use {product_link} or {cart_link} make sure you add anchor tag(some email client shows as plain text instead of hyperlink) <pre>&lt;a href='{product_link}'&gt;{product_name}&lt;/a&gt; </pre><pre>&lt;a href='{cart_link}'&gt;{cart_link}&lt;/a&gt;</pre> </strong>";
        }

        public function success_subscription_mail() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='checkbox' name='cwginstocksettings[enable_success_sub_mail]' <?php isset($options['enable_success_sub_mail']) ? checked($options['enable_success_sub_mail'], 1) : ''; ?> value="1"/>
            <?php
        }

        public function enable_copy_subscription() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type="checkbox" name="cwginstocksettings[enable_copy_subscription]" <?php isset($options['enable_copy_subscription']) ? checked($options['enable_copy_subscription'], 1) : ''; ?> value ='1'/>
            <?php
            echo "<p>" . __('For Example: If admin/shop owner want to receive email copy of subcribers then enable this option followed by enter their email ids', 'cwginstocknotifier') . "</p>";
        }

        public function success_subscription_mail_subject() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='text' style='width: 400px;' name='cwginstocksettings[success_sub_subject]' value="<?php echo $this->api->sanitize_text_field($options['success_sub_subject']); ?>"/>
            <?php
        }

        public function success_subscription_mail_message() {
            $options = get_option('cwginstocksettings');
            ?>
            <textarea rows="15" cols="50" name="cwginstocksettings[success_sub_message]"><?php echo $this->api->sanitize_textarea_field($options['success_sub_message']); ?></textarea>
            <?php
        }

        public function subscription_copy_recipients() {
            $options = get_option('cwginstocksettings');
            ?>
            <textarea rows='15' cols='50' name='cwginstocksettings[subscription_copy_recipients]'><?php echo isset($options['subscription_copy_recipients']) ? $options['subscription_copy_recipients'] : ''; ?></textarea>
            <?php
        }

        public function enable_instock_mail() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='checkbox' name='cwginstocksettings[enable_instock_mail]' <?php isset($options['enable_instock_mail']) ? checked($options['enable_instock_mail'], 1) : ''; ?> value="1"/>
            <?php
        }

        public function instock_mail_subject() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='text' style='width: 400px;' name='cwginstocksettings[instock_mail_subject]' value="<?php echo $this->api->sanitize_text_field($options['instock_mail_subject']); ?>"/>
            <?php
        }

        public function instock_mail_message() {
            $options = get_option('cwginstocksettings');
            ?>
            <textarea rows="15" cols="50" name="cwginstocksettings[instock_mail_message]"><?php echo $this->api->sanitize_textarea_field($options['instock_mail_message']); ?></textarea>
            <?php
        }

        public function background_process_heading() {
            _e("Please select background process engine, this is important to send a mail in background by default it is WP Background Process and you can also choose WooCommerce Background Process", 'cwginstocknotifier');
        }

        public function bgp_engine() {
            $options = get_option('cwginstocksettings');
            ?>
            <select name="cwginstocksettings[bgp_engine]" style="width:400px;">
                <option value="wpbgp" <?php echo isset($options['bgp_engine']) && $options['bgp_engine'] == 'wpbgp' ? "selected=selected" : "selected=selected"; ?>><?php _e('Default Background Process', 'cwginstocknotifier'); ?></option>
                <option value="wcbgp" <?php echo isset($options['bgp_engine']) && $options['bgp_engine'] == 'wcbgp' ? "selected=selected" : ""; ?>><?php _e('WooCommerce Background Process', 'cwginstocknotifier'); ?></option>
            </select>
            <?php
        }

        public function success_subscription_message() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='text' style='width: 400px;' name='cwginstocksettings[success_subscription]' value="<?php echo $this->api->sanitize_text_field($options['success_subscription']); ?>"/>
            <?php
        }

        public function email_already_subscribed() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='text' style='width: 400px;' name='cwginstocksettings[already_subscribed]' value="<?php echo $this->api->sanitize_text_field($options['already_subscribed']); ?>"/>
            <?php
        }

        public function default_value() {
            //delete_option('cwginstocksettings');
            $success_subscribe_message = "Dear {subscriber_email}, <br/>"
                    . "Thank you for subscribing to the #{product_name}. We will email you once product back in stock";
            $instock_message = "Hello {email_id}, <br/>"
                    . "Thanks for your patience and finally the wait is over! <br/> Your Subscribed Product {product_name} is now back in stock! We only have a limited amount of stock, and this email is not a guarantee you'll get one, so hurry to be one of the lucky shoppers who do <br/> Add this product {product_name} directly to your cart <a href='{cart_link}'>{cart_link}</a>";
            $data = apply_filters('cwginstock_default_values', array(
                'form_title' => 'Email when stock available',
                'form_placeholder' => 'Your Email Address',
                'button_label' => 'Subscribe Now',
                'empty_error_message' => 'Email Address cannot be empty',
                'invalid_email_error' => 'Please enter valid Email Address',
                'enable_success_sub_mail' => '1',
                'success_sub_subject' => 'You subscribed to {product_name} at {shopname}',
                'success_sub_message' => $success_subscribe_message,
                'enable_instock_mail' => '1',
                'instock_mail_subject' => 'Product {product_name} has back in stock',
                'instock_mail_message' => $instock_message,
                'success_subscription' => 'You have successfully subscribed, we will inform you when this product back in stock',
                'already_subscribed' => 'Seems like you have already subscribed to this product',
            ));

            if (is_array($data) && !empty($data)) {
                add_option('cwginstocksettings', $data);
            }
            $get_data = get_option('cwginstocksettings');

            if (!isset($get_data['specific_categories_visibility'])) {
                $get_data['specific_categories_visibility'] = '1';
                $get_data['specific_products_visibility'] = '1';
                update_option('cwginstocksettings', $get_data);
            }

            $get_data = get_option('cwginstocksettings');
            if (!isset($get_data['specific_tags_visibility'])) {
                $get_data['specific_tags_visibility'] = '1';
                update_option('cwginstocksettings', $get_data);
            }

            do_action('cwginstock_settings_default');
        }

        public function sanitize_data($input) {
            $textarea_field = array('instock_mail_message', 'success_sub_message');
            if (is_array($input) && !empty($input)) {
                foreach ($input as $key => $value) {
                    if (!is_array($value)) {
                        if (in_array($key, $textarea_field)) {
                            $input[$key] = $this->api->sanitize_textarea_field($value);
                        } else {
                            $input[$key] = $this->api->sanitize_text_field($value);
                        }
                    }
                }
            }

            return $input;
        }

    }

    new CWG_Instock_Settings();
}
