<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('CWG_Instock_Premium_Extensions')) {

    class CWG_Instock_Premium_Extensions {

        public function __construct() {
            add_action('admin_menu', array($this, 'add_settings_menu'), 999);
            // add_filter('woocommerce_screen_ids', array($this, 'manage_screen_ids'));
        }

        public function add_settings_menu() {
            add_submenu_page('edit.php?post_type=cwginstocknotifier', __('Extensions', 'cwginstocknotifier'), __('Extensions', 'cwginstocknotifier'), 'manage_woocommerce', 'cwg-instock-extensions', array($this, 'manage_settings'));
        }

        public function manage_settings() {
            ?>
            <style type="text/css">
                div.cwg-addon-wrap {
                    width: 100%;
                }

                div.cwg-addon-wrap .cwg-section {
                    width: 200px;
                    height: 200px;
                    float: left;
                    margin:10px;
                }

                div.cwg-section {
                    background:#ff5544;
                    position: relative;
                }



                div.cwg-section .cwg-addon-title {
                    padding:5px;
                    text-align: center;
                    position: absolute;
                    top: 40%;
                    transform: translateY(-50%);
                    color:#fff;
                    font-weight:bolder;
                    font-size:15px;
                    line-height: 30px;
                }
                .cwg-addon-bottom {

                    position: absolute;
                    bottom:0;
                    left:0;
                }
                .cwg-addon-bottom input {
                    width:200px;
                }

                .pricetag{
                    white-space:nowrap;
                    position:relative;
                    margin:0 5px 0 10px;
                    displaY:inline-block;
                    height:25px;
                    border-radius: 0 5px 5px 0;
                    padding: 0 25px 0 15px;
                    background:green;
                    border: 0 solid green;
                    border-top-width:1px;
                    border-bottom-width:1px;
                    color:#fff;
                    line-height:23px;
                }

                .pricetag:before{
                    position:absolute;
                    content:"\25CF";
                    color:white;
                    text-shadow: 0 0 1px #333;
                    font-size:11px;
                    line-height:0px;
                    text-indent:12px;
                    left:-15px;
                    width: 1px;
                    height:0px;
                    border-right:14px solid green;
                    border-top:  13px solid transparent;
                    border-bottom:  13px solid transparent;
                }

            </style>
            <?php
            $this->display_as_html();
        }

        public function display_as_html() {
            $array_of_extensions = array(
                'Bundle Add-ons - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier-bundle-add-ons/',
                'WPML - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/wpml/',
                'Unsubscribe - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/unsubscribe/',
                'Ban Email Domains and Email Addresses - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/ban-emails/',
                'Export CSV - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/export-csv/',
                'Custom CSS - Back In Stock Notifier for WooCommerce' => 'https://codewoogeek.online/shop/back-in-stock-notifier/custom-css/',
                'More Addons coming soon' => '',
            );
            ?>
            <div class="wrap cwg-addon-wrap">
                <h1>
                    Add-ons for Back In Stock Notifier
                </h1>
                <p>
                    We created few add-ons below which boosts the core products with extended functionality.
                </p>
                <h3>Advantage of buying this Add-ons</h3>
                <ol>
                    <li>
                        You can use this add-ons for Unlimited Sites, hence single purchase is enough.
                    </li>
                    <li>
                        Premium Support
                    </li>
                    <li>
                        No Subscription
                    </li>
                    <li>
                        Handy Price for each Add-on - Just $5.00 only
                    </li>
                </ol>

                <?php
                $i = 1;
                foreach ($array_of_extensions as $name => $url) {

                    $final_url = $url != '' ? $url : 'http://codewoogeek.online/product-category/back-in-stock-notifier/';
                    ?>

                    <div class="cwg-section">
                        <a href="<?php echo $final_url; ?>" target="__blank">
                            <span style="width: 200px;height: 200px;position: absolute;">
                                <span class="cwg-addon-title">
                                    <?php echo $name; ?>
                                </span>
                                <?php if ($url != '') { ?>
                                    <?php if ($i == 1) { ?>
                                        <span class="pricetag">$20.00</span>
                                    <?php } ?>
                                    <span class="cwg-addon-bottom" style="font-weight:bold;">

                                        <?php
                                        if ($i == 1) {
                                            $text = "Unlimited Sites for $20.00";
                                        } else {
                                            $text = "Unlimited Sites for $5.00";
                                        }
                                        echo get_submit_button($text);
                                        ?>
                                    </span>
                                <?php } ?>
                            </span>
                        </a>
                    </div>
                    <?php
                    $i++;
                }
                ?>
                <div class="clear"></div>
            </div>
            <?php
        }

        public function manage_screen_ids($screen_ids) {
            $get_current_screen = get_current_screen();
            $screen = $get_current_screen->id;
            if ($screen == 'cwginstocknotifier_page_cwg-instock-extensions') {
                $screen_ids[] = $screen;
            }
            return $screen_ids;
        }

    }

    new CWG_Instock_Premium_Extensions();
}