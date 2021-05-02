import { CoreApi } from './api';

export interface ApiTask {
    id: string;
    name: string;
    description: string;
    completedAt?: string;
}

export class TaskApi {
    public static check(taskId: string, completed: boolean = true) {
        let url = `/json/task/${taskId}/check`;

        return CoreApi.put<ApiTask>(url, {
            completed
        });
    }
}