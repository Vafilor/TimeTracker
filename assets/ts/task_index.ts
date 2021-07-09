import '../styles/task_index.scss';

import $ from 'jquery';
import { ApiTask, TaskApi } from "./core/api/task_api";
import { JsonResponse } from "./core/api/api";
import LoadingButton from "./components/loading_button";
import TimeTrackerRoutes from "./core/routes";
import Observable from "./components/observable";
import { createTagsView } from "./components/tags";

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
        const timeEntriesViewRoute = this.routes.timeEntryIndex({taskId: task.id});
        const taskViewRoute = this.routes.taskView(task.id);
        const tagAdjustmentClass = task.tags.length !== 0 ? 'mt-1' : '';

        let tagHtml = createTagsView(task.tags);

        return `
        <div
            class="stack-list-item task-list-item js-task"
            data-id="${task.id}"
        >
            <div class="d-flex align-items-baseline">
                <div class="spinner spinner-border spinner-border-sm text-primary js-loading mr-2 d-none" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <div class="form-check flex-grow-1">
                    <input
                        id="${task.id}"
                        data-task-id="${task.id}"
                        type="checkbox"
                        class="form-check-input js-task-completed"
                    <label for="{{ task.idString }}">${task.name}</label>
                </div>
                <div class="flex-shrink-0">
                    <a href="${timeEntriesViewRoute}">Time Entries</a>
                    <a href="${taskViewRoute}" class="btn btn-primary js-view ml-2">View</a>
                </div>
            </div>
            <div class="tag-list js-tag-list many-rows ${tagAdjustmentClass}">
                <div class="js-tag-list-view">
                    ${tagHtml}
                </div>
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

                const $nameLabel = $target.parent().parent().find('.js-name-label')

                TaskApi.check(taskId, checked)
                    .then((res: JsonResponse<ApiTask>) => {
                        if (!showCompleted) {
                            $target.closest('.js-task').remove();
                            return;
                        }

                        $loading.addClass('d-none');
                        if (res.data.completedAt) {
                            $nameLabel.addClass('completed');
                        } else {
                            $nameLabel.removeClass('completed');
                        }
                    })
                    .catch(() => {
                        $target.removeAttr('disabled');
                        $loading.addClass('d-none');
                    })
            });
});