import '../styles/task_index.scss';

import $ from 'jquery';
import Flashes from "./components/flashes";
import { CreateTaskForm, TaskList, TaskListFilter } from "./components/task";

$(document).ready(() => {
    const $data = $('.js-data');
    const showCompleted = $data.data('show-completed');

    const flashes = new Flashes($('#fixed-flash-messages'));
    const filter = new TaskListFilter($('.filter'), flashes);

    const taskTable = new TaskList($('.js-task-list'), showCompleted, flashes);
    const createForm = new CreateTaskForm($('.js-task-create'));
    createForm.taskCreated.addObserver((response) => {
        taskTable.addTask(response.task, response.view);
    });
});

