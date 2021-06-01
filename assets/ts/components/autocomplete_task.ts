import { ApiTask, TaskApi } from "../core/api/task_api";
import { JsonResponse, PaginatedResponse } from "../core/api/api";
import { PaginatedAutocomplete } from "./autocomplete";

export default class AutocompleteTask extends PaginatedAutocomplete<ApiTask> {
    protected template(item: ApiTask): string {
        if (item.completedAt) {
            return `<div><span class="task-complete"><i class="far fa-check-square"></i></span>${item.name}</div>`;
        }

        return `<div><span class="task-complete"><i class="far fa-square"></i></span>${item.name}</div>`;
    }

    protected queryApi(query: string): Promise<JsonResponse<PaginatedResponse<ApiTask>>> {
        return TaskApi.index(query);
    }
}