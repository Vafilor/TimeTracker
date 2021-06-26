import '../styles/statistic.scss';

import $ from "jquery";

$(document).ready(() => {
    const $previewContainer = $('.js-statistic-icon-preview');

    $('.js-color').on('input', (event) => {
        const hexColor = $(event.currentTarget).val() as string;

        $previewContainer.css('color', hexColor);
    })

    let timeout: any = undefined;


    $('.js-icon').on('input', (event) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const val = $(event.currentTarget).val() as string;

            $previewContainer.html(`<i class="${val}"></i>`);
        }, 300);
    })
});