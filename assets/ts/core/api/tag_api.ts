import { CoreApi } from "./api";

export class TagApi {
    public static listTags(searchTerm: string, excludeTags: Array<string> = [] ) {
        let url = `/json/tag/list?searchTerm=${searchTerm}`;
        if (excludeTags.length !== 0) {
            const excludeTerms = excludeTags.join(",");
            url += '&exclude=' + excludeTerms;
        }

        return CoreApi.get(url);
    }
}