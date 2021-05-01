export function createTagView(tagName: string, tagColor: string, extraClasses: string = ''): string {
    return `<div class="tag mr-2 ${extraClasses}" style="background-color: ${tagColor}" data-name="${tagName}">${tagName} <span class="remove js-tag-remove"><i class="fas fa-times"></i></span></div>`;
}