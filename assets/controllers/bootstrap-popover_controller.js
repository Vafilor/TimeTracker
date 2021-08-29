import { Controller } from 'stimulus';
import { Popover } from 'bootstrap';

export default class extends Controller {
    #popover = null;

    connect() {
        this.#popover = new Popover(this.element);
    }

    disconnect() {
        this.#popover = null;
    }
}