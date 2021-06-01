import { ApiTag, TagApi } from "../core/api/tag_api";
import { JsonResponse, PaginatedResponse } from "../core/api/api";
import { PaginatedAutocomplete } from "./autocomplete";

export default class AutocompleteTags extends PaginatedAutocomplete<ApiTag> {
    private tagNames = new Array<string>();

    public setTagNames(tagNames: string[]) {
        this.tagNames = tagNames;
    }

    protected template(item: ApiTag): string {
        return `<div>${item.name}</div>`;
    }

    protected queryApi(query: string): Promise<JsonResponse<PaginatedResponse<ApiTag>>> {
        return TagApi.index(query, this.tagNames);
    }
}