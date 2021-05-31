import '../styles/time_entry_index.scss';

import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap'; // Adds functions to jQuery

import { ApiTimeEntry, DateFormat, TimeEntryApi, TimeEntryApiErrorCode } from "./core/api/time_entry_api";
import Flashes from "./components/flashes";
import LoadingButton from "./components/loading_button";
import AutocompleteTags from "./components/autocomplete_tags";
import { ApiTag, TagApi } from "./core/api/tag_api";
import AutocompleteTasks from "./components/autocomplete_tasks";
import { ApiTask, ApiTaskAssign, TaskApi } from "./core/api/task_api";
import TagList from "./components/tag_index";
import TimerView, { DataAttributeTimerView } from "./components/timer";
import { TimeEntryApiAdapter, TimeEntryTaskAssigner } from "./time_entry";
import { SyncTaskTimeEntryDescription } from "./components/task_time_entry";
import { SyncInputV2, SyncStatus, SyncUploadEvent } from "./components/sync_input";
import { ApiErrorResponse, JsonResponse, PaginatedResponse } from "./core/api/api";
import { timeAgo } from "./components/time";
import Observable from "./components/observable";


import { createPopper } from '@popperjs/core';
import AutocompleteTaskCreate from "./components/autocomplete_tasks_create";
import { createResolvePromise } from "./components/empty_promise";


class EditableContent {
    private status: SyncStatus = 'up-to-date';
    private $status?: JQuery;
    private $editContainer?: JQuery;
    private $editable?: JQuery;
    private syncDescription?: SyncInputV2;

    constructor(protected $view: JQuery, private timeEntryId: string) {

    }

    edit() {
        const content = this.$view.data('description');
        this.$view.addClass('d-none');

        this.$editable = $(`<textarea class="js-content-edit w-100" rows="2">${content}</textarea>`);
        this.$status = $(`<div class="timestamp status">Up to date</div>`);

        this.$editContainer = $(`<div class="mt-2"></div>`);
        this.$editContainer.append(this.$editable);
        this.$editContainer.append(this.$status);

        this.$editContainer.insertAfter(this.$view);

        this.syncDescription = new SyncInputV2(
            this.$editable,
            (content: string) => this.onContentFinishChange(content),
            () => this.onContentChange()
        );

        this.syncDescription.start()
    }

    async onContentFinishChange(content: string) {
        if (!this.$status) {
            throw new Error('$status not set');
        }

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
        if (!this.$status) {
            throw new Error('$status not set');
        }

        if (this.status === 'modified') {
            return;
        }

        this.status = 'modified';
        this.$status.text('Modified');
    }

    protected updateViewContent(content: string) {
        this.$view.text(content);
        this.$view.data('description', content);
    }

    finishEdit() {
        if (!this.$editable) {
            throw new Error('editable not set');
        }

        if (!this.$editContainer) {
            throw new Error('editContainer not set');
        }

        const content = this.$editable.val() as string;

        this.$editContainer.remove();

        this.updateViewContent(content);

        this.$view.removeClass('d-none');

        this.syncDescription?.stop();
        this.syncDescription = undefined;
    }
}

class MarkdownEditableContent extends EditableContent {
    static markdownConverter?: any;
    static gettingMarkdownConverter = false;

    constructor($view: JQuery, timeEntryId: string) {
        super($view, timeEntryId);

        if (!MarkdownEditableContent.markdownConverter && !MarkdownEditableContent.gettingMarkdownConverter) {
            MarkdownEditableContent.gettingMarkdownConverter = true;
            import('showdown').then(res => {
                MarkdownEditableContent.markdownConverter = new res.Converter();
                MarkdownEditableContent.gettingMarkdownConverter = false;
            });
        }
    }

    protected updateViewContent(content: string) {
        this.$view.data('description', content);

        if (MarkdownEditableContent.markdownConverter) {
            content = MarkdownEditableContent.markdownConverter.makeHtml(content);
        }

        this.$view.html(content);
    }
}

class TimeEntryIndexItem {
    // TODO it might be a good idea to split this up into a view and edit mode or something
    // That way you can get rid of all the optionals? So this class becomes a manager?
    private readonly id: string;
    private taskId?: string;
    private taskName?: string;
    private readonly dateFormat: DateFormat;
    private taskEdit?: TimeEntryTaskAssignerV2;
    private readonly flashes: Flashes;

