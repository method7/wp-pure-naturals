<?php

if (!defined('ABSPATH')) {
    exit;
}

class CWG_Instock_Mail_Process extends WP_Background_Process {

    /**
     * @var string
     */
    protected $action = 'cwg_instock_mail_process';

    /**
     * Task
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param mixed $item Queue item to iterate over
     *
     * @return mixed
     */
    protected function task($each_id) {
        $get_post_status = get_post_status($each_id);
        if ($get_post_status == 'cwg_subscribed') {
            $get_email = get_post_meta($each_id, 'cwginstock_subscriber_email', true);
            $option = get_option('cwginstocksettings');
            $is_enabled = $option['enable_instock_mail'];
            if ($is_enabled == '1' || $is_enabled == 1) {
                $mailer = new CWG_Trigger_Instock_Mail($each_id);
                $send_mail = $mailer->send(); // mail sent

                if ($send_mail) {
                    $api = new CWG_Instock_API();
                    $mail_status = $api->mail_sent_status($each_id); // update mail sent status
                    $logger = new CWG_Instock_Logger('info', "Automatic Instock Mail Triggered for ID #$each_id with #$get_email");
                    $logger->record_log();
                } else {
                    $api = new CWG_Instock_API();
                    $mail_status = $api->mail_not_sent_status($each_id);
                    $logger = new CWG_Instock_Logger('error', "Failed to send Automatic Instock Mail for ID #$each_id with #$get_email");
                    $logger->record_log();
                }
            }
        }
        return false;
    }

    /**
     * Complete
     *
     * Override if applicable, but ensure that the below actions are
     * performed, or, call parent::complete().
     */
    protected function complete() {
        parent::complete();
    }

}
