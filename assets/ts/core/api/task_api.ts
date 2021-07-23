import { CoreApi, PaginatedResponse } from './api';
import { ApiTag } from "./tag_api";

export enum TaskApiErrorCode {
    codeNoParentTask = 'code_no_parent_task',
}

export interface ApiTask {
    id: string;
    name: string;
    description: string;
    createdAt: string;
    createdAtEpoch: number;
    completedAt?: string;
    completedAtEpoch?: number;
    url?: string;
    tags: ApiTag[];
}

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
    public static index(options?: IndexOptions) {
        let url = `/json/task`;

        if (options) {
            let params = new URLSearchParams();

            if (options.nameLike) {
                params.append('content', options.nameLike);
            }

            if (options.showCompleted) {
                params.append('showCompleted', `${options.showCompleted}`);
            }

            url = url + '?' + params.toString();
        }

        return CoreApi.get<PaginatedResponse<ApiTask>>(url);
    }

    public static create(options: CreateTaskOptions) {
        return CoreApi.post<CreateTaskResponse>(`/json/task`, options);
    }

    public static check(taskId: string, completed: boolean = true) {
        const url = `/json/task/${taskId}/check`;

        return CoreApi.put<ApiTask>(url, {
            completed
        });
    }

    public static getLineage(taskId: string) {
        return CoreApi.get<ApiTask[]>(`/json/task/${taskId}/lineage`);
    }

    public static async getLineageHtml(taskId: string) {
        const response = await fetch(`/task/${taskId}/lineage`);
        return await response.text()
    }

    public static update(taskId: string, update: ApiUpdateTask) {
        return CoreApi.put<ApiTask>(`/json/task/${taskId}`, update);
    }

    public static setParentTask(taskId: string, parentTaskId: string) {
        return CoreApi.put<ApiTask>(`/json/task/${taskId}/parent`, {
            parentTaskId
        });
    }

    public static removeParentTask(taskId: string) {
        return CoreApi.delete(`/json/task/${taskId}/parent`);
    }

    public static reportForTask(taskId: string) {
        return CoreApi.get<TaskTimeReport>(`/json/report/task/${taskId}`);
    }

    public static addTag(taskId: string, tagName: string) {
        return CoreApi.post<ApiTag>(`/json/task/${taskId}/tag`, {
            name: tagName
        });
    }

    public static getTags(taskId: string) {
        return CoreApi.get<ApiTag[]>(`/json/task/${taskId}/tags`);
    }

    public static removeTag(taskId: string, tagName: string) {
        tagName = encodeURIComponent(tagName);

        return CoreApi.delete(`/json/task/${taskId}/tag/${tagName}`);
    }
}