import { Controller } from '@hotwired/stimulus';

import $ from "jquery";

import Flashes from "../ts/components/flashes";
import TaskTimeEntry from "../ts/components/task_time_entry";
import { TimeEntryApi } from "../ts/core/api/time_entry_api";
import { CreateTaskForm, TaskList } from "../ts/components/task";
import { Breadcrumbs } from "../ts/components/breadcrumbs";
import { ParentTaskAssigner } from "../ts/components/parent_task_assigner";
import { TaskApi } from "../ts/core/api/task_api";
import { TimeEntryActivity, updateTotalTime } from "../ts/task";

/**
 * This is a placeholder class that reproduces the logic of the former Task page.
 * The components in here need to be designed to work with stimulus + turbo
 */
export default class extends Controller {
    connect() {
        const $data = $('.js-data');
        const taskId = $data.data('task-id');
        const durationFormat = $data.data('duration-format');
        const flashes = new Flashes($('#fixed-flash-messages'));

        const timeEntryActivity = new TimeEntryActivity('.js-task-time-entry-activity');

        const taskTimeEntry = new TaskTimeEntry(taskId);
        taskTimeEntry.setDurationFormat(durationFormat);
        taskTimeEntry.initialize();
        taskTimeEntry.stopped.addObserver((timeEntry) => {
            timeEntryActivity.prepend(timeEntry);
            updateTotalTime(taskId);
        });

        TimeEntryApi.index({taskId})
            .then(res => {
                for(const timeEntry of res.data.data) {
                    timeEntryActivity.append(timeEntry);
                }
            })

        updateTotalTime(taskId);

        const breadcrumbs = new Breadcrumbs($('.js-breadcrumbs'));
        const taskAssigner = new ParentTaskAssigner($('.js-autocomplete-task'), taskId, flashes);
        taskAssigner.parentTaskAssigned.addObserver(async (taskId) => {
            breadcrumbs.loader = true;
            const html = await TaskApi.getLineageHtml(taskId);
            breadcrumbs.setHtml(html);
            breadcrumbs.loader = false;
        });

        taskAssigner.parentTaskRemoved.addObserver(async (taskId) => {
            breadcrumbs.loader = true;
            const html = await TaskApi.getLineageHtml(taskId);
            breadcrumbs.setHtml(html);
            breadcrumbs.loader = false;
        })
    }
}