import { CoreApi } from "./api";
import { ApiTag } from "./tag_api";

export interface ApiTimestamp {
    id: string;
    createdAt: string;
    createdAtEpoch: number;
    createdAgo?: string;
    tags: ApiTag[];
}

export class TimestampApi {
    public static addTag(timestampId: string, tagName: string) {
        return CoreApi.post<ApiTag>(`/json/timestamp/${timestampId}/tag`, {
            tagName
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
        return CoreApi.post<ApiTimestamp>(`/json/timestamp/${timestampId}/repeat`, {});
    }
}