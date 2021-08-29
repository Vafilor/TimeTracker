import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        field: String,
        key: String,
    }

    markdownConverter = null;

    connect() {
        import('showdown').then(res => {
            this.markdownConverter = new res.Converter();
        });
    }

    convert(event) {
        const { key, value } = event.detail;

        if (this.hasKeyValue && key !== this.keyValue) {
            return;
        }

        if (this.markdownConverter) {
            this.element.innerHTML = this.markdownConverter.makeHtml(value);
        }
    }
}