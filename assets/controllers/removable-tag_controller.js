import { Controller } from '@hotwired/stimulus';
import { useDispatch } from "stimulus-use";

export default class extends Controller {
    static values = {
        name: String,
        color: String,
    };

    connect() {
        useDispatch(this);
    }

    remove() {
        this.dispatch('remove', {
            name: this.nameValue,
            color: this.colorValue
        });
    }
}