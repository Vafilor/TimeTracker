import { CoreApi, PaginatedResponse } from './api';

export type TimeType = 'instant' | 'interval';

export interface ApiStatistic {
    name: string;
    canonicalName: string;
}

export interface ApiStatisticValue {
    id: string;
    name: string;
    value: number;
}

export interface AddStatisticRequest {
    statisticName: string;
    value: number;
}

export class StatisticApi {
    public static index(searchTerm: string, timeType: TimeType = 'instant') {
        let url = `/json/statistic?searchTerm=${searchTerm}&timeType=${timeType}`;

        return CoreApi.get<PaginatedResponse<ApiStatistic>>(url);
    }
}