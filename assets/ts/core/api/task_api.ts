import { PaginatedResponse } from './api';
import { ApiTag, ApiTask } from "./types";
import { AxiosResponse } from "axios";

const axios = require('axios').default;

// The data required to assign a task to something.
// If id is not provided, a new task will be created.
export interface ApiTaskAssign {
    id?: string;
    name: string;
}

export interface ApiUpdateTask {
    description?: string;
}

export interface CreateTaskOptions {
    name: string;
    parentTask?: string;
}

export interface CreateTaskResponse {
    task: ApiTask;
    view: string;
}

export interface TaskTimeReport {
    totalTime: string;
    totalSeconds: string;
}

export interface IndexOptions {
    nameLike?: string;
    showCompleted?: boolean;
}

export class TaskApi {
    public static index(options?: IndexOptions): Promise<AxiosResponse<PaginatedResponse<ApiTask>>> {
        let url = `/json/task`;

        if (options) {
            let params = new URLSearchParams();

            if (options.nameLike) {
                params.append('content', encodeURIComponent(options.nameLike));
            }

            if (options.showCompleted) {
                params.append('showCompleted', `${options.showCompleted}`);
            }

            url = url + '?' + params.toString();
        }

        return axios.get(url);
    }

    public static create(options: CreateTaskOptions): Promise<AxiosResponse<CreateTaskResponse>> {
        return axios.post(`/json/task`, options);
    }

    public static check(taskId: string, completed: boolean = true): Promise<AxiosResponse<ApiTask>> {
        return axios.put(`/json/task/${taskId}/check`, {
            completed
        });
    }

    public static getLineage(taskId: string): Promise<AxiosResponse<ApiTask[]>> {
        return axios.get(`/json/task/${taskId}/lineage`);
    }

    public static async getLineageHtml(taskId: string): Promise<string> {
        const response = await axios.get(`/task/${taskId}/lineage`);

        return response.data;
    }

    public static update(taskId: string, update: ApiUpdateTask): Promise<AxiosResponse<ApiTask>> {
        return axios.put(`/json/task/${taskId}`, update);
    }

    public static setParentTask(taskId: string, parentTaskId: string): Promise<AxiosResponse<ApiTask>> {
        return axios.put(`/json/task/${taskId}/parent`, {
            parentTaskId
        });
    }

    public static removeParentTask(taskId: string): Promise<AxiosResponse> {
        return axios.delete(`/json/task/${taskId}/parent`);
    }

    public static reportForTask(taskId: string): Promise<AxiosResponse<TaskTimeReport>> {
        return axios.get(`/json/report/task/${taskId}`);
    }

    public static addTag(taskId: string, tagName: string): Promise<AxiosResponse<ApiTag>> {
        return axios.post(`/json/task/${taskId}/tag`, {
            name: tagName
        });
    }

    public static getTags(taskId: string): Promise<AxiosResponse<ApiTag[]>> {
        return axios.get(`/json/task/${taskId}/tags`);
    }

    public static removeTag(taskId: string, tagName: string): Promise<AxiosResponse> {
        tagName = encodeURIComponent(tagName);

        return axios.delete(`/json/task/${taskId}/tag/${tagName}`);
    }

    public static assignToResource(url: string, taskName: string, taskId?: string): Promise<AxiosResponse<ApiTask>> {
        const data = {
            name: taskName,
        };

        if (taskId) {
            data['id'] = taskId;
        }

        return axios.post(url, data);
    }
    public static unassignTask(url: string): Promise<AxiosResponse> {
        return axios.delete(url);
    }
}