import { Controller } from 'stimulus';
import { timeAgo } from '../ts/components/time';

export default class extends Controller {
    static values = {
        start: Number, // Required. A timestamp in seconds.
        timeout: Number // In milliseconds. Optional, defaults to 1 second
    };

    _start = null;
    interval = null;

    startValueChanged() {
        console.log('startValueChanged');
        this._start = this.startValue * 1000;
    }

    connect() {
        // Input is in seconds, convert to milliseconds for convenience with timeAgo function call
        const timeout = this.hasTimeout ? this.timeoutValue : 1000;

        this.interval = setInterval(() => this.update(), timeout);
    }

    disconnect() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }

    update() {
        this.element.innerHTML = timeAgo(this._start, (new Date()).getTime());
    }
}