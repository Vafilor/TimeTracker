import { Controller } from 'stimulus';

export default class extends Controller {
    addFlash(event) {
        const { type, title, message, url, urlText } = event.detail;
        this.add(type, title, message, url, urlText);
    }

    add(type, title, message, url, urlText) {
        const ele = document.createElement('div');
        ele.classList.add('alert', `alert-${type}`, 'alert-dismissible', 'fade', 'show', 'flash');
        ele.setAttribute('role', 'alert');

        if (title) {
            const titleElement = document.createElement('strong');
            titleElement.appendChild(document.createTextNode(title));
            ele.appendChild(titleElement);
        }

        ele.appendChild(document.createTextNode(message + ' '));

        if (url) {
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.appendChild(document.createTextNode(urlText));
            ele.appendChild(link);
        }

        const btn = document.createElement('button');
        btn.classList.add('btn-close');
        btn.setAttribute('type', 'button');
        btn.setAttribute('data-bs-dismiss', 'alert');
        btn.setAttribute('aria-label', 'close');
        ele.appendChild(btn);

        this.element.appendChild(ele);
    }
}