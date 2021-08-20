import { AddStatisticRequest } from "./statistic_api";
import { CreateStatisticValueResponse } from "./statistic_value_api";
import { dateToISOLocal } from "../../components/time";
import { AxiosResponse } from "axios";
import { ApiTag, ApiTask, ApiTimeEntry } from "./types";
import { PaginatedResponse } from "./api";

const axios = require('axios').default;

export type DateFormat = 'date' | 'date_time' | 'date_time_today';

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
    startedAt?: Date;
    endedAt?: Date;
}

export enum TimeEntryApiErrorCode {
    codeRunningTimer = 'code_running_timer',
    codeTimeEntryOver = 'code_time_entry_over',
}

export interface CreateTimeEntryOptions {
    taskId?: string;
    htmlTemplate?: string;
}

export interface ContinueTimeEntryOptions {
    withHtmlTemplate?: boolean;
}

export class TimeEntryApi {
    public static create(options: CreateTimeEntryOptions, format: DateFormat = 'date_time'): Promise<AxiosResponse<CreateTimeEntryResponse>> {
        let url = '/json/time-entry';
        if (options.htmlTemplate) {
            url += `?template=${options.htmlTemplate}`;
            options.htmlTemplate = undefined;
        }

        return axios.post(url, {
            'time_format': format,
            ...options
        });
    }

    public static index(options: IndexTimeEntryOptions): Promise<AxiosResponse<PaginatedResponse<ApiTimeEntry>>> {
        let url = `/json/time-entry`;

        let params = new URLSearchParams();

        if (options.taskId) {
            params.append('taskId', options.taskId);
        }

        url = url + '?' + params.toString();

        return axios.get(url);
    }

    public static stop(timeEntryId: string, format: DateFormat = 'date_time'): Promise<AxiosResponse<ApiTimeEntry>> {
        return axios.put(`/json/time-entry/${timeEntryId}/stop`, {
            'time_format': format
        });
    }

    public static continue(timeEntryId: string, options: ContinueTimeEntryOptions): Promise<AxiosResponse<CreateTimeEntryResponse>> {
        let url = `/json/time-entry/${timeEntryId}/continue`;
        if (options.withHtmlTemplate) {
            url += '?template=true';
        }

        return axios.post(url, {});
    }

    public static addTag(timeEntryId: string, tagName: string): Promise<AxiosResponse<ApiTag>> {
        return axios.post(`/json/time-entry/${timeEntryId}/tag`, {
            name: tagName
        });
    }

    public static getTags(timeEntryId: string): Promise<AxiosResponse<ApiTag[]>> {
        return axios.get(`/json/time-entry/${timeEntryId}/tags`);
    }

    public static removeTag(timeEntryId: string, tagName: string): Promise<AxiosResponse> {
        tagName = encodeURIComponent(tagName);

        return axios.delete(`/json/time-entry/${timeEntryId}/tag/${tagName}`);
    }

    public static assignToTask(timeEntryId: string, taskName: string, taskId?: string): Promise<AxiosResponse<ApiTask>> {
        const url = `/json/time-entry/${timeEntryId}/task`;

        const data = {
            name: taskName,
        };

        if (taskId) {
            data['id'] = taskId;
        }

        return axios.post(url, data);
    }

    public static unassignTask(timeEntryId: string): Promise<AxiosResponse> {
        const url = `/json/time-entry/${timeEntryId}/task`;

        return axios.delete(url);
    }

    public static update(timeEntryId: string, update: ApiUpdateTimeEntry): Promise<AxiosResponse<ApiTimeEntry>> {
        return axios.put(`/json/time-entry/${timeEntryId}`, {
            description: update.description,
            startedAt: update.startedAt ? dateToISOLocal(update.startedAt): undefined,
            endedAt: update.endedAt ? dateToISOLocal(update.endedAt): undefined
        });
    }

    public static getActive(): Promise<AxiosResponse<ApiTimeEntry|null>> {
        return axios.get('/json/time-entry/active');
    }

    public static addStatistic(timeEntryId: string, request: AddStatisticRequest): Promise<AxiosResponse<CreateStatisticValueResponse>> {
        return axios.post(`/json/time-entry/${timeEntryId}/statistic`, request);
    }

    public static removeStatistic(timeEntryId: string, statisticId: string): Promise<AxiosResponse> {
        return axios.delete(`/json/time-entry/${timeEntryId}/statistic/${statisticId}`);
    }
}