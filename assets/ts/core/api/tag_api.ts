import { CoreApi, PaginatedResponse } from './api';

export interface ApiTag {
    name: string;
    color: string;
}

export function ApiTagFromName(name: string): ApiTag {
    return {
        name,
        color: '#5d5d5d'
    };
}

export class TagApi {
    public static index(searchTerm: string, excludeTags: Array<string> = [] ) {
        const search = new URLSearchParams();
        search.append('searchTerm', encodeURIComponent(searchTerm));

        if (excludeTags.length !== 0) {
            const excludeTerms = encodeURIComponent(excludeTags.join(','));
            search.append('exclude', excludeTerms);
        }

        return CoreApi.get<PaginatedResponse<ApiTag>>(`/json/tag?${search.toString()}`);
    }
}