    private $element: JQuery;
    private $viewButton: JQuery;
    private $editButton: JQuery;
    private $continueButton: JQuery;
    private $activityIndicator: JQuery;
    private readonly $description: JQuery;
    private stopButton?: LoadingButton;
    private readonly durationTimer?: DataAttributeTimerView;

    private editableContent: EditableContent;
    private tagEdit?: TimeEntryTagAssignerV2;
    private startedEdit?: EditDateTime;
    private endedEdit?: EditDateTime;
    private updateButton?: LoadingButton;

    get assignedToTask(): boolean {
        return this.taskId !== undefined;
    }

    constructor($element: JQuery, durationFormat: string, dateFormat: DateFormat, flashes: Flashes) {
        this.$element = $element;
        this.dateFormat = dateFormat;
        this.$viewButton = $element.find('.js-view');
        this.$editButton = $element.find('.js-edit');
        this.$continueButton = $element.find('.js-continue');
        this.$activityIndicator = $element.find('.js-time-entry-activity');
        this.flashes = flashes;
        this.id = $element.data('id');
        this.taskId = $element.data('task-id');
        this.taskName = $element.data('task-name');
        this.$description = $element.find('.js-description');
        this.editableContent = new MarkdownEditableContent(this.$description, this.id);

        const $stop = $element.find('.js-stop');
        if ($stop.length !== 0) {
            this.stopButton = new LoadingButton($element.find('.js-stop'));
            this.stopButton.$container.on('click', () => this.stop());
        }

        const $durationTimer = $element.find('.js-duration.active');
        if ($durationTimer.length !== 0) {
            this.durationTimer = new DataAttributeTimerView($durationTimer, durationFormat);
            this.durationTimer.start();
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

        const $newElement = $(TimeEntryTaskAssignerV2.template());
        $newElement.insertAfter($task);

        this.taskEdit = new TimeEntryTaskAssignerV2($newElement, this.id,  this.flashes);
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
        this.editableContent.edit();
    }

    private finishContentEdit() {
        this.editableContent.finishEdit();
    }

    private startTagEdit() {
        const $tagList = this.$element.find('.js-tag-list');
        $tagList.find('.js-tag-list-view').addClass('d-none');

        const $tagEditList = $('<div class="js-tag-edit-list d-inline-block"></div>');
        $tagEditList.data(TagList.initialDataKey, $tagList.data(TagList.initialDataKey));
        $tagList.append($tagEditList);
        // TODO pass initial data...or get it via a source.
        const tagList = new TagList($tagEditList, new TimeEntryApiAdapter(this.id, this.flashes));
        const $template = $(TimeEntryTagAssignerV2.template());
        $tagList.append($template);

        this.tagEdit = new TimeEntryTagAssignerV2($template, tagList, this.flashes);

    }

    private finishTagEdit() {
        const $tagList = this.$element.find('.js-tag-list');

        if (this.tagEdit) {
            const tagNames = this.tagEdit.getTagList().getTagNamesCommaSeparated();
            $tagList.data(TagList.initialDataKey, tagNames);
            $tagList.find('.js-tag-edit-list').remove();
            this.tagEdit.$container.remove();
            this.tagEdit = undefined;
        }

        $tagList.find('.js-tag-list-view').removeClass('d-none');
    }

    private startTimestampEdit() {
        const $timestamps = this.$element.find('.js-timestamps');

        const $started = $timestamps.find('.js-started-at');
        $started.addClass('d-none');

        const $ended = $timestamps.find('.js-ended-at');
        $ended.addClass('d-none');

        const $startedEdit = $(EditDateTime.template('js-started-at'));
        const $endedEdit = $(EditDateTime.template('js-ended-at'));

        $timestamps.append($startedEdit);
        $timestamps.append($endedEdit);

        this.startedEdit = new EditDateTime($startedEdit, $started.data('timestamp'));
        this.endedEdit = new EditDateTime($endedEdit, $ended.data('timestamp'));
    }

    private finishTimestampEdit(): Promise<JsonResponse<ApiTimeEntry>>|Promise<void> {
        let updateStarted: DateTimeParts|undefined = undefined;
        let updateEnded: DateTimeParts|undefined = undefined;

        const $timestamps = this.$element.find('.js-timestamps');
        const $started = $timestamps.find('.js-started-at');
        const $ended = $timestamps.find('.js-ended-at');

        if (this.startedEdit) {
            const dateTime = this.startedEdit.getDateTime();
            if (dateTime) {
                const dateTimeString = dateTime.date + ' ' + dateTime.time;
                $started.text(dateTimeString);

                if ($started.data('timestamp') != dateTimeString) {
                    updateStarted = dateTime;
                }

                $started.data('timestamp', dateTimeString);
            }

            this.startedEdit.$container.remove();
            this.startedEdit = undefined;
        }

        $started.removeClass('d-none');


        if (this.endedEdit) {
            const dateTime = this.endedEdit.getDateTime();
            if (dateTime) {
                const dateTimeString = dateTime.date + ' ' + dateTime.time;
                if ($ended.data('timestamp') != dateTimeString) {
                    updateEnded = dateTime;
                }

                $ended.data('timestamp', dateTimeString);
            }

            this.endedEdit.$container.remove();
            this.endedEdit = undefined;
        }

        $ended.removeClass('d-none');

        if (updateStarted || updateEnded) {
            return TimeEntryApi.update(this.id, {
                startedAt: updateStarted,
                endedAt: updateEnded
            })
        }

        return createResolvePromise();
    }

    async stop() {
        if (!this.stopButton) {
            return;
        }

        this.stopButton.startLoading();

        try {
            const res = await TimeEntryApi.stop(this.id, this.dateFormat)
            this.$element.find('.js-ended-at').text('- ' + res.data.endedAt);
            this.$element.find('.js-duration').text(res.data.duration);

            if (this.durationTimer) {
                this.durationTimer.stop();
            }

            this.stopButton.stopLoading();
            this.stopButton.$container.remove();
            this.stopButton = undefined;

            this.$activityIndicator.remove();
        } catch (e) {
            this.flashes.append('danger', 'Unable to stop time entry');
            this.stopButton!.stopLoading();
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
            const res = await this.finishTimestampEdit();
            const jsonRes = res as JsonResponse<ApiTimeEntry>;
            if (jsonRes) {
                const $timestamps = this.$element.find('.js-timestamps');
                const $started = $timestamps.find('.js-started-at');
                $started.text(jsonRes.data.startedAt);

                const $ended = $timestamps.find('.js-ended-at');
                if (jsonRes.data.endedAt) {
                    this.durationTimer?.stop();
                    this.$element.find('.js-duration.active').text(jsonRes.data.duration);
                    this.$element.find('.js-time-entry-activity').remove();
                    $ended.text('- ' + jsonRes.data.endedAt);
                }
            }
        } catch (e) {
            this.flashes.append('danger', 'Unable to update timestamps');
        }

        this.finishTagEdit();
        this.showViewButtons();
        this.finishTaskEdit();
        this.finishContentEdit();
        this.updateButton?.stopLoading();
        this.removeDoneEditButtons();
        this.$element.find('.js-time-entry-activity').removeClass('d-none');
    }
}

$(document).ready( () => {
    const $data = $('.js-data');
    const dateFormat = $data.data('date-format');
    const durationFormat = $data.data('duration-format');
    const flashes = new Flashes($('#fixed-flash-messages'));

    //--- new
    $('.js-time-entry').each((index, element) => {
        const timeEntry = new TimeEntryIndexItem($(element), durationFormat, dateFormat, flashes);
    });

    //--- end new



    // const stopButton = new LoadingButton($('.js-stop'));
    //
    // stopButton.$container.on('click', (event) => {
    //     const $target = $(event.currentTarget);
    //     const $row = $target.parent().parent();
    //
    //     const timeEntryId = $target.data('time-entry-id') as string;
    //
    //     stopButton.stopLoading();
    //
    //     TimeEntryApi.stop(timeEntryId, dateFormat)
    //         .then(res => {
    //             $row.find('.js-ended-at').text(res.data.endedAt);
    //             $row.find('.js-duration').text(res.data.duration);
    //             stopButton.stopLoading();
    //             $target.remove();
    //         }).catch(res => {
    //             flashes.append('danger', 'Unable to stop time entry');
    //             stopButton.stopLoading();
    //         }
    //     );
    // })

    const createTimeEntryButton = new LoadingButton($('.js-create-time-entry'));

    createTimeEntryButton.$container.on('click', (event) => {
        createTimeEntryButton.startLoading();

        TimeEntryApi.create(dateFormat)
            .then(res => {
                window.location.href = res.data.url;
                createTimeEntryButton.stopLoading();
            }).catch(res => {
                $('.js-stop-running').data('time-entry-id', res.errors[0].resource);
                $('#confirm-stop-modal').modal();
                createTimeEntryButton.stopLoading();
            }
        );
    })

    const stopRunningButton = new LoadingButton($('.js-stop-running'));
    stopRunningButton.$container.on('click', (event)=> {
        const $target = $(event.currentTarget);
        const timeEntryId = $target.data('time-entry-id');

        stopRunningButton.startLoading();

        TimeEntryApi.stop(timeEntryId, dateFormat)
            .then(() => {
                TimeEntryApi.create(dateFormat)
                    .then(res => {
                        window.location.href = res.data.url;
                        stopRunningButton.stopLoading();
                    }).catch(res => {
                        $('#confirm-stop-modal').modal('hide');
                        stopRunningButton.stopLoading();
                    }
                );
            })
            .catch(() => {
                flashes.append('danger', 'Unable to stop time entry');
            });
    })

    const tagList = new TagList($('.js-tags'));
    const $realInput = $('.js-real-input');

    const autoComplete = new AutocompleteTags($('.js-autocomplete-tags'));
    if (autoComplete.live()) {
        autoComplete.valueEmitter.addObserver((apiTag: ApiTag) => {
            tagList.add(apiTag);
        })
    }

    tagList.tagsChanged.addObserver(() => {
        autoComplete.setTags(tagList.getTagNames());
        $realInput.val(tagList.getTagNamesCommaSeparated());
    });

    const $realTaskInput = $('.js-real-task-input');
    const autoCompleteTask = new AutocompleteTasks($('.js-autocomplete-tasks'));
    if (autoCompleteTask.live()) {
        autoCompleteTask.valueEmitter.addObserver((task: ApiTask) => {
            $realTaskInput.val(task.id);
        });

        autoCompleteTask.$nameInput.on('input', () => {
            $realTaskInput.val('');
        });
    }


    // debug
    const autoComplete2 = new TaskAutocomplete($('.js-autocomplete-task'));
    autoComplete2.itemSelected.addObserver((item: ApiTask) => {
        console.log(item);
    })

});


/**
 * Autocomplete provides a basic autocomplete for an input/search results set of elements.
 * It sets up a debounce so queries are not immediately made.
 * It sets up an event listener so the search result are cleared when clicked outside.
 * It sets up a clear button so the search query is cleared.
 *
 * This class must be extended to provide search logic and set up the ui to place inside the results.
 *
 * The expected html is:
 *
 * <div class="autocomplete js-autocomplete">
 *     <div class="search">
 *        <input class="js-input" />
 *        <button class="clear js-clear"><i class="fas fa-times"></i></button>
 *     </div>
 *     <div class="search-results js-search-results d-none"></div>
 * </div>
 */
abstract class Autocomplete {
    /**
     * Used to make sure we have a debounce.
     */
    private timeout: any;

