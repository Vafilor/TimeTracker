import { Controller } from '@hotwired/stimulus';
import { useDebounce, useDispatch } from "stimulus-use";

export default class extends Controller {
    static debounces = ['dispatchEvent'];

    static values = {
        key: String,
        debounce: Number
    }

    connect() {
        useDispatch(this)

        if (this.debounceValue !== 0) {
            useDebounce(this)
        }
    }

    valueChanged(event) {
        this.dispatchEvent(this.keyValue, event.currentTarget.value);
    }

    dispatchEvent(key, value) {
        this.dispatch('change', {
            key,
            value
        });
    }
}
