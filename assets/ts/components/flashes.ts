import $ from 'jquery';

export default class Flashes {
    constructor(private $container: JQuery) {
    }

    create(label: string, message: string): string {
        return `<div class="alert alert-${label} hide-top alert-dismissible fade show flash">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
        </div>`;
    }

    createWithLink(label: string, message: string, url: string, urlText: string): string {
        return `<div class="alert alert-${label} alert-dismissible fade show flash">
            ${message}
            
            <a href="${url}">${urlText}</a>
            
            <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close">
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

        requestAnimationFrame(() => {
            $flash.addClass('unhide-top');
        })

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