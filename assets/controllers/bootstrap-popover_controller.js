import { Controller } from 'stimulus';
import { Popover } from 'bootstrap';

export default class extends Controller {
    connect() {
        const popover = new Popover(this.element);
    }
}