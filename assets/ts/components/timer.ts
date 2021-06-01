import $ from "jquery";
import { formatTimeDifference } from "./time";

/**
 * TimerView sets up a 1 second timer interval and updates the UI with the duration
 * from the starting time to now.
 */
export default class TimerView {
    private interval: any = null;
    // in milliseconds
    private startedAt: number;

    constructor(
        private $element: JQuery,
        protected durationFormat: string,
        protected callback?: any) {
    }

    protected updateTimerElement($element: JQuery, now: number): string {
        const durationAsString = formatTimeDifference(this.startedAt, now, this.durationFormat);

        $element.text(durationAsString);

        return durationAsString;
    }

    setDurationFormat(format: string) {
        this.durationFormat = format;
    }

    /**
     * @param startedAt in milliseconds
     */
    start(startedAt?: number) {
        if(this.$element.length === 0) {
            return;
        }

        if (this.interval !== null) {
            return;
        }

        if (startedAt) {
            this.startedAt = startedAt;
        }

        if (!this.startedAt) {
            throw new Error('startedAt is not set');
        }

        this.interval = setInterval(() => {
            const now = Math.floor((new Date()).getTime());

            const durationAsString = this.updateTimerElement(this.$element, now);

            if(this.callback) {
                this.callback(durationAsString);
            }
        }, 1000);
    }

    stop() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }

    getZeroDurationString(): string {
        return formatTimeDifference(0, 0, this.durationFormat);
    }
}