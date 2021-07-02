import { CoreApi } from "./api";
import { ApiStatistic } from "./statistic_api";

export interface ApiStatisticValue {
    id: string;
    value: number;
    statistic: ApiStatistic;
}

export class StatisticValueApi {
    public static addForDay(name: string, value: number, day?: string) {
        return CoreApi.post<ApiStatisticValue>(`/json/record`, {
            statisticName: name,
            value,
            day
        });
    }

    public static update(id: string, value: number) {
        return CoreApi.put<ApiStatisticValue>(`/json/statistic-value/${id}`, {
            value
        });
    }

    public static remove(id: string) {
        return CoreApi.delete(`/json/statistic-value/${id}`);
    }
}