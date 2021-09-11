import Flashes from "./flashes";
import IdGenerator from "./id_generator";
import { TimeEntryApi } from "../core/api/time_entry_api";
import { ApiErrorResponse } from "../core/api/api";
import { TaskAssigner } from "./task_assigner";
import { TaskApiErrorCode } from "../core/api/types";

export class TimeEntryTaskAssigner extends TaskAssigner {
    private readonly timeEntryId: string;
    private readonly flashes: Flashes;

    static template(taskId: string = '', taskName: string = '', taskUrl: string = ''): string {
        const id = IdGenerator.next();

        return `
        <div 
            class="autocomplete js-autocomplete js-autocomplete-task js-time-entry-task-assigner"
            data-task-id="${taskId}"
            data-task-name="${taskName}"
            data-task-url="${taskUrl}">
            <div class="autocomplete-search-group">
                <label class="visually-hidden" for="autocomplete-task-${id}">name</label>
                <input
                        id="autocomplete-task-${id}"
                        type="search"
                        class="js-input form-control search"
                        placeholder="task name"
                        name="task"
                        autocomplete="off">
                <button type="button" class="btn js-delete btn-outline-danger autocomplete-search-group-append">
                    <i class="fas fa-trash"></i>
                </button>   
                <div class="search-results d-none js-search-results"></div>
            </div>
        </div>`
    }

    constructor($container: JQuery, timeEntryId: string, flashes: Flashes) {
        super($container);

        this.timeEntryId = timeEntryId;
        this.flashes = flashes;
    }

    protected override async assignToTask(taskName: string, taskId?: string) {
        this.autocomplete.clearSearchContent();

        const res = await TimeEntryApi.assignToTask(this.timeEntryId, taskName, taskId);
        this.task = res.data;
        this.autocomplete.setQuery(taskName);

        if (res.status === 201 && res.data.url) {
            this.flashes.appendWithLink('success', `Created new task`, res.data.url, res.data.name, true);
        } else if (res.status === 200 && res.data.url) {
            this.flashes.appendWithLink('success', `Assigned to task`, res.data.url, res.data.name, true);
        }
    }

    protected override async clearTask() {
        try {
            await TimeEntryApi.unassignTask(this.timeEntryId);
            this.task = undefined;
            this.autocomplete.clear();
            this.flashes.append('success', 'Removed task', true);
        } catch (e) {
            if (e instanceof ApiErrorResponse) {
                const errRes = e as ApiErrorResponse;
                if (errRes.hasErrorCode(TaskApiErrorCode.codeNoAssignedTask)) {
                    this.flashes.append('danger', 'Time entry has no assigned task');
                }
            }
        }
    }
}