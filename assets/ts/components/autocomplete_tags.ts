import $ from "jquery";
import { ApiTag, TagApi } from "../core/api/tag_api";
import Autocomplete from "./autocomplete";
import { JsonResponse } from "../core/api/api";

export default class AutocompleteTags extends Autocomplete<ApiTag> {
    private tags = new Array<string>();

    constructor(selector: string) {
        super(selector);

        if (this.live()) {
            this.$nameInput.on('keypress', (event) => {
                if (event.key === 'Enter') {
                    // So form doesn't submit, if there is one.
                    event.preventDefault();
                    this.enterTag(this.getInputValue());
                }
            });

            $('.js-add').on('click', (event) => {
                this.enterTag(this.getInputValue());
            });
        }
    }

    protected createItemTemplate(item: ApiTag): string {
        return `<div>${item.name}</div>`;
    }

    protected listItems(name: string, request: any): Promise<JsonResponse<any>> {
        return TagApi.listTags(name, this.tags);
    }

    protected onItemSelected(item: ApiTag) {
        this.valueEmitter.emit(item);
        this.$nameInput.val('');
    }

    public setTags(tags: Array<string>) {
        this.tags = tags;
    }

    public getTags(): Array<string> {
        return this.tags;
    }

    private enterTag(name: string, color: string = '#5d5d5d') {
        if (name === '') {
            return;
        }

        this.valueEmitter.emit({
            name,
            color
        });

        this.$nameInput.val('');
    }
}