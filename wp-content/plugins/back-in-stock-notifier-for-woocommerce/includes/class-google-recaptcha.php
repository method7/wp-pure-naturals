<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CWG_Instock_Google_Recaptcha')) {

    class CWG_Instock_Google_Recaptcha {

        public function __construct() {
            $options = get_option('cwginstocksettings');
            $this->options = get_option('cwginstocksettings');
            $check_is_enable = isset($options['enable_recaptcha']) && $options['enable_recaptcha'] == '1' ? '1' : '2';
            if ($check_is_enable == '1') {
                add_action('cwg_instock_after_email_field', array($this, 'add_recaptcha_to_subscribe_form'), 10, 2);
                add_filter('cwgstock_submit_attr', array($this, 'disable_attr_on_recaptcha'), 10, 3);
                add_action('wp_enqueue_scripts', array($this, 'enqueue_script'), 999);
            }
            add_action('cwginstock_register_settings', array($this, 'add_settings_field'));
            add_filter('cwginstock_localization_array', array($this, 'add_localize_data'));
        }

        public function add_recaptcha_to_subscribe_form($product_id, $variation_id) {
            $variation_id = intval($variation_id);
            $options = $this->options;
            if ($variation_id > 0) {
                ?>
                <div id="cwg-google-recaptcha"></div>
                <?php
            } else {
                ?>
                <div class="g-recaptcha" data-sitekey="<?php echo isset($options['recaptcha_site_key']) && $options['recaptcha_site_key'] != '' ? $options['recaptcha_site_key'] : ''; ?>" data-callback="cwginstock_recaptcha_callback"></div>
                <?php
            }
            wp_enqueue_script("recaptcha");
        }

        public function disable_attr_on_recaptcha($attr, $product_id, $variation_id) {
            $attr = "disabled='disabled' ";
            return $attr;
        }

        public function enqueue_script() {
            wp_register_script("recaptcha", "https://www.google.com/recaptcha/api.js", array('cwginstock_js'), '1.8', true);
            wp_enqueue_script("recaptcha");
        }

        public function add_settings_field() {
            add_settings_section('cwginstock_section_recaptcha', __('Google reCAPTCHA v2 Settings ', 'cwginstocknotifier'), array($this, 'recaptcha_settings_heading'), 'cwginstocknotifier_settings');
            add_settings_field('cwg_instock_enable_recaptcha', __('Enable reCAPTCHA v2 in Subscribe Form', 'cwginstocknotifier'), array($this, 'enable_recaptcha'), 'cwginstocknotifier_settings', 'cwginstock_section_recaptcha');
            add_settings_field('cwg_instock_recaptcha_sitekey', __('reCAPTCHA v2 Site Key', 'cwginstocknotifier'), array($this, 'recaptcha_site_key'), 'cwginstocknotifier_settings', 'cwginstock_section_recaptcha');
            add_settings_field('cwg_instock_enable_gcaptcha_verify', __('Verify reCAPTCHA response in Server Side - this will ignore nonce validation', 'cwginstocknotifier'), array($this, 'enable_recaptcha_verify'), 'cwginstocknotifier_settings', 'cwginstock_section_recaptcha');
            add_settings_field('cwg_instock_recaptcha_secret', __('reCAPTCHA v2 Secret Key(this is required when you want to verify reCAPTCHA response in server side)', 'cwginstocknotifier'), array($this, 'recaptcha_secret_key'), 'cwginstocknotifier_settings', 'cwginstock_section_recaptcha');
        }

        public function recaptcha_settings_heading() {
            $url = "<a href='https://www.google.com/recaptcha/'>" . __('Check this for more information about google reCAPTCHA') . "</a>";
            $captcha_heading = __("Add Google reCAPTCHA to the Subscribe Form", 'cwginstocknotifier');
            echo $captcha_heading . "  " . $url;
        }

        public function enable_recaptcha() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='checkbox' name='cwginstocksettings[enable_recaptcha]' <?php isset($options['enable_recaptcha']) ? checked($options['enable_recaptcha'], 1) : ''; ?> value="1"/>
            <p><i><?php _e("Select this option to enable reCAPTCHA in Subscribe Form(site key required for this option)", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function enable_recaptcha_verify() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='checkbox' name='cwginstocksettings[enable_recaptcha_verify]' <?php isset($options['enable_recaptcha_verify']) ? checked($options['enable_recaptcha_verify'], 1) : ''; ?> value="1"/>
            <p><i><?php _e("By Default this option is unchecked means reCAPTCHA verified in client side and WP Nonce Verification in server side, if you check this option then reCAPTCHA Verification can take place in both Client/Server Side(validate again client reCAPTCHA response) and ignore WP Nonce", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function recaptcha_site_key() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='text' style='width: 400px;' name='cwginstocksettings[recaptcha_site_key]' value='<?php echo isset($options['recaptcha_site_key']) ? $options['recaptcha_site_key'] : ''; ?>'/>
            <?php
        }

        public function recaptcha_secret_key() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='text' style='width: 400px;' name='cwginstocksettings[recaptcha_secret_key]' value='<?php echo isset($options['recaptcha_secret_key']) ? $options['recaptcha_secret_key'] : ''; ?>'/>
            <p><i><?php _e("reCAPTCHA Secret Key required only when you enabled this option - 'Verify reCAPTCHA response in Server Side', otherwise it is optional", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

        public function add_localize_data($already_loaded) {
            $options = get_option('cwginstocksettings');
            $already_loaded['enable_recaptcha'] = isset($options['enable_recaptcha']) && $options['enable_recaptcha'] == '1' ? '1' : '2';
            $already_loaded['recaptcha_site_key'] = isset($options['recaptcha_site_key']) && $options['recaptcha_site_key'] != '' ? $options['recaptcha_site_key'] : '';
            $already_loaded['enable_recaptcha_verify'] = isset($options['enable_recaptcha_verify']) && $options['enable_recaptcha_verify'] == '1' ? '1' : '2';
            $already_loaded['recaptcha_secret_present'] = isset($options['recaptcha_secret_key']) && $options['recaptcha_secret_key'] != '' ? 'yes' : 'no';
            return $already_loaded;
        }

    }

    new CWG_Instock_Google_Recaptcha();
}
