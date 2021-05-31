import { CoreApi, JsonResponse } from "./api";
import { ApiTag } from "./tag_api";
import { ApiTask } from "./task_api";

export type DateFormat = 'date' | 'today';

export interface ApiDateTimeUpdate {
    date: string;
    time: string;
}

export interface ApiTimeEntry {
    id: string;
    createdAt: string;
    updatedAt: string;
    updatedAtEpoch: number;
    startedAt: string;
    startedAtEpoch: number;
    endedAt?: string;
    endedAtEpoch?: number;
    description: string;
    duration: string;
    taskId: string;
    url?: string;
    tags: ApiTag[];
}

export interface IndexTimeEntryOptions {
    taskId?: string;
}

export interface CreateTimeEntryResponse {
    timeEntry: ApiTimeEntry;
    url: string;
}

export interface ApiUpdateTimeEntry {
    description?: string;
    startedAt?: ApiDateTimeUpdate;
    endedAt?: ApiDateTimeUpdate;
}

export enum TimeEntryApiErrorCode {
    codeNoAssignedTask = 'code_no_assigned_task',
    codeRunningTime = 'code_running_timer',
    codeTimeEntryOver = 'code_time_entry_over',
}

export interface CreateTimeEntryOptions {
    taskId: string;
}

export class TimeEntryApi {
    public static create(options: CreateTimeEntryOptions, format: DateFormat = 'date') {
        return CoreApi.post<CreateTimeEntryResponse>(`/json/time-entry`, {
            'time_format': format,
            ...options
        });
    }

    public static index(options: IndexTimeEntryOptions) {
        let url = `/json/time-entry`;

        let params = new URLSearchParams();

        if (options.taskId) {
            params.append('taskId', options.taskId);
        }

        url = url + '?' + params.toString();

        return CoreApi.get<JsonResponse<ApiTimeEntry[]>>(url);
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

    public static assignToTask(timeEntryId: string, taskName: string, taskId?: string) {
        const url = `/json/time-entry/${timeEntryId}/task`;

        const data = {
            name: taskName,
        };

        if (taskId) {
            data['id'] = taskId;
        }

        return CoreApi.post<ApiTask>(url, data);
    }

    public static unassignTask(timeEntryId: string) {
        const url = `/json/time-entry/${timeEntryId}/task`;

        return CoreApi.delete(url);
    }

    public static getTags(timeEntryId: string) {
        return CoreApi.get<ApiTag[]>(`/json/time-entry/${timeEntryId}/tags`);
    }

    public static removeTag(timeEntryId: string, tagName: string) {
        return CoreApi.delete(`/json/time-entry/${timeEntryId}/tag/${tagName}`);
    }

    public static update(timeEntryId: string, update: ApiUpdateTimeEntry) {
        return CoreApi.put<ApiTimeEntry>(`/json/time-entry/${timeEntryId}`, update);
    }

    public static getActive() {
        const url = '/json/time-entry/active';
        return CoreApi.get<ApiTimeEntry|null>(url);
    }
}