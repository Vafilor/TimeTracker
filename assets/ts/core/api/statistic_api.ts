import { CoreApi, PaginatedResponse } from './api';

export type TimeType = 'instant' | 'interval';

export interface ApiStatistic {
    name: string;
    canonicalName: string;
    createdAt: string;
    createAtEpoch: number;
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
}