import { Controller } from 'stimulus';
import {formatTimeDifference} from "../ts/components/time";

export default class extends Controller {
    static values = {
        start: Number, // Required. A timestamp in seconds.
        timeout: Number, // In milliseconds. Optional, defaults to 1 second
        format: String,
    };

    _start;
    interval = null;

    startValueChanged() {
        this._start = this.startValue * 1000;
    }

    connect() {
        // Input is in seconds, convert to milliseconds for convenience with timeAgo function call
        const timeout = this.hasTimeout ? this.timeoutValue : 1000;

        this.interval = setInterval(() => this.updateUI(), timeout);
    }

    disconnect() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }

    updateUI() {
        const now = Math.floor((new Date()).getTime());
        const durationString = formatTimeDifference(this._start, now, this.formatValue);
        this.element.textContent = durationString;

        return durationString;
    }
}