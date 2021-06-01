import '../styles/time_entry_index.scss';

import $ from 'jquery';
import 'bootstrap'; // Adds functions to jQuery

import {
    ApiTimeEntry,
    CreateTimeEntryResponse,
    DateFormat,
    TimeEntryApi,
    TimeEntryApiErrorCode
} from "./core/api/time_entry_api";
import Flashes from "./components/flashes";
import LoadingButton from "./components/loading_button";
import { ApiTag } from "./core/api/tag_api";
import { ApiTask } from "./core/api/task_api";
import TagList from "./components/tag_index";
import { SyncInput, SyncStatus } from "./components/sync_input";
import { ApiErrorResponse, ApiResourceError, JsonResponse } from "./core/api/api";
import { ConfirmClickEvent, ConfirmDialog } from "./components/confirm_dialog";
import { TimeEntryApiAdapter } from "./components/time_entry_api_adapater";
import { EditDateTime } from "./components/edit_date_time";
import { DateTimeParts } from "./core/datetime";
import { TimeEntryTaskAssigner } from "./components/time_entry_task_assigner";
import { TimeEntryTagAssigner } from "./components/time_entry_tag_assigner";
import TimerView from "./components/timer";
import AutocompleteTags from "./components/autocomplete_tags";
import AutocompleteTask from "./components/autocomplete_task";
import MarkdownView from "./components/markdown_view";

interface TimeEntryActionDelegate {
    continue(timeEntryId: string): Promise<any>;
}

/**
 * TimeEntryDescriptionSync connects to an editable content area and updates a
 * TimeEntry's description with it via API calls.
 *
 * The status/progress of the updates is reported as the events occur.
 */
class TimeEntryDescriptionSync {
    private readonly $container: JQuery;
    private readonly timeEntryId: string;
    private readonly $status: JQuery;
    private readonly $editable: JQuery;
    private status: SyncStatus = 'up-to-date';
    private syncDescription: SyncInput;

    public static template(content: string, extraClass: string): string {
        return `
            <div class="js-time-entry-description-sync ${extraClass}">
                <textarea class="js-content-edit w-100" rows="2">${content}</textarea>
                <div class="timestamp js-status">Up to date</div>
            </div>
        `;

    }

    constructor($container: JQuery, timeEntryId: string) {
        this.$container = $container;
        this.timeEntryId = timeEntryId;

        this.$editable = this.$container.find('.js-content-edit');
        this.$status = this.$container.find('.js-status');

        this.syncDescription = new SyncInput(
            this.$editable,
            (content: string) => this.onContentFinishChange(content),
            () => this.onContentChange()
        );

        this.syncDescription.start()
    }

    async onContentFinishChange(content: string) {
        this.status = 'updating';

        this.$status.html(`
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div> Updating...`);

        await TimeEntryApi.update(this.timeEntryId, {
            description: content,
        });

        this.status = 'up-to-date';
        this.$status.text('Up to date');
    }

    onContentChange() {
        if (this.status === 'modified') {
            return;
        }

        this.status = 'modified';
        this.$status.text('Modified');
    }

    get data(): string {
        return this.$editable.val() as string;
    }

    dispose() {
        this.syncDescription.stop();
        this.$container.remove();
    }
}

class TimeEntryMarkdownDescriptionSync{
    static markdownConverter?: any;
    static gettingMarkdownConverter = false;

    constructor() {

        if (!TimeEntryMarkdownDescriptionSync.markdownConverter && !TimeEntryMarkdownDescriptionSync.gettingMarkdownConverter) {
            TimeEntryMarkdownDescriptionSync.gettingMarkdownConverter = true;
            import('showdown').then(res => {
                TimeEntryMarkdownDescriptionSync.markdownConverter = new res.Converter();
                TimeEntryMarkdownDescriptionSync.gettingMarkdownConverter = false;
            });
        }
    }
    //
    // protected updateViewContent(content: string) {
    //     this.$view.data('description', content);
    //
    //     if (MarkdownEditableContent.markdownConverter) {
    //         content = MarkdownEditableContent.markdownConverter.makeHtml(content);
    //     }
    //
    //     this.$view.html(content);
    // }
}

