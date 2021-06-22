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
import { TimeEntryTaskAssigner } from "./components/time_entry_task_assigner";
import { TagAssigner } from "./components/tag_assigner";
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

interface TaskModel {
    id: string;
    name: string;
    url?: string;
}

interface TimeEntryModel {
    id: string;
    task?: TaskModel,
    description: string;
    startedAt?: string;
    startedAtEpoch: number; // epoch in milliseconds
    endedAt?: string;
    endedAtEpoch?: number; // epoch in milliseconds
    tags: Array<ApiTag>;
}

class TimeEntryView {
    private readonly id: string;
    private readonly $container: JQuery;
    private readonly _descriptionView: MarkdownView;
    public get descriptionView(): MarkdownView {
        return this._descriptionView;
    }

    private $activityIndicator: JQuery;
    private $taskName: JQuery;

    constructor($container: JQuery, id: string) {
        this.id = id;
        this.$container = $container;

        this._descriptionView = new MarkdownView($container.find('.js-description'));
        this.$activityIndicator = $container.find('.js-time-entry-activity');
        this.$taskName = $container.find('.js-task-content');
    }

    private setTagData(tags: ApiTag[]) {
        const $tagList = this.$container.find('.js-tag-list');
        const $tagView = $tagList.find('.js-tag-list-view');
        $tagView.html('');

        for(const tag of tags) {
            const tagHtml = `<div class="tag" data-name="${tag.name}" style="background-color: ${tag.color};">${tag.name}</div> `;
            $tagView.append(tagHtml);
        }

        $tagList.data(TagList.initialDataObjectsKey, tags);
        $tagList.data(TagList.initialDataKey, '');
    }

    private setTaskData(task?: TaskModel) {
        this.$taskName.remove();
        if (task && task.url) {
            this.$taskName = $(`<a data-task-name="${task.name}" data-task-id="${task.id}" class="js-task-content" href="${task.url}">${task.name}</a>`);
        } else {
            this.$taskName = $(`<div data-task-name="" data-task-id="" class="js-task-content d-inline-block">No Task</div>`);
        }

        this.$container.find('.js-task').append(this.$taskName);
    }

    private setStartedAtData(epoch: number, display: string) {
        const $element = this.$container.find('.js-started-at');

        if (epoch === $element.data('timestamp')) {
            return;
        }

        $element.text(display);
        $element.data('timestamp', epoch);
    }

    setEndedAtData(epoch: number, display: string) {
        const $element = this.$container.find('.js-ended-at');

        if (epoch === $element.data('timestamp')) {
            return;
        }

        $element.text('- ' + display);
        $element.data('timestamp', epoch);
    }

    set data(model: TimeEntryModel) {
        this._descriptionView.data = model.description;

        this.setTagData(model.tags);
        this.setTaskData(model.task);

        if (model.startedAt) {
            this.setStartedAtData(model.startedAtEpoch, model.startedAt);
        }

        if (model.endedAt && model.endedAtEpoch) {
            this.setEndedAtData(model.endedAtEpoch, model.endedAt);
        }
    }

    private getTags(): ApiTag[] {
        const $tagList = this.$container.find('.js-tag-list');
        const tags = $tagList.data(TagList.initialDataObjectsKey) as ApiTag[];
        if (tags) {
            return tags;
        }

        const tagNames = $tagList.data(TagList.initialDataKey) as string;
        let tagsFromName = new Array<ApiTag>();
        for(const tagName of tagNames.split(',')) {
            if (tagName !== '') {
                tagsFromName.push({
                    name: tagName,
                    color: '#5d5d5d'
                });
            }
        }

        return tagsFromName;
    }

    get data(): TimeEntryModel {
        const $startedAt = this.$container.find('.js-started-at');
        const $endedAt = this.$container.find('.js-ended-at');

        return {
            id: this.id,
            task: {
                id: this.$taskName.data('task-id'),
                name: this.$taskName.data('task-name'),
                url: this.$taskName.attr('href')
            },
            description: this._descriptionView.data,
            startedAt: $startedAt.text(),
            startedAtEpoch: $startedAt.data('timestamp'),
            endedAt: $endedAt.text(),
            endedAtEpoch: $endedAt.data('timestamp'),
            tags: this.getTags()
        };
    }

    hide() {
        this._descriptionView.hide();
        this.$activityIndicator.addClass('d-none');
        this.$container.find('.js-tag-list .js-tag-list-view').addClass('d-none');
        this.$taskName.removeClass('d-inline-block');
        this.$taskName.addClass('d-none');

        this.$container.find('.js-started-at').addClass('d-none');
        this.$container.find('.js-ended-at').addClass('d-none');
    }

    show() {
        this.$container.find('.js-started-at').removeClass('d-none');
        this.$container.find('.js-ended-at').removeClass('d-none');

        this.$taskName.addClass('d-inline-block');
        this.$taskName.removeClass('d-none');
        this._descriptionView.show();
        this.$activityIndicator.removeClass('d-none');
        this.$container.find('.js-tag-list .js-tag-list-view').removeClass('d-none');
    }

