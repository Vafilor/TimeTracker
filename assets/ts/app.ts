/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import $ from 'jquery';
import 'bootstrap'; // Adds functions to jQuery
import '@fortawesome/fontawesome-free/js/solid';
import '@fortawesome/fontawesome-free/js/brands';
import '@fortawesome/fontawesome-free/js/regular';
import '@fortawesome/fontawesome-free/js/fontawesome';

// any CSS you import will output into a single css file (app.css in this case)
import '../styles/app.scss';

// start the Stimulus application

$(document).ready(() => {
    // @ts-ignore
    $('[data-toggle="tooltip"]').tooltip();
    $('[data-toggle="popover"]').popover();

    $('.popover-dismiss').popover({
        trigger: 'focus'
    })

    $('.js-clear-datetime').on('click', (event) => {
        const $parent = $(event.currentTarget).parent();

        $parent.find('input').val('');
    })
});