class TimeEntryIndexItem {
    // TODO it might be a good idea to split this up into a view and edit mode or something
    // That way you can get rid of all the optionals? So this class becomes a manager?
    private readonly id: string;
    private taskId?: string;
    private taskName?: string;
    private readonly dateFormat: DateFormat;
    private taskEdit?: TimeEntryTaskAssigner;
    private readonly flashes: Flashes;

    private $element: JQuery;
    private $viewButton: JQuery;
    private $editButton: JQuery;
    private $continueButton: JQuery;
    private $activityIndicator: JQuery;
    private stopButton?: LoadingButton;
    private durationTimer?: TimerView;

    private descriptionView: MarkdownView;
    private descriptionEditView?: TimeEntryDescriptionSync;
    private tagEdit?: TimeEntryTagAssigner;
    private startedEdit?: EditDateTime;
    private endedEdit?: EditDateTime;
    private updateButton?: LoadingButton;
    private delegate: TimeEntryActionDelegate;

    get assignedToTask(): boolean {
        return this.taskId !== undefined;
    }

    constructor($element: JQuery, delegate: TimeEntryActionDelegate, durationFormat: string, dateFormat: DateFormat, flashes: Flashes) {
        this.$element = $element;
        this.delegate = delegate;
        this.id = $element.data('id');
        this.dateFormat = dateFormat;
        this.$viewButton = $element.find('.js-view');
        this.$editButton = $element.find('.js-edit');
        this.$activityIndicator = $element.find('.js-time-entry-activity');
        this.flashes = flashes;
        this.taskId = $element.data('task-id');
        this.taskName = $element.data('task-name');
        this.descriptionView = new MarkdownView($element.find('.js-description'));
        this.$continueButton = $element.find('.js-continue');
        this.$continueButton.on('click', () => this.delegate.continue(this.id));

        const $stop = $element.find('.js-stop');
        if ($stop.length !== 0) {
            this.stopButton = new LoadingButton($element.find('.js-stop'));
            this.stopButton.$container.on('click', () => this.stop());
        }

        const $durationTimer = $element.find('.js-duration.active');
        if ($durationTimer.length !== 0) {
            this.durationTimer = new TimerView($durationTimer, durationFormat);
            this.durationTimer.start($durationTimer.data('start') * 1000);
        }

        $element.find('.js-edit').on('click', () => this.onEdit());
    }

    private hideViewButtons() {
        this.$viewButton.addClass('d-none');
        this.$editButton.addClass('d-none');
        this.$continueButton.addClass('d-none');

        if (this.stopButton) {
            this.stopButton.$container.addClass('d-none');
        }
    }

    private showDoneEditButtons() {
        const $element = $(`
            <button class="btn btn-primary js-update">
                <span class="spinner-border spinner-border-sm d-none js-loading" role="status" aria-hidden="true"></span>
                Done editing
            </button>`);

        $element.on('click', () => this.onFinishEdit());
        this.updateButton = new LoadingButton($element);

        this.$element.find('.js-actions').append($element);
    }

    private removeDoneEditButtons() {
        const $element = this.$element.find('.js-actions .js-update');
        $element.remove();
        this.updateButton = undefined;
    }

    private showViewButtons() {
        this.$viewButton.removeClass('d-none');
        this.$editButton.removeClass('d-none');
        this.$continueButton.removeClass('d-none');

        if (this.stopButton) {
            this.stopButton.$container.removeClass('d-none');
        }
    }

    private startTaskEdit() {
        const $task = this.$element.find('.js-task');
        $task.addClass('d-none');

        const $newElement = $(TimeEntryTaskAssigner.template());
        $newElement.insertAfter($task);

        this.taskEdit = new TimeEntryTaskAssigner($newElement, this.id,  this.flashes);
        if (this.taskId && this.taskName) {
            this.taskEdit.setTaskSimple(this.taskId, this.taskName);
        }
    }

