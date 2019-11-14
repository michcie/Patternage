
module.exports = (function ($) {
    var jsdata = jsdata || {};

    var actionHandler = [];
    var selectionChange = [];

    jsdata.actionHandler = function (actionHandlerFunc) {
        actionHandler.push(actionHandlerFunc);
    };
    jsdata.selectionChange = function (selectionChangeFunc) {
        selectionChange.push(selectionChangeFunc);
    };

    var recalculate = function ($table) {
        var $selectedCheckbox = $table.find(".js-data-row-checkbox:checked");

        var selected = $selectedCheckbox.length;
        $table.find(".js-data-selected-count").html(selected);
        if (selected > 0) {
            $table.find(".js-data-row-actions").show();
        } else {
            $table.find(".js-data-row-actions").hide();
        }

        var $selectedIds = [];
        $selectedCheckbox.each(function () {
            $selectedIds.push($(this).data('id'));
        });

        $table.find(".js-data-selected-action").attr('data-ids', $selectedIds.join());

        selectionChange.forEach(function (f) {
            f($table, $selectedIds.join());
        });
    };

    jsdata.init = function () {

        $(".js-data-row").click(function (event) {
            var nodeName = event.target.nodeName;
            if(nodeName == "I" || nodeName == "A" || nodeName == "INPUT" || nodeName == "BUTTON") {
                return;
            }
            $(this).find('.js-data-row-checkbox').click();
        });

        $(".js-data-action").click(function (event) {
            var $action = $(this);
            var $table = $action.parents('table');
            var ids = $action.attr('data-ids');
            var newTab = $action.attr('target') == "_blank" || event.metaKey;

            var redirectUrl = undefined;
            actionHandler.forEach(function (f) {
                var x = f($action, $table, ids, newTab);
                if(x !== undefined) {
                    redirectUrl = x;
                }
            });

            if(redirectUrl != undefined) {
                if(newTab) {
                    $("<a>").attr("href", redirectUrl).attr("target", "_blank")[0].click();
                } else {
                    window.location.href = redirectUrl;
                }
            }
        });

        $(".js-data-row-checkbox-all").click(function (e) {
            var $checkbox = $(this);
            var $table = $checkbox.parents('table');
            var $selectAllCheckbox = $table.find(".js-data-row-checkbox-all");

            if ($checkbox.is(":checked")) {
                $selectAllCheckbox.prop('checked', true);
                $table.find(".js-data-row-checkbox").prop('checked', true);
            } else {
                $selectAllCheckbox.prop('checked', false);
                $table.find(".js-data-row-checkbox").prop('checked', false);
            }
            recalculate($table);
        });

        $(".js-data-row-checkbox").click(function (e) {
            var $checkbox = $(this);
            var $table = $checkbox.parents('table');
            var $selectAllCheckbox = $table.find(".js-data-row-checkbox-all");

            var cl = $table.find(".js-data-row-checkbox:checked").length;
            if (cl == $table.find(".js-data-row-checkbox").length) {
                $selectAllCheckbox.prop('checked', true);
                $selectAllCheckbox.prop('indeterminate', false);
            } else if (cl > 0) {
                $selectAllCheckbox.prop('checked', false);
                $selectAllCheckbox.prop('indeterminate', true);
            } else {
                $selectAllCheckbox.prop('checked', false);
                $selectAllCheckbox.prop('indeterminate', false);
            }
            recalculate($table);
        });

        $("table").each(function () {
            recalculate($(this));
        });
    };

    return jsdata;
})($);

// register as global
global.jsdata = module.exports;