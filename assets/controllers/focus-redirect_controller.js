import { Controller } from 'stimulus';

/**
 * Listens to click events and focuses another element when they happen.
 */
export default class extends Controller {
    static values = {
        targetId: String // The id of the element to focus.
    }

    connect() {
        this.onElementClick = this.onElementClick.bind(this);

        this.element.addEventListener('click', this.onElementClick);
    }

    disconnect() {
        this.element.removeEventListener('click', this.onElementClick);
    }

    onElementClick() {
        const target = document.getElementById(this.targetIdValue);
        if (target) {
            target.focus();
        }
    }
}