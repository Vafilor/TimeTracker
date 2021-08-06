import { CoreApi } from "./api";
import { ApiTag } from "./tag_api";
import { AddStatisticRequest } from "./statistic_api";
import { CreateStatisticValueResponse } from "./statistic_value_api";

export interface ApiTimestamp {
    id: string;
    createdAt: string;
    createdAtEpoch: number;
    createdAgo?: string;
    tags: ApiTag[];
}

export interface CreateTimestampResponse {
    timestamp: ApiTimestamp;
    view: string;
}

export class TimestampApi {
    public static addTag(timestampId: string, tagName: string) {
        return CoreApi.post<ApiTag>(`/json/timestamp/${timestampId}/tag`, {
            name: tagName
        });
    }

    public static getTags(timestampId: string) {
        return CoreApi.get<ApiTag[]>(`/json/timestamp/${timestampId}/tags`);
    }

    public static removeTag(timestampId: string, tagName: string) {
        tagName = encodeURIComponent(tagName);

        return CoreApi.delete(`/json/timestamp/${timestampId}/tag/${tagName}`);
    }

    public static repeat(timestampId: string) {
        return CoreApi.post<CreateTimestampResponse>(`/json/timestamp/${timestampId}/repeat`, {});
    }

    public static addStatistic(timestampId: string, request: AddStatisticRequest) {
        return CoreApi.post<CreateStatisticValueResponse>(`/json/timestamp/${timestampId}/statistic`, request);
    }

    public static removeStatistic(timestampId: string, statisticId: string) {
        return CoreApi.delete(`/json/timestamp/${timestampId}/statistic/${statisticId}`);
    }
}