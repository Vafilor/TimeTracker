import $ from "jquery";
import { ApiTimeEntry, TimeEntryApi, TimeEntryApiErrorCode } from "../core/api/time_entry_api";
import Observable from "./observable";
import { ConfirmClickEvent, ConfirmDialog } from "./confirm_dialog";
import { ApiError, ApiErrorResponse, ApiResourceError } from "../core/api/api";
import TimerView, { DataAttributeTimerView, StaticStartTimerView } from "./timer";

export enum TimeEntryState {
    created,  // the object has been created, but nothing set yet.
    initializing,  // the object is awaiting data
    failedToInitialize, // the object failed to get data
    notRunning, // this is the state after a successful stop, because
    starting, // attempting to start
    failedToStart, // failed to start
    running, // the time entry is currently active and running
    stopping, // attempting to stop
    failedToStop// failed to stop
}

export default class TaskTimeEntry {
    private readonly taskId: string;

    private $container: JQuery;
    private $description: JQuery;
    private $button: JQuery;
    private $loading: JQuery;

    private durationTimer: StaticStartTimerView;
    private model?: ApiTimeEntry;
    private _state: TimeEntryState;
    private get state(): TimeEntryState {
        return this._state;
    }
    private set state(state: TimeEntryState) {
        this._state = state;

        switch (state) {
            case TimeEntryState.starting:
                this.startLoading();
                break;
            case TimeEntryState.stopping:
                this.startLoading();
                break;
            case TimeEntryState.running:
                this.stopLoading();
                this.setButtonToStop();
                this.durationTimer.start(this.model.startedAtEpoch * 1000);
                break
            case TimeEntryState.notRunning:
                this.stopLoading();
                this.setButtonToStart();
                this.durationTimer.stop(); // TODO clear?
                break;
        }
    }

    public readonly stopped = new Observable<ApiTimeEntry>();

    public constructor(taskId: string) {
        this.taskId = taskId;
        this.state = TimeEntryState.created;

        this.$container = $('.js-task-time-entry');
        this.$description = this.$container.find('.js-content .js-description');
        this.$button = this.$container.find('.js-content button');
        this.$button.on('click', () => {
            if (this.state === TimeEntryState.running) {
                this.stop();
            } else if (this.state === TimeEntryState.notRunning) {
                this.start();
            }
        });

        this.$loading = this.$container.find('.js-loading');

        // TODO user duration format. or input
        this.durationTimer = new StaticStartTimerView('.js-duration', '%Hh:%Im:%Ss');
    }

    public initialize() {
        this.state = TimeEntryState.initializing;

        TimeEntryApi.getActive()
            .then(res => {
                if (res.data) {
                    this.setData(res.data);
                } else {
                    this.state = TimeEntryState.notRunning;
                }
            });
    }

    private setData(model: ApiTimeEntry) {
        if (model && model.taskId === this.taskId) {
            this.model = model;

            if (this.model.endedAt === undefined) {
                this.state = TimeEntryState.running;
                this.setDescription(model.description);
            } else {
                this.state = TimeEntryState.notRunning;
            }
        } else {
            this.state = TimeEntryState.notRunning;
        }
    }

    private startLoading() {
        this.$loading.removeClass('d-none');
    }

    private stopLoading() {
        this.$loading.addClass('d-none');
    }

    private setButtonToStart() {
        this.$button.removeClass('btn-danger');
        this.$button.addClass('btn-primary');
        this.$button.text('Start');
    }

    private setButtonToStop() {
        this.$button.removeClass('btn-primary');
        this.$button.addClass('btn-danger');
        this.$button.text('Stop');
    }

    private setDescription(text: string) {
        this.$description.text(text);
    }

    private confirmStopRunningTimeEntry(timeEntryId: string) {
        const confirmDialog = new ConfirmDialog('btn-danger');
        confirmDialog.clicked.addObserver((event: ConfirmClickEvent) => {
            if (event.buttonClicked === 'confirm') {
                TimeEntryApi.stop(timeEntryId)
                    .then(res => {
                        this.tryCreatingTimeEntry();
                        confirmDialog.remove();
                    })
            } else {
                this.state = TimeEntryState.notRunning;
            }
        });

        confirmDialog.show({
            title: 'Stop running time entry?',
            body: 'You have a running time entry, stop it and start a new one?',
            cancelText: 'close',
            confirmText: 'stop'
        });
    }

    private tryCreatingTimeEntry() {
        TimeEntryApi.create({
            taskId: this.taskId,
            }).then(res => {
                this.model = res.data.timeEntry;
                this.state = TimeEntryState.running;
            }).catch( (res: ApiErrorResponse) => {
                if (res.hasErrorCode(TimeEntryApiErrorCode.codeRunningTime)) {
                    const error = res.getErrorForCode(TimeEntryApiErrorCode.codeRunningTime) as ApiResourceError;
                    const timeEntryId = error.resource;
                    this.confirmStopRunningTimeEntry(timeEntryId);
                }
            }
        );
    }

    public start() {
        if (this.state !== TimeEntryState.notRunning) {
            return;
        }

        this.state = TimeEntryState.starting;

        this.tryCreatingTimeEntry();
    }

    public stop() {
        if (this.state !== TimeEntryState.running) {
            return;
        }

        this.state = TimeEntryState.stopping;

        TimeEntryApi.stop(this.model.id)
            .then(res => {
                this.state = TimeEntryState.notRunning;
                this.stopped.emit(res.data);
            })
    }
}