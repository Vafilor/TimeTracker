import { Controller } from '@hotwired/stimulus';
import { TagApi } from "../ts/core/api/tag_api";
import { useFlash } from "../use-flash/use-flash";
import { createRemovableTag } from "../ts/components/tags";

export default class SyncTagListController extends Controller {
    static values = {
        addUrl: String,
        removeUrl: String,
        autocompleteId: String
    }

    #removeUrlFormatted;

    removeUrlValueChanged() {
        // Format removeUrl value
        const encodedValue = encodeURIComponent('{NAME}');
        const index = this.removeUrlValue.indexOf(encodedValue);

        // index -1 to also remove trailing slash
        this.#removeUrlFormatted = this.removeUrlValue.substr(0, index - 1);
    }

    connect() {
        useFlash(this);
        if (this.hasAutocompleteIdValue) {
            this.requestAddFromAutocomplete = this.requestAddFromAutocomplete.bind(this);
            document.getElementById(this.autocompleteIdValue).addEventListener('autocomplete.change', this.requestAddFromAutocomplete);
        }
    }

    disconnect() {
        if (this.hasAutocompleteIdValue) {
            const element = document.getElementById(this.autocompleteIdValue);
            if (element) {
                element.removeEventListener('autocomplete.change', this.requestAddFromAutocomplete);
            }
        }
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

    async addTag(name, color) {
        const existingElement = this.element.querySelector(`[data-removable-tag-name-value="${name}"]`);
        if (existingElement) {
            existingElement.classList.add('tag-exists');
            setTimeout(() => {
                existingElement.classList.remove('tag-exists');
            }, 1000);

            return;
        }

        const newElement = createRemovableTag(name, color);
        newElement.classList.add('pending');
        this.element.appendChild(newElement);

        try {
            await TagApi.addTagToResource(this.addUrlValue, name);
            newElement.classList.remove('pending');
        } catch (e) {
            this.flash({
                type: 'danger',
                message: 'Unable to add tag'
            });

            newElement.remove();
        }
    }

    async removeTag(event) {
        const { name } = event.detail;

        event.target.classList.add('pending');

        try {
            await TagApi.removeTagFromResource(this.#removeUrlFormatted, name);
            event.target.remove();
        } catch (e) {
            this.flash({
                type: 'danger',
                message: 'Unable to remove tag'
            });

            event.target.classList.remove('pending');
        }
    }
}