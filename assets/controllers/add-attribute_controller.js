import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static values = {
        name: String,
        value: String,
        delay: Number,
    }

    #addAttribute() {
        this.element.setAttribute(this.nameValue, this.valueValue || "");
    }

    connect() {
        if (!this.hasDelayValue || this.delayValue <= 0) {
            this.#addAttribute();
        } else {
            setTimeout(() => this.#addAttribute(), this.delayValue);
        }
    }
}