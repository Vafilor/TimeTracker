import Autocomplete from "./autocomplete";
import { JsonResponse } from "../core/api/api";
import { ApiTask, ApiTaskAssign, TaskApi } from "../core/api/task_api";
import Observable from "./observable";

export default class AutocompleteTaskCreate extends Autocomplete<ApiTask> {
    public readonly enterValueEmitter = new Observable<ApiTaskAssign>()

    constructor(selector: string) {
        super(selector);
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
        return `<div>${item.name}</div>`;
    }

    protected listItems(name: string, request: any): Promise<JsonResponse<any>> {
        return TaskApi.list(name);
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