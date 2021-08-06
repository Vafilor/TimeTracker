import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        key: String
    }

    setColor(event) {
        const { value, key } = event.detail;

        // Only change the color value if the event key matches.
        if (this.keyValue !== key) {
            return;
        }

        this.element.style.color = value;
    }
}