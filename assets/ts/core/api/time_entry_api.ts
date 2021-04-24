import { CoreApi } from "./api";
import { ApiTag } from "./tag_api";

export type DateFormat = 'date' | 'today';

export interface ApiTimeEntry {
    createdAt: string;
    updatedAt: string;
    startedAt: string;
    startedAtEpoch: number;
    endedAt: string;
    endedAtEpoch: number;
    description: string;
    duration: string;
    apiTags: ApiTag[];
}

export interface CreateTimeEntryResponse {
    timeEntry: ApiTimeEntry;
    url: string;
}

export interface ApiUpdateTimeEntry {
    description?: string;
    endedAt?: boolean;
}

export class TimeEntryApi {
    public static create(format: DateFormat = 'date') {
        return CoreApi.post<CreateTimeEntryResponse>(`/json/time-entry/create`, {
            'time_format': format
        });
    }

    public static stop(timeEntryId: string, format: DateFormat = 'date') {
        return CoreApi.put<ApiTimeEntry>(`/json/time-entry/${timeEntryId}/stop`, {
            'time_format': format
        });
    }

    public static addTag(timeEntryId: string, tagName: string) {
        return CoreApi.post<ApiTag>(`/json/time-entry/${timeEntryId}/tag`, {
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