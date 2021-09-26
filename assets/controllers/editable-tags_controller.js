import { Controller } from 'stimulus';
import { createRemovableTag, createTag } from "../ts/components/tags";

export default class extends Controller {
    static targets = ['viewTags', 'editButton', 'stopEditButton', 'autocomplete', 'editableTagList'];

    #editing = false;

    edit() {
        if (this.#editing) {
            return;
        }

        const tags = this.#getTags();
        this.#editing = true;

        this.editableTagListTarget.innerHTML = '';

        for (const tag of tags) {
            const newTagElement = createRemovableTag(tag.name, tag.color);
            this.editableTagListTarget.appendChild(newTagElement);
        }

        this.viewTagsTarget.classList.add('d-none');
        this.editableTagListTarget.classList.remove('d-none');
        this.autocompleteTarget.classList.remove('d-none');
        this.editButtonTarget.classList.add('d-none');
        this.stopEditButtonTarget.classList.remove('d-none');
    }

    stopEditing() {
        if (!this.#editing) {
            return;
        }

        const tags = this.#getTags();
        this.#editing = false;

        this.editableTagListTarget.classList.add('d-none');
        this.autocompleteTarget.classList.add('d-none');

        this.viewTagsTarget.innerHTML = '';
        for(const tag of tags) {
            this.viewTagsTarget.appendChild(createTag(tag.name, tag.color));
        }

        this.editButtonTarget.classList.remove('d-none');
        this.stopEditButtonTarget.classList.add('d-none');
        this.viewTagsTarget.classList.remove('d-none');
    }

    /**
     * Goes through the tags and returns an array of their name and color.
     *
     * @return {{name: string, color: string}[]}
     */
    #getTags() {
        const tags = [];

        if (this.#editing) {
            for (const tagView of this.editableTagListTarget.querySelectorAll('[data-removable-tag-name-value]')) {
                tags.push({
                    name: tagView.dataset.removableTagNameValue,
                    color: tagView.dataset.removableTagColorValue
                });
            }
        } else {
            for (const tagView of this.viewTagsTarget.querySelectorAll('[data-name]')) {
                tags.push({
                    name: tagView.dataset.name,
                    color: tagView.dataset.color
                });
            }
        }

        return tags;
    }
}