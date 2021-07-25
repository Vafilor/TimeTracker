import { Controller } from 'stimulus';
import { Tooltip } from 'bootstrap';

export default class extends Controller {
    connect() {
        const tooltip = new Tooltip(this.element);
    }
}