    /**
     * If true, the X or 'clear' button has been pressed.
     * We don't show results of a query if it has and there is one underway.
     */
    protected cancelled = false;

    /**
     * How long we wait until we consider the input to be "ready" to send to an API.
     */
    private _debounceTime = 500;
    public set debounceTime(value: number) {
        this._debounceTime = value;
    }
    public get debounceTime(): number {
        return this._debounceTime;
    }

    /**
     * The input element that has the query.
     */
    protected $input: JQuery;

    /**
     * The element containing the entire search box.
     * Includes the input and clear button, but not search results.
     */
    protected readonly $search: JQuery;

    /**
     * The element containing the search results or other messages.
     * E.g. it contains messages for `loading` and `no search results found`
     */
    protected readonly $searchContent: JQuery;

    /**
     * @param $element the element containing the autocomplete content.
     */
    constructor(private $element: JQuery) {
        this.$input = $element.find('.js-input');
        this.$input.on('input', (event) => this.onInput(event));
        this.$search = $element.find('.search');
        this.$searchContent = $element.find('.js-search-results');

        $(document).on('click', () => this.onClickOutside());
        $element.on('click', (event) => {
            event.stopPropagation();
        })

        $element.find('.js-clear').on('click', () => this.clear());
    }

    /**
     * search is called whenever the user enters in a search term and the debounce is over.
     * This should make the API request and handle any results/errors.
     */
    abstract search(query: string);