    private finishTaskEdit() {
        const $task = this.$element.find('.js-task');

        if (this.taskEdit) {
            const task = this.taskEdit.getTask();
            if (task) {
                this.taskId = task.id;
                this.taskName = task.name;
                $task.find('.js-task-content').remove();
                $task.append($(`<a class="js-task-content" href="${task.url}">${task.name}</a>`));
            } else {
                $task.find('.js-task-content').remove();
                $task.append(`<div class="js-task-content">No task</div>`);
                this.taskId = undefined;
                this.taskName = undefined;
            }

            this.taskEdit.getContainer().remove();
            this.taskEdit = undefined;
        }

        $task.removeClass('d-none');
    }

    private startContentEdit() {
        this.descriptionView.hide();

        const $html = $(TimeEntryDescriptionSync.template(this.descriptionView.data, 'mt-2'));

        $html.insertAfter(this.descriptionView.$container);

        this.descriptionEditView = new TimeEntryDescriptionSync($html, this.id)
    }

    private finishContentEdit() {
        this.descriptionView.data = this.descriptionEditView!.data;
        this.descriptionEditView?.dispose();
        this.descriptionEditView = undefined;
        this.descriptionView.show();
    }

    private startTagEdit() {
        const $tagList = this.$element.find('.js-tag-list');
        $tagList.find('.js-tag-list-view').addClass('d-none');

        const $tagEditList = $('<div class="js-tag-edit-list d-inline-block"></div>');
        $tagEditList.data(TagList.initialDataKey, $tagList.data(TagList.initialDataKey));
        $tagList.append($tagEditList);

        const tagList = new TagList($tagEditList, new TimeEntryApiAdapter(this.id, this.flashes));
        const $template = $(TimeEntryTagAssigner.template());
        $tagList.append($template);

        this.tagEdit = new TimeEntryTagAssigner($template, tagList, this.flashes);
    }

    private finishTagEdit() {
        const $tagList = this.$element.find('.js-tag-list');
        const $tagView = $tagList.find('.js-tag-list-view');

        if (this.tagEdit) {
            $tagView.html('');
            for(const tag of this.tagEdit.getTagList().getTags()) {
                const tagHtml = `<div class="tag" data-name="${tag.name}" style="background-color: ${tag.color};">${tag.name}</div> `;
                $tagView.append(tagHtml);
            }

            const tagNames = this.tagEdit.getTagList().getTagNamesCommaSeparated();
            $tagList.data(TagList.initialDataKey, tagNames);
            $tagList.find('.js-tag-edit-list').remove();
            this.tagEdit.$container.remove();
            this.tagEdit = undefined;
        }

        $tagView.removeClass('d-none');
    }

    private startTimestampEdit() {
        const $timestamps = this.$element.find('.js-timestamps');

        const $started = $timestamps.find('.js-started-at');
        $started.addClass('d-none');

        const $ended = $timestamps.find('.js-ended-at');
        $ended.addClass('d-none');

        const $startedEdit = $(EditDateTime.templateWithLabel('Started', 'js-edit-started-at'));
        const $endedEdit = $(EditDateTime.templateWithLabel('Ended', 'js-edit-ended-at ml-2'));

        $timestamps.append($startedEdit);
        $timestamps.append($endedEdit);

        this.startedEdit = new EditDateTime($startedEdit, $started.data('timestamp'));
        this.endedEdit = new EditDateTime($endedEdit, $ended.data('timestamp'));
    }

