import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';

export default class extends Controller {
    static targets = ['modal', 'frame'];
    static values = {
        'editUrlTemplate': String
    }

    #modal = null;

    async openModal(event) {
        const taskId = event.params.taskId;
        const url = decodeURI(this.editUrlTemplateValue);
        const targetUrl = url.replace('{{ID}}', taskId);

        this.frameTarget.src = encodeURI(targetUrl);
        if (!this.#modal) {
            this.#modal = new Modal(this.modalTarget);
        }

        this.#modal.show();
    }
}
