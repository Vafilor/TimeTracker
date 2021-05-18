import '../styles/task.scss';
import '../styles/partials/task_time_entry.scss';

import AutoMarkdown from "./components/automarkdown";
import $ from "jquery";
import { TaskApi } from "./core/api/task_api";
import TaskTimeEntry from "./components/task_time_entry";

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

    const timeEntry = new TaskTimeEntry(taskId);
    timeEntry.setDurationFormat(durationFormat);
    timeEntry.initialize();

});
