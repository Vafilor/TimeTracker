import '../styles/time_entry_index.scss';

import $ from 'jquery';
import 'bootstrap'; // Adds functions to jQuery

import Flashes from "./components/flashes";
import { ApiTask } from "./core/api/task_api";
import AutocompleteTask from "./components/autocomplete_task";
import { AutocompleteEnterPressedEvent } from "./components/autocomplete";
import { TagFilter } from "./components/tag_filter";
import LoadingButton from "./components/loading_button";
import {
    CreateTimeEntryResponse,
    DateFormat,
    TimeEntryApi,
    TimeEntryApiErrorCode
} from "./core/api/time_entry_api";
import { ApiErrorResponse, ApiResourceError } from "./core/api/api";
import { ConfirmClickEvent, ConfirmDialog } from "./components/confirm_dialog";
import TimerView from "./components/timer";

class TimeEntryListItem {
    public readonly id: string;
    public readonly $container: JQuery;
    private timer: TimerView;
    private stopButton?: LoadingButton;
    private flashes: Flashes;

    public constructor($container: JQuery, flashes: Flashes) {
        this.id = $container.data('id');
        this.$container = $container;
        this.flashes = flashes;
        this.timer = new TimerView($container.find('.js-duration'));

        const $stop = $container.find('.js-stop');
        if ($stop.length !== 0) {
            this.stopButton = new LoadingButton($stop);
            this.stopButton.$container.on('click', async () => {
                await this.requestStop();
            })
        }
    }

    async stop() {
        if (!this.timer.running) {
            return;
        }

        await TimeEntryApi.stop(this.id);

        this.$container.find('.js-stop').remove();

        this.timer.stop();
        this.$container.find('.js-loading').addClass('d-none');
    }

    private async requestStop() {
        this.stopButton?.startLoading();

        try {
            await this.stop();
        } catch (e) {
            this.flashes.append('danger', 'Unable to stop time entry');
        }

        this.stopButton?.stopLoading();
    }
}

class TimeEntryListFilter {
    private $element: JQuery;
    private flashes: Flashes;
    private tagFilter: TagFilter;

    constructor($element: JQuery, flashes: Flashes) {
        this.$element = $element;
        this.flashes = flashes;

        this.setUpTaskFilter();
        this.tagFilter = new TagFilter($element);
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

        autocompleteTask.enterPressed.addObserver((event: AutocompleteEnterPressedEvent<ApiTask>) => {
            if (event.data) {
                autocompleteTask.setQuery(event.data.name);
                autocompleteTask.clearSearchContent();
                $realTaskInput.val(event.data.id);
            }
        })

        autocompleteTask.inputChange.addObserver(() => {
            $realTaskInput.val('');
        })

        autocompleteTask.inputClear.addObserver(() => {
            $realTaskInput.val('');
        })
    }
}

class TimeEntryIndexPage {
    private flashes: Flashes;
    private dateTimeFormat: DateFormat;
    private filter: TimeEntryListFilter;
    private createButton: LoadingButton;
    private confirmDialog?: ConfirmDialog;
    private $timeEntryList: JQuery;
    private timeEntries = new Map<string, TimeEntryListItem>();

    constructor() {
        this.flashes = new Flashes($('#fixed-flash-messages'));
        this.dateTimeFormat = $('.js-data').data('date-format') as DateFormat;
        this.$timeEntryList = $('.js-time-entry-list');

        this.$timeEntryList.find('.js-time-entry').each(((index, element) => {
            const view = new TimeEntryListItem($(element), this.flashes);
            this.timeEntries.set(view.id, view);
        }));

        this.filter = new TimeEntryListFilter($('.filter'), this.flashes);

        this.createButton = new LoadingButton($('.js-create-time-entry'));
        this.createButton.$container.on('click', () => this.createTimeEntry());
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

    private async stopTimeEntryAndCreate(timeEntryId: string) {
        this.confirmDialog?.startLoading();

        const view = this.timeEntries.get(timeEntryId);
        if (!view) {
            throw new Error('missing time entry');
        }

        try {
            await view.stop()
        } catch (e) {
            this.flashes.append('danger', 'Unable to stop time entry');
            this.confirmDialog?.remove();
            this.confirmDialog = undefined;
            return;
        }

        try {
            const createResponse = await TimeEntryApi.create({htmlTemplate: 'small'}, this.dateTimeFormat);
            this.addTimeEntryToUI(createResponse.data);
        } catch (e) {
            this.flashes.append('danger', 'Unable to create a new time entry');
            this.confirmDialog?.remove();
            this.confirmDialog = undefined;
            return;
        }

        this.confirmDialog?.remove();
        this.confirmDialog = undefined;
    }

    private addTimeEntryToUI(response: CreateTimeEntryResponse) {
        if (!response.template) {
            throw new Error('Response does not have a template');
        }

        const view = new TimeEntryListItem($(response.template), this.flashes);

        this.timeEntries.set(view.id, view);

        this.$timeEntryList.prepend(view.$container);
    }

    async createTimeEntry() {
        this.createButton.startLoading();

        try {
            const res = await TimeEntryApi.create({htmlTemplate: 'small'});

            this.addTimeEntryToUI(res.data);
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

        this.createButton.stopLoading();
    }
}

$(document).ready( () => {
    const page = new TimeEntryIndexPage();
})