import $ from 'jquery';

import '../styles/tag.scss';

$(document).ready(() => {
    // Set up the preview to change color when the form input does
    const $colorPreview = $('.js-tag-preview');
    $('.js-tag-color').on('input', (event) => {
        const hexColor = $(event.currentTarget).val() as string;

        $colorPreview.css('background-color', hexColor);
    })
});