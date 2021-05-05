import Autocomplete from "./autocomplete";
import { JsonResponse } from "../core/api/api";
import { ApiTask, TaskApi } from "../core/api/task_api";

export default class AutocompleteTasks extends Autocomplete<ApiTask> {
    protected listItems(name: string, request: any): Promise<JsonResponse<any>> {
        return TaskApi.list(name);
    }

    protected createItemTemplate(item: ApiTask): string {
        return `<div>${item.name}</div>`;
    }

    protected onItemSelected(item: ApiTask) {
        this.valueEmitter.emit(item);
    }
}