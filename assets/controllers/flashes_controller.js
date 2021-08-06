import { Controller } from 'stimulus';

export default class extends Controller {
    addFlash(event) {
        const { type, title, message } = event.detail;
        this.addFlashHtml(type, title, message);
    }

    addFlashHtml(type, title, message) {
        const titleHtml = title ? `<strong>${title}</strong>` : '';
        const flashHtml = `<div class="alert alert-${type} alert-dismissible fade show flash" role="alert">
            ${titleHtml}
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            </button>
        </div>`;

        this.element.innerHTML += flashHtml;
    }
}