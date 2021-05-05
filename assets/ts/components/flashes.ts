import $ from 'jquery';

export default class Flashes {
    constructor(private $container: JQuery) {
    }

    create(label: string, message: string): string {
        return `<div class="alert alert-${label} alert-dismissible fade show flash">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>`;
    }

    createWithLink(label: string, message: string, url: string, urlText: string): string {
        return `<div class="alert alert-${label} alert-dismissible fade show flash">
            ${message}
            
            <a href="${url}">${urlText}</a>
            
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

    appendWithLink(label: string, message: string, url: string, urlText: string, autoDismiss: boolean = false): JQuery {
        const $flash = $(this.createWithLink(label, message, url, urlText));

        this.$container.append($flash);

        if(autoDismiss) {
            setTimeout(() => {
                $flash.remove();
            }, 3000)
        }

        return $flash;
    }
}