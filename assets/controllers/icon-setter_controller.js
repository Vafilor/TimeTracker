import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        key: String
    }

    setIcon(event) {
        const { value, key } = event.detail;

        // Only change the value if the event key matches.
        if (this.keyValue !== key) {
            return;
        }

        this.element.innerHTML = `<i class="${value}"></i>`;
    }
}