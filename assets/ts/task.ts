import '../styles/task.scss';
import '../styles/partials/task_time_entry.scss';

import AutoMarkdown from "./components/automarkdown";
import $ from "jquery";
import { TaskApi } from "./core/api/task_api";
import TaskTimeEntry from "./components/task_time_entry";
import { ApiTimeEntry, TimeEntryApi } from "./core/api/time_entry_api";
import { timeAgo } from "./components/time";

class TaskEntryAutoMarkdown extends AutoMarkdown {
    private readonly taskId: string;

    constructor(
        inputSelector: string,
        markdownSelector: string,
        loadingSelector: string,
        taskId: string) {
        super(inputSelector, markdownSelector, loadingSelector);
        this.taskId = taskId;
    }

    protected update(body: string): Promise<any> {
        return TaskApi.update(this.taskId, {
            description: body,
        });
    }
}

class TimeEntryActivity {
    private $container: JQuery;

    constructor(selector: string) {
        this.$container = $(selector);
    }

    private createTemplate(timeEntry: ApiTimeEntry): string {
        const now = new Date();
        const createdAgo = timeAgo(timeEntry.startedAtEpoch * 1000, now.getTime());
        const description = timeEntry.description && timeEntry.description.length > 0 ? timeEntry.description : 'No description';


        // // <div>${timeEntry.duration}</div>
        return `
            <div class="time-entry-activity">
                <a href="${timeEntry.url}" class="created-ago">${createdAgo}</a>
              
                <div class="created-at">${timeEntry.createdAt}</div>
                <div class="description">${description}</div>
            </div>`;
    }

    prepend(timeEntry: ApiTimeEntry) {
        const $element = $(this.createTemplate(timeEntry));

        this.$container.prepend($element);
    }

    append(timeEntry: ApiTimeEntry) {
        const $element = $(this.createTemplate(timeEntry));

        this.$container.append($element);
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const taskId = $data.data('task-id');
    const durationFormat = $data.data('duration-format');

    const autoMarkdown = new TaskEntryAutoMarkdown(
        '.js-description',
        '#preview-content',
        '.markdown-link',
        taskId
    );

    const timeEntryActivity = new TimeEntryActivity('.js-task-time-entry-activity');

    const taskTimeEntry = new TaskTimeEntry(taskId);
    taskTimeEntry.setDurationFormat(durationFormat);
    taskTimeEntry.initialize();
    taskTimeEntry.stopped.addObserver((timeEntry: ApiTimeEntry) => {
       timeEntryActivity.prepend(timeEntry);
    });

    TimeEntryApi.index({taskId})
        .then(res => {
            for(const timeEntry of res.data.data) {
                timeEntryActivity.append(timeEntry);
            }
        })
});
