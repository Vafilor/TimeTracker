import { ApiTag } from "../core/api/tag_api";

export function createTagViewRemovable(tagName: string, tagColor: string, extraClasses: string = ''): string {
    return `<div class="tag me-2 ${extraClasses}" style="background-color: ${tagColor}" data-name="${tagName}"><span class="label">${tagName}</span> <span class="remove js-tag-remove"><i class="fas fa-times"></i></span></div>`;
}

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