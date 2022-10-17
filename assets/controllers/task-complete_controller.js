import { Controller } from 'stimulus';
import { useDispatch } from "stimulus-use";

export default class extends Controller {
    static targets = ['spinner'];

    static values = {
        id: String,
        loadOnComplete: Boolean
    }

    connect() {
        useDispatch(this);
    }

    complete(event) {
        event.currentTarget.disabled = true;
        const completed = event.currentTarget.checked;

        if (this.loadOnCompleteValue) {
            this.spinnerTarget.classList.remove('d-none');
        }

        this.dispatch('complete', {
            taskId: this.idValue,
            completed
        });
    }
}