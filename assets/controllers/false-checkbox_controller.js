import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    #falseElement = null;
    #inputEventListener = null;

    connect() {
        this.checked = this.element.checked;

        this.#inputEventListener = this.inputChange.bind(this);
        this.element.addEventListener('input', this.#inputEventListener);
    }

    disconnect() {
        if (this.#inputEventListener) {
            this.element.removeEventListener('input', this.#inputEventListener);
        }
    }

    inputChange(event) {
        this.checked = event.currentTarget.checked;
    }

    createHiddenInput(name, value) {
        const ele = document.createElement('input');
        ele.type = 'hidden';
        ele.value = value;
        ele.name = name;

        return ele;
    }

    set checked(value) {
        if (value) {
            if (this.#falseElement) {
                this.#falseElement.remove();
            }
        } else {
            this.#falseElement = this.createHiddenInput(this.element.name, '0');
            this.element.parentElement.append(this.#falseElement);
        }
    }
}