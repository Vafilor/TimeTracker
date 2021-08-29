import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['button', 'loading'];

    start(event) {
        this.buttonTarget.disabled = true;
        this.loadingTarget.classList.remove('d-none');
    }
}