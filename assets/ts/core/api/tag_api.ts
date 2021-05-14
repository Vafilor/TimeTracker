import { CoreApi } from './api';

export interface ApiTag {
    name: string;
    color: string;
}

export class TagApi {
    public static listTags(searchTerm: string, excludeTags: Array<string> = [] ) {
        let url = `/json/tag?searchTerm=${searchTerm}`;
        if (excludeTags.length !== 0) {
            const excludeTerms = excludeTags.join(",");
            url += '&exclude=' + excludeTerms;
        }

        return CoreApi.get(url);
    }
}