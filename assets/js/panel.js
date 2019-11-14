
require('../scss/panel.scss');
require("font-awesome-webpack");

const $ = require('jquery');
global.$ = global.jQuery = $;

// bootstrap
require('bootstrap');

require('@coreui/coreui');

require('./jsdata');
// delete confirmations etc.
require('sweetalert');

const toastr = require('toastr');
toastr.options = {
    "closeButton": true,
    "newestOnTop": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "300",
    "timeOut": "5000",
    "extendedTimeOut": "5000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};
global.toastr = toastr;


$(document).ready(function () {
    $(".js-goto").click(function () {
        var $btn = $(this);
        var $form = $btn.parents('form');
        $('<input>').attr({
            type: 'hidden',
            name: 'goto',
            value: $btn.attr('data-goto')
        }).appendTo($form);
    });
});