import $ from 'jquery';

export default class Flashes {
    constructor(private $container: JQuery) {
    }

    create(label, message) {
        return `<div class="alert alert-${label} alert-dismissible fade show flash">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>`;
    }

    prepend(label: string, message: string): JQuery {
        const $flash = $(this.create(label, message));

        this.$container.prepend($flash);

        return $flash;
    }

    append(label: string, message: string, autoDismiss: boolean = false): JQuery {
        const $flash = $(this.create(label, message));

        this.$container.append($flash);

        if(autoDismiss) {
            setTimeout(() => {
                $flash.remove();
            }, 3000)
        }

        return $flash;
    }
}