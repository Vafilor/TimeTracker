import { Controller } from '@hotwired/stimulus';
import { useDebounce, useDispatch } from "stimulus-use";

const axios = require('axios').default;

export default class extends Controller {
    static debounces = ['updateApi'];

    static values = {
        key: String,
        url: String,
        fieldName: String,
        debounce: Number
    };

    connect() {
        const waitDebounce = this.hasDebounceValue ? this.debounceValue : 400;
        useDebounce(this, { wait: waitDebounce });
        useDispatch(this);
    }

    update(event) {
        this.updateApi(event.currentTarget.value);
    }

    async updateApi(value) {
        this.dispatch('update:start', {
            key: this.keyValue
        });

        const data = {};
        data[this.fieldNameValue] = value;

        try {
            await axios.put(this.urlValue, data);

            this.dispatch('update:finish', {
                key: this.keyValue,
                value,
                status: 'success'
            });
        } catch (e) {
            this.dispatch('update:finish', {
                key: this.keyValue,
                status: 'failure'
            });
        }
    }
}