    private getTimestampEditUpdate(): Promise<JsonResponse<ApiTimeEntry>>|Promise<void> {
        let updateStarted: DateTimeParts|undefined = undefined;
        let updateEnded: DateTimeParts|undefined = undefined;

        const $timestamps = this.$element.find('.js-timestamps');
        const $started = $timestamps.find('.js-started-at');
        const $ended = $timestamps.find('.js-ended-at');

        if (this.startedEdit) {
            const dateTime = this.startedEdit.getDateTime();
            if (dateTime) {
                const dateTimeString = dateTime.date + ' ' + dateTime.time;
                if ($started.data('timestamp') != dateTimeString) {
                    updateStarted = dateTime;
                }
            }
        }

        if (this.endedEdit) {
            const dateTime = this.endedEdit.getDateTime();
            if (dateTime) {
                const dateTimeString = dateTime.date + ' ' + dateTime.time;
                if ($ended.data('timestamp') != dateTimeString) {
                    updateEnded = dateTime;
                }
            }
        }

        if (updateStarted || updateEnded) {
            return TimeEntryApi.update(this.id, {
                startedAt: updateStarted,
                endedAt: updateEnded
            })
        }

        return Promise.resolve();
    }

    private finishTimestampEdit() {
        const $timestamps = this.$element.find('.js-timestamps');
        const $started = $timestamps.find('.js-started-at');
        const $ended = $timestamps.find('.js-ended-at');

        if (this.startedEdit) {
            const dateTime = this.startedEdit.getDateTime();
            if (dateTime) {
                const dateTimeString = dateTime.date + ' ' + dateTime.time;
                $started.text(dateTimeString);
                $started.data('timestamp', dateTimeString);
            }

            this.startedEdit.$container.remove();
            this.startedEdit = undefined;
        }


        if (this.endedEdit) {
            const dateTime = this.endedEdit.getDateTime();
            if (dateTime) {
                const dateTimeString = dateTime.date + ' ' + dateTime.time;
                $ended.data('timestamp', dateTimeString);
            }

            this.endedEdit.$container.remove();
            this.endedEdit = undefined;
        }

        $started.removeClass('d-none');
        $ended.removeClass('d-none');
    }

    stopUI(timeEntry: ApiTimeEntry) {
        this.$element.find('.js-ended-at').text('- ' + timeEntry.endedAt);
        this.$element.find('.js-duration').text(timeEntry.duration);

        this.durationTimer?.stop();
        this.$activityIndicator.remove();
    }

    async stop() {
        this.stopButton?.startLoading();

        try {
            const res = await TimeEntryApi.stop(this.id, this.dateFormat)
            this.$element.find('.js-ended-at').text('- ' + res.data.endedAt);
            this.$element.find('.js-duration').text(res.data.duration);

            if (this.durationTimer) {
                this.durationTimer.stop();
            }

            this.stopButton?.stopLoading();
            this.stopButton?.$container.remove();
            this.stopButton = undefined;

            this.$activityIndicator.remove();

            if (this.$element.find('.js-continue').length === 0) {
                this.$continueButton = $(`<button type="button" class="btn btn-secondary js-continue ml-2">Continue</button>`);
                this.$continueButton.on('click', () => this.delegate.continue(this.id));
                this.$element.find('.js-actions').append(this.$continueButton);
            }

        } catch (e) {
            this.flashes.append('danger', 'Unable to stop time entry');
            this.stopButton?.stopLoading();
        }
    }

    onEdit() {
        this.$element.find('.js-time-entry-activity').addClass('d-none');
        this.hideViewButtons();
        this.showDoneEditButtons();
        this.startTaskEdit();
        this.startTagEdit();
        this.startContentEdit();
        this.startTimestampEdit();
    }

    async onFinishEdit() {
        this.updateButton?.startLoading();
        try {
            const res = await this.getTimestampEditUpdate();
            const jsonRes = res as JsonResponse<ApiTimeEntry>;
            if (jsonRes) {
                const $timestamps = this.$element.find('.js-timestamps');
                const $started = $timestamps.find('.js-started-at');
                $started.text(jsonRes.data.startedAt);

                const $ended = $timestamps.find('.js-ended-at');
                if (jsonRes.data.endedAt) {
                    this.durationTimer?.stop();
                    this.durationTimer = undefined;
                    this.$element.find('.js-duration.active').text(jsonRes.data.duration);
                    this.$element.find('.js-time-entry-activity').remove();
                    $ended.text('- ' + jsonRes.data.endedAt);
                }
            }
        } catch (e) {
            this.flashes.append('danger', 'Unable to update timestamps');
        }

        this.finishTimestampEdit();
        this.finishTagEdit();
        this.showViewButtons();
        this.finishTaskEdit();
        this.finishContentEdit();
        this.updateButton?.stopLoading();
        this.removeDoneEditButtons();
        this.$element.find('.js-time-entry-activity').removeClass('d-none');
    }
}

