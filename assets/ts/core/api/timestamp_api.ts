import { AddStatisticRequest } from "./statistic_api";
import { CreateStatisticValueResponse } from "./statistic_value_api";
import { AxiosResponse } from "axios";
import { ApiTag, ApiTimestamp } from "./types";

const axios = require('axios').default;

export interface CreateTimestampResponse {
    timestamp: ApiTimestamp;
    view: string;
}

export class TimestampApi {
    public static addTag(timestampId: string, tagName: string): Promise<AxiosResponse<ApiTag>> {
        return axios.post(`/json/timestamp/${timestampId}/tag`, {
            name: tagName
        });
    }

    public static getTags(timestampId: string): Promise<AxiosResponse<ApiTag[]>> {
        return axios.get(`/json/timestamp/${timestampId}/tags`);
    }

    public static removeTag(timestampId: string, tagName: string): Promise<AxiosResponse> {
        tagName = encodeURIComponent(tagName);

        return axios.delete(`/json/timestamp/${timestampId}/tag/${tagName}`);
    }

    public static repeat(timestampId: string): Promise<AxiosResponse<CreateTimestampResponse>> {
        return axios.post(`/json/timestamp/${timestampId}/repeat`, {});
    }

    public static addStatistic(timestampId: string, request: AddStatisticRequest): Promise<AxiosResponse<CreateStatisticValueResponse>> {
        return axios.post(`/json/timestamp/${timestampId}/statistic`, request);
    }

    public static removeStatistic(timestampId: string, statisticId: string): Promise<AxiosResponse> {
        return axios.delete(`/json/timestamp/${timestampId}/statistic/${statisticId}`);
    }
}