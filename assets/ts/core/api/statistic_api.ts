import { ApiStatistic, ApiStatisticValue, TimeType } from "./types";
import { AxiosResponse } from "axios";
import { ApiTag } from "./tag_api";
import { CoreApi, JsonResponse, PaginatedResponse } from "./api";

const axios = require('axios').default;

export interface AddStatisticRequest {
    statisticName: string;
    value: number;
}

export interface CreateStatisticOptions {
    name: string;
    description: string;
    timeType: TimeType;
}

export interface CreateStatisticResponse {
    statistic: ApiStatistic;
    view: string;
}

export class StatisticApi {
    // TODO remove
    public static indexV1(searchTerm: string, timeType: TimeType = 'instant') {
        const url = `/json/statistic?searchTerm=${searchTerm}&timeType=${timeType}`;

        return CoreApi.get<PaginatedResponse<ApiStatistic>>(url);
    }

    public static index(searchTerm: string, timeType: TimeType = 'instant'): Promise<AxiosResponse<PaginatedResponse<ApiStatistic>>> {
        return axios.get('/json/statistic', {
            params: {
                searchTerm,
                timeType
            }
        })
    }

    public static create(options: CreateStatisticOptions): Promise<AxiosResponse<CreateStatisticResponse>> {
        return axios.post(`/json/statistic`, options);
    }

    public static addTag(statisticId: string, tagName: string): Promise<AxiosResponse<ApiTag>> {
        return axios.post(`/json/statistic/${statisticId}/tag`, {
            name: tagName
        });
    }

    public static getTags(statisticId: string): Promise<AxiosResponse<ApiTag[]>> {
        return axios.get(`/json/statistic/${statisticId}/tags`);
    }

    public static removeTag(statisticId: string, tagName: string): Promise<AxiosResponse<void>> {
        tagName = encodeURIComponent(tagName);

        return axios.delete(`/json/statistic/${statisticId}/tag/${tagName}`);
    }
}