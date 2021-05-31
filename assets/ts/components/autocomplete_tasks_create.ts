import Autocomplete from "./autocomplete";
import { JsonResponse } from "../core/api/api";
import { ApiTask, ApiTaskAssign, TaskApi } from "../core/api/task_api";
import Observable from "./observable";

export default class AutocompleteTaskCreate extends Autocomplete<ApiTask> {
    public readonly enterValueEmitter = new Observable<ApiTaskAssign>()

    constructor($container: JQuery) {
        super($container);
        if (this.live()) {
            this.$nameInput.on('keypress', (event) => {
                if (event.key === 'Enter') {
                    // So form doesn't submit, if there is one.
                    event.preventDefault();
                    this.enterTask(this.getInputValue());
                }
            });

            this.$container.find('.js-set').on('click', () => {
                this.enterTask(this.getInputValue());
            })
        }
    }

    protected createItemTemplate(item: ApiTask): string {
        if (item.completedAt) {
            return `<div><span class="task-complete"><i class="far fa-check-square"></i></span>${item.name}</div>`;
        }

        return `<div><span class="task-complete"><i class="far fa-square"></i></span>${item.name}</div>`;
    }

    protected listItems(name: string, request: any): Promise<JsonResponse<any>> {
        return TaskApi.index(name);
    }

    protected onItemSelected(item: ApiTask) {
        this.valueEmitter.emit(item);
    }

    private enterTask(name: string) {
        if (name === '') {
            return;
        }

        this.enterValueEmitter.emit({
            name,
        });
    }
}