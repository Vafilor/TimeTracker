import { TaskApi } from "../core/api/task_api";
import { PaginatedResponse } from "../core/api/api";
import { PaginatedAutocomplete } from "./autocomplete";
import { ApiTask } from "../core/api/types";
import { AxiosResponse } from "axios";

export default class AutocompleteTask extends PaginatedAutocomplete<ApiTask> {
    protected template(item: ApiTask): string {
        if (item.completedAt) {
            return `<div><span class="task-complete"><i class="far fa-check-square"></i></span>${item.name}</div>`;
        }

        return `<div><span class="task-complete"><i class="far fa-square"></i></span>${item.name}</div>`;
    }

    protected queryApi(query: string): Promise<AxiosResponse<PaginatedResponse<ApiTask>>> {
        return TaskApi.index({nameLike: query, showCompleted: true});
    }
}