import { PaginatedResponse } from './api';
import { ApiTag } from "./types";
import { AxiosResponse } from "axios";

const axios = require('axios').default;

export class TagApi {
    public static index(searchTerm: string, excludeTags: Array<string> = []): Promise<AxiosResponse<PaginatedResponse<ApiTag>>> {
        const search = new URLSearchParams();
        search.append('searchTerm', encodeURIComponent(searchTerm));

        if (excludeTags.length !== 0) {
            const excludeTerms = encodeURIComponent(excludeTags.join(','));
            search.append('exclude', excludeTerms);
        }

        return axios.get(`/json/tag?${search.toString()}`);
    }
}