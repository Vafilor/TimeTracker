import { Controller } from 'stimulus';
import { useDispatch } from "stimulus-use";

export default class extends Controller {
    static values = {
        value: String
    };

    connect() {
        console.log('connected');
        useDispatch(this, { debug: true });
    }

    emit(event) {
        console.log('emit');
        this.dispatch({
            value: this.valueValue
        });
    }
}