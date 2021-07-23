import AutocompleteTask from "./autocomplete_task";
import { ApiTask } from "../core/api/task_api";
import { AutocompleteEnterPressedEvent } from "./autocomplete";

export abstract class TaskAssigner {
    protected autocomplete: AutocompleteTask;
    protected task?: ApiTask;

    constructor(public readonly $container: JQuery) {
        this.autocomplete = new AutocompleteTask($container);

        this.autocomplete.itemSelected.addObserver((item: ApiTask) => this.onItemSelected(item));

        this.$container.find('.js-delete').on('click', () => this.clearTask());

        this.autocomplete.enterPressed.addObserver((event: AutocompleteEnterPressedEvent<ApiTask>) => {
            if (event.data) {
                this.assignToTask(event.data.name, event.data.id);
            } else {
                this.assignToTask(event.query);
            }
        });

        const taskId = $container.data('task-id') as string;
        const taskName = $container.data('task-name') as string;
        const taskUrl = $container.data('task-url') as string;
        if (taskId && taskName && taskUrl) {
            this.setTaskSimple(taskId, taskName, taskUrl);
        }
    }

    setTaskSimple(id: string, name: string, taskUrl = '') {
        this.task = {
            id,
            name,
            url: taskUrl,
            description: '',
            createdAt: '',
            createdAtEpoch: 0,
            tags: []
        };

        this.autocomplete.setQuery(name);
    }

    protected abstract assignToTask(taskName: string, taskId?: string);

    protected async onItemSelected(item: ApiTask) {
        this.autocomplete.setQuery(item.name);
        return this.assignToTask(item.name, item.id);
    }

    protected abstract clearTask();

    getTask(): ApiTask|undefined {
        return this.task;
    }

    getContainer(): JQuery {
        return this.$container;
    }

    dispose() {
        this.$container.remove();
    }
}