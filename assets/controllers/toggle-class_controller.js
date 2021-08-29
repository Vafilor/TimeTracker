import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        key: String,
        className: String
    }

    add(event) {
        if (this.hasKeyValue && event.detail.key !== this.keyValue) {
            return;
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