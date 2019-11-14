
require('../scss/shop.scss');
require("font-awesome-webpack");

const $ = require('jquery');
global.$ = global.jQuery = $;

require('../js/minicart.js');

require('bootstrap');

toastr
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
