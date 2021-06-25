import { CoreApi } from "./api";

export interface ApiStatisticValue {
    id: string;
    name: string;
    value: number;
}

export class StatisticValueApi {
    public static addForDay(name: string, value: number, day?: string) {
        return CoreApi.post<ApiStatisticValue>(`/json/record`, {
            statisticName: name,
            value,
            day
        });
    }
}