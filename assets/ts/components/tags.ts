import { ApiTag } from "../core/api/types";

// TODO REMOVE
export function createTagViewRemovable(tagName: string, tagColor: string, extraClasses: string = ''): string {
    return `<div class="tag me-2 ${extraClasses}" style="background-color: ${tagColor}" data-name="${tagName}"><span class="label">${tagName}</span> <span class="remove js-tag-remove"><i class="fas fa-times"></i></span></div>`;
}

// TODO REMOVE
export function createTagView(tagName: string, tagColor: string, extraClasses: string = ''): string {
    return `<div class="tag ${extraClasses}" style="background-color: ${tagColor}" data-name="${tagName}">${tagName}</div>`;
}

export function createTagsView(tags: ApiTag[]): string {
    let tagHtml = '';

    for(const tag of tags) {
        tagHtml += createTagView(tag.name, tag.color) + ' ';
    }

    return tagHtml;
}

export function createRemovableTag(name: string, color: string): HTMLDivElement {
    const container = document.createElement('div');
    container.dataset.controller = 'removable-tag';
    container.dataset.removableTagNameValue = name;
    container.dataset.removableTagColorValue = color;
    container.classList.add('tag');
    container.style.backgroundColor = color;

    container.innerHTML = `
            <span class="label">${name}</span>
            <span data-action="click->removable-tag#remove" class="remove js-tag-remove">
                <i class="fas fa-times"></i>
            </span>`;

    return container;
}

export function createTag(name: string, color: string): HTMLDivElement {
    const element = document.createElement('div');
    element.dataset.name = name;
    element.dataset.color = color;
    element.classList.add('tag');
    element.style.backgroundColor = color;
    element.innerText = name;

    return element;
}