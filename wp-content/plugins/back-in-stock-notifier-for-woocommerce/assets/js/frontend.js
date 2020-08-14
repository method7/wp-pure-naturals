"use strict";

var ajaxurl = cwginstock.ajax_url;
var security_error = cwginstock.security_error;
var userid = cwginstock.user_id;
var emptyemail = cwginstock.empty_email;
var invalidemail = cwginstock.invalid_email;
var recaptcha_enabled = cwginstock.enable_recaptcha;
var recaptcha_site_key = cwginstock.recaptcha_site_key;
var recaptcha_verify_enabled = cwginstock.enable_recaptcha_verify;
var recaptcha_secret_present = cwginstock.recaptcha_secret_present;
var is_iagree = cwginstock.is_iagree_enable;
var iagree_error = cwginstock.iagree_error;


function cwginstock_isEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
}

function cwginstock_recaptcha_callback(response) {
    document.getElementsByClassName("cwgstock_button")[0].disabled = false;
    if (recaptcha_verify_enabled == '1' && recaptcha_secret_present == 'yes') {
        document.getElementsByClassName("cwg-security")[0].value = response;
    }
}

var googlerecaptcha_widget_id = null;
var onloadCallback = function () {
    if (recaptcha_enabled == '1') {
        if (jQuery('#cwg-google-recaptcha').length) {
            //console.log("True");
            if (googlerecaptcha_widget_id === null) {
                //console.log('Recaptcha True');
                googlerecaptcha_widget_id = grecaptcha.render('cwg-google-recaptcha', {
                    'sitekey': recaptcha_site_key,
                    'callback': cwginstock_recaptcha_callback,
                });
                //cwginstock_recaptcha_onload();
            } else {
                //console.log('Reset True');
                grecaptcha.reset(googlerecaptcha_widget_id);
                cwginstock_recaptcha_callback();
                googlerecaptcha_widget_id = null;
                onloadCallback();
            }
        }
    }
};


var resetcallback = function () {
    if (recaptcha_enabled == '1') {
        grecaptcha.reset();
        document.getElementsByClassName("cwgstock_button")[0].disabled = true;
    }
};



jQuery(function () {
//    jQuery(".variations_form").on("woocommerce_variation_select_change", function () {
//        // Fires whenever variation selects are changed
//        // onloadCallback();
//    });

    jQuery(".single_variation_wrap").on("show_variation", function (event, variation) {
        // Fired when the user selects all the required dropdowns / attributes
        // and a final variation is selected / shown
        var vid = variation.variation_id;
        jQuery('.cwginstock-subscribe-form').hide(); //remove existing form
        jQuery('.cwginstock-subscribe-form-' + vid).show(); //add subscribe form to show
        if (recaptcha_enabled == '1') {
            onloadCallback();
        }
    });

    jQuery(document).on('click', '.cwgstock_button', function () {
        var submit_button_obj = jQuery(this);
        var email_id = jQuery(this).closest('.cwginstock-subscribe-form').find('.cwgstock_email').val();
        var product_id = jQuery(this).closest('.cwginstock-subscribe-form').find('.cwg-product-id').val();
        var var_id = jQuery(this).closest('.cwginstock-subscribe-form').find('.cwg-variation-id').val();
        var security = jQuery(this).closest('.cwginstock-subscribe-form').find('.cwg-security').val();
        if (email_id == '') {
            jQuery(this).closest('.cwginstock-subscribe-form').find('.cwgstock_output').fadeIn();
            jQuery(this).closest('.cwginstock-subscribe-form').find('.cwgstock_output').html("<div class='cwginstockerror' style='color:red;'>" + emptyemail + "</div>");
            return false;
        } else {
            //check is valid email
            if (!cwginstock_isEmail(email_id)) {
                jQuery(this).closest('.cwginstock-subscribe-form').find('.cwgstock_output').fadeIn();
                jQuery(this).closest('.cwginstock-subscribe-form').find('.cwgstock_output').html("<div class='cwginstockerror' style='color:red;'>" + invalidemail + "</div>");
                return false;
            }

            if (is_iagree == '1') {
                if (!jQuery(this).closest('.cwginstock-subscribe-form').find('.cwg_iagree_checkbox_input').is(':checked')) {
                    jQuery(this).closest('.cwginstock-subscribe-form').find('.cwgstock_output').fadeIn();
                    jQuery(this).closest('.cwginstock-subscribe-form').find('.cwgstock_output').html("<div class='cwginstockerror' style='color:red;'>" + iagree_error + "</div>");
                    return false;
                }
            }
            var data = {
                action: 'cwginstock_product_subscribe',
                product_id: product_id,
                variation_id: var_id,
                user_email: email_id,
                user_id: userid,
                security: security,
                dataobj: cwginstock,
            };

            //jQuery.blockUI({message: null});
            if (jQuery.fn.block) {
                submit_button_obj.closest('.cwginstock-subscribe-form').block({message: null});
            } else {
                var overlay = jQuery('<div id="cwg-bis-overlay"> </div>');
                overlay.appendTo(submit_button_obj.closest('.cwginstock-subscribe-form'));
            }
            jQuery.ajax({
                type: "post",
                url: ajaxurl,
                data: data,
                success: function (msg) {
                    submit_button_obj.closest('.cwginstock-subscribe-form').find('.cwgstock_output').fadeIn(2000);
                    submit_button_obj.closest('.cwginstock-subscribe-form').find('.cwgstock_output').html(msg);
                    //jQuery.unblockUI();
                    if (jQuery.fn.block) {
                        submit_button_obj.closest('.cwginstock-subscribe-form').unblock();
                    } else {
                        submit_button_obj.closest('.cwginstock-subscribe-form').find('#cwg-bis-overlay').fadeOut(400, function () {
                            submit_button_obj.closest('.cwginstock-subscribe-form').find('#cwg-bis-overlay').remove();
                        });
                    }
                    resetcallback();
                },
                error: function (request, status, error) {
                    if (request.responseText === '-1' || request.responseText === -1) {
                        submit_button_obj.closest('.cwginstock-subscribe-form').find('.cwgstock_output').fadeIn(2000);
                        submit_button_obj.closest('.cwginstock-subscribe-form').find('.cwgstock_output').html("<div class='cwginstockerror' style='color:red;'>" + security_error + "</div>");
                    }
                    //jQuery.unblockUI();
                    if (jQuery.fn.block) {
                        submit_button_obj.closest('.cwginstock-subscribe-form').unblock();
                    } else {
                        submit_button_obj.closest('.cwginstock-subscribe-form').find('#cwg-bis-overlay').fadeOut(400, function () {
                            submit_button_obj.closest('.cwginstock-subscribe-form').find('#cwg-bis-overlay').remove();
                        });
                    }
                    resetcallback();
                }
            });
        }
        return false;
    });
});