    /**
     * clearSearchContent is called whenever we need to remove the search content.
     * This happens when we click outside the search.
     */
    public clearSearchContent() {
        this.$searchContent.addClass('d-none');
        this.$searchContent.html('');
    }

    /**
     * onClickOutside is called whenever we click something outside the search element.
     */
    protected onClickOutside() {
        this.clearSearchContent();
    }

    /**
     * onInput is called whenever the input element's content changes.
     */
    protected onInput(event: any) {
        if (this.cancelled) {
            this.cancelled = false;
        }

        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
            const text = $(event.currentTarget).val() as string;

            this.search(text);
        }, this.debounceTime);
    }

    /**
     * setMinSearchContentDimensions is called whenever we have search content modifications
     * and we want to make sure the search content has some minimum dimensions.
     *
     * Right now this is used to make sure the search content is as least as wide as the search input.
     */
    protected setMinSearchContentDimensions() {
        const width = this.$search[0].offsetWidth;
        this.$searchContent.css('min-width', width + 'px');
    }

    /**
     * positionSearchResults is called when we're about to display search results
     * and we want to position the search results below the search input.
     *
     * Popper is used to achieve this by default.
     */
    protected positionSearchContent() {
        createPopper(this.$search[0], this.$searchContent[0], {
            placement: 'bottom-start',
            modifiers: [
                {
                    name: 'offset',
                    options: {
                        offset: [0, 2]
                    }
                }
            ]
        });
    }

    /**
     * setSearchLoadingContent is called whenever we change the query and want to display loading content.
     * @param query
     */
    protected setSearchLoadingContent(query: string) {
        this.$searchContent.removeClass('d-none');

        this.$searchContent.html(`
            <div class="searching">
                <div class="spinner-border spinner-border-sm text-primary mr-1" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                 Searching...
            </div>
        `);
    }

    /**
     * onSearchLoading is called whenever we are ready to send off a query.
     * It sets the minimum content size,
     * sets the loading content,
     * and positions the search content.
     * @param query
     */
    protected onSearchLoading(query: string) {
        this.setMinSearchContentDimensions();
        this.setSearchLoadingContent(query);
        this.positionSearchContent();
    }

    /**
     * setQuery sets the input's value. No search is performed.
     * @param value
     */
    setQuery(value: string) {
        this.$input.val(value);
    }

    /**
     * getQuery gets the input's current value.
     */
    getQuery(): string {
        return this.$input.val() as string;
    }

    /**
     * clear clears the search content and the input. It does not trigger a search.
     */
    clear() {
        this.clearSearchContent();
        this.$input.val('');
        this.cancelled = true;
    }
}

