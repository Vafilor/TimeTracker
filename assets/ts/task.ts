import '../styles/task.scss';
import '../styles/partials/task_time_entry.scss';

import $ from "jquery";
import AutoMarkdown from "./components/automarkdown";
import { TaskApi } from "./core/api/task_api";
import TaskTimeEntry from "./components/task_time_entry";
import { ApiTimeEntry, TimeEntryApi } from "./core/api/time_entry_api";
import { formatShortTimeDifference, timeAgo } from "./components/time";
import { createTagView } from "./components/tags";
import TagList, { TagListDelegate } from "./components/tag_index";
import Flashes from "./components/flashes";
import { ApiTag } from "./core/api/tag_api";
import { TagAssigner } from "./components/tag_assigner";
import { CreateTaskForm, TaskList } from "./components/task";
import { Breadcrumbs } from "./components/breadcrumbs";
import { ParentTaskAssigner } from "./components/parent_task_assigner";

class TaskApiAdapter implements TagListDelegate {
    constructor(private taskId: string, private flashes: Flashes) {
    }

    addTag(tag: ApiTag): Promise<ApiTag> {
        return TaskApi.addTag(this.taskId, tag.name)
            .then(res => {
                return res.data;
            })
            .catch(res => {
                this.flashes.append('danger', `Unable to add tag '${tag.name}'`)
                throw res;
            });
    }

    removeTag(tagName: string): Promise<void> {
        return TaskApi.removeTag(this.taskId, tagName)
            .catch(res => {
                this.flashes.append('danger', `Unable to add remove tag '${tagName}'`)
                throw res;
            });
    }
}

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

function updateTotalTime(taskId: string) {
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

$(document).ready(() => {
    const $data = $('.js-data');
    const taskId = $data.data('task-id');
    const durationFormat = $data.data('duration-format');
    const flashes = new Flashes($('#fixed-flash-messages'));

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
        updateTotalTime(taskId);
    });

    TimeEntryApi.index({taskId})
        .then(res => {
            for(const timeEntry of res.data.data) {
                timeEntryActivity.append(timeEntry);
            }
        })

    updateTotalTime(taskId);

    const $tagList = $('.js-tags');
    const tagList = new TagList($tagList, new TaskApiAdapter(taskId, flashes));
    const $template = $('.js-autocomplete-tags-container');

    const tagEdit = new TagAssigner($template, tagList, flashes);

    const taskTable = new TaskList($('.js-task-list'), true, flashes);
    const createForm = new CreateTaskForm($('.js-task-create'), taskId);
    createForm.taskCreated.addObserver((response) => {
        taskTable.addTask(response.task, response.view);
    });

    const breadcrumbs = new Breadcrumbs($('.js-breadcrumbs'));
    const taskAssigner = new ParentTaskAssigner($('.js-autocomplete-task'), taskId, flashes);
    taskAssigner.parentTaskAssigned.addObserver(async (taskId: string) => {
        breadcrumbs.loader = true;
        const html = await TaskApi.getLineageHtml(taskId);
        breadcrumbs.setHtml(html);
        breadcrumbs.loader = false;
    });

    taskAssigner.parentTaskRemoved.addObserver(async (taskId: string) => {
        breadcrumbs.loader = true;
        const html = await TaskApi.getLineageHtml(taskId);
        breadcrumbs.setHtml(html);
        breadcrumbs.loader = false;
    })
});
