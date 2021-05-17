import $ from "jquery";
import { formatTimeDifference } from "./time";

/**
 * TimerView sets up a 1 second timer interval and updates the UI with the duration
 * from the starting time to now.
 */
export default abstract class TimerView {
    private interval: any = null;
    private $timers: JQuery;

    constructor(
        private timerSelector: string,
        protected durationFormat: string,
        protected callback?: any) {
        this.$timers = $(timerSelector);
    }

    protected abstract updateTimerElement(element: HTMLElement, now: number): string;

    start() {
        if(this.$timers.length === 0) {
            return;
        }

        if (this.interval !== null) {
            return;
        }

        this.interval = setInterval(() => {
            const now = Math.floor((new Date()).getTime());

            this.$timers.each(((index, element) => {
                const durationAsString = this.updateTimerElement(element, now);

                if(this.callback) {
                    this.callback(durationAsString);
                }
            }));
        }, 1000);
    }

    stop() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }
}

/**
 * DataAttributeTimerView gets the start time from each element's data-start attribute and uses that to compute
 * the total duration.
 */
export class DataAttributeTimerView extends TimerView {
    protected updateTimerElement(element: HTMLElement, now: number): string {
        const $element = $(element);
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
    start(startTime: number = null) {
        if (startTime === null) {
            startTime = Math.floor((new Date()).getTime());
        }
        this.startTime = startTime;
        super.start();
    }

    protected updateTimerElement(element: HTMLElement, now: number): string {
        const $element = $(element);

        const durationAsString = formatTimeDifference(this.startTime, now, this.durationFormat);

        $element.text(durationAsString);

        return durationAsString;
    }
}
