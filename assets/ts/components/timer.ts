import $ from "jquery";
import { formatTimeDifference } from "./time";

/**
 * TimerView sets up a 1 second timer interval and updates the UI with the duration
 * from the starting time to now.
 */
export default class TimerView {
    private interval: any;

    /**
     * @param timerSelector selector for the timer element(s)
     * @param durationFormat format for the duration, currently supports php format https://www.php.net/manual/en/dateinterval.format.php
     * @param callback a function that takes a duration string as input. Called whenever the timer is invoked.
     */
    constructor(private timerSelector: string, private durationFormat: string, callback?: any) {
        const $timers = $(timerSelector);
        if($timers.length === 0) {
            return;
        }

        this.interval = setInterval(() => {
            $timers.each(((index, element) => {
                const $element = $(element);
                const milliSecondsSinceEpoch = $element.data('start') * 1000;
                const now = Math.floor((new Date()).getTime());

                const durationAsString = formatTimeDifference(milliSecondsSinceEpoch, now, durationFormat);
                $element.text(durationAsString);

                if(callback) {
                    callback(durationAsString);
                }
            }));
        }, 1000);
    }
}

