"use strict";
jQuery(function ($) {
    function getEnhancedSelectFormatString() {
        return {
            'language': {
                errorLoading: function () {
                    // Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
                    return wc_enhanced_select_params.i18n_searching;
                },
                inputTooLong: function (args) {
                    var overChars = args.input.length - args.maximum;

                    if (1 === overChars) {
                        return wc_enhanced_select_params.i18n_input_too_long_1;
                    }

                    return wc_enhanced_select_params.i18n_input_too_long_n.replace('%qty%', overChars);
                },
                inputTooShort: function (args) {
                    var remainingChars = args.minimum - args.input.length;

                    if (1 === remainingChars) {
                        return wc_enhanced_select_params.i18n_input_too_short_1;
                    }

                    return wc_enhanced_select_params.i18n_input_too_short_n.replace('%qty%', remainingChars);
                },
                loadingMore: function () {
                    return wc_enhanced_select_params.i18n_load_more;
                },
                maximumSelected: function (args) {
                    if (args.maximum === 1) {
                        return wc_enhanced_select_params.i18n_selection_too_long_1;
                    }

                    return wc_enhanced_select_params.i18n_selection_too_long_n.replace('%qty%', args.maximum);
                },
                noResults: function () {
                    return wc_enhanced_select_params.i18n_no_matches;
                },
                searching: function () {
                    return wc_enhanced_select_params.i18n_searching;
                }
            }
        };
    }
    jQuery(document.body).on('wc-enhanced-select-init', function () {
        // Ajax tag search boxes
        jQuery(':input.wc-tag-search').filter(':not(.enhanced)').each(function () {
            var select2_args = jQuery.extend({
                allowClear: jQuery(this).data('allow_clear') ? true : false,
                placeholder: jQuery(this).data('placeholder'),
                minimumInputLength: jQuery(this).data('minimum_input_length') ? jQuery(this).data('minimum_input_length') : 3,
                escapeMarkup: function (m) {
                    return m;
                },
                ajax: {
                    url: wc_enhanced_select_params.ajax_url,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            term: params.term,
                            action: 'woocommerce_json_search_tags',
                            security: cwg_enhanced_selected_params.search_tags_nonce
                        };
                    },
                    processResults: function (data) {
                        var terms = [];
                        if (data) {
                            jQuery.each(data, function (id, term) {
                                terms.push({
                                    id: term.slug,
                                    text: term.formatted_name
                                });
                            });
                        }
                        return {
                            results: terms
                        };
                    },
                    cache: true
                }
            }, getEnhancedSelectFormatString());

            jQuery(this).selectWoo(select2_args).addClass('enhanced');
        });
    }).trigger('wc-enhanced-select-init');
});