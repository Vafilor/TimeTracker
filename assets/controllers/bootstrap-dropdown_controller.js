import { Controller } from '@hotwired/stimulus';
import { Dropdown } from 'bootstrap';

export default class extends Controller {
    #popover = null;

    connect() {
        this.#popover = new Dropdown(this.element);
    }

    disconnect() {
        this.#popover = null;
    }
}