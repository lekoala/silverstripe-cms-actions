/* global $, window, document, jQuery */

/**
 * Custom admin tweaks
 */
(function ($) {
    $.entwine("ss", function ($) {
        // Load tab if set in url
        var tabLoaded = false;
        $("ul.ui-tabs-nav a").entwine({
            onmatch: function () {
                this._super();

                if (tabLoaded) {
                    return;
                }

                // Load any tab if specified
                var url = this.attr("href"),
                    hash = url.split("#")[1];

                if (window.location.hash) {
                    var currHash = window.location.hash.substring(1);
                    if (currHash == hash) {
                        this.trigger("click");
                        tabLoaded = true;
                    }
                }
            },
            onclick: function () {
                var input = $("#js-form-active-tab");
                if (!input.length) {
                    // Add an input that track active tab
                    input = $(
                        '<input type="hidden" name="_activetab" class="no-change-track" id="js-form-active-tab" />'
                    );
                    $("#Form_ItemEditForm").append(input);
                }
                var url = this.attr("href");
                var split = url.split("#");
                var hash = split[1];

                // Replace state without changing history (because it would break back functionnality)
                window.history.replaceState(undefined, undefined, url);

                input.val(hash);
            },
        });

        // Prevent navigation for no ajax, otherwise it triggers the action AND navigate to edit form
        $(".grid-field__icon-action.no-ajax,.custom-link.no-ajax").entwine({
            onmatch: function () {},
            onunmatch: function () {},
            onclick: function (e) {
                if (this.attr("target") == "_blank") {
                    // Maybe not necessary?
                    e.stopPropagation();
                } else {
                    // Prevent ajax submission
                    e.preventDefault();

                    // This will update history
                    document.location.href = this.attr("href");
                }
            },
        });
    });
})(jQuery);
