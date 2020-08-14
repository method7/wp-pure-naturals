<?php
/**
 * Class WFSPB_F_FrontEnd
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class WFSPB_F_FrontEnd {
	protected $settings;

	public function __construct() {
		$this->settings = new WFSPB_F_Data();
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script_fontend' ), 899999 );
		add_action( 'wp_footer', array( $this, 'show_bar_conditional' ), 90 );
	}

	/**
	 * Init Script
	 */
	public function enqueue_script_fontend() {
		$params = $this->settings;

		$enable = $params->get_option( 'enable' );


		if ( $enable && $params->check_woo_shipping_zone() ) {
			wp_enqueue_style( 'woo-free-shipping-bar', plugins_url( 'woo-free-shipping-bar/assets/css/woo-free-shipping-bar-frontend-style.css', 'woo-free-shipping-bar' ) );
			if ( defined( WP_CACHE ) && WP_CACHE ) {
				wp_enqueue_script( 'woo-free-shipping-bar-cache', plugins_url( 'woo-free-shipping-bar/assets/js/woo-free-shipping-bar-cache.js' ), array( 'jquery' ), WFSPB_F_VERSION, true );
			}
			wp_enqueue_script( 'woo-free-shipping-bar', plugins_url( 'woo-free-shipping-bar/assets/js/woo-free-shipping-bar-frontend.js' ), array( 'jquery' ), WFSPB_F_VERSION, true );

			$bg_color        = $params->get_option( 'bg-color' );
			$text_color      = $params->get_option( 'text-color' );
			$link_color      = $params->get_option( 'link-color' );
			$text_align      = $params->get_option( 'text-align' );
			$font            = $params->get_option( 'font' );
			$font_size       = $params->get_option( 'font-size' );
			$enable_progress = $params->get_option( 'enable-progress' );
			$style           = $params->get_option( 'style' );
			if ( $font == 'Default' ) {
				$font = '';
			}
			if ( ! empty( $font ) ) {
				$font = str_replace( '+', ' ', $font );
				wp_enqueue_style( 'google-font-' . strtolower( $font ), '//fonts.googleapis.com/css?family=' . $font . ':400,700' );
			}

			switch ( $style ) {
				case 2:
					wp_enqueue_style( 'woo-free-shipping-bar-style2', plugins_url( 'woo-free-shipping-bar/assets/css/style-progress/style2.css', 'woo-free-shipping-bar' ) );
					$css_style2 = "
						#wfspb-top-bar #wfspb-progress::before, #wfspb-top-bar #wfspb-progress::after{
							border-bottom-color: {$bg_color} !important;
						}
					";
					wp_add_inline_style( 'woo-free-shipping-bar-style2', $css_style2 );
					break;
				case 3:
					wp_enqueue_style( 'woo-free-shipping-bar-style3', plugins_url( 'woo-free-shipping-bar/assets/css/style-progress/style3.css', 'woo-free-shipping-bar' ) );
					break;
				default :
					wp_enqueue_style( 'woo-free-shipping-bar-style', plugins_url( 'woo-free-shipping-bar/assets/css/style-progress/style.css', 'woo-free-shipping-bar' ) );
					break;
			}


			$custom_css = "
				#wfspb-top-bar{
					background-color: {$bg_color} !important;
					color: {$text_color} !important;
				} 
				#wfspb-top-bar{
					text-align: {$text_align} !important;
				}
				#wfspb-top-bar > #wfspb-main-content{
					padding: 0 " . ( $font_size * 2 ) . "px;
					font-size: {$font_size}px !important;
					text-align: {$text_align} !important;
					color: {$text_color} !important;
				}
				#wfspb-top-bar > #wfspb-main-content > a,#wfspb-top-bar > #wfspb-main-content b, #wfspb-top-bar > #wfspb-main-content b span{
					color: {$link_color} !important;
				}
				div#wfspb-close{
				font-size: {$font_size}px !important;
				line-height: {$font_size}px !important;
				}
				";
			if ( $font ) {
				$custom_css .= "
				#wfspb-top-bar{
					font-family: {$font} !important;
				}";
			}

			if ( $enable_progress ) {
				$default_zone        = $params->get_option( 'default-zone' );
				$bg_progress         = $params->get_option( 'bg-color-progress' );
				$bg_current_progress = $params->get_option( 'bg-current-progress' );
				$progress_text_color = $params->get_option( 'progress-text-color' );
				$fontsize_progress   = $params->get_option( 'font-size-progress' );

				$customer = WC()->session->get( 'customer' );
				$country  = isset( $customer['shipping_country'] ) ? $customer['shipping_country'] : '';
				$state    = isset( $customer['shipping_state'] ) ? $customer['shipping_state'] : '';

				$postcode = isset( $customer['shipping_postcode'] ) ? $customer['shipping_postcode'] : '';
				if ( $country ) {
					$min_amount = $params->detect_ip( $country, $state, $postcode );
				} elseif ( $default_zone ) {
					$min_amount = $params->get_min_amount( $default_zone );
				} else {
					$min_amount = $params->get_shipping_min_amount();
				}
				$total = WC()->cart->get_displayed_subtotal();

				if ( WC()->cart->display_prices_including_tax() ) {
					$total = round( $total - ( WC()->cart->get_discount_total() + WC()->cart->get_discount_tax() ), wc_get_price_decimals() );
				} else {
					$total = round( $total - WC()->cart->get_discount_total(), wc_get_price_decimals() );
				}

				if ( $total >= $min_amount ) {
					$custom_css .= "
							#wfspb-current-progress{ width: 100%; }
						";
				} else {
					if ( $min_amount == 0 ) {
						$amount_total_pr = $total * 100;
					} else {
						$amount_total_pr = round( ( $total * 100 ) / $min_amount, 2 );
					}
					$custom_css .= "
						#wfspb-current-progress{
							width: {$amount_total_pr}%;
						}";
				}
				$custom_css .= "
					#wfspb-progress,.woo-free-shipping-bar-order .woo-free-shipping-bar-order-bar{
						background-color: {$bg_progress} !important;
					}
					#wfspb-current-progress,.woo-free-shipping-bar-order .woo-free-shipping-bar-order-bar .woo-free-shipping-bar-order-bar-inner{
						background-color: {$bg_current_progress} !important;
					}
					#wfspb-top-bar > #wfspb-progress.wfsb-effect-2{
					outline-color:{$bg_current_progress} !important;
					}
					#wfspb-label{
						color: {$progress_text_color} !important;
						font-size: {$fontsize_progress}px !important;
					}
				";
			}
			$css = $params->get_option( 'custom_css' );

			wp_add_inline_style( 'woo-free-shipping-bar', $custom_css . $css );
			// Localize the script with new data
			$translation_array = array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			);
			wp_localize_script( 'woo-free-shipping-bar', '_wfsb_params', $translation_array );


		}

	}

	public function show_bar_conditional() {
		$params = $this->settings;
		if ( ! is_admin() ) {
			$enable = $params->get_option( 'enable' );

			if ( $enable && $params->check_woo_shipping_zone() ) {
				echo $this->show_bar();
			}

		}
	}

	public function get_message( $arg ) {
		$params = $this->settings;
		$lang   = function_exists( 'wpml_get_current_language' ) ? wpml_get_current_language() : 'default';
		$result = ! empty( $params->get_option( $arg )[ $lang ] ) ? $params->get_option( $arg )[ $lang ] : $params->get_option( $arg )['default'];

		return $result;
	}

	public function show_bar() {
		$params            = $this->settings;
		$message_purchased = $this->get_message( 'message-purchased' );
		$announce_system   = $this->get_message( 'announce-system' );
		$message_success   = $this->get_message( 'message-success' );
		$message_error     = $this->get_message( 'message-error' );
		$close_message     = $params->get_option( 'close-message' );
		$default_zone      = $params->get_option( 'default-zone' );
		$position          = $params->get_option( 'position' );
		$enable_progress   = $params->get_option( 'enable-progress' );
		$progress_effect   = $params->get_option( 'progress_effect' );
		$show_giftbox      = $params->get_option( 'show-giftbox' );

		if ( $position == 0 ) {
			$class_pos = 'top_bar';
		} else {
			$class_pos = 'bottom_bar';
		}

		$announce_min_amount = '{min_amount}';

		$key          = array(
			'{total_amounts}',
			'{cart_amount}',
			'{min_amount}',
			'{missing_amount}'
		);
		$key_msgerror = array(
			'{missing_amount}',
			'{shopping}'
		);

		$shopping = '<a class="button" href="' . get_permalink( get_option( 'woocommerce_shop_page_id' ) ) . '">' . __( 'Shopping', 'woo-free-shipping-bar' ) . '</a>';
		$checkout = '<a href="' . wc_get_checkout_url() . '" title="' . esc_html__( 'Checkout', 'woo-free-shipping-bar' ) . '">' . esc_html__( 'Checkout', 'woo-free-shipping-bar' ) . '</a>';

		$message_ss = str_replace( '{checkout_page}', $checkout, '<div id="wfspb-main-content">' . strip_tags( $message_success ) . '</div>' );
		$total      = WC()->cart->get_displayed_subtotal();
		if ( WC()->cart->display_prices_including_tax() ) {
			$total = round( $total - ( WC()->cart->get_discount_total() + WC()->cart->get_discount_tax() ), wc_get_price_decimals() );
		} else {
			$total = round( $total - WC()->cart->get_discount_total(), wc_get_price_decimals() );
		}
		$cart_amount = WC()->cart->cart_contents_count;

		// get minimum order amount of free shipping method
		$customer = WC()->session->get( 'customer' );
		$country  = isset( $customer['shipping_country'] ) ? $customer['shipping_country'] : '';
		$state    = isset( $customer['shipping_state'] ) ? $customer['shipping_state'] : '';

		$postcode = isset( $customer['shipping_postcode'] ) ? $customer['shipping_postcode'] : '';

		if ( $country ) {
			$order_min_amount = $params->detect_ip( $country, $state, $postcode );
			if ( ! $order_min_amount && $default_zone ) {
				$order_min_amount = $this->settings->toInt( $this->settings->get_min_amount( $default_zone ) );
			}

		} elseif ( $default_zone ) {
			$order_min_amount = $params->get_min_amount( $default_zone );
		} else {
			$order_min_amount = $this->settings->get_shipping_min_amount();
		}
		/**
		 * Check If min amount is empty
		 */
		if ( ! $order_min_amount ) {
			return;
		}
		if ( is_checkout() ) {
			if ( $total < $order_min_amount ) {
				$missing_amount   = $order_min_amount - $total;
				$missing_amount_r = $missing_amount;
				if ( ! wc()->cart->display_prices_including_tax() ) {

					if ( 'incl' !== get_option( 'woocommerce_tax_display_shop' ) ) {

						if ( ! wc_prices_include_tax() ) {

							$missing_amount_r = $params->real_amount( $missing_amount );
						}

					}
				}

				$msgerror_replaced = array( wc_price( $missing_amount_r ), $shopping );
				$message           = str_replace( $key_msgerror, $msgerror_replaced, '<div id="wfspb-main-content">' . strip_tags( $message_error ) . '</div>' );
			} else {
				$message = $message_ss;
			}
		} else {
			if ( $total < $order_min_amount ) {

				$missing_amount = $order_min_amount - $total;

				$cart_amount1 = '<b id="current-quantity">' . $cart_amount . '</b>';
				$order_mins   = '<b id="wfspb_min_order_amount">' . wc_price( $order_min_amount ) . '</b>';

				$total_amount = '<b id="wfspb-current-amout">' . wc_price( $total ) . '</b>';

				if ( is_cart() ) {
					if ( wc()->cart->display_prices_including_tax() ) {
						$missing_amount1 = '<b id="wfspb_missing_amount">' . wc_price( $missing_amount ) . '</b>';
					} else {
						if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
							$missing_amount1 = '<b id="wfspb_missing_amount">' . wc_price( $missing_amount ) . '</b>';

						} else {
							if ( wc_prices_include_tax() ) {
								$missing_amount1 = '<b id="wfspb_missing_amount">' . wc_price( $missing_amount ) . '</b>';
							} else {
								$missing_amount_r = $params->real_amount( $missing_amount );
								$missing_amount1  = '<b id="wfspb_missing_amount">' . wc_price( $missing_amount_r ) . '</b>';
							}

						}
					}
				} else {
					if ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
						if ( wc()->cart->display_prices_including_tax() ) {
							$missing_amount1 = '<b id="wfspb_missing_amount">' . wc_price( $missing_amount ) . '</b>';
						} else {
							$missing_amount_r = $params->get_price_including_tax( $missing_amount );
							$missing_amount1  = '<b id="wfspb_missing_amount">' . wc_price( $missing_amount_r ) . esc_html__( '(incl. tax)', 'woo-free-shipping-bar' ) . '</b>';
						}

					} else {
						if ( wc_prices_include_tax() ) {
							$missing_amount1 = '<b id="wfspb_missing_amount">' . wc_price( $missing_amount ) . '</b>';
						} else {
							$missing_amount_r = $params->real_amount( $missing_amount );
							$missing_amount1  = '<b id="wfspb_missing_amount">' . wc_price( $missing_amount_r ) . '</b>';
						}

					}
				}

				$replaced = array(
					$total_amount,
					$cart_amount1,
					$order_mins,
					$missing_amount1
				);
				$message  = str_replace( $key, $replaced, '<div id="wfspb-main-content">' . strip_tags( $message_purchased ) . '</div>' );
			} else {
				$message = $message_ss;
			}
		}
		if ( $total ) {
			$class_pos .= ' has_items';
		}
		ob_start(); ?>

        <div id="wfspb-top-bar" class="displaying customized <?php echo esc_attr( $class_pos ) ?>"
             style="<?php if ( ! is_checkout() ) {
			     echo 'display: none;';
		     } ?>">
			<?php
			if ( $total == 0 ) {
				echo $message = str_replace( $announce_min_amount, wc_price( $order_min_amount ), '<div id="wfspb-main-content">' . strip_tags( $announce_system ) . '</div>' );
			} else {
				echo $message;
			}

			if ( $enable_progress ) {
				if ( $order_min_amount == 0 ) {
					$current_percent = $total * 100;
				} else {
					$current_percent = ( $total * 100 ) / $order_min_amount;
				}
				$class = array();
				if ( ! $total || $current_percent >= 100 ) {
					$class[] = 'wfsb-hidden';
				}
				if ( $progress_effect ) {
					$class[] = 'wfsb-effect-' . $progress_effect;
				}

				?>
                <div id="wfspb-progress" class="<?php echo esc_attr( implode( ' ', $class ) ) ?>">
                    <div id="wfspb-current-progress"
                         style="<?php echo intval( $current_percent ) > 0 ? 'width:' . $current_percent . '%' : '' ?>">
                        <div id="wfspb-label"><?php echo round( $current_percent, 0 ); ?>%</div>
                    </div>
                </div>
				<?php
			}
			if ( $close_message == 1 ) {
				echo '<div class="" id="wfspb-close"></div>';
			} ?>
        </div>
		<?php

		if ( $show_giftbox == 0 ) {
			return ob_get_clean();
		} ?>
        <div class="wfspb-gift-box" data-display="<?php echo esc_attr( $show_giftbox ); ?>">
            <img src="<?php echo esc_url( WFSPB_F_SHIPPING_IMAGES . 'free-delivery.png' ) ?>"/>
        </div>

		<?php return ob_get_clean();
	}
}

new WFSPB_F_FrontEnd();