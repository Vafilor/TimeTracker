import $ from "jquery";
import Flashes from "./flashes";
import { ApiTask } from "../core/api/task_api";
import { TimeEntryApi, TimeEntryApiErrorCode } from "../core/api/time_entry_api";
import { ApiErrorResponse } from "../core/api/api";
import AutocompleteTask from "./autocomplete_task";

export class TimeEntryTaskAssigner {
    private readonly timeEntryId: string;
    private readonly $container: JQuery;
    private readonly flashes: Flashes;
    private autocomplete: AutocompleteTask;
    private task?: ApiTask;

    static template(taskId: string = '', taskName: string = '', taskUrl: string = ''): string {
        return `
        <div 
            class="autocomplete js-autocomplete js-autocomplete-task js-time-entry-task-assigner"
            data-task-id="${taskId}"
            data-task-name="${taskName}"
            data-task-url="${taskUrl}">
            <div class="d-flex">
                <div class="search border-right-0 rounded-right-0">
                    <input
                            type="text"
                            class="js-input"
                            placeholder="task name..."
                            name="task"
                            autocomplete="off">
                    <button class="clear js-clear btn btn-sm"><i class="fas fa-times"></i></button>
                </div>
                <button type="button" class="btn js-clear btn-outline-danger rounded-left-0">
                    <i class="fas fa-trash"></i>
                </button>   
            </div>
            <div class="search-results js-search-results d-none"></div>
        </div>`
    }

    constructor($container: JQuery, timeEntryId: string, flashes: Flashes) {
        this.$container = $container;
        this.timeEntryId = timeEntryId;
        this.flashes = flashes;

        this.autocomplete = new AutocompleteTask($container);

        this.autocomplete.itemSelected.addObserver((item: ApiTask) => this.onItemSelected(item));

        this.$container.find('.js-delete').on('click', () => this.clearTask());

        this.autocomplete.enterPressed.addObserver((query: string) => this.assignToTask(query));

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
        };

        this.autocomplete.setQuery(name);
    }

    private async assignToTask(taskName: string) {
        this.autocomplete.clearSearchContent();

        const res = await TimeEntryApi.assignToTask(this.timeEntryId, taskName);
        this.task = res.data;

        if (res.source.status === 201 && res.data.url) {
            this.flashes.appendWithLink('success', `Created new task`, res.data.url, res.data.name);
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
