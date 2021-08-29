import { ApiStatisticValue } from "./types";
import { AxiosResponse } from "axios";
import { AddStatisticRequest } from "./statistic_api";

const axios = require('axios').default;

export interface CreateStatisticValueResponse {
    statisticValue: ApiStatisticValue;
    view: string;
}

export class StatisticValueApi {
    static addForDay(name: string, value: number, day?: string): Promise<AxiosResponse<ApiStatisticValue>> {
        return axios.post(`/json/record`, {
            statisticName: name,
            value,
            day
        })
    }

    static update(url: string, value: number): Promise<AxiosResponse<ApiStatisticValue>> {
        return axios.put(url, {
            value
        });
    }

    static updateById(id: string, value: number): Promise<AxiosResponse<ApiStatisticValue>> {
        return StatisticValueApi.update(`/json/statistic-value/${id}`, value);
    }

    static remove(url: string): Promise<AxiosResponse<void>> {
        return axios.delete(url);
    }

    static removeById(id: string): Promise<AxiosResponse<void>> {
        return StatisticValueApi.remove(`/json/statistic-value/${id}`);
    }

    public static addToResource(url: string, request: AddStatisticRequest): Promise<AxiosResponse<CreateStatisticValueResponse>> {
        return axios.post(url, request);
    }
}