class TimeEntryList {
    private readonly $container: JQuery;
    /**
     * key is the id of a TimeEntry.
     */
    private timeEntries = new Map<string, TimeEntryIndexItem>();
    private readonly dateFormat: DateFormat;
    private readonly durationFormat: string;
    private readonly flashes: Flashes;
    private readonly delegate: TimeEntryActionDelegate;

    constructor($container: JQuery, delegate: TimeEntryActionDelegate, durationFormat: string, dateFormat: DateFormat, flashes: Flashes) {
        this.$container = $container;
        this.delegate = delegate;
        this.durationFormat = durationFormat;
        this.dateFormat = dateFormat;
        this.flashes = flashes;
    }

    addExisting(id: string, $element: JQuery) {
        this.timeEntries.set(id, new TimeEntryIndexItem($element, this.delegate, this.durationFormat, this.dateFormat, this.flashes));
    }

    /**
     * Adds a new TimeEntry to the list. The entry is added to the top of the list, regardless of sort order
     * so you can always see it.
     */
    add(id: string, $element: JQuery) {
        this.timeEntries.set(id, new TimeEntryIndexItem($element, this.delegate, this.durationFormat, this.dateFormat, this.flashes));
        this.$container.prepend($element);
    }

    /**
     * Stop a timeEntry immediately by providing the data it should have when stopped.
     * If the timeEntry is not in the list, nothing happens.
     */
    stopTimeEntryUI(timeEntry: ApiTimeEntry) {
        const timeEntryIndexItem = this.timeEntries.get(timeEntry.id);
        timeEntryIndexItem?.stopUI(timeEntry);
    }
}

class TimeEntryListFilter {
    private $element: JQuery;
    private flashes: Flashes;
    private autocompleteTags: AutocompleteTags;

    constructor($element: JQuery, flashes: Flashes) {
        this.$element = $element;
        this.flashes = flashes;

        this.setUpTaskFilter();
        this.setUpTagFilter();
    }

    private setUpTaskFilter() {
        // The actual, hidden, form element
        const $realTaskInput = $('.js-real-task-input');

        const autocompleteTask = new AutocompleteTask($('.js-autocomplete-task'));
        autocompleteTask.itemSelected.addObserver((task: ApiTask) => {
            autocompleteTask.setQuery(task.name);
            autocompleteTask.clearSearchContent();
            $realTaskInput.val(task.id);
        })

        autocompleteTask.inputChange.addObserver(() => {
            $realTaskInput.val('');
        })

        autocompleteTask.inputClear.addObserver(() => {
            $realTaskInput.val('');
        })
    }

    private setUpTagFilter() {
        const $tagListFilter = this.$element.find('.js-tags-filter');
        const tagList = new TagList($tagListFilter.find('.js-tag-list'));
        this.autocompleteTags = new AutocompleteTags($tagListFilter.find('.js-autocomplete-tags'));

        this.autocompleteTags.itemSelected.addObserver((apiTag: ApiTag) => {
            tagList.add(apiTag);
            setTimeout(() => {
                this.autocompleteTags.positionSearchContent();
            }, 10);
        })

        const $realTagInput = this.$element.find('.js-real-tag-input');
        tagList.tagsChanged.addObserver(() => {
            this.autocompleteTags.setTagNames(tagList.getTagNames());
            $realTagInput.val(tagList.getTagNamesCommaSeparated());
        });
    }
}

class TimeEntryIndexPage implements TimeEntryActionDelegate{
    private readonly dateFormat: DateFormat;
    private readonly durationFormat: string;
    private readonly flashes: Flashes;
    private timeEntryList: TimeEntryList;
    private createTimeEntryButton: LoadingButton;
    private confirmDialog?: ConfirmDialog;
    private filter: TimeEntryListFilter;

