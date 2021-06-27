import { CoreApi, PaginatedResponse } from './api';
import { ApiTag } from "./tag_api";

export type TimeType = 'instant' | 'interval';

export interface ApiStatistic {
    name: string;
    canonicalName: string;
    createdAt: string;
    createAtEpoch: number;
    color: string;
    unit: string;
    icon?: string;
    url?: string;
}

export interface AddStatisticRequest {
    statisticName: string;
    value: number;
}

export interface CreateStatisticOptions {
    name: string;
    description: string;
    timeType: TimeType;
}

export class StatisticApi {
    public static index(searchTerm: string, timeType: TimeType = 'instant') {
        const url = `/json/statistic?searchTerm=${searchTerm}&timeType=${timeType}`;

        return CoreApi.get<PaginatedResponse<ApiStatistic>>(url);
    }

    public static create(options: CreateStatisticOptions) {
        const url = `/json/statistic`;

        return CoreApi.post<ApiStatistic>(url, options);
    }

    public static addTag(statisticId: string, tagName: string) {
        return CoreApi.post<ApiTag>(`/json/statistic/${statisticId}/tag`, {
            name: tagName
        });
    }

    public static getTags(statisticId: string) {
        return CoreApi.get<ApiTag[]>(`/json/statistic/${statisticId}/tags`);
    }

    public static removeTag(statisticId: string, tagName: string) {
        tagName = encodeURIComponent(tagName);

        return CoreApi.delete(`/json/statistic/${statisticId}/tag/${tagName}`);
    }
}