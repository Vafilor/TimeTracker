import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['check'];
    async check(data) {
        const { taskId, completed } = data.detail;

        const form = this.element;

        form.action = form.action.replace('REPLACE_ID', taskId);
        this.checkTarget.checked = completed;
        this.element.requestSubmit();
    }
}