import $ from "jquery";
import Flashes from "./flashes";
import { ApiTask } from "../core/api/task_api";
import { TimeEntryApi, TimeEntryApiErrorCode } from "../core/api/time_entry_api";
import { ApiErrorResponse } from "../core/api/api";
import AutocompleteTask from "./autocomplete_task";
import { AutocompleteEnterPressedEvent } from "./autocomplete";
import IdGenerator from "./id_generator";

export class TimeEntryTaskAssigner {
    private readonly timeEntryId: string;
    private readonly $container: JQuery;
    private readonly flashes: Flashes;
    private autocomplete: AutocompleteTask;
    private task?: ApiTask;

    static template(taskId: string = '', taskName: string = '', taskUrl: string = ''): string {
        const id = IdGenerator.next();

        return `
        <div 
            class="autocomplete js-autocomplete js-autocomplete-task js-time-entry-task-assigner"
            data-task-id="${taskId}"
            data-task-name="${taskName}"
            data-task-url="${taskUrl}">
            <div class="autocomplete-search-group">
                <div class="search">
                    <label class="sr-only" for="autocomplete-task-${id}">name</label>
                    <input
                            id="autocomplete-task-${id}"
                            type="text"
                            class="js-input form-control unset-height"
                            placeholder="task name"
                            name="task"
                            autocomplete="off">
                    <button class="clear js-clear btn btn-sm"><i class="fas fa-times"></i></button>
                </div>
                <button type="button" class="btn js-delete btn-outline-danger autocomplete-search-group-append">
                    <i class="fas fa-trash"></i>
                </button>   
                <div class="search-results d-none js-search-results"></div>
            </div>
        </div>`
    }

    constructor($container: JQuery, timeEntryId: string, flashes: Flashes) {
        this.$container = $container;
        this.timeEntryId = timeEntryId;
        this.flashes = flashes;

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

    private async assignToTask(taskName: string, taskId?: string) {
        this.autocomplete.clearSearchContent();

        const res = await TimeEntryApi.assignToTask(this.timeEntryId, taskName, taskId);
        this.task = res.data;
        this.autocomplete.setQuery(taskName);

        if (res.source.status === 201 && res.data.url) {
            this.flashes.appendWithLink('success', `Created new task`, res.data.url, res.data.name);
        } else if (res.source.status === 200 && res.data.url) {
            this.flashes.appendWithLink('success', `Assigned to task`, res.data.url, res.data.name);
        }
    }

    private async onItemSelected(item: ApiTask) {
        this.autocomplete.setQuery(item.name);
        this.autocomplete.clearSearchContent();

        const res = await TimeEntryApi.assignToTask(this.timeEntryId, item.name, item.id);
        this.task = res.data;

        this.flashes.append('success', `Assigned to task '${this.task.name}'`, true);
    }

    private async clearTask() {
        try {
            await TimeEntryApi.unassignTask(this.timeEntryId);
            this.task = undefined;
            this.autocomplete.clear();
            this.flashes.append('success', 'Removed task', true);
        } catch (e) {
            if (e instanceof ApiErrorResponse) {
                const errRes = e as ApiErrorResponse;
                if (errRes.hasErrorCode(TimeEntryApiErrorCode.codeNoAssignedTask)) {
                    this.flashes.append('danger', 'Time entry has no assigned task');
                }
            }
        }
    }

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
