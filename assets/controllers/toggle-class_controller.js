import { Controller } from '@hotwired/stimulus';
import { useFlash } from "../use-flash/use-flash";

export default class extends Controller {
    static values = {
        key: String,
        className: String
    }

    connect() {
        useFlash(this);
    }

    add(event) {
        if (this.hasKeyValue && event.detail.key !== this.keyValue) {
            return;
        }

        if ('failure' === event.detail.status) {
            this.flash({
                type: 'danger',
                message: "Unable to update"
            });
        }

        this.element.classList.add(this.classNameValue);
    }

    remove(event) {
        if (this.hasKeyValue && event.detail.key !== this.keyValue) {
            return;
        }

        this.element.classList.remove(this.classNameValue);
    }
}