    constructor() {
        const $data = $('.js-data');
        this.dateFormat = $data.data('date-format') as DateFormat;
        this.durationFormat = $data.data('duration-format');
        this.flashes = new Flashes($('#fixed-flash-messages'));
        this.timeEntryList = new TimeEntryList($('.js-time-entry-list'), this, this.durationFormat, this.dateFormat, this.flashes);
        this.createTimeEntryButton = new LoadingButton($('.js-create-time-entry'));
        this.createTimeEntryButton.$container.on('click', () => this.requestToCreateTimeEntry());

        $('.js-time-entry').each((index, element) => {
            const $element = $(element);
            this.timeEntryList.addExisting($element.data('id'), $element);
        });

        this.filter = new TimeEntryListFilter($('.filter'), this.flashes);
    }

    private createTimeEntry(response: CreateTimeEntryResponse) {
        if (!response.template) {
            throw new Error('Response does not have a template');
        }

        const $element = $(response.template);
        this.timeEntryList.add(response.timeEntry.id, $element);
    }

    private async stopTimeEntryAndCreate(timeEntryId: string) {
        this.confirmDialog?.startLoading();

        const res = await TimeEntryApi.stop(timeEntryId, this.dateFormat);
        this.timeEntryList.stopTimeEntryUI(res.data);

        const createResponse = await TimeEntryApi.create({withHtmlTemplate: true}, this.dateFormat);
        this.createTimeEntry(createResponse.data);

        this.confirmDialog?.remove();
        this.confirmDialog = undefined;

    }

    private async stopTimeEntryAndContinue(stopTimeEntryId: string, continueTimeEntryId) {
        this.confirmDialog?.startLoading();

        const res = await TimeEntryApi.stop(stopTimeEntryId, this.dateFormat);
        this.timeEntryList.stopTimeEntryUI(res.data);

        const createResponse = await TimeEntryApi.continue(continueTimeEntryId, {withHtmlTemplate: true});
        this.createTimeEntry(createResponse.data);

        this.confirmDialog?.remove();
        this.confirmDialog = undefined;
    }

    private confirmStopExistingTimer(onConfirm: (arg: void) => void) {
        this.confirmDialog = new ConfirmDialog('btn-danger');
        this.confirmDialog.clicked.addObserver((event: ConfirmClickEvent) => {
            if (event.buttonClicked === 'confirm') {
                onConfirm();
            }
        });

        this.confirmDialog.show({
            title: 'Stop running time entry?',
            body: 'You have a running time entry, stop it and start a new one?',
            confirmText: 'Stop'
        });
    }

    async requestToCreateTimeEntry() {
        this.createTimeEntryButton.startLoading();

        try {
            const res = await TimeEntryApi.create({withHtmlTemplate: true}, this.dateFormat);
            this.createTimeEntry(res.data);
        } catch (e) {
            if (e instanceof ApiErrorResponse) {
                const runningTimerError = e.getErrorForCode(TimeEntryApiErrorCode.codeRunningTimer) as ApiResourceError;
                if (runningTimerError) {
                    this.confirmStopExistingTimer(() => {
                        this.stopTimeEntryAndCreate(runningTimerError.resource);
                    });
                }
            }
        }

        this.createTimeEntryButton.stopLoading();
    }

    async continue(timeEntryId: string): Promise<any> {
        try {
            const res = await TimeEntryApi.continue(timeEntryId, {withHtmlTemplate: true});
            this.createTimeEntry(res.data);
        } catch (e) {
            if (e instanceof ApiErrorResponse) {
                const runningTimerError = e.getErrorForCode(TimeEntryApiErrorCode.codeRunningTimer) as ApiResourceError;
                if (runningTimerError) {
                    this.confirmStopExistingTimer(() => {
                        this.stopTimeEntryAndContinue(runningTimerError.resource, timeEntryId);
                    });
                }
            }
        }
    }
}

$(document).ready( () => {
    const page = new TimeEntryIndexPage();


});