import { Controller } from 'stimulus';
import { Tooltip } from 'bootstrap';

export default class extends Controller {
    #tooltip = null;

    connect() {
        this.#tooltip = new Tooltip(this.element);
    }

    disconnect() {
        this.#tooltip = null;
    }
}