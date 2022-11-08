import { Controller } from '@hotwired/stimulus';
import { visit } from '@hotwired/turbo';

/**
 * Attach this to filter & paginated resource pages if you want to reset the page to 1
 * if you change a parameter and are on another page.
 */
export default class extends Controller {
    submit(event) {
        // Get the difference between the form elements and the query parameters present
        event.preventDefault();

        const queryParamMap = new Map();
        const params = new URLSearchParams(window.location.search);
        for(const entry of params.entries()) {
            queryParamMap.set(entry[0], entry[1]);
        }

        const formParamMap = new Map();
        const formData = new FormData(this.element);
        for(const entry of formData.entries()) {
            formParamMap.set(entry[0], entry[1]);
        }

        const differenceSet = new Set();
        for(const [key, value] of formParamMap.entries()) {
            if (queryParamMap.has(key) && queryParamMap.get(key) !== value) {
                differenceSet.add(key);
            }
        }

        // If we changed something other than the page, we need to reset it
        // On initial page load, the page defaults to 1, but it will not be present in query.
        if (params.has("page") && params.get("page") !== "1" && differenceSet.size > 0) {
            for(const entry of formData.entries()) {
                params.set(entry[0], entry[1]);
            }
            params.set("page", "1");

            visit("?" + params.toString());
        } else {
            this.element.submit();
        }
    }
}