    removeActivityIndicator() {
        this.$activityIndicator.remove();
    }
}

class TimeEntryEditView {
    private readonly timeEntryId: string;
    private readonly $container: JQuery;
    private description: TimeEntryDescriptionSync;
    private tagEdit: TagAssigner;
    private taskEdit: TimeEntryTaskAssigner;
    private startedAt: EditDateTime;
    private endedAt: EditDateTime;

    constructor($container: JQuery, timeEntryId: string, flashes: Flashes) {
        this.$container = $container;
        this.timeEntryId = timeEntryId;
        this.description = new TimeEntryDescriptionSync($container.find('.js-time-entry-description-sync'), timeEntryId);

        const tagList = new TagList($container.find('.js-tag-edit-list'), new TimeEntryApiAdapter(timeEntryId, flashes));
        this.tagEdit = new TagAssigner($container.find('.js-autocomplete-tags'), tagList, flashes);

        this.taskEdit = new TimeEntryTaskAssigner($container.find('.js-time-entry-task-assigner'), this.timeEntryId, flashes);

        this.startedAt = new EditDateTime($container.find('.js-edit-started-at'));
        this.endedAt = new EditDateTime($container.find('.js-edit-ended-at'));
    }

    get data(): TimeEntryModel {
        const task = this.taskEdit.getTask();

        if (!this.startedAt.getDate()) {
            throw new Error('Started at does not have a date');
        }

        return {
            id: this.timeEntryId,
            task: {
                id: task ? task.id: '',
                name: task ? task.name: '',
                url: task ? task.url: undefined
            },
            description: this.description.data,
            startedAtEpoch: this.startedAt.getDate()!.getTime(),
            endedAtEpoch: this.endedAt.getDate()?.getTime(),
            tags: this.tagEdit.getTagList().getTags()
        };
    }

    dispose() {
        this.description.dispose();
        this.tagEdit.$container.remove();
        this.$container.find('.js-tag-edit-list').remove();
        this.taskEdit.dispose();
        this.startedAt.dispose();
        this.endedAt.dispose();
    }
}

class TimeEntryIndexItem {
    private readonly id: string;
    private readonly dateFormat: DateFormat;
    private readonly flashes: Flashes;

    private readonly $container: JQuery;
    private $viewButton: JQuery;
    private $editButton: JQuery;
    private $continueButton: JQuery;
    private stopButton?: LoadingButton;
    private durationTimer: TimerView;

    private updateButton?: LoadingButton;
    private delegate: TimeEntryActionDelegate;

    private data?: TimeEntryModel;
    private view: TimeEntryView;
    private editView?: TimeEntryEditView;
    get editing(): boolean {
        return this.editView !== undefined;
    }

    constructor($container: JQuery, delegate: TimeEntryActionDelegate, durationFormat: string, dateFormat: DateFormat, flashes: Flashes) {
        this.$container = $container;
        this.delegate = delegate;
        this.id = $container.data('id');

        this.view = new TimeEntryView($container, this.id);

        this.dateFormat = dateFormat;
        this.$viewButton = $container.find('.js-view');
        this.$editButton = $container.find('.js-edit');
        this.flashes = flashes;
        this.$continueButton = $container.find('.js-continue');
        this.$continueButton.on('click', () => this.delegate.continue(this.id));

        const $stop = $container.find('.js-stop');
        if ($stop.length !== 0) {
            this.stopButton = new LoadingButton($container.find('.js-stop'));
            this.stopButton.$container.on('click', () => this.stop());
        }

        this.durationTimer = new TimerView($container.find('.js-duration'));

        $container.find('.js-edit').on('click', () => this.onEdit());
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

        this.$container.find('.js-actions').append($element);
    }

