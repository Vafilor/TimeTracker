/**
 * Utility class to help with loading buttons in UI.
 *
 * Each assumes that the container has a loading indicator with a class of 'js-loading' inside it that is
 * initially not displayed.
 */
export default class LoadingButton {
    constructor(public $container: JQuery) {
    }

    startLoading() {
        this.$container.attr('disabled', 'true');
        this.$container.find('.js-loading').toggleClass('d-none');
    }

    stopLoading() {
        this.$container.removeAttr('disabled');
        this.$container.find('.js-loading').toggleClass('d-none');
    }
}