import '../styles/today.scss';

import $ from 'jquery';
import 'bootstrap';
import { TimeEntryActionDelegate, TimeEntryIndexItem } from "./components/time_entry";
import {
    ApiTimeEntry,
    CreateTimeEntryResponse,
    DateFormat,
    TimeEntryApi,
    TimeEntryApiErrorCode
} from "./core/api/time_entry_api";
import Flashes from "./components/flashes";
import LoadingButton from "./components/loading_button";
import { ConfirmClickEvent, ConfirmDialog } from "./components/confirm_dialog";
import { ApiErrorResponse, ApiResourceError } from "./core/api/api"; // Adds functions to jQuery

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

class TodayIndexPage implements TimeEntryActionDelegate {
    private readonly dateFormat: DateFormat;
    private readonly durationFormat: string;
    private readonly flashes: Flashes;
    private timeEntryList: TimeEntryList;
    private createTimeEntryButton: LoadingButton;
    private confirmDialog?: ConfirmDialog;

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

        const createResponse = await TimeEntryApi.create({htmlTemplate: 'regular'}, this.dateFormat);

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
            const res = await TimeEntryApi.create({htmlTemplate: 'regular'}, this.dateFormat);

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
    const page = new TodayIndexPage();
})