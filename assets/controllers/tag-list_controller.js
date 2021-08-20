import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['hidden'];

    static values = {
        addUrl: String,
        removeUrl: String,
        tags: Array, // json { name, color },
        tagsCsv: String // comma separated tag names. No color information.
    };

    connect() {
        if (this.hasRemoveUrlValue) {
            const encodedValue = encodeURIComponent('{NAME}');
            const index = this.removeUrlValue.indexOf(encodedValue);

            // index -1 to also remove trailing slash
            this.removeUrlValue = this.removeUrlValue.substr(0, index - 1);
        }

        if (this.tagsValue.length === 0 && this.tagsCsvValue !== '') {
            for(const tagName of this.tagsCsvValue.split(',')) {
                this.addExisting(tagName, '#5d5d5d');
            }
        } else if(this.tagsValue.length !== 0) {
            for(const tag of this.tagsValue) {
                this.addExisting(tag.name, tag.color);
            }
        }
    }

    tagsValueChanged() {
        if (this.hasHiddenTarget) {
            const csv = this.tagsValue.map(value => value.name).join(',');
            this.hiddenTarget.value = csv;
        }
    }

    createRemovableTag(name, color, added) {
        const state = added || 'adding';

        const container = document.createElement('div');
        container.style.backgroundColor = color;
        container.classList.add('tag');
        container.dataset.controller = 'tag';
        container.dataset.tagNameValue = name;
        container.dataset.tagStateValue = state;
        container.dataset.tagColorValue = color;

        if (this.hasAddUrlValue) {
            container.dataset.tagAddUrlValue = this.addUrlValue;
        }

        if (this.hasRemoveUrlValue) {
            container.dataset.tagRemoveUrlValue = this.removeUrlValue;
        }

        const label = document.createElement('span');
        label.classList.add('label');
        label.appendChild(document.createTextNode(name));
        container.appendChild(label);

        const remove = document.createElement('span');
        remove.dataset.action = 'click->tag#remove';
        remove.classList.add('remove');
        remove.innerHTML = '<i class="fas fa-times"></i>'
        container.appendChild(remove);

        return container;
    }

    requestAddFromAutocomplete(event) {
        const { type, value, textValue, object } = event.detail;
        if (type !== 'tag') {
            return;
        }

        if (object) {
            const { name, color } = JSON.parse(object);
            this.requestAdd(name, color);
        } else {
            this.requestAdd(value, '#5d5d5d');
        }
    }

    requestAdd(name, color) {
        const existingElement = this.element.querySelector(`[data-tag-name-value=${name}]`);
        if (existingElement) {
            existingElement.classList.add('tag-exists');
            setTimeout(() => {
                existingElement.classList.remove('tag-exists');
            }, 1000);

            return;
        }

        const newElement = this.createRemovableTag(name, color);
        this.element.appendChild(newElement);
    }

    addExisting(name, color) {
        const newElement = this.createRemovableTag(name, color, 'added');
        this.element.appendChild(newElement);
    }

    add(event) {
        const { name, color } = event.detail;
        const copy = this.tagsValue.slice();
        copy.push({
            name,
            color
        });

        this.tagsValue = copy;

        return true;
    }

    remove(event) {
        const { name } = event.detail;
        const index = this.tagsValue.findIndex((value => value.name === name));

        if (index > -1) {
            const copy = this.tagsValue.slice();
            copy.splice(index, 1);
            this.tagsValue = copy;
        }
    }
}