import { Controller } from '@hotwired/stimulus';
import { createRemovableTag } from "../ts/components/tags";
import { useDispatch } from "stimulus-use";

export default class extends Controller {
    static targets = ['hidden', 'tags'];
    static values = {
        autocompleteId: String
    }

    connect() {
        if (this.hasAutocompleteIdValue) {
            this.requestAddFromAutocomplete = this.requestAddFromAutocomplete.bind(this);
            document.getElementById(this.autocompleteIdValue).addEventListener('autocomplete.change', this.requestAddFromAutocomplete);
        }

        useDispatch(this);
    }

    disconnect() {
        if (this.hasAutocompleteIdValue) {
            const element = document.getElementById(this.autocompleteIdValue);
            if (element) {
                element.removeEventListener('autocomplete.change', this.requestAddFromAutocomplete);
            }
        }
    }

    #updateHiddenElement() {
        const tags = this.element.querySelectorAll(`[data-controller="removable-tag"]`);
        let value = null;

        if (tags.length !== 0) {
            let csv = tags[0].dataset.removableTagNameValue;
            for (let i = 1; i < tags.length; i++) {
                csv += ',' + tags[i].dataset.removableTagNameValue;
            }

            value = csv;
        }

        this.hiddenTarget.value = value;

        this.dispatch('change', {
            value
        });
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
        if (this.hasTagsTarget) {
            this.tagsTarget.appendChild(newElement);
        } else {
            this.element.appendChild(newElement);
        }

        this.#updateHiddenElement();
    }

    removeTag(event) {
        event.target.remove();
        this.#updateHiddenElement();
    }
}