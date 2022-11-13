import { Controller } from "@hotwired/stimulus";
import { Tab } from "bootstrap";

/**
 * Activates bootstrap tab via javascript, and sets the targetElement to be the page's fragment.
 * On connecting, if the pages fragment is set to the targetElement, the tab is shown.
 */
export default class extends Controller
{
    #tab;

    connect() {
        this.#tab = new Tab(this.element);

        if (window.location.hash === this.#getTarget()) {
            this.#tab.show();
        }
    }

    disconnect() {
        this.#tab = null;
    }

    activateTab(event) {
        event.preventDefault();
        this.#tab.show();

        window.location.hash = this.#getTarget();
    }

    #getTarget() {
        return this.element.dataset.bsTarget;
    }
}