/**
 * PaginatedAutocomplete simplifies creating an autocomplete for responses that are Paginated.
 * A subclass should implement the following methods
 * * template
 * * queryApi
 *
 */
abstract class PaginatedAutocomplete<T> extends Autocomplete {
    /**
     * itemSelected is fired whenever a search result is clicked on.
     * The pagination response item is the data emitted.
     */
    public itemSelected = new Observable<T>();

    /**
     * enterPressed is fired whenever the enter key is pressed in the search input.
     * The value is the current query.
     */
    public enterPressed = new Observable<string>();

    constructor($element: JQuery) {
        super($element);

        this.$input.on('keypress', (event) => {
            if (event.key === 'Enter') {
                // Don't form doesn't submit, if there is one.
                event.preventDefault();
                this.enterPressed.emit($(event.currentTarget).val() as string);
            }
        });
    }

    /**
     * template returns the html template to display in the search results for the input item.
     * @param item
     */
    protected abstract template(item: T): string;

    /**
     * queryApi is the network request made to get a response given the query.
     * @param query
     */
    protected abstract queryApi(query: string): Promise<JsonResponse<PaginatedResponse<T>>>;

    /**
     * noResultsTemplate is the element returned to display that there are no results.
     * It should have a class of "no-more-results" on the root element.
     */
    protected noResultsTemplate(): string {
        return `<div class="no-more-results">No results found</div>`;
    }

