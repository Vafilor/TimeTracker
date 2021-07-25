import { Controller } from 'stimulus';

export default class extends Controller {
    static targets = ['clearable']
    static values = {
        empty: String
    };

    clear() {
        const empty = this.hasEmptyValue ? this.emptyValue: '';
        this.clearableTargets.forEach((clearableTarget) => {
            clearableTarget.value = empty;
        })
    }
}