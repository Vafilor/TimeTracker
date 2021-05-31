import $ from "jquery";
import { formatTimeDifference } from "./time";

/**
 * TimerView sets up a 1 second timer interval and updates the UI with the duration
 * from the starting time to now.
 */
export default abstract class TimerView {
    private interval: any = null;

    constructor(
        private $element: JQuery,
        protected durationFormat: string,
        protected callback?: any) {
    }

    protected abstract updateTimerElement($element: JQuery, now: number): string;

    setDurationFormat(format: string) {
        this.durationFormat = format;
    }

    start() {
        if(this.$element.length === 0) {
            return;
        }

        if (this.interval !== null) {
            return;
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

/**
 * DataAttributeTimerView gets the start time from each element's data-start attribute and uses that to compute
 * the total duration.
 */
export class DataAttributeTimerView extends TimerView {
    protected updateTimerElement($element: JQuery, now: number): string {
        const milliSecondsSinceEpoch = $element.data('start') * 1000;

        const durationAsString = formatTimeDifference(milliSecondsSinceEpoch, now, this.durationFormat);

        $element.text(durationAsString);

        return durationAsString;
    }
}

/**
 * StaticStartTimerView uses an input value for the start time to compute the total duration.
 */
export class StaticStartTimerView extends TimerView {
    private startTime: number;

    /**
     * start starts the timer using the input time, in milliseconds since epoch, as the starting point
     * @param startTime
     */
    start(startTime: number|null = null) {
        if (startTime === null) {
            startTime = Math.floor((new Date()).getTime());
        }
        this.startTime = startTime;
        super.start();
    }

    protected updateTimerElement($element: JQuery, now: number): string {
        const durationAsString = formatTimeDifference(this.startTime, now, this.durationFormat);

        $element.text(durationAsString);

        return durationAsString;
    }
}
