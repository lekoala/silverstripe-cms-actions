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

        // Allow posting from CmsInlineFormAction
        $("button.inline-action[data-action]").entwine({
            onclick: function (e) {
                e.preventDefault();
                var form = this.parents("form");
                var action = form.attr("action");
                form.attr("action", this.data("action"));

                // somehow this does nothing?
                // form.submit();

                $('#Form_ItemEditForm_action_doSave').click();
                form.attr("action", action);
            },
        });

        // Handle progressive actions
        function progressiveCall(inst, url, formData) {
            $.ajax({
                headers: { "X-Progressive": 1 },
                type: "POST",
                data: formData,
                url: url,
                dataType: "json",
                success: function (data) {
                    // Progress can return messages
                    if (data.message) {
                        jQuery.noticeAdd({
                            text: data.message,
                            stayTime: 1000,
                            inEffect: { left: "0", opacity: "show" },
                        });
                    }
                    // It's finished!
                    if (data.progress_step >= data.progress_total) {
                        if (!data.label) {
                            data.label = "Completed";
                        }
                        inst.find("span").text(data.label);
                        inst.find(".btn__progress").remove();

                        if (data.reload) {
                            window.location.reload();
                        }
                        return;
                    }
                    // Update progress data
                    if (data.progress_step) {
                        formData["progress_step"] = data.progress_step;
                    }
                    if (data.progress_total) {
                        formData["progress_total"] = data.progress_total;
                    }
                    if (data.progress_id) {
                        formData["progress_id"] = data.progress_id;
                    }
                    if (data.progress_data) {
                        formData["progress_data"] = data.progress_data;
                    }
                    // Update UI
                    if (data.progress_step && data.progress_total) {
                        var perc = Math.round(
                            (data.progress_step / data.progress_total) * 100
                        );
                        inst.find("span").text(perc + "%");
                        inst.find(".btn__progress").css("width", perc);
                    }
                    progressiveCall(inst, url, formData);
                },
                error: function (e) {
                    inst.find("span").text("Failed");
                    console.error("Invalid response");
                },
            });
        }
        $(".progressive-action").entwine({
            onclick: function (e) {
                e.preventDefault();

                if (this.hasClass("disabled")) {
                    return;
                }

                if (this.hasClass("confirm")) {
                    var confirmed = confirm($(this).data("message"));
                    if (!confirmed) {
                        return;
                    }
                }

                var url = this.data("url");
                if (!url) {
                    url = this.attr("href");
                }
                var form = this.closest("form");
                var formData = {};
                var csrf = form.find('input[name="SecurityID"]').val();
                // Add csrf
                if (csrf) {
                    formData["SecurityID"] = csrf;
                }

                // Add current button
                formData[this.attr("name")] = this.val();

                // And step
                formData["progress_step"] = 0;

                // Total can be preset
                if (
                    typeof this.data("progress-total") !== "undefined" &&
                    this.data("progress-total") !== null
                ) {
                    formData["progress_total"] = this.data("progress-total");
                }

                // Cosmetic things
                this.addClass("disabled");
                if (!this.find("span").length) {
                    this.html("<span>" + this.text() + "</span>");
                }
                this.css("width", this.outerWidth());
                this.find("span").text("Please wait");
                this.append('<div class="btn__progress"></div>');

                progressiveCall(this, url, formData);
                return false;
            },
        });
    });
})(jQuery);
