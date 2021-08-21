import { formatTimeDifference } from "./time";

/**
 * TimerView sets up a 1 second timer interval and updates the UI with the duration
 * from the starting time to now.
 */

export default class TimerView {
    private interval: any = null;
    private durationFormat?: string;

    // in milliseconds
    private _startedAt: number;
    get startedAt(): number {
        return this._startedAt;
    }

    /**
     * @param value in milliseconds
     */
    set startedAt(value: number) {
        if (value === this._startedAt) {
            return;
        }

        this._startedAt = value;
        this.$container.data('start', value);
    }

    get running(): boolean {
        return this.interval !== null;
    }

    constructor(
        private $container: JQuery,
        protected callback?: any) {

        const start = $container.data('start');
        if (start) {
            this._startedAt = start;
        }

        const durationFormat = $container.data('duration-format');
        if (durationFormat) {
            this.setDurationFormat(durationFormat);
        }

        if ($container.data('active')) {
            this.start();
        }
    }

    protected updateTimerElement($element: JQuery, now: number): string {
        const durationAsString = formatTimeDifference(this.startedAt, now, this.durationFormat!);

        $element.text(durationAsString);

        return durationAsString;
    }

    setDurationFormat(format: string) {
        this.durationFormat = format;
    }

    /**
     * Sets the text to display.
     */
    setText(value: string) {
       this.$container.text(value);
    }

    start() {
        if(this.$container.length === 0) {
            return;
        }

        if (this.interval !== null) {
            return;
        }

        if (!this._startedAt) {
            throw new Error('startedAt is not set');
        }

        if (!this.durationFormat) {
            throw new Error('No duration format');
        }

        this.interval = setInterval(() => {
            this.update();
        }, 1000);
    }

    /**
     * Recalculate the duration and update the UI.
     */
    update() {
        const now = Math.floor((new Date()).getTime());

        const durationAsString = this.updateTimerElement(this.$container, now);

        if(this.callback) {
            this.callback(durationAsString);
        }
    }

    stop() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }

    getZeroDurationString(): string {
        if (!this.durationFormat) {
            throw new Error('No duration format');
        }

        return formatTimeDifference(0, 0, this.durationFormat);
    }
}