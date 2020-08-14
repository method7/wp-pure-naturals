<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CWG_Instock_Troubleshoot')) {

    class CWG_Instock_Troubleshoot {

        public function __construct() {
            add_action('cwginstock_register_settings', array($this, 'add_settings_field'), 999);
        }

        public function add_settings_field() {
            add_settings_section('cwginstock_section_troubleshoot', __('Troubleshoot Settings (Experimental)', 'cwginstocknotifier'), array($this, 'troubleshoot_settings_heading'), 'cwginstocknotifier_settings');
            add_settings_field('cwg_instock_enable_troubleshoot', __('Enable if Subscribe Form Layout Problem/Input Field Overlap', 'cwginstocknotifier'), array($this, 'enable_troubleshoot'), 'cwginstocknotifier_settings', 'cwginstock_section_troubleshoot');
        }

        public function troubleshoot_settings_heading() {
            $troubleshoot_heading = __("If frontend Subscribe Form layout breaks/input field overlap? then enable below checkbox option to troubleshoot this issue. If it is not work out then please open a support ticket with us https://codewoogeek.online", 'cwginstocknotifier');
            echo $troubleshoot_heading;
        }

        public function enable_troubleshoot() {
            $options = get_option('cwginstocksettings');
            ?>
            <input type='checkbox' name='cwginstocksettings[enable_troubleshoot]' <?php isset($options['enable_troubleshoot']) ? checked($options['enable_troubleshoot'], 1) : ''; ?> value="1"/>
            <p><i><?php _e("Select this option only if the subscribe form layout breaks in frontend(experimental)", 'cwginstocknotifier'); ?></i></p>
            <?php
        }

    }

    new CWG_Instock_Troubleshoot();
}