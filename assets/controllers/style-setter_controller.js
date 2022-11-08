import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        key: String,
        property: String,
    }

    setProperty(event) {
        const { value, key } = event.detail;

        // Only change the color value if the event key matches.
        if (this.keyValue !== key) {
            return;
        }

        this.element.style[this.propertyValue] = value;
    }
}