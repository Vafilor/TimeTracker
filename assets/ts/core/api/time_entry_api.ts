import { CoreApi, JsonResponse } from "./api";
import { ApiTag } from "./tag_api";
import { ApiTask } from "./task_api";
import { AddStatisticRequest } from "./statistic_api";
import { ApiStatisticValue } from "./statistic_value_api";

export type DateFormat = 'date' | 'date_time' | 'date_time_today';

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
    duration?: string; // If the time entry is not over, no duration.
    taskId?: string;
    url?: string;
    tags: ApiTag[];
}

export interface IndexTimeEntryOptions {
    taskId?: string;
}

export interface CreateTimeEntryResponse {
    timeEntry: ApiTimeEntry;
    url: string;
    template?: string;
}

export interface ApiUpdateTimeEntry {
    description?: string;
    startedAt?: ApiDateTimeUpdate;
    endedAt?: ApiDateTimeUpdate;
}

export enum TimeEntryApiErrorCode {
    codeNoAssignedTask = 'code_no_assigned_task',
    codeRunningTimer = 'code_running_timer',
    codeTimeEntryOver = 'code_time_entry_over',
}

export interface CreateTimeEntryOptions {
    taskId?: string;
    withHtmlTemplate?: boolean;
}

export interface ContinueTimeEntryOptions {
    withHtmlTemplate?: boolean;
}

export class TimeEntryApi {
    public static create(options: CreateTimeEntryOptions, format: DateFormat = 'date_time') {
        let url = '/json/time-entry';
        if (options.withHtmlTemplate) {
            url += '?template=true';
            options.withHtmlTemplate = undefined;
        }

        return CoreApi.post<CreateTimeEntryResponse>(url, {
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

    public static stop(timeEntryId: string, format: DateFormat = 'date_time') {
        return CoreApi.put<ApiTimeEntry>(`/json/time-entry/${timeEntryId}/stop`, {
            'time_format': format
        });
    }

    public static continue(timeEntryId: string, options: ContinueTimeEntryOptions) {
        let url = `/json/time-entry/${timeEntryId}/continue`;
        if (options.withHtmlTemplate) {
            url += '?template=true';
        }

        return CoreApi.post<CreateTimeEntryResponse>(url, {});
    }

    public static addTag(timeEntryId: string, tagName: string) {
        return CoreApi.post<ApiTag>(`/json/time-entry/${timeEntryId}/tag`, {
            name: tagName
        });
    }

    public static getTags(timeEntryId: string) {
        return CoreApi.get<ApiTag[]>(`/json/time-entry/${timeEntryId}/tags`);
    }

    public static removeTag(timeEntryId: string, tagName: string) {
        tagName = encodeURIComponent(tagName);

        return CoreApi.delete(`/json/time-entry/${timeEntryId}/tag/${tagName}`);
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

    public static update(timeEntryId: string, update: ApiUpdateTimeEntry) {
        return CoreApi.put<ApiTimeEntry>(`/json/time-entry/${timeEntryId}`, update);
    }

    public static getActive() {
        const url = '/json/time-entry/active';
        return CoreApi.get<ApiTimeEntry|null>(url);
    }

    public static addStatistic(timeEntryId: string, request: AddStatisticRequest) {
        return CoreApi.post<ApiStatisticValue>(`/json/time-entry/${timeEntryId}/statistic`, request);
    }

    public static removeStatistic(timeEntryId: string, statisticId: string) {
        return CoreApi.delete(`/json/time-entry/${timeEntryId}/statistic/${statisticId}`);
    }
}