import { Controller } from 'stimulus';
import { createRemovableTag, createTag } from "../ts/components/tags";

export default class extends Controller {
    static targets = [
        'editableTagList',
        'editContainer',
        'editButton',
        'viewContainer'
    ];

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

        this.viewContainerTarget.classList.add('d-none');
        this.editContainerTarget.classList.remove('d-none');
    }

    stopEditing() {
        if (!this.#editing) {
            return;
        }


        const tags = this.#getTags();
        this.#editing = false;

        // Remove all existing tags
        for (const existingTagElement of this.viewContainerTarget.querySelectorAll('.tag')) {
            existingTagElement.remove();
        }
        // Remove the no-tags element, if it exists.
        const noTagsElement = this.viewContainerTarget.querySelector('[data-type=no-data]');
        if (noTagsElement) {
            noTagsElement.remove();
        }

        for(const tag of tags) {
            this.viewContainerTarget.insertBefore(createTag(tag.name, tag.color), this.editButtonTarget);
        }

        if (tags.length === 0) {
            const noTagsElement = document.createElement('span');
            noTagsElement.textContent = 'No tags';
            noTagsElement.dataset.type = 'no-data';
            this.viewContainerTarget.insertBefore(noTagsElement, this.editButtonTarget);
        }

        this.viewContainerTarget.classList.remove('d-none');
        this.editContainerTarget.classList.add('d-none');
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
            for (const tagView of this.viewContainerTarget.querySelectorAll('[data-name]')) {
                tags.push({
                    name: tagView.dataset.name,
                    color: tagView.dataset.color
                });
            }
        }

        return tags;
    }
}