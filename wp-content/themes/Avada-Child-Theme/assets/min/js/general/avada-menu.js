// this file is not executed
// this is a backup file because the child theme Javascript cannot override a parent - we have to modify the parent avada theme.
// pure-naturals/app/public/wp-content/themes/Avada/assets/min/js/general/avada-menu.js

jQuery(document).ready(function () {
    "use strict";
    function a(a) {
        "Top" === avadaMenuVars.header_position &&
            (jQuery(a).mouseenter(function () {
                jQuery(this).find("> div").length &&
                    0 > jQuery(this).find("> div").offset().left &&
                    jQuery(this).find("> div").css({ left: "0", right: "auto" }),
                    jQuery(this).find("> div").length &&
                    jQuery(this).find("> div").offset().left +
                    jQuery(this).find("> div").width() >
                    jQuery(window).width() &&
                    jQuery(this).find("> div").css({ left: "auto", right: "0" });
            }),
                jQuery(window).on("resize", function () {
                    jQuery(a)
                        .find("> div")
                        .each(function () {
                            var a = jQuery(this),
                                b = a.outerWidth(),
                                c = a.offset().left,
                                d = c + b,
                                e = a.parent().offset().left,
                                f = jQuery(window).width();
                            jQuery("body.rtl").length
                                ? (c == e && d > f) || (c < e && d + b > f)
                                    ? a.css({ left: "auto", right: "0" })
                                    : a.css({ left: "0", right: "auto" })
                                : (c < e && 0 > c) || (c == e && 0 > c - b)
                                    ? a.css({ left: "0", right: "auto" })
                                    : a.css({ left: "auto", right: "0" });
                        });
                }));
    }
    function b() {
        var a,
            b,
            c = jQuery(".fusion-header-has-flyout-menu"),
            d = 0,
            e = c.find(".fusion-header").offset().top,
            f =
                Modernizr.mq(
                    "only screen and (min-device-width: 768px) and (max-device-width: 1366px) and (orientation: portrait)"
                ) ||
                Modernizr.mq(
                    "only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: landscape)"
                ),
            g = Modernizr.mq(
                "only screen and (max-width: " +
                avadaMenuVars.side_header_break_point +
                "px)"
            );
        jQuery("body").bind("touchmove", function (a) {
            jQuery(a.target).parents(".fusion-flyout-menu").length ||
                a.preventDefault();
        }),
            1 <= jQuery(".fusion-mobile-menu-design-flyout").length
                ? ((b =
                    1 <= jQuery(".fusion-is-sticky").length &&
                        1 <= jQuery(".fusion-mobile-sticky-nav-holder").length
                        ? c.find(".fusion-flyout-menu.fusion-mobile-sticky-nav-holder")
                        : c.find(
                            ".fusion-flyout-menu:not(.fusion-mobile-sticky-nav-holder)"
                        )),
                    c.find(".fusion-flyout-menu").css({ display: "none" }),
                    b.css({ display: "flex" }))
                : (b = c.find(".fusion-flyout-menu")),
            jQuery(".fusion-header-has-flyout-menu .fusion-secondary-header")
                .length &&
            (d = jQuery(
                ".fusion-header-has-flyout-menu .fusion-secondary-header"
            ).outerHeight()),
            (window.$wpadminbarHeight = jQuery("#wpadminbar").length
                ? jQuery("#wpadminbar").height()
                : 0),
            (a =
                jQuery(".fusion-header-has-flyout-menu-content").height() +
                jQuery(".fusion-secondary-header").height() +
                window.$wpadminbarHeight),
            c.hasClass("fusion-flyout-menu-active") &&
            (b.css({ height: "calc(100% - " + a + "px)", "margin-top": a }),
                b.find(".fusion-menu").height() > b.height() &&
                b.css("display", "flex")),
            "0" == avadaMenuVars.header_sticky ||
                (f && "0" == avadaMenuVars.header_sticky_tablet) ||
                (g && "0" == avadaMenuVars.header_sticky_mobile)
                ? (c
                    .find(".fusion-header")
                    .css({
                        position: "fixed",
                        width: "100%",
                        "max-width": "100%",
                        top: window.$wpadminbarHeight + d,
                        "z-index": "210",
                    }),
                    jQuery(".fusion-header-sticky-height").css({
                        display: "block",
                        height: c.find(".fusion-header").outerHeight(),
                    }))
                : e > window.$wpadminbarHeight &&
                (c
                    .find(".fusion-header")
                    .css({ position: "fixed", top: window.$wpadminbarHeight + d }),
                    jQuery(".layout-boxed-mode").length &&
                    c
                        .find(".fusion-header")
                        .css("max-width", jQuery("#wrapper").outerWidth() + "px"),
                    jQuery(".fusion-header-wrapper").css("height", ""));
    }
    function c() {
        setTimeout(function () {
            var a = jQuery(".fusion-header-has-flyout-menu"),
                b = 0,
                c =
                    Modernizr.mq(
                        "only screen and (min-device-width: 768px) and (max-device-width: 1366px) and (orientation: portrait)"
                    ) ||
                    Modernizr.mq(
                        "only screen and (min-device-width: 768px) and (max-device-width: 1024px) and (orientation: landscape)"
                    ),
                d = Modernizr.mq(
                    "only screen and (max-width: " +
                    avadaMenuVars.side_header_break_point +
                    "px)"
                );
            jQuery(".fusion-header-has-flyout-menu .fusion-secondary-header")
                .length &&
                (b = jQuery(
                    ".fusion-header-has-flyout-menu .fusion-secondary-header"
                ).outerHeight()),
                a.find(".fusion-flyout-menu").css("display", ""),
                "0" == avadaMenuVars.header_sticky ||
                    (c && "0" == avadaMenuVars.header_sticky_tablet) ||
                    (d && "0" == avadaMenuVars.header_sticky_mobile)
                    ? (a.find(".fusion-header").attr("style", ""),
                        jQuery(".fusion-header-sticky-height").attr("style", ""))
                    : "fixed" === a.find(".fusion-header").css("position") &&
                    (a.find(".fusion-header").css("position", ""),
                        a.find(".fusion-header").offset().top > b &&
                        a.find(".fusion-header").css("top", window.$wpadminbarHeight),
                        jQuery(window).trigger("scroll")),
                jQuery("body").unbind("touchmove");
        }, 250);
    }
    function m7_close_search() {
        jQuery('.fusion-main-menu-search').removeClass("fusion-main-menu-search-open")
            .find(".fusion-custom-menu-item-contents").hide()
            .find("style").remove();
        jQuery('.fusion-mobile-menu-search').hide()
    }
    function m7_close_nav() {
        jQuery(".fusion-mobile-nav-holder.fusion-mobile-menu-expanded").hide().removeClass("fusion-mobile-menu-expanded")
    }
    var d;
    jQuery(".fusion-dropdown-svg").length &&
        jQuery(".fusion-dropdown-svg").each(function () {
            var a = jQuery(this).parents("li").find("> .sub-menu > li:first-child");
            (jQuery(a).hasClass("current-menu-item") ||
                jQuery(a).hasClass("current-menu-parent") ||
                jQuery(a).hasClass("current_page_item")) &&
                jQuery(this).addClass("fusion-svg-active"),
                jQuery(a)
                    .not(".current-menu-item, .current-menu-parent, .current_page_item")
                    .find("> a")
                    .on("hover", function () {
                        jQuery(this)
                            .parents("li")
                            .find(".fusion-dropdown-svg")
                            .toggleClass("fusion-svg-active");
                    });
        }),
        (jQuery.fn.fusion_position_menu_dropdown = function () {
            return ("Top" === avadaMenuVars.header_position &&
                !jQuery("body.rtl").length) ||
                "Left" === avadaMenuVars.header_position
                ? jQuery(this)
                    .children(".sub-menu")
                    .each(function () {
                        var a,
                            b,
                            c,
                            d,
                            e,
                            f,
                            g,
                            h,
                            i,
                            j,
                            k,
                            l,
                            m,
                            n,
                            o,
                            p = jQuery(this);
                        p.removeAttr("style"),
                            p.show(),
                            p.removeData("shifted"),
                            p.length &&
                            ((a = p.offset()),
                                (b = a.left),
                                (c = a.top),
                                (d = p.height()),
                                (e = p.outerWidth()),
                                (f = c + d),
                                (g = b + e),
                                (j = jQuery("#wpadminbar").length
                                    ? jQuery("#wpadminbar").height()
                                    : 0),
                                (k = jQuery(window).scrollTop()),
                                (l = jQuery(window).height()),
                                (m = k + l),
                                (h = jQuery(window).width()),
                                g > h
                                    ? (p.addClass("fusion-switched-side"),
                                        p.parent().parent(".sub-menu").length
                                            ? p.css({ left: -1 * e })
                                            : p.css("left", -1 * e + p.parent().width()),
                                        p.data("shifted", 1))
                                    : p.parent().parent(".sub-menu").length &&
                                    (p.removeClass("fusion-switched-side"),
                                        p.parent().parent(".sub-menu").data("shifted") &&
                                        (p.css("left", -1 * e), p.data("shifted", 1))),
                                "Top" !== avadaMenuVars.header_position &&
                                f > m &&
                                ((i = d < m ? -1 * (f - m + 10) : -1 * (c - k - j)),
                                    jQuery(".fusion-dropdown-svg").length &&
                                    (p.find("> li > a").off("hover"),
                                        p
                                            .parents("li")
                                            .find(".fusion-dropdown-svg")
                                            .removeClass("fusion-svg-active"),
                                        (n = Math.floor(i / p.find("li").outerHeight())),
                                        (i = n * p.find("li").outerHeight()),
                                        (o = p.find(
                                            "> li:nth-child( " + (Math.abs(n) + 1) + ")"
                                        )),
                                        (jQuery(o).hasClass("current-menu-item") ||
                                            jQuery(o).hasClass("current-menu-parent") ||
                                            jQuery(o).hasClass("current_page_item")) &&
                                        p
                                            .parents("li")
                                            .find(".fusion-dropdown-svg")
                                            .addClass("fusion-svg-active"),
                                        jQuery(o)
                                            .not(
                                                ".current-menu-item, .current-menu-parent, .current_page_item"
                                            )
                                            .find("> a")
                                            .on("hover", function () {
                                                p.parents("li")
                                                    .find(".fusion-dropdown-svg")
                                                    .toggleClass("fusion-svg-active");
                                            })),
                                    p.css("top", i)));
                    })
                : jQuery(this)
                    .children(".sub-menu")
                    .each(function () {
                        var a, b, c, d, e, f, g, h, i, j, k, l, m, n, o;
                        jQuery(this).removeAttr("style"),
                            jQuery(this).removeData("shifted"),
                            (a = jQuery(this)),
                            a.length &&
                            ((b = a.offset()),
                                (c = b.left),
                                (d = b.top),
                                (e = a.height()),
                                (f = a.outerWidth()),
                                (g = d + e),
                                (h = jQuery("#wpadminbar").length
                                    ? jQuery("#wpadminbar").height()
                                    : 0),
                                (i = jQuery(window).scrollTop()),
                                (j = jQuery(window).height()),
                                (k = i + j),
                                (m = "right"),
                                0 > c
                                    ? (a.addClass("fusion-switched-side"),
                                        a.parent().parent(".sub-menu").length
                                            ? c < f
                                                ? a.attr("style", m + ":" + -1 * f + "px !important")
                                                : a.css(m, -1 * f)
                                            : a.css(m, -1 * f + a.parent().width()),
                                        a.data("shifted", 1))
                                    : a.parent().parent(".sub-menu").length &&
                                    (a.removeClass("fusion-switched-side"),
                                        a.parent().parent(".sub-menu").data("shifted") &&
                                        a.css(m, -1 * f)),
                                "Top" !== avadaMenuVars.header_position &&
                                g > k &&
                                ((l = e < k ? -1 * (g - k + 10) : -1 * (d - i - h)),
                                    jQuery(".fusion-dropdown-svg").length &&
                                    (a.find("> li > a").off("hover"),
                                        a
                                            .parents("li")
                                            .find(".fusion-dropdown-svg")
                                            .removeClass("fusion-svg-active"),
                                        (n = Math.floor(l / a.find("li").outerHeight())),
                                        (l = n * a.find("li").outerHeight()),
                                        (o = a.find(
                                            "> li:nth-child( " + (Math.abs(n) + 1) + ")"
                                        )),
                                        (jQuery(o).hasClass("current-menu-item") ||
                                            jQuery(o).hasClass("current-menu-parent") ||
                                            jQuery(o).hasClass("current_page_item")) &&
                                        a
                                            .parents("li")
                                            .find(".fusion-dropdown-svg")
                                            .addClass("fusion-svg-active"),
                                        jQuery(o)
                                            .not(
                                                ".current-menu-item, .current-menu-parent, .current_page_item"
                                            )
                                            .find("> a")
                                            .on("hover", function () {
                                                a.parents("li")
                                                    .find(".fusion-dropdown-svg")
                                                    .toggleClass("fusion-svg-active");
                                            })),
                                    a.css("top", l)));
                    });
        }),
        (jQuery.fn.walk_through_menu_items = function () {
            jQuery(this).fusion_position_menu_dropdown(),
                jQuery(this).find(".sub-menu").length &&
                jQuery(this).find(".sub-menu li").walk_through_menu_items();
        }),
        (jQuery.fn.position_cart_dropdown = function () {
            "Top" !== avadaMenuVars.header_position &&
                jQuery(this)
                    .find(".fusion-menu-cart-items")
                    .each(function () {
                        var a,
                            b,
                            c,
                            d = jQuery(this),
                            e = d.height(),
                            f = jQuery("#wpadminbar").length
                                ? jQuery("#wpadminbar").height()
                                : 0,
                            g = jQuery(window).scrollTop(),
                            h = jQuery(window).height(),
                            i = g + h;
                        d.css("top", ""),
                            (a = d.offset().top),
                            (b = a + e) > i &&
                            ((c = e < h ? -1 * (b - i + 10) : -1 * (a - g - f)),
                                d.css("top", c));
                    });
        }),
        (jQuery.fn.position_menu_search_form = function () {
            "Top" !== avadaMenuVars.header_position &&
                jQuery(this).each(function () {
                    var a,
                        b,
                        c,
                        d = jQuery(this),
                        e = d.outerHeight(),
                        f = jQuery(window).scrollTop(),
                        g = jQuery(window).height(),
                        h = f + g;
                    d.css("top", ""),
                        (a = d.offset().top),
                        (b = a + e) > h && ((c = -1 * (b - h + 10)), d.css("top", c));
                });
        }),
        (jQuery.fn.fusion_position_megamenu = function () {
            var a, b, c, d, e, f;
            return jQuery(".side-header-left").length
                ? this.each(function () {
                    jQuery(this)
                        .children("li")
                        .each(function () {
                            var a,
                                b,
                                c,
                                d,
                                e,
                                f,
                                g,
                                h,
                                i = jQuery(this),
                                j = i.find(".fusion-megamenu-wrapper");
                            j.length &&
                                (j.removeAttr("style"),
                                    (a = jQuery("#side-header").outerWidth() - 1),
                                    (b = j.offset().top),
                                    (c = j.height()),
                                    (d = b + c),
                                    (e = jQuery("#wpadminbar").length
                                        ? jQuery("#wpadminbar").height()
                                        : 0),
                                    (f = jQuery(".side-header-wrapper").offset().top - e),
                                    (g = jQuery(window).height()),
                                    jQuery("body.rtl").length
                                        ? j.css({ left: a, right: "auto" })
                                        : j.css("left", a),
                                    d > f + g &&
                                    jQuery(window).height() >=
                                    jQuery(".side-header-wrapper").height() &&
                                    ((h = c < g ? -1 * (d - f - g + 20) : -1 * (b - e)),
                                        j.css("top", h)));
                        });
                })
                : jQuery(".side-header-right").length
                    ? this.each(function () {
                        jQuery(this)
                            .children("li")
                            .each(function () {
                                var a,
                                    b,
                                    c,
                                    d,
                                    e,
                                    f,
                                    g,
                                    h,
                                    i = jQuery(this),
                                    j = i.find(".fusion-megamenu-wrapper");
                                j.length &&
                                    (j.removeAttr("style"),
                                        (a = -1 * j.outerWidth()),
                                        (b = j.offset().top),
                                        (c = j.height()),
                                        (d = b + c),
                                        (e = jQuery("#wpadminbar").length
                                            ? jQuery("#wpadminbar").height()
                                            : 0),
                                        (f = jQuery(".side-header-wrapper").offset().top - e),
                                        (g = jQuery(window).height()),
                                        jQuery("body.rtl").length
                                            ? j.css({ left: a, right: "auto" })
                                            : j.css("left", a),
                                        d > f + g &&
                                        jQuery(window).height() >=
                                        jQuery(".side-header-wrapper").height() &&
                                        ((h = c < g ? -1 * (d - f - g + 20) : -1 * (b - e)),
                                            j.css("top", h)));
                            });
                    })
                    : ((a = ""),
                        (a = jQuery(".fusion-header-v4").length
                            ? jQuery(this).parent(".fusion-main-menu").parent()
                            : jQuery(this).parent(".fusion-main-menu")),
                        jQuery(this).parent(".fusion-main-menu").length
                            ? ((b = a),
                                (c = b.offset()),
                                (d = b.width()),
                                (e = c.left),
                                (f = e + d),
                                jQuery("body.rtl").length
                                    ? this.each(function () {
                                        jQuery(this)
                                            .children("li")
                                            .each(function () {
                                                var a,
                                                    b = jQuery(this),
                                                    c = b.offset(),
                                                    d = c.left + b.outerWidth(),
                                                    g = b.find(".fusion-megamenu-wrapper"),
                                                    h = g.outerWidth(),
                                                    i = 0;
                                                g.length &&
                                                    (g.removeAttr("style"),
                                                        (a = jQuery(".fusion-secondary-main-menu").length
                                                            ? jQuery(
                                                                ".fusion-header-wrapper .fusion-secondary-main-menu .fusion-row"
                                                            )
                                                            : jQuery(".fusion-header-wrapper .fusion-row")),
                                                        g.hasClass("col-span-12") &&
                                                            a.width() < g.data("maxwidth")
                                                            ? g.css("width", a.width())
                                                            : g.removeAttr("style"),
                                                        d - h < e &&
                                                        ((i = -1 * (h - (d - e))),
                                                            ("left" ===
                                                                avadaMenuVars.logo_alignment.toLowerCase() ||
                                                                ("center" ===
                                                                    avadaMenuVars.logo_alignment.toLowerCase() &&
                                                                    !jQuery(".header-v5").length) ||
                                                                jQuery(this).parents(".sticky-header").length) &&
                                                            d - i > f &&
                                                            (i = -1 * (f - d)),
                                                            g.css("right", i)));
                                            });
                                    })
                                    : this.each(function () {
                                        jQuery(this)
                                            .children("li")
                                            .each(function () {
                                                var a = jQuery(this),
                                                    b = a.offset(),
                                                    c = a.find(".fusion-megamenu-wrapper"),
                                                    d = c.outerWidth(),
                                                    g = 0,
                                                    h = 0;
                                                c.length &&
                                                    (c.removeAttr("style"),
                                                        (h = jQuery(".fusion-secondary-main-menu").length
                                                            ? jQuery(
                                                                ".fusion-header-wrapper .fusion-secondary-main-menu .fusion-row"
                                                            )
                                                            : jQuery(".fusion-header-wrapper .fusion-row")),
                                                        c.hasClass("col-span-12") &&
                                                            h.width() < c.data("maxwidth")
                                                            ? c.css("width", h.width())
                                                            : c.removeAttr("style"),
                                                        (d = c.outerWidth()),
                                                        b.left + d > f &&
                                                        ((g = -1 * (b.left - (f - d))),
                                                            "right" ===
                                                            avadaMenuVars.logo_alignment.toLowerCase() &&
                                                            b.left + g < e &&
                                                            (g = -1 * (b.left - e)),
                                                            c.css("left", g)));
                                            });
                                    }))
                            : void 0);
        }),
        (jQuery.fn.calc_megamenu_responsive_column_widths = function () {
            jQuery(this)
                .find(".fusion-megamenu-menu")
                .each(function () {
                    var a,
                        b = jQuery(this).find(".fusion-megamenu-holder"),
                        c = b.data("width"),
                        d = jQuery(".fusion-secondary-main-menu").length
                            ? jQuery(
                                ".fusion-header-wrapper .fusion-secondary-main-menu .fusion-row"
                            )
                            : jQuery(".fusion-header-wrapper .fusion-row"),
                        e = d.width();
                    "Top" !== avadaMenuVars.header_position &&
                        ((a = jQuery("#main").css("padding-left").replace("px", "")),
                            (e =
                                jQuery(window).width() -
                                a -
                                jQuery("#side-header").outerWidth())),
                        e < c
                            ? (b.css("width", e),
                                b
                                    .parents(".fusion-megamenu-wrapper")
                                    .hasClass("fusion-megamenu-fullwidth") ||
                                b.find(".fusion-megamenu-submenu").each(function () {
                                    var a = jQuery(this),
                                        b = (a.data("width") * e) / c;
                                    a.css("width", b);
                                }))
                            : (b.css("width", c),
                                b
                                    .parents(".fusion-megamenu-wrapper")
                                    .hasClass("fusion-megamenu-fullwidth") ||
                                b.find(".fusion-megamenu-submenu").each(function () {
                                    jQuery(this).css("width", jQuery(this).data("width"));
                                }));
                });
        }),
        (jQuery.fn.position_last_top_menu_item = function () {
            var a, b, c, d, e, f;
            (jQuery(this).children("ul").length ||
                jQuery(this).children("div").length) &&
                ((a = jQuery(this)),
                    (b = a.position().left),
                    a.outerWidth(),
                    (d = jQuery(".fusion-secondary-header .fusion-row")),
                    (e = d.position().left),
                    (f = d.outerWidth()),
                    a.children("ul").length
                        ? (c = a.children("ul"))
                        : a.children("div").length && (c = a.children("div")),
                    jQuery("body.rtl").length
                        ? c.position().left < b &&
                        (c.css("left", "-1px").css("right", "auto"),
                            c.find(".sub-menu").each(function () {
                                jQuery(this).css("left", "100px").css("right", "auto");
                            }))
                        : b + c.outerWidth() > e + f &&
                        (c.css("right", "-1px").css("left", "auto"),
                            c.find(".sub-menu").each(function () {
                                jQuery(this).css("right", "100px").css("left", "auto");
                            })));
        }),
        jQuery(".fusion-main-menu > ul > li:last-child").addClass(
            "fusion-last-menu-item"
        ),
        jQuery.fn.fusion_position_menu_dropdown &&
        (jQuery(".fusion-dropdown-menu, .fusion-dropdown-menu li").mouseenter(
            function () {
                // BATCH 1 - 1 2.5.3 start
                m7_close_search();
                // BATCH 1 - 1 2.5.3 end
                jQuery(this).fusion_position_menu_dropdown();
            }
        ),
            jQuery(".fusion-dropdown-menu > ul > li").each(function () {
                jQuery(this).walk_through_menu_items();
            }),
            jQuery(window).on("resize", function () {
                jQuery(".fusion-dropdown-menu > ul > li").each(function () {
                    jQuery(this).walk_through_menu_items();
                });
            })),
        jQuery(".fusion-dropdown-menu").mouseenter(function () {
            jQuery(this).css("overflow", "visible");
        }),

        jQuery(
            ".fusion-dropdown-menu, .fusion-megamenu-menu, .fusion-custom-menu-item "
        ).mouseleave(function () {
            jQuery(this).css("overflow", ""),
                jQuery(".fusion-active-link").removeClass("fusion-active-link");
        }),
        jQuery("a").on("focus", function () {
            jQuery(".fusion-active-link ").removeClass("fusion-active-link"),
                jQuery(this).parents(
                    ".fusion-dropdown-menu, .fusion-main-menu-cart, .fusion-megamenu-menu, .fusion-custom-menu-item"
                ).length &&
                (jQuery(this).parents("li").addClass("fusion-active-link"),
                    jQuery(".fusion-main-menu").css("overflow", "visible"));
        }),
        jQuery(document).click(function () {
            jQuery(
                ".fusion-main-menu-search .fusion-custom-menu-item-contents"
            ).hide(),
                jQuery(".fusion-main-menu-search").removeClass(
                    "fusion-main-menu-search-open"
                ),
                jQuery(".fusion-main-menu-search").find("style").remove();
        }),
        jQuery(".fusion-main-menu-search").click(function (a) {
            a.stopPropagation();
        }),
        // BATCH 1 - 2.4.2 / 2.4.3 start
        jQuery(".fusion-bar-highlight").click(function (a) {
            // BATCH 1 - 1 2.5.3 start
            m7_close_search();
            // BATCH 1 - 1 2.5.3 end
            false === jQuery(this).parent().hasClass("menu-item-type-custom") &&
                (a.preventDefault())

        }),
        // BATCH 1 - 2.4.2 / 2.4.3 end
        jQuery(".fusion-main-menu-search .fusion-main-menu-icon").click(function (
            a
        ) {
            a.preventDefault(),
                a.stopPropagation(),
                "block" ===
                    jQuery(this)
                        .parent()
                        .find(".fusion-custom-menu-item-contents")
                        .css("display")
                    ? (jQuery(this)
                        .parent()
                        .find(".fusion-custom-menu-item-contents")
                        .hide(),
                        jQuery(this).parent().removeClass("fusion-main-menu-search-open"),
                        jQuery(this).parent().find("style").remove())
                    : (jQuery(this)
                        .parent()
                        .find(".fusion-custom-menu-item-contents")
                        .removeAttr("style"),
                        jQuery(this)
                            .parent()
                            .find(".fusion-custom-menu-item-contents")
                            .show(),
                        jQuery(this).parent().addClass("fusion-main-menu-search-open"),
                        jQuery(this)
                            .parent()
                            .append(
                                "<style>.fusion-main-menu{overflow:visible!important;</style>"
                            ),
                        jQuery(this)
                            .parent()
                            .find(".fusion-custom-menu-item-contents .s")
                            .focus(),
                        "Top" === avadaMenuVars.header_position &&
                        (!jQuery("body.rtl").length &&
                            0 >
                            jQuery(this)
                                .parent()
                                .find(".fusion-custom-menu-item-contents")
                                .offset().left &&
                            jQuery(this)
                                .parent()
                                .find(".fusion-custom-menu-item-contents")
                                .css({ left: "0", right: "auto" }),
                            jQuery("body.rtl").length &&
                            jQuery(this)
                                .parent()
                                .find(".fusion-custom-menu-item-contents")
                                .offset().left +
                            jQuery(this)
                                .parent()
                                .find(".fusion-custom-menu-item-contents")
                                .width() >
                            jQuery(window).width() &&
                            jQuery(this)
                                .parent()
                                .find(".fusion-custom-menu-item-contents")
                                .css({ left: "auto", right: "0" })
                        ));
        }),
        jQuery.fn.fusion_position_megamenu &&
        (jQuery(".fusion-main-menu > ul").fusion_position_megamenu(),
            jQuery(".fusion-main-menu .fusion-megamenu-menu").mouseenter(function () {
                // BATCH 1 - 1 2.5.3 start
                m7_close_search();
                // BATCH 1 - 1 2.5.3 end
                jQuery(this).parent().fusion_position_megamenu();
            }),
            jQuery(window).resize(function () {
                jQuery(".fusion-main-menu > ul").fusion_position_megamenu();
            }),
            // BATCH 1 - 2.4.1 start
            jQuery(".fusion-main-menu > ul a").on("click", function (e) {
                if (jQuery(e.target).attr("aria-haspopup") || jQuery(e.target).parent().attr("aria-haspopup")) {
                    e.preventDefault();
                }
            }) // BATCH 1 - 2.4.1 end
        )

    jQuery.fn.calc_megamenu_responsive_column_widths &&
        (jQuery(
            ".fusion-main-menu > ul"
        ).calc_megamenu_responsive_column_widths(),
            jQuery(window).resize(function () {
                jQuery(
                    ".fusion-main-menu > ul"
                ).calc_megamenu_responsive_column_widths();
            })),
        jQuery(
            ".fusion-header-wrapper .fusion-secondary-menu > ul > li:last-child"
        ).position_last_top_menu_item(),
        a(".fusion-main-menu .fusion-main-menu-cart"),
        a(".fusion-secondary-menu .fusion-menu-login-box"),
        jQuery(".fusion-megamenu-menu").mouseenter(function () {
            jQuery(this).find(".shortcode-map").length &&
                jQuery(this)
                    .find(".shortcode-map")
                    .each(function () {
                        jQuery(this).reinitializeGoogleMap();
                    });
        }),
        (d = !1),
        jQuery(".fusion-megamenu-menu").mouseover(function () {
            jQuery(this)
                .find(".fusion-megamenu-widgets-container iframe")
                .each(function () {
                    d || jQuery(this).attr("src", jQuery(this).attr("src")), (d = !0);
                });
        }),
        jQuery(".fusion-megamenu-wrapper iframe").mouseover(function () {
            jQuery(this)
                .parents(".fusion-megamenu-widgets-container")
                .css("display", "block"),
                jQuery(this)
                    .parents(".fusion-megamenu-wrapper")
                    .css({ opacity: "1", visibility: "visible" });
        }),
        jQuery(".fusion-megamenu-wrapper iframe").mouseout(function () {
            jQuery(this)
                .parents(".fusion-megamenu-widgets-container")
                .css("display", ""),
                jQuery(this)
                    .parents(".fusion-megamenu-wrapper")
                    .css({ opacity: "", visibility: "" });
        }),
        jQuery(".fusion-main-menu").on(
            "mouseenter",
            ".fusion-menu-cart",
            function () {
                jQuery(this).position_cart_dropdown();
            }
        ),
        jQuery(
            ".fusion-main-menu .fusion-main-menu-search .fusion-main-menu-icon"
        ).click(function () {
            var a = jQuery(this);
            setTimeout(function () {
                a.parent()
                    .find(".fusion-custom-menu-item-contents")
                    .position_menu_search_form();
            }, 5);
        }),
        jQuery(window).on("resize", function () {
            jQuery(
                ".fusion-main-menu .fusion-main-menu-search .fusion-custom-menu-item-contents"
            ).position_menu_search_form();
        }),
        jQuery(".fusion-mobile-nav-holder")
            .not(".fusion-mobile-sticky-nav-holder")
            .each(function () {
                var a = jQuery(this),
                    b = "",
                    c = "",
                    d = "";
                (c = jQuery(".fusion-mobile-navigation").length
                    ? jQuery(this)
                        .parent()
                        .find(".fusion-mobile-navigation, .fusion-secondary-menu")
                        .not(".fusion-sticky-menu")
                    : jQuery(this)
                        .parent()
                        .find(".fusion-main-menu, .fusion-secondary-menu")
                        .not(".fusion-sticky-menu")),
                    c.length &&
                    ("classic" === avadaMenuVars.mobile_menu_design &&
                        (a.append(
                            '<button class="fusion-mobile-selector" aria-expanded="false"><span>' +
                            avadaMenuVars.dropdown_goto +
                            "</span></button>"
                        ),
                            jQuery(this)
                                .find(".fusion-mobile-selector")
                                .append('<div class="fusion-selector-down"></div>')),
                        jQuery(a).append(jQuery(c).find("> ul").clone()),
                        (b = jQuery(a).find("> ul")),
                        (d = b.attr("id")),
                        b.attr("id", "mobile-" + d),
                        b.removeClass("fusion-middle-logo-ul"),
                        "classic" === avadaMenuVars.mobile_menu_design &&
                        a
                            .find(".fusion-mobile-selector")
                            .attr("aria-controls", b.attr("id")),
                        b
                            .find(
                                ".fusion-middle-logo-menu-logo, .fusion-caret, .fusion-menu-login-box .fusion-custom-menu-item-contents, .fusion-menu-cart .fusion-custom-menu-item-contents, .fusion-main-menu-search, li> a > span > .button-icon-divider-left, li > a > span > .button-icon-divider-right, .fusion-arrow-svg, .fusion-dropdown-svg"
                            )
                            .remove(),
                        (jQuery(".no-mobile-slidingbar").length ||
                            "classic" !== avadaMenuVars.mobile_menu_design) &&
                        b.find(".fusion-main-menu-sliding-bar").remove(),
                        "classic" === avadaMenuVars.mobile_menu_design
                            ? b
                                .find(".fusion-menu-cart > a")
                                .html(avadaMenuVars.mobile_nav_cart)
                            : b.find(".fusion-main-menu-cart").remove(),
                        b.find("li").each(function () {
                            var a = "fusion-mobile-nav-item";
                            jQuery(this).data("classes") &&
                                (a += " " + jQuery(this).data("classes")),
                                jQuery(this).find("img").hasClass("wpml-ls-flag") &&
                                (a += " wpml-ls-item"),
                                jQuery(this).hasClass("menu-item-has-children") &&
                                (a += " menu-item-has-children"),
                                jQuery(this)
                                    .find("> a > .menu-text")
                                    .removeAttr("class")
                                    .addClass("menu-text"),
                                (jQuery(this).hasClass("current-menu-item") ||
                                    jQuery(this).hasClass("current-menu-parent") ||
                                    jQuery(this).hasClass("current-menu-ancestor")) &&
                                (a += " fusion-mobile-current-nav-item"),
                                jQuery(this).attr("class", a),
                                jQuery(this).attr("id") &&
                                jQuery(this).attr(
                                    "id",
                                    jQuery(this)
                                        .attr("id")
                                        .replace("menu-item", "mobile-menu-item")
                                ),
                                jQuery(this).attr("style", "");
                        }),
                        jQuery(this)
                            .find(".fusion-mobile-selector")
                            .click(function () {
                                b.hasClass("mobile-menu-expanded")
                                    ? (b.removeClass("mobile-menu-expanded"),
                                        jQuery(this).attr("aria-expanded", "false"))
                                    : (b.addClass("mobile-menu-expanded"),
                                        jQuery(this).attr("aria-expanded", "true")),
                                    b.slideToggle(200, "easeOutQuad"),
                                    jQuery(".fusion-mobile-menu-search").slideToggle(
                                        200,
                                        "easeOutQuad"
                                    );
                            }));
            }),
        jQuery(".fusion-mobile-sticky-nav-holder").each(function () {
            var a = jQuery(this),
                b = "",
                c = jQuery(this).parent().find(".fusion-sticky-menu");
            "classic" === avadaMenuVars.mobile_menu_design &&
                (a.append(
                    '<button class="fusion-mobile-selector" aria-expanded="false"><span>' +
                    avadaMenuVars.dropdown_goto +
                    "</span></button>"
                ),
                    jQuery(this)
                        .find(".fusion-mobile-selector")
                        .append('<div class="fusion-selector-down"></div>')),
                jQuery(a).append(jQuery(c).find("> ul").clone()),
                (b = jQuery(a).find("> ul")),
                "classic" === avadaMenuVars.mobile_menu_design &&
                a.find(".fusion-mobile-selector").attr("aria-controls", b.attr("id")),
                b
                    .find(
                        ".fusion-middle-logo-menu-logo, .fusion-menu-cart, .fusion-menu-login-box, .fusion-main-menu-search, .fusion-arrow-svg, .fusion-dropdown-svg"
                    )
                    .remove(),
                (jQuery(".no-mobile-slidingbar").length ||
                    "classic" !== avadaMenuVars.mobile_menu_design) &&
                b.find(".fusion-main-menu-sliding-bar").remove(),
                b.find(".fusion-button").attr("class", "menu-text"),
                b.find("li").each(function () {
                    var a = "fusion-mobile-nav-item";
                    jQuery(this).data("classes") &&
                        (a += " " + jQuery(this).data("classes")),
                        jQuery(this).find("img").hasClass("wpml-ls-flag") &&
                        (a += " wpml-ls-item"),
                        (jQuery(this).hasClass("current-menu-item") ||
                            jQuery(this).hasClass("current-menu-parent") ||
                            jQuery(this).hasClass("current-menu-ancestor")) &&
                        (a += " fusion-mobile-current-nav-item"),
                        jQuery(this).attr("class", a),
                        jQuery(this).attr("id") &&
                        jQuery(this).attr(
                            "id",
                            jQuery(this).attr("id").replace("menu-item", "mobile-menu-item")
                        ),
                        jQuery(this).attr("style", "");
                }),
                jQuery(this)
                    .find(".fusion-mobile-selector")
                    .click(function () {
                        b.hasClass("mobile-menu-expanded")
                            ? (b.removeClass("mobile-menu-expanded"),
                                jQuery(this).attr("aria-expanded", "false"))
                            : (b.addClass("mobile-menu-expanded"),
                                jQuery(this).attr("aria-expanded", "true")),
                            b.slideToggle(200, "easeOutQuad"),
                            jQuery(".fusion-mobile-menu-search").slideToggle(
                                200,
                                "easeOutQuad"
                            );
                    });
        }),
        jQuery(".fusion-mobile-nav-holder > ul > li").each(function () {
            jQuery(this).find(".fusion-megamenu-widgets-container").remove(),
                jQuery(this)
                    .find(".fusion-megamenu-holder > ul")
                    .each(function () {
                        jQuery(this).attr("class", "sub-menu"),
                            jQuery(this).attr("style", ""),
                            jQuery(this)
                                .find("> li")
                                .each(function () {
                                    var a,
                                        b = "fusion-mobile-nav-item";
                                    jQuery(this).data("classes") &&
                                        (b += " " + jQuery(this).data("classes")),
                                        jQuery(this).find("img").hasClass("wpml-ls-flag") &&
                                        (b += " wpml-ls-item"),
                                        (jQuery(this).hasClass("current-menu-item") ||
                                            jQuery(this).hasClass("current-menu-parent") ||
                                            jQuery(this).hasClass("current-menu-ancestor") ||
                                            jQuery(this).hasClass(
                                                "fusion-mobile-current-nav-item"
                                            )) &&
                                        (b += " fusion-mobile-current-nav-item"),
                                        jQuery(this).hasClass("menu-item-has-children") &&
                                        (b += " menu-item-has-children"),
                                        jQuery(this).attr("class", b),
                                        jQuery(this).find(".fusion-megamenu-title a, > a").length ||
                                        (jQuery(this)
                                            .find(".fusion-megamenu-title")
                                            .each(function () {
                                                jQuery(this).children("a").length ||
                                                    jQuery(this).append(
                                                        '<a href="#">' + jQuery(this).text() + "</a>"
                                                    );
                                            }),
                                            jQuery(this).find(".fusion-megamenu-title").length ||
                                            ((a = jQuery(this)),
                                                jQuery(this)
                                                    .find(".sub-menu")
                                                    .each(function () {
                                                        a.after(jQuery(this));
                                                    }),
                                                jQuery(this).remove())),
                                        jQuery(this).prepend(
                                            jQuery(this).find(".fusion-megamenu-title a, > a")
                                        ),
                                        jQuery(this).find(".fusion-megamenu-title").remove();
                                }),
                            jQuery(this)
                                .closest(".fusion-mobile-nav-item")
                                .append(jQuery(this));
                    }),
                jQuery(this)
                    .find(".fusion-megamenu-wrapper, .caret, .fusion-megamenu-bullet")
                    .remove();
        }),
        jQuery(".fusion-is-sticky").length &&
            jQuery(".fusion-mobile-sticky-nav-holder").length
            ? jQuery(".fusion-mobile-menu-icons .fusion-icon-bars").attr(
                "aria-controls",
                jQuery(".fusion-mobile-sticky-nav-holder > ul").attr("id")
            )
            : jQuery(".fusion-mobile-menu-icons .fusion-icon-bars").attr(
                "aria-controls",
                jQuery(".fusion-mobile-nav-holder")
                    .not(".fusion-mobile-sticky-nav-holder")
                    .find("> ul")
                    .attr("id")
            ),
        jQuery(window).scroll(function () {
            setTimeout(function () {
                jQuery(".fusion-is-sticky").length &&
                    jQuery(".fusion-mobile-sticky-nav-holder").length
                    ? jQuery(".fusion-mobile-menu-icons .fusion-icon-bars").attr(
                        "aria-controls",
                        jQuery(".fusion-mobile-sticky-nav-holder > ul").attr("id")
                    )
                    : jQuery(".fusion-mobile-menu-icons .fusion-icon-bars").attr(
                        "aria-controls",
                        jQuery(".fusion-mobile-nav-holder")
                            .not(".fusion-mobile-sticky-nav-holder")
                            .find("> ul")
                            .attr("id")
                    );
            }, 50);
        }),
        jQuery(".fusion-mobile-menu-icons .fusion-icon-bars").click(function (a) {
            var b, c;
            // BATCH 1 - 1 2.5.3 start
            m7_close_search();
            // BATCH 1 - 1 2.5.3 end
            a.preventDefault(),
                (b =
                    1 <= jQuery(".fusion-header-v4").length ||
                        1 <= jQuery(".fusion-header-v5").length
                        ? ".fusion-secondary-main-menu"
                        : 1 <= jQuery("#side-header").length
                            ? "#side-header"
                            : ".fusion-header"),
                (c =
                    1 <= jQuery(".fusion-is-sticky").length &&
                        1 <= jQuery(".fusion-mobile-sticky-nav-holder").length
                        ? jQuery(b).find(".fusion-mobile-sticky-nav-holder")
                        : jQuery(b)
                            .find(".fusion-mobile-nav-holder")
                            .not(".fusion-mobile-sticky-nav-holder")),
                c.slideToggle(200, "easeOutQuad"),
                c.toggleClass("fusion-mobile-menu-expanded"),
                c.hasClass("fusion-mobile-menu-expanded")
                    ? jQuery(this).attr("aria-expanded", "true")
                    : jQuery(this).attr("aria-expanded", "false");
        }),
        jQuery(".fusion-mobile-menu-icons .fusion-icon-search").click(function (a) {
            a.preventDefault(),
                jQuery(".fusion-mobile-menu-search").slideToggle(200, "easeOutQuad");
            // BATCH 1 - 1 2.5.3 start
            m7_close_nav();
            // BATCH 1 - 1 2.5.3 end
        }),
        jQuery(
            '.fusion-mobile-nav-holder .fusion-mobile-nav-item a:not([href="#"])'
        ).click(function () {
            "" !== jQuery(this.hash).length &&
                this.hash.slice(1) &&
                (jQuery(this).parents(".fusion-mobile-menu-design-classic").length
                    ? (jQuery(this)
                        .parents(".fusion-menu, .menu")
                        .hide()
                        .removeClass("mobile-menu-expanded"),
                        jQuery(".fusion-mobile-menu-search").hide())
                    : jQuery(this).parents(".fusion-mobile-nav-holder").hide());
        }),
        1 == avadaMenuVars.submenu_slideout &&
        "flyout" !== avadaMenuVars.mobile_menu_design &&
        (jQuery(".fusion-mobile-nav-holder > ul li").each(function () {
            var a,
                b,
                c = "fusion-mobile-nav-item",
                d = jQuery(this).find(" > ul");
            jQuery(this).data("classes") &&
                (c += " " + jQuery(this).data("classes")),
                jQuery(this).find("img").hasClass("wpml-ls-flag") &&
                (c += " wpml-ls-item"),
                (jQuery(this).hasClass("current-menu-item") ||
                    jQuery(this).hasClass("current-menu-parent") ||
                    jQuery(this).hasClass("current-menu-ancestor") ||
                    jQuery(this).hasClass("fusion-mobile-current-nav-item")) &&
                (c += " fusion-mobile-current-nav-item"),
                jQuery(this).hasClass("menu-item-has-children") &&
                (c += " menu-item-has-children"),
                jQuery(this).attr("class", c),
                d.length &&
                ((a = jQuery(this).find("> a")),
                    (b =
                        void 0 !== a.attr("title")
                            ? " " + a.attr("title")
                            : " " +
                            a
                                .children(".menu-text")
                                .clone()
                                .children()
                                .remove()
                                .end()
                                .text()),
                    a.after(
                        '<button href="#" aria-label="' +
                        avadaMenuVars.mobile_submenu_open +
                        b +
                        '" aria-expanded="false" class="fusion-open-submenu"></button>'
                    ),
                    // BATCH 1 - 2.4.1 start
                    a.click(function (e) {
                        e.preventDefault();
                        jQuery(this).next(".fusion-open-submenu").trigger("click");
                    }),
                    // BATCH 1 - 2.4.1 end
                    d.hide());
        }),
            jQuery(".fusion-mobile-nav-holder .fusion-open-submenu").click(function (
                a
            ) {
                var b = jQuery(this).parent().children(".sub-menu"),
                    c = jQuery(this).parent().children("a"),
                    d =
                        void 0 !== c.attr("title")
                            ? " " + c.attr("title")
                            : " " +
                            c
                                .children(".menu-text")
                                .clone()
                                .children()
                                .remove()
                                .end()
                                .text();
                a.stopPropagation(),
                    b.slideToggle(200, "easeOutQuad"),
                    b.toggleClass("fusion-sub-menu-open"),
                    b.hasClass("fusion-sub-menu-open")
                        ? (jQuery(this).attr(
                            "aria-label",
                            avadaMenuVars.mobile_submenu_close + d
                        ),
                            jQuery(this).attr("aria-expanded", "true"))
                        : (jQuery(this).attr(
                            "aria-label",
                            avadaMenuVars.mobile_submenu_open + d
                        ),
                            jQuery(this).attr("aria-expanded", "false"));
            }),
            jQuery(".fusion-mobile-nav-holder a").click(function (a) {
                "#" === jQuery(this).attr("href") &&
                    ("modal" === jQuery(this).data("toggle")
                        ? jQuery(this).trigger("show.bs.modal")
                        : (a.preventDefault(), a.stopPropagation()),
                        jQuery(this).next(".fusion-open-submenu").trigger("click"));
            })),
        jQuery(".fusion-flyout-menu-icons .fusion-flyout-menu-toggle").on(
            "click",
            function (a) {
                var d = jQuery(".fusion-header-has-flyout-menu");
                a.preventDefault(),
                    d.hasClass("fusion-flyout-active")
                        ? (d.hasClass("fusion-flyout-search-active")
                            ? (d.addClass("fusion-flyout-menu-active"), b())
                            : (d.removeClass("fusion-flyout-active"),
                                d.removeClass("fusion-flyout-menu-active"),
                                c()),
                            d.removeClass("fusion-flyout-search-active"))
                        : (d.addClass("fusion-flyout-active"),
                            d.addClass("fusion-flyout-menu-active"),
                            b());
            }
        ),
        jQuery(".fusion-flyout-menu-icons .fusion-flyout-search-toggle").on(
            "click",
            function (a) {
                var d = jQuery(".fusion-header-has-flyout-menu");
                a.preventDefault(),
                    d.hasClass("fusion-flyout-active")
                        ? (d.hasClass("fusion-flyout-menu-active")
                            ? (d.addClass("fusion-flyout-search-active"),
                                Modernizr.mq(
                                    "only screen and (min-width:" +
                                    parseInt(avadaMenuVars.side_header_break_point, 10) +
                                    "px)"
                                ) && d.find(".fusion-flyout-search .s").focus())
                            : (d.removeClass("fusion-flyout-active"),
                                d.removeClass("fusion-flyout-search-active"),
                                c()),
                            d.removeClass("fusion-flyout-menu-active"))
                        : (d.addClass("fusion-flyout-active"),
                            d.addClass("fusion-flyout-search-active"),
                            Modernizr.mq(
                                "only screen and (min-width:" +
                                parseInt(avadaMenuVars.side_header_break_point, 10) +
                                "px)"
                            ) && d.find(".fusion-flyout-search .s").focus(),
                            b());
            }
        ),
        jQuery("html").on(
            "mouseenter",
            ".fusion-no-touch .fusion-flyout-menu .menu-item a",
            function () {
                jQuery(this)
                    .parents(".fusion-flyout-menu")
                    .find(
                        ".fusion-flyout-menu-backgrounds #item-bg-" +
                        jQuery(this).parent().data("item-id")
                    )
                    .addClass("active");
            }
        ),
        jQuery("html").on(
            "mouseleave",
            ".fusion-no-touch .fusion-flyout-menu .menu-item a",
            function () {
                jQuery(this)
                    .parents(".fusion-flyout-menu")
                    .find(
                        ".fusion-flyout-menu-backgrounds #item-bg-" +
                        jQuery(this).parent().data("item-id")
                    )
                    .removeClass("active");
            }
        ),
        jQuery(window).resize(function () {
            jQuery(".fusion-mobile-menu-design-flyout").hasClass(
                "fusion-flyout-active"
            ) &&
                Modernizr.mq(
                    "screen and (min-width: " +
                    (parseInt(avadaHeaderVars.side_header_break_point, 10) + 1) +
                    "px)"
                ) &&
                jQuery(".fusion-flyout-menu-icons .fusion-flyout-menu-toggle").trigger(
                    "click"
                );
        });
}),
    jQuery(window).load(function () {
        function a() {
            var a = 0;
            Modernizr.mq(
                "only screen and (max-width: " +
                avadaMenuVars.side_header_break_point +
                "px)"
            )
                ? (jQuery(".fusion-secondary-menu > ul")
                    .children("li")
                    .each(function () {
                        a += jQuery(this).outerWidth(!0) + 2;
                    }),
                    a > jQuery(window).width() && 318 < jQuery(window).width()
                        ? window.mobileMenuSepAdded ||
                        (jQuery(".fusion-secondary-menu > ul").append(
                            '<div class="fusion-mobile-menu-sep"></div>'
                        ),
                            jQuery(".fusion-secondary-menu > ul").css("position", "relative"),
                            jQuery(".fusion-mobile-menu-sep").css({
                                position: "absolute",
                                top:
                                    jQuery(".fusion-secondary-menu > ul > li").height() -
                                    1 +
                                    "px",
                                width: "100%",
                                "border-bottom-width": "1px",
                                "border-bottom-style": "solid",
                            }),
                            (window.mobileMenuSepAdded = !0))
                        : (jQuery(".fusion-secondary-menu > ul").css("position", ""),
                            jQuery(".fusion-secondary-menu > ul")
                                .find(".fusion-mobile-menu-sep")
                                .remove(),
                            (window.mobileMenuSepAdded = !1)))
                : (jQuery(".fusion-secondary-menu > ul").css("position", ""),
                    jQuery(".fusion-secondary-menu > ul")
                        .find(".fusion-mobile-menu-sep")
                        .remove(),
                    (window.mobileMenuSepAdded = !1));
        }
        (window.mobileMenuSepAdded = !1),
            a(),
            "classic" === avadaMenuVars.mobile_menu_design &&
            (jQuery(".sh-mobile-nav-holder").append(
                '<div class="mobile-selector"><span>' +
                avadaMenuVars.dropdown_goto +
                "</span></div>"
            ),
                jQuery(".sh-mobile-nav-holder .mobile-selector").append(
                    '<div class="selector-down"></div>'
                )),
            jQuery(".sh-mobile-nav-holder").append(
                jQuery(".nav-holder .fusion-navbar-nav").clone()
            ),
            jQuery(".sh-mobile-nav-holder .fusion-navbar-nav").attr(
                "id",
                "mobile-nav"
            ),
            jQuery(".sh-mobile-nav-holder ul#mobile-nav").removeClass(
                "fusion-navbar-nav"
            ),
            jQuery(".sh-mobile-nav-holder ul#mobile-nav").children(".cart").remove(),
            jQuery(".sh-mobile-nav-holder ul#mobile-nav .mobile-nav-item")
                .children(".login-box")
                .remove(),
            jQuery(".sh-mobile-nav-holder ul#mobile-nav li")
                .children("#main-nav-search-link")
                .each(function () {
                    jQuery(this).parents("li").remove();
                }),
            jQuery(".sh-mobile-nav-holder ul#mobile-nav")
                .find("li")
                .each(function () {
                    var a = "mobile-nav-item";
                    (jQuery(this).hasClass("current-menu-item") ||
                        jQuery(this).hasClass("current-menu-parent") ||
                        jQuery(this).hasClass("current-menu-ancestor")) &&
                        (a += " mobile-current-nav-item"),
                        jQuery(this).attr("class", a),
                        jQuery(this).attr("id") &&
                        jQuery(this).attr(
                            "id",
                            jQuery(this).attr("id").replace("menu-item", "mobile-menu-item")
                        ),
                        jQuery(this).attr("style", "");
                }),
            jQuery(".sh-mobile-nav-holder .mobile-selector").click(function () {
                jQuery(".sh-mobile-nav-holder #mobile-nav").hasClass(
                    "mobile-menu-expanded"
                )
                    ? jQuery(".sh-mobile-nav-holder #mobile-nav").removeClass(
                        "mobile-menu-expanded"
                    )
                    : jQuery(".sh-mobile-nav-holder #mobile-nav").addClass(
                        "mobile-menu-expanded"
                    ),
                    jQuery(".sh-mobile-nav-holder #mobile-nav").slideToggle(
                        200,
                        "easeOutQuad"
                    );
            }),
            1 == avadaMenuVars.submenu_slideout &&
            (jQuery(
                ".header-wrapper .mobile-topnav-holder .mobile-topnav li, .header-wrapper .mobile-nav-holder .navigation li, .sticky-header .mobile-nav-holder .navigation li, .sh-mobile-nav-holder .navigation li"
            ).each(function () {
                var a = "mobile-nav-item";
                (jQuery(this).hasClass("current-menu-item") ||
                    jQuery(this).hasClass("current-menu-parent") ||
                    jQuery(this).hasClass("current-menu-ancestor") ||
                    jQuery(this).hasClass("mobile-current-nav-item")) &&
                    (a += " mobile-current-nav-item"),
                    jQuery(this).attr("class", a),
                    jQuery(this).find(" > ul").length &&
                    (jQuery(this).prepend(
                        '<span href="#" aria-haspopup="true" class="open-submenu"></span>'
                    ),
                        jQuery(this).find(" > ul").hide());
            }),
                jQuery(
                    ".header-wrapper .mobile-topnav-holder .open-submenu, .header-wrapper .mobile-nav-holder .open-submenu, .sticky-header .mobile-nav-holder .open-submenu, .sh-mobile-nav-holder .open-submenu"
                ).click(function (a) {
                    a.stopPropagation(),
                        jQuery(this)
                            .parent()
                            .children(".sub-menu")
                            .slideToggle(200, "easeOutQuad");
                })),
            ("ontouchstart" in document.documentElement ||
                navigator.msMaxTouchPoints) &&
            (jQuery(
                ".fusion-main-menu li.menu-item-has-children > a, .fusion-secondary-menu li.menu-item-has-children > a, .order-dropdown > li .current-li"
            ).on("click", function () {
                var a = jQuery(this);
                return a.hasClass("hover")
                    ? (a.removeClass("hover"), !0)
                    : (a.addClass("hover"),
                        jQuery(
                            ".fusion-main-menu li.menu-item-has-children > a, .fusion-secondary-menu li.menu-item-has-children > a, .order-dropdown > li .current-li"
                        )
                            .not(this)
                            .removeClass("hover"),
                        !1);
            }),
                jQuery(".sub-menu li, .fusion-mobile-nav-item li")
                    .not("li.menu-item-has-children")
                    .on("click", function () {
                        var a = jQuery(this).find("a").attr("href");
                        return (
                            "_blank" !== jQuery(this).find("a").attr("target") &&
                            (0 < a.indexOf("#") &&
                                (a =
                                    "/" === a.charAt(a.indexOf("#") - 1)
                                        ? a.replace("#", "#_")
                                        : a.replace("#", "/#_")),
                                (window.location = a)),
                            !0
                        );
                    })),
            jQuery(
                ".fusion-main-menu li.menu-item-has-children > a, .fusion-secondary-menu li.menu-item-has-children > a, .side-nav li.page_item_has_children > a"
            ).each(function () {
                jQuery(this).attr("aria-haspopup", "true");
            }),
            1 <= jQuery(".megaResponsive").length &&
            jQuery(".mobile-nav-holder.main-menu").addClass("set-invisible"),
            "Top" === avadaMenuVars.header_position &&
            jQuery(window).on("resize", function () {
                jQuery(".main-nav-search").each(function () {
                    var a, b, c, d, e, f;
                    jQuery(this).hasClass("search-box-open") &&
                        ((a = jQuery(this).find(".main-nav-search-form")),
                            (b = a.outerWidth()),
                            (c = a.offset().left),
                            (d = c + b),
                            (e = a.parent().offset().left),
                            (f = jQuery(window).width()),
                            jQuery("body.rtl").length
                                ? (c == e && d > f) || (c < e && d + b > f)
                                    ? a.css({ left: "auto", right: "0" })
                                    : a.css({ left: "0", right: "auto" })
                                : (c < e && 0 > c) || (c == e && 0 > c - b)
                                    ? a.css({ left: "0", right: "auto" })
                                    : a.css({ left: "auto", right: "0" }));
                });
            }),
            jQuery(window).on("resize", function () {
                a();
            }),
            jQuery(".fusion-custom-menu-item").on("hover", function (a) {
                var b = jQuery(this),
                    c = b.find(".fusion-custom-menu-item-contents");
                c.find("input").click(function () {
                    b.addClass("fusion-active-login"),
                        b.closest(".fusion-main-menu").css("overflow", "visible");
                }),
                    c.find("input").on("change", function (a) {
                        b.hasClass("fusion-active-login") &&
                            (b
                                .removeClass("fusion-active-login")
                                .addClass("fusion-active-link"),
                                b.closest(".fusion-main-menu").css("overflow", ""));
                    });
            }),
            jQuery(document).click(function (a) {
                "fusion-custom-menu-item-contents" !== a.target.className &&
                    "input-text" !== a.target.className &&
                    (jQuery(".fusion-custom-menu-item-contents")
                        .parents(".fusion-custom-menu-item")
                        .removeClass("fusion-active-login")
                        .removeClass("fusion-active-link"),
                        jQuery(".fusion-main-menu").css("overflow", ""));
            });
    });
