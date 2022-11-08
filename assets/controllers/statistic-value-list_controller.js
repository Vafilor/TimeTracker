import { Controller } from '@hotwired/stimulus';
import { useDebounce } from "stimulus-use";

const axios = require('axios').default;

export default class extends Controller {
    static debounces = ['updateApiValue'];

    static values = {
        refreshUrl: String
    }

    connect() {
        useDebounce(this);
    }

    refresh(event) {
        this.refreshApi()
    }

    async refreshApi() {
        const response = await axios.get(this.refreshUrlValue);
        this.element.innerHTML = response.data;
    }

    addValue(event) {
        this.element.innerHTML += event.detail.view;
    }

    removeValue(event) {
        document.getElementById(event.detail.id).remove();
    }
}