    private removeDoneEditButtons() {
        const $element = this.$container.find('.js-actions .js-update');
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

    private addContinueButtonIfNotExist() {
        if (this.$container.find('.js-continue').length === 0) {
            this.$continueButton = $(`<button type="button" class="btn btn-secondary js-continue ml-2">Continue</button>`);
            this.$continueButton.on('click', () => this.delegate.continue(this.id));
            this.$container.find('.js-actions').append(this.$continueButton);
        }
    }

    stopUI(timeEntry: ApiTimeEntry) {
        if (!timeEntry.endedAtEpoch || !timeEntry.endedAt) {
            throw new Error('timeEntry does not have endedAt or endedAtEpoch');
        }


        if (!this.editing) {
            this.addContinueButtonIfNotExist();
        }

        this.view.setEndedAtData(timeEntry.endedAtEpoch * 1000, timeEntry.endedAt)

        if (timeEntry.duration) {
            this.durationTimer.setText(timeEntry.duration);
        }

        this.durationTimer.stop();
        this.view.removeActivityIndicator();

        this.stopButton?.$container.remove();

    }

    async stop() {
        this.stopButton?.startLoading();

        try {
            const res = await TimeEntryApi.stop(this.id, this.dateFormat)
            if (res.data.endedAtEpoch && res.data.endedAt) {
                this.view.setEndedAtData(res.data.endedAtEpoch * 1000, res.data.endedAt);
            } else {
                throw new Error('Missing required endedAtEpoch and endedAt from api');
            }

            this.durationTimer.stop();
            if (res.data.duration) {
                this.durationTimer.setText(res.data.duration)
            }

            this.stopButton?.stopLoading();
            this.stopButton?.$container.remove();
            this.stopButton = undefined;

            this.view.removeActivityIndicator();

            this.addContinueButtonIfNotExist();
        } catch (e) {
            this.flashes.append('danger', 'Unable to stop time entry');
            this.stopButton?.stopLoading();
        }
    }

    onEdit() {
        this.hideViewButtons();
        this.showDoneEditButtons();

        const data = this.view.data;
        this.data = data;
        this.view.hide();

        // Task
        const $newElement = $(TimeEntryTaskAssigner.template(data.task?.id, data.task?.name, data.task?.url));
        this.$container.find('.js-task').append($newElement);

        // Tag List
        const $tagList = this.$container.find('.js-tag-list');
        const $tagEditList = $('<div class="js-tag-edit-list d-inline-block"></div>');
        $tagEditList.data(TagList.initialDataObjectsKey, data.tags);
        $tagList.append($tagEditList);
        $tagList.append($(TagAssigner.template()));

        // Time Entry Description
        const $timeEntryDescriptionHtml = $(TimeEntryDescriptionSync.template(data.description, 'mt-2'));
        $timeEntryDescriptionHtml.insertAfter(this.view.descriptionView.$container);

        // Timestamps
        const $timestamps = this.$container.find('.js-timestamps');
        const $startedEdit = $(EditDateTime.templateWithLabel('Started', 'js-edit-started-at'));
        $startedEdit.data('timestamp', data.startedAtEpoch);
        const $endedEdit = $(EditDateTime.templateWithLabel('Ended', 'js-edit-ended-at ml-2'));
        if (data.endedAtEpoch) {
            $endedEdit.data('timestamp', data.endedAtEpoch);
        }

        $timestamps.append($startedEdit);
        $timestamps.append($endedEdit);

        this.editView = new TimeEntryEditView(this.$container, this.id, this.flashes);

        this.$container.addClass('edit');
    }

    async onFinishEdit() {
        if (!this.data) {
            throw new Error("data is not set");
        }

        if (!this.editView) {
            throw new Error('EditView not set');
        }

        this.updateButton?.startLoading();

        const newData = this.editView.data;

        if ( (this.data.startedAtEpoch !== newData.startedAtEpoch) || (this.data.endedAtEpoch !== newData.endedAtEpoch) ) {
            try {
                let update = {};
                let updated = false;

                if (this.data.startedAtEpoch !== newData.startedAtEpoch) {
                    update['startedAt'] = EditDateTime.dateToParts(new Date(newData.startedAtEpoch));
                    updated = true;
                }

                if (newData.endedAtEpoch && (this.data.endedAtEpoch !== newData.endedAtEpoch)) {
                    update['endedAt'] = EditDateTime.dateToParts(new Date(newData.endedAtEpoch!));
                    updated = true;
                }

                if (updated) {
                    const res = await TimeEntryApi.update(this.id, update);

                    const jsonRes = res as JsonResponse<ApiTimeEntry>;
                    newData.startedAt = jsonRes.data.startedAt;
                    newData.endedAt = jsonRes.data.endedAt;


                    if (jsonRes && jsonRes.data.endedAt && !this.data.endedAt) {
                        this.durationTimer.stop();
                        this.view.removeActivityIndicator();
                    }

                    this.durationTimer.startedAt = newData.startedAtEpoch;
                    this.durationTimer.update();

                    if (jsonRes.data.duration) {
                        this.durationTimer.setText(jsonRes.data.duration);
                    }
                }
            } catch (e) {
                this.flashes.append('danger', 'Unable to update timestamps');
            }
        }

        this.showViewButtons();
        this.updateButton?.stopLoading();
        this.removeDoneEditButtons();
        this.$container.find('.js-time-entry-activity').removeClass('d-none');

        this.view.data = newData;

        this.editView.dispose();
        this.editView = undefined;

        this.view.show();

        this.data = newData;

        if (!this.durationTimer.running) {
            this.addContinueButtonIfNotExist();
        }

        setTimeout(() => {
            this.$container.removeClass('edit');
        }, 500);
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
    private page: string;

    constructor() {
        const $data = $('.js-data');
        this.page = $data.data('page');

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

        if (this.page === 'today') {
            window.location.href = createResponse.data.url;
        } else {
            this.createTimeEntry(createResponse.data);
        }

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

            if (this.page === 'today') {
                if (res.data.url) {
                    window.location.href = res.data.url;
                }
            } else {
                this.createTimeEntry(res.data);
            }
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