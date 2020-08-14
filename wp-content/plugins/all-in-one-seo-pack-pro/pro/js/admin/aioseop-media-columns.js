var aioseopMediaColumns;

jQuery(function ($) {
    "use strict";

    aioseopMediaColumns = {

        /**
         * Initializes the code.
         * 
         * @since   3.4.0
         * 
         * @return  void
         */
        init: function () {
            this.addTooltipText();
        },

        /**
         * Adds the help text for our tooltips.
         * 
         * @since   3.4.0
         * 
         * @return  void
         */
        addTooltipText: function () {
            for (let key in aioseopMediaColumnsData.i18n) {
                $(`.column-${key} .aioseop-media-lib-tooltip-text`).each(function () {
                    $(this).text(aioseopMediaColumnsData.i18n[key]);
                })
            }
        },

        /**
         * Updates the post title for an image.
         * 
         * @since   3.4.0
         * 
         * @param   Integer     postId      The ID of the image. 
         * @param   String      value       The value of the attribute.
         */
        updatePostTitle: function (postId, value) {
            if ('' === value) {
                value = aioseopMediaColumnsData.i18n.noTitle;
            }

            let postTitle = $(`#post-${postId} .title strong a`).first();
            // We need to clone the thumbnail because it otherwise gets deleted.
            let span = jQuery.extend(true, {}, postTitle.find('span').first());

            postTitle.text(value)
            postTitle.prepend(span);
        },
    }

    aioseopMediaColumns.init();
});