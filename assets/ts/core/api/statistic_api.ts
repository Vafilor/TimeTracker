import { CoreApi, PaginatedResponse } from './api';

export type TimeType = 'instant' | 'interval';

export interface ApiStatistic {
    name: string;
}

export class StatisticApi {
    public static index(searchTerm: string, timeType: TimeType = 'instant') {
        let url = `/json/statistic?searchTerm=${searchTerm}&timeType=${timeType}`;

        return CoreApi.get<PaginatedResponse<ApiStatistic>>(url);
    }
}