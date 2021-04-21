import { CoreApi } from "./api";

export interface ApiTag {
    name: string;
    color: string;
}

export interface ApiUpdateTimeEntry {
    description?: string;
    endedAt?: boolean;
}

export class TimeEntryApi {
    public static addTag(timeEntryId: string, tagName: string) {
        return CoreApi.post(`/json/time-entry/${timeEntryId}/tag`, {
            tagName
        });
    }

    public static getTags(timeEntryId: string) {
        return CoreApi.get<ApiTag[]>(`/json/time-entry/${timeEntryId}/tags`);
    }

    public static deleteTag(timeEntryId: string, tagName: string) {
        return CoreApi.delete(`/json/time-entry/${timeEntryId}/tag/${tagName}`);
    }

    public static update(timeEntryId: string, update: ApiUpdateTimeEntry) {
        return CoreApi.put(`/json/time-entry/${timeEntryId}`, update);
    }
}