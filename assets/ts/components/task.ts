import Observable, { Observer } from "./observable";
import $ from "jquery";
import Flashes from "./flashes";
import { ApiTask, CreateTaskResponse, TaskApi } from "../core/api/task_api";
import LoadingButton from "./loading_button";
import { TagFilter } from "./tag_filter";

export class TaskList {
    private tasks = new Map<string, TaskListItem>()
    private observers = new Map<string, Observer<TaskListItem>>();

    constructor(
        public readonly $container: JQuery,
        private showCompleted: boolean,
        private flashes: Flashes) {

        this.$container = $container;
        this.flashes = flashes;

        this.$container.find('.js-task').each((index, element) => {
            this.addTaskElement($(element));
        })

    }

    private addTaskElement($element: JQuery) {
        const taskItem = new TaskListItem($element);
        this.tasks.set(taskItem.taskId, taskItem);

        const observer = (item) => { this.onTaskChecked(item) };
        this.observers.set(taskItem.taskId, observer);
        taskItem.checkedChange.addObserver(observer);
    }

    async onTaskChecked(taskListItem: TaskListItem) {
        taskListItem.loading = true;
        taskListItem.disabled = true;

        try {
            const response = await TaskApi.check(taskListItem.taskId, taskListItem.checked)
            const completed = !!response.data.completedAt;

            if (this.showCompleted) {
                taskListItem.completed = completed;
            } else {
                this.removeItem(taskListItem);
            }
        } catch (e) {
            const action = taskListItem.completed ? 'uncomplete' : 'complete';
            this.flashes.append('danger', `Unable to ${action} task`);
        }

        taskListItem.disabled = false;
        taskListItem.loading = false;
    }

    private removeItem(taskListItem: TaskListItem) {
        taskListItem.$container.remove();
        const observer = this.observers.get(taskListItem.taskId);
        if (observer) {
            taskListItem.checkedChange.removeObserver(observer);
        }

        this.tasks.delete(taskListItem.taskId);
    }

    public addTask(task: ApiTask, view: string) {
        const $view = $(view);
        this.addTaskElement($view);
        this.$container.prepend($view);

    }
}

export class CreateTaskForm {
    private $input: JQuery;
    private submitButton: LoadingButton;
    public readonly taskCreated = new Observable<CreateTaskResponse>();

    constructor(private $container, private parentId?: string) {
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
            parentTask: this.parentId
        }).then((res) => {
            this.submitButton.stopLoading();
            this.taskCreated.emit(res.data);
            this.$input.val('');
        }).catch(() => {
            this.submitButton.stopLoading();
        });
    }
}

export class TaskListFilter {
    private $element: JQuery;
    private flashes: Flashes;
    private tagFilter: TagFilter;

    constructor($element: JQuery, flashes: Flashes) {
        this.$element = $element;
        this.flashes = flashes;
        this.tagFilter = new TagFilter($element);
    }
}

export class TaskListItem {
    public readonly taskId: string;
    public readonly $container: JQuery;
    private $loadingIndicator: JQuery;
    private $checkbox: JQuery;

    private _checked: boolean;
    public get checked(): boolean {
        return this._checked;
    }
    public set checked(value: boolean) {
        this._checked = value;

        this.$checkbox.prop('checked', value);
    }

    private _completed: boolean;
    public get completed(): boolean {
        return this._completed
    }
    public set completed(value: boolean) {
        this._completed = value;

        if (value) {
            this.$container.addClass('completed');
        } else {
            this.$container.removeClass('completed');
        }
    }

    private _loading: boolean;
    public get loading(): boolean {
        return this._loading;
    }
    public set loading(value: boolean) {
        this._loading = value;

        if (value) {
            this.$loadingIndicator.removeClass('d-none');
        } else {
            this.$loadingIndicator.addClass('d-none');
        }
    }

    private _disabled: boolean;
    public get disabled(): boolean {
        return this._disabled;
    }

    public set disabled(value: boolean) {
        this._disabled = value;

        if (value) {
            this.$container.addClass('disabled');
            this.$checkbox.prop('disabled', 'true');
        } else {
            this.$container.removeClass('disabled');
            this.$checkbox.removeAttr('disabled');
        }
    }

    public checkedChange = new Observable<TaskListItem>();

    constructor($container: JQuery) {
        this.$container = $container;
        this.taskId = $container.data('id');
        this._completed = $container.data('completed');
        this._disabled = false;
        this._loading = false;
        this.$loadingIndicator = $container.find('.js-loading');

        this.$checkbox = $container.find('input[type=checkbox]');
        this.$checkbox.on('change', (event) => {
            this._checked = $(event.currentTarget).is(':checked');
            this.checkedChange.emit(this);
        })
    }
}