    /**
     * moreResultsTemplate is the element returned to display that there are more results for this query.
     * It should have a class of "more-results" on the root element.
     * @param response
     */
    protected moreResultsTemplate(response: PaginatedResponse<T>): string {
        const notDisplayed = response.totalCount - response.count;
        return `<div class="more-results"">${notDisplayed} more results</div>`;
    }

    async search(query: string) {
        this.onSearchLoading(query);

        const results = await this.queryApi(query);

        if (this.cancelled) {
            this.clearSearchContent();
            return;
        }
        if (results.data.count === 0) {
            this.$searchContent.html(this.noResultsTemplate());
            return;
        }

        this.$searchContent.html('');
        for(const item of results.data.data) {
            const $template = $(this.template(item));
            $template.addClass('search-result-item');
            $template.on('click', () => this.itemSelected.emit(item));
            this.$searchContent.append($template);
            this.$searchContent.append('<hr class="separator"/>');
        }

        if(results.data.totalCount > results.data.count) {
            this.$searchContent.append($(this.moreResultsTemplate(results.data)));
        }

        this.$searchContent.append(this.$searchContent);
        this.$searchContent.removeClass('d-none');
    }
}

export class TaskAutocomplete extends PaginatedAutocomplete<ApiTask> {
    protected template(item: ApiTask): string {
        if (item.completedAt) {
            return `<div><span class="task-complete"><i class="far fa-check-square"></i></span>${item.name}</div>`;
        }

        return `<div><span class="task-complete"><i class="far fa-square"></i></span>${item.name}</div>`;
    }

    protected queryApi(query: string): Promise<JsonResponse<PaginatedResponse<ApiTask>>> {
        return TaskApi.index(query);
    }
}

class TimeEntryTaskAssignerV2 {
    private readonly timeEntryId: string;
    private readonly $container: JQuery;
    private readonly flashes: Flashes;
    private autocomplete: TaskAutocomplete;
    private task?: ApiTask;

    static template(): string {
        return `
        <div class="autocomplete js-autocomplete js-autocomplete-task">
            <div class="d-flex">
                <div class="search border-right-0 rounded-right-0">
                    <input
                            type="text"
                            class="js-input"
                            placeholder="task name..."
                            name="task">
                    <button class="clear js-clear btn btn-sm"><i class="fas fa-times"></i></button>
                </div>
                <button type="button" class="btn js-clear btn-outline-danger rounded-left-0">
                    <i class="fas fa-trash"></i>
                </button>   
            </div>
            <div class="search-results js-search-results d-none"></div>
        </div>`
    }

    constructor($container: JQuery, timeEntryId: string, flashes: Flashes) {
        this.timeEntryId = timeEntryId;
        this.flashes = flashes;

        this.$container = $container;

        this.autocomplete = new TaskAutocomplete($container);
        this.autocomplete.itemSelected.addObserver((item: ApiTask) => this.onItemSelected(item));

        this.$container.find('.js-clear').on('click', () => this.clearTask());

        this.autocomplete.enterPressed.addObserver((query: string) => this.assignToTask(query));
    }

    setTaskSimple(id: string, name: string) {
        this.task = {
            id,
            name,
            description: '',
            createdAt: '',
        };

        this.autocomplete.setQuery(name);
    }

    private async assignToTask(taskName: string) {
        this.autocomplete.clearSearchContent();

        const res = await TimeEntryApi.assignToTask(this.timeEntryId, taskName);
        this.task = res.data;

        if (res.source.status === 201 && res.data.url) {
            this.flashes.appendWithLink('success', `Created new task`, res.data.url, res.data.name);
        }
    }

    private async onItemSelected(item: ApiTask) {
        this.autocomplete.setQuery(item.name);
        this.autocomplete.clearSearchContent();

        const res = await TimeEntryApi.assignToTask(this.timeEntryId, item.name, item.id);
        this.task = res.data;

        this.flashes.append('success', `Assigned to task '${this.task.name}'`, true);
    }

