import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['target'];

    updateTarget(event) {
        console.log('update target', event);
    }
}
