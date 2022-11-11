import { Controller } from '@hotwired/stimulus';
import { Modal } from 'bootstrap';
import { visit } from '@hotwired/turbo';

export default class extends Controller {
    static targets = ['modal', 'frame'];
    static values = {
        'editUrlTemplate': String
    }

    #modal = null;

    connect() {
        this.element.addEventListener('turbo:before-fetch-response', (event) => {
            const fetchResponse = event.detail.fetchResponse;
            const redirectLocation = fetchResponse.response.headers.get('Turbo-Location');
            if (!redirectLocation) {
                return;
            }
            visit(redirectLocation);
        });
    }

    disconnect() {
        // \
    }

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
