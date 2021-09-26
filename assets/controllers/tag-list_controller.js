import { Controller } from 'stimulus';
import { createRemovableTag } from "../ts/components/tags";

export default class extends Controller {
    static targets = ['hidden'];

    #updateHiddenElement() {
        const tags = this.element.querySelectorAll(`[data-controller="removable-tag"]`);
        if (tags.length === 0) {
            this.hiddenTarget.value = null;
            return;
        }

        let csv = tags[0].dataset.removableTagNameValue;
        for(let i = 1; i < tags.length; i++) {
            csv += ',' + tags[i].dataset.removableTagNameValue;
        }

        this.hiddenTarget.value = csv;
    }

    requestAddFromAutocomplete(event) {
        const { type, value, object } = event.detail;
        if (type !== 'tag') {
            return;
        }

        if (object) {
            const { name, color } = object;
            this.addTag(name, color);
        } else {
            this.addTag(value, '#5d5d5d');
        }
    }

    addTag(name, color) {
        const existingElement = this.element.querySelector(`[data-removable-tag-name-value="${name}"]`);
        if (existingElement) {
            existingElement.classList.add('tag-exists');
            setTimeout(() => {
                existingElement.classList.remove('tag-exists');
            }, 1000);

            return;
        }

        const newElement = createRemovableTag(name, color);
        this.element.appendChild(newElement);

        this.#updateHiddenElement();
    }

    removeTag(event) {
        event.target.remove();
        this.#updateHiddenElement();
    }
}