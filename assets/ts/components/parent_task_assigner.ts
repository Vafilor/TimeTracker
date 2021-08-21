import { TaskAssigner } from "./task_assigner";
import Flashes from "./flashes";
import Observable from "./observable";
import { TaskApi } from "../core/api/task_api";
import { ApiErrorResponse } from "../core/api/api";
import { TaskApiErrorCode } from "../core/api/types";
import { ApiError } from "../core/api/errors";
import { TimeEntryApiErrorCode } from "../core/api/time_entry_api";

export class ParentTaskAssigner extends TaskAssigner {
    // This is the id of the task whose parent we are changing
    private readonly childTaskId: string;
    private readonly flashes: Flashes;

    /**
     * When a task's parent is changed, the id of the task is emitted (not the parent id).
     */
    public readonly parentTaskAssigned = new Observable<string>();

    /**
     * When a task's parent is changed, the id of the task is emitted (not the parent id).
     */
    public readonly parentTaskRemoved = new Observable<string>();

    constructor($container: JQuery, childTaskId: string, flashes: Flashes) {
        super($container);

        this.childTaskId = childTaskId;
        this.flashes = flashes;
    }

    protected override async assignToTask(taskName: string, taskId?: string) {
        // Don't create a new task, only allow assigning to existing ones
        if (!taskId) {
            return;
        }

        this.autocomplete.clearSearchContent();

        const res = await TaskApi.setParentTask(this.childTaskId, taskId);

        this.task = res.data;
        this.autocomplete.setQuery(taskName);

        if (res.status === 200 && res.data.url) {
            this.flashes.appendWithLink('success', `Assigned to task`, res.data.url, res.data.name);
            this.parentTaskAssigned.emit(this.childTaskId);
        } else {
            throw new Error('Incorrect response from server');
        }
    }

    protected override async clearTask() {
        try {
            await TaskApi.removeParentTask(this.childTaskId);
            this.task = undefined;
            this.autocomplete.clear();
            this.flashes.append('success', 'Removed task', true);
            this.parentTaskRemoved.emit(this.childTaskId);
        } catch (err) {
            if (err && err.response.data) {
                const error = ApiError.findByCode(err.response.data, TaskApiErrorCode.codeNoParentTask)
                if (error) {
                    this.flashes.append('danger', 'Task has no parent task');
                }
            }
        }
    }
}
