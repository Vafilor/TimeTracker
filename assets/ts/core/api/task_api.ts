import { CoreApi } from './api';

export interface ApiTask {
    id: string;
    name: string;
    description: string;
    completedAt?: string;
}

export interface ApiUpdateTask {
    description?: string;
}

export class TaskApi {
    public static list(nameLike?: string) {
        let url = `/json/task`;

        if (nameLike) {
            let params = new URLSearchParams();
            params.append('name', nameLike);

            url = url + '?' + params.toString();
        }

        return CoreApi.get<Array<ApiTask>>(url);
    }

    public static check(taskId: string, completed: boolean = true) {
        let url = `/json/task/${taskId}/check`;

        return CoreApi.put<ApiTask>(url, {
            completed
        });
    }

    public static update(taskId: string, update: ApiUpdateTask) {
        return CoreApi.put(`/json/task/${taskId}`, update);
    }
}