    private async clearTask() {
        try {
            await TimeEntryApi.unassignTask(this.timeEntryId);
            this.task = undefined;
            this.autocomplete.clear();
            this.flashes.append('success', 'Removed task', true);
        } catch (e) {
            if (e instanceof ApiErrorResponse) {
                const errRes = e as ApiErrorResponse;
                if (errRes.hasErrorCode(TimeEntryApiErrorCode.codeNoAssignedTask)) {
                    this.flashes.append('danger', 'Time entry has no assigned task');
                }
            }
        }
    }

    getTask(): ApiTask|undefined {
        return this.task;
    }

    getContainer(): JQuery {
        return this.$container;
    }
}

// TODO rename
export class TagsAutocompleteV2 extends PaginatedAutocomplete<ApiTag> {
    private tagNames = new Array<string>();

    public setTagNames(tagNames: string[]) {
        this.tagNames = tagNames;
    }

    protected template(item: ApiTag): string {
        return `<div>${item.name}</div>`;
    }

    protected queryApi(query: string): Promise<JsonResponse<PaginatedResponse<ApiTag>>> {
        return TagApi.index(query, this.tagNames);
    }
}

// TODO name
class TimeEntryTagAssignerV2 {
    private readonly _$container: JQuery;
    get $container(): JQuery {
        return this._$container;
    }

    private readonly flashes: Flashes;
    private readonly tagList: TagList;
    private autocomplete: TagsAutocompleteV2;

    static template(): string {
        return `
        <div class="autocomplete js-autocomplete js-autocomplete-tags">
            <div class="d-flex">
                <div class="search border-right-0 rounded-right-0">
                    <input
                            type="text"
                            class="js-input"
                            placeholder="tag name..."
                            name="tag">
                    <button class="clear js-clear btn btn-sm"><i class="fas fa-times"></i></button>
                </div>
                <button type="button" class="btn js-add btn-outline-primary rounded-left-0">
                    Add
                </button>   
            </div>
            <div class="search-results js-search-results d-none"></div>
        </div>`
    }

    constructor($container: JQuery, tagList: TagList, flashes: Flashes) {
        this._$container = $container;
        this.tagList = tagList;
        this.flashes = flashes;
        this.autocomplete = new TagsAutocompleteV2($container);

        this.autocomplete.itemSelected.addObserver((tag: ApiTag) => this.onTagSelected(tag));
        this.autocomplete.enterPressed.addObserver((name: string) => this.onAddTag(name));
        $container.find('.js-add').on('click', (event) => {
            this.onAddTag(this.autocomplete.getQuery());
        });

        this.autocomplete.setTagNames(tagList.getTagNames());
        this.tagList.tagsChanged.addObserver(() => {
            this.autocomplete.setTagNames(tagList.getTagNames());
        });
    }

    getTagList(): TagList {
        return this.tagList;
    }

    onTagSelected(tag: ApiTag) {
        this.tagList.add(tag);
        this.autocomplete.clear();
    }

    onAddTag(name: string) {
        this.onTagSelected({
            name,
            color: '#5d5d5d'
        });
    }
}

interface DateTimeParts {
    date: string;
    time: string;
}

class EditDateTime {
    private readonly _$container: JQuery;
    get $container(): JQuery {
        return this._$container;
    }

    public static template(extraClass: string = ''): string {
        return `
        <div class="js-edit-date-time ${extraClass}">
            <input class="js-date" type="date" />
            <input class="js-time" type="time" />
        </div>`;
    }

    constructor($container: JQuery, timestamp?: string) {
        this._$container = $container;

        if (timestamp) {
            const parts = timestamp.split(' ');

            $container.find('.js-date').val(parts[0]);
            $container.find('.js-time').val(parts[1]);
        }
    }

    getDateTime(): DateTimeParts|undefined {
        const dateValue = this.$container.find('.js-date').val() as string;
        if (!dateValue) {
            return undefined;
        }

        const timeValue = this.$container.find('.js-time').val() as string;
        if (!timeValue) {
            return undefined;
        }

        return {
            date: this.$container.find('.js-date').val() as string,
            time: this.$container.find('.js-time').val() as string
        };
    }
}