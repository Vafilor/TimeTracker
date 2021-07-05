import '../styles/task_index.scss';

import $ from 'jquery';
import { ApiTask, TaskApi } from "./core/api/task_api";
import { JsonResponse } from "./core/api/api";
import LoadingButton from "./components/loading_button";
import TimeTrackerRoutes from "./core/routes";
import Observable from "./components/observable";
import { createTagsView } from "./components/tags";
import { timeAgo } from "./components/time";

class TaskTable {
    private $container: JQuery;
    private routes: TimeTrackerRoutes;
    private readonly nameSort: string;

    constructor(
        selector: string,
        nameSort: string,
        routes: TimeTrackerRoutes) {
        this.$container = $(selector);
        this.nameSort = nameSort;
        this.routes = routes;
    }

    public createListItem(task: ApiTask) {
        const nowMillis = (new Date()).getTime();
        const timeEntryRoute = this.routes.timeEntryIndex({taskId: task.id});
        const taskViewRoute = this.routes.taskView(task.id);
        const tagAdjustmentClass = task.tags.length !== 0 ? 'mt-1' : '';

        let tagHtml = createTagsView(task.tags);


        return `
        <div
            class="card-list-item js-task"
            data-id="${task.id}"
        >
            <div class="d-flex align-items-baseline">
                <div class="spinner spinner-border spinner-border-sm text-primary js-loading mr-2 d-none" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <div class="form-check">
                    <input
                        id="${task.id}"
                        data-task-id="${task.id}"
                        type="checkbox"
                        class="form-check-input js-task-completed"/>
                    <label for="${task.id}">${task.name}</label>
                </div>
            </div>
            <div class="tag-list js-tag-list many-rows ${tagAdjustmentClass}">
                <div class="js-tag-list-view">
                    ${tagHtml}
                </div>
            </div>
            <div class="{% if not task.hasTags %}mt-2{% endif %}">
                <div class="row no-gutters justify-content-between">
                    <div>
                        <strong>Created</strong>
                        <div
                            class="time-ago js-task-ago ml-1"
                            data-created-at="{{ task.createdAt.timestamp}}">
                            ${timeAgo(task.createdAtEpoch * 1000, nowMillis)}
                        </div>
                        <div class="datetime ml-1">${task.createdAt}</div>
                    </div>
                    <div class="js-task-completed">
                    </div>
                </div>
                <div class="mt-2 text-break">${task.description}</div>
            </div>
            <hr/>
            <div class="d-flex justify-content-end js-actions">
                <a href="${taskViewRoute}" class="btn btn-primary js-view">View</a>
                <a href="${timeEntryRoute}" class="btn btn-secondary ml-2">Time Entries</a>
            </div>
        </div>
        `;
    }

    addTask(task: ApiTask) {
        const $newItem = $(this.createListItem(task));

        if (this.nameSort === 'asc') {
            this.$container.find('.js-task').each((index, element) => {
                const name = $(element).data('name') as string;

                if (task.name < name) {
                    $newItem.insertBefore(element);
                    return false;
                }
            });
        } else if (this.nameSort === 'desc') {
            this.$container.find('.js-task').each((index, element) => {
                const name = $(element).data('name') as string;

                if (task.name > name) {
                    $newItem.insertBefore(element);
                    return false;
                }
            });
        } else {
            this.$container.prepend($newItem);
        }
    }
}

class CreateTaskForm {
    private $container: JQuery;
    private $input: JQuery;
    private submitButton: LoadingButton;
    public readonly taskCreated = new Observable<ApiTask>();

    constructor(selector: string) {
        this.$container = $(selector);

        this.$input = this.$container.find('.js-name');
        this.$input.on('keypress', (event) => {
            if (event.key === 'Enter') {
                // So form doesn't submit, if there is one.
                event.preventDefault();
                this.createTask(this.getInputValue());
            }
        });

        this.submitButton = new LoadingButton(this.$container.find('.js-loading-button'));

        this.submitButton.$container.on('click', (event) => {
            const inputText = this.getInputValue();
            if (inputText && inputText.length > 0) {
                this.createTask(inputText);
            }
        });
    }

    private getInputValue(): string {
        return this.$input.val() as string;
    }

    createTask(text: string) {
        this.submitButton.startLoading();

        TaskApi.create({
            name: text,
        }).then((res) => {
            this.submitButton.stopLoading();
            this.taskCreated.emit(res.data);
            this.$input.val('');
        }).catch(() => {
            this.submitButton.stopLoading();
        });
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const showCompleted = $data.data('show-completed');
    const nameSort = $data.data('name-sort');

    const routes = new TimeTrackerRoutes();
    routes.addTemplateFromJoined($data.data('route-time-entry-index'));
    routes.addTemplateFromJoined($data.data('route-task-view'));

    const taskTable = new TaskTable('.js-task-list', nameSort, routes);
    const createForm = new CreateTaskForm('.js-task-create');
    createForm.taskCreated.addObserver((task) => {
        taskTable.addTask(task);
    });

    $('.js-task-list')
        .on('change',
            '.js-task-completed',
            (event) => {
                const $target = $(event.currentTarget);
                const checked = $target.is(':checked');
                const taskId = $target.data('task-id') as string;

                $target.attr('disabled', 'true');
                $target.removeAttr('disabled');
                const $loading = $target.parent().parent().find('.js-loading');
                $loading.removeClass('d-none');

                TaskApi.check(taskId, checked)
                    .then((res: JsonResponse<ApiTask>) => {
                        if (!showCompleted) {
                            $target.closest('.js-task').remove();
                            return;
                        }

                        if (res.data.completedAt && res.data.completedAtEpoch) {
                            const now = (new Date()).getTime();
                            const html = `
                            <div><strong>Completed</strong></div>
                                <div
                                    class="time-ago js-task-ago ml-1"
                                    data-created-at="${res.data.completedAtEpoch}">
                                    ${timeAgo(res.data.completedAtEpoch * 1000, now)}
                                </div>
                            <div class="ml-1 datetime js-completed-at">${res.data.completedAt}</div>
                            `;

                            $target.parent().parent().parent().find('.js-task-completed').append($(html));
                        } else {
                            $target.parent().parent().parent().find('.js-task-completed').html('');
                        }

                        $loading.addClass('d-none');
                    })
                    .catch(() => {
                        $target.removeAttr('disabled');
                        $loading.addClass('d-none');
                    })
            });
});