import '../styles/task_index.scss';

import $ from 'jquery';
import { ApiTask, TaskApi } from "./core/api/task_api";
import { JsonResponse } from "./core/api/api";
import LoadingButton from "./components/loading_button";
import TimeTrackerRoutes from "./core/routes";
import Observable from "./components/observable";

class TaskTable {
    private $container: JQuery;
    private $rows: JQuery;
    private routes: TimeTrackerRoutes;
    private readonly nameSort: string;

    constructor(
        selector: string,
        nameSort: string,
        routes: TimeTrackerRoutes) {
        this.$container = $(selector);
        this.nameSort = nameSort;
        this.routes = routes;
        this.$rows = this.$container.find('tbody');
    }

    public createTableRow(task: ApiTask) {
        const timeEntryRoute = this.routes.timeEntryIndex({taskId: task.id});
        const taskViewRoute = this.routes.taskView(task.id);

        return `
        <tr>
            <td>
                <input data-task-id="${task.id}" type="checkbox" class="js-task-completed" />
            </td>
            <td>${task.name}</td>
            <td>${task.createdAt}</td>
            <td>${task.description.slice(0, 50)}</td>
            <td><a href="${timeEntryRoute}">Time Entries</a></td>
            <td><a href="${taskViewRoute}" class="btn btn-primary">View</a></td>
        </tr>`;
    }

    addTask(task: ApiTask) {
        const $row = $(this.createTableRow(task));

        if (this.nameSort === 'asc') {
            this.$rows.find('tr').each((index, element) => {
                const name = $(element).data('name') as string;

                if (task.name < name) {
                    $row.insertBefore(element);
                    return false;
                }
            });
        } else if (this.nameSort === 'desc') {
            this.$rows.find('tr').each((index, element) => {
                const name = $(element).data('name') as string;

                if (task.name > name) {
                    $row.insertBefore(element);
                    return false;
                }
            });
        } else {
            this.$rows.prepend($row);
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
    const dateFormat = $data.data('date-format');
    const showCompleted = $data.data('show-completed');
    const nameSort = $data.data('name-sort');

    const routes = new TimeTrackerRoutes();
    routes.addTemplateFromJoined($data.data('route-time-entry-index'));
    routes.addTemplateFromJoined($data.data('route-task-view'));

    const taskTable = new TaskTable('.js-task-table', nameSort, routes);
    const createForm = new CreateTaskForm('.js-task-create');
    createForm.taskCreated.addObserver((task) => {
        taskTable.addTask(task);
    });

    $('.js-task-table tbody')
        .on('change',
            '.js-task-completed',
            (event) => {
                const $target = $(event.currentTarget);
                const checked = $target.is(':checked');
                const taskId = $target.data('task-id') as string;

                $target.attr('disabled', 'true');

                TaskApi.check(taskId, checked)
                    .then((res: JsonResponse<ApiTask>) => {
                        $target.removeAttr('disabled');
                        $target.parent().find('.js-completed-at').remove();

                        if (res.data.completedAt && showCompleted) {
                            $target.parent().append(`<span class="ml-1 js-completed-at">${res.data.completedAt}</span>`);
                        } else {
                            $target.parent().parent().remove();
                        }
                    })
                    .catch(() => {
                        $target.removeAttr('disabled');
                    })
            });
});