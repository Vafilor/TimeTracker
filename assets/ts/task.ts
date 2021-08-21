import '../styles/task.scss';
import '../styles/partials/task_time_entry.scss';

import $ from "jquery";
import { TaskApi } from "./core/api/task_api";
import TaskTimeEntry from "./components/task_time_entry";
import { TimeEntryApi } from "./core/api/time_entry_api";
import { formatShortTimeDifference, timeAgo } from "./components/time";
import { createTagView } from "./components/tags";
import Flashes from "./components/flashes";
import { CreateTaskForm, TaskList } from "./components/task";
import { Breadcrumbs } from "./components/breadcrumbs";
import { ParentTaskAssigner } from "./components/parent_task_assigner";
import { ApiTimeEntry } from "./core/api/types";

export class TimeEntryActivity {
    private $container: JQuery;

    constructor(selector: string) {
        this.$container = $(selector);
    }

    private createTemplate(timeEntry: ApiTimeEntry): string {
        if (!timeEntry.endedAtEpoch) {
            throw new Error('timeEntry.endedAtEpoch is undefined');
        }

        const now = new Date();
        const createdAgo = timeAgo(timeEntry.startedAtEpoch * 1000, now.getTime());
        const description = timeEntry.description && timeEntry.description.length > 0 ? timeEntry.description : 'No description';
        const duration = formatShortTimeDifference(timeEntry.startedAtEpoch * 1000, timeEntry.endedAtEpoch * 1000);

        let tags = '';
        for(const tag of timeEntry.tags) {
            tags += createTagView(tag.name, tag.color, 'tag-sm');
        }

        return `
            <div class="time-entry-activity">
                <a href="${timeEntry.url}" class="created-ago">${createdAgo} for ${duration}</a>
                <div>${tags}</div>
                <div class="created-at">${timeEntry.createdAt} - ${timeEntry.endedAt}</div>
                <div class="description">${description}</div>
            </div>
            <hr/>`;
    }

    prepend(timeEntry: ApiTimeEntry) {
        if (!timeEntry.endedAt) {
            return;
        }
        const $element = $(this.createTemplate(timeEntry));

        this.$container.prepend($element);
    }

    append(timeEntry: ApiTimeEntry) {
        if (!timeEntry.endedAt) {
            return;
        }
        const $element = $(this.createTemplate(timeEntry));

        this.$container.append($element);
    }
}

export function updateTotalTime(taskId: string) {
    const $element = $('.js-total-time');
    const $value = $element.find('.js-value');
    const $loading = $element.find('.js-loading');

    $loading.removeClass('d-none');

    TaskApi.reportForTask(taskId)
        .then(res => {
            $value.text(res.data.totalTime);
            $loading.addClass('d-none');
        })
}
