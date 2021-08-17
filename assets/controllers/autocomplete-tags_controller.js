import Autocomplete from './autocomplete_controller';

export default class extends Autocomplete {
    static values = {
        excludeTagsCsv: String,
        excludeTags: Array
    };

    connect() {
        super.connect();

        if (this.excludeTagsCsvValue !== '') {
            this.excludeTagsValue = this.excludeTagsCsvValue.split(',');
        }
    }

    excludeTagEvent(event) {
        this.excludeTag(event.detail.name);
    }

    excludeTag(value) {
        const index = this.excludeTagsValue.indexOf(value);
        if (index > 0) {
            return;
        }

        const copy = this.excludeTagsValue.slice();
        copy.push(value);
        this.excludeTagsValue = copy;
    }

    includeTagEvent(event) {
        this.includeTag(event.detail.name);
    }

    includeTag(value) {
        const index = this.excludeTagsValue.indexOf(value);
        if (index > 0) {
            return;
        }

        const copy = this.excludeTagsValue.slice();
        copy.splice(index, 1);

        this.excludeTagsValue = copy;
    }

    onKeydown(event) {
        if( event.key === 'Enter' ) {
            event.preventDefault();

            const selected = this.resultsTarget.querySelector(
                '[aria-selected="true"]'
            );

            if (!selected) {
                this.addTag();
                return;
            }
        }

        super.onKeydown(event);
    }

    addTag() {
        let name = this.inputTarget.getAttribute("data-autocomplete-value");
        if (!name) {
            name = this.inputTarget.value;
        }

        if (name === '') {
            return;
        }

        const color = '#5d5d5d';

        this.hideAndRemoveOptions()
        this.inputTarget.value = '';
        this.inputTarget.focus();

        this.element.dispatchEvent(
            new CustomEvent("autocomplete.tags.change", {
                bubbles: true,
                detail: {
                    name,
                    color
                }
            })
        )
    }

    commit(selected) {
        if (selected.getAttribute("aria-disabled") === "true") {
            return;
        }

        const value = selected.getAttribute("data-autocomplete-value");

        this.hideAndRemoveOptions()
        this.inputTarget.value = '';

        const tag = JSON.parse(value);

        this.element.dispatchEvent(
            new CustomEvent("autocomplete.tags.change", {
                bubbles: true,
                detail: tag
            })
        )
    }

    fetchRequest(endpoint, query) {
        const headers = { "X-Requested-With": "XMLHttpRequest" }
        const url = new URL(endpoint, window.location.href)

        const params = new URLSearchParams(url.search.slice(1))
        params.append("q", query)
        if (this.excludeTagsValue) {
            const excludeQuery = encodeURIComponent(this.excludeTagsValue);
            params.append('exclude', excludeQuery);
        }

        url.search = params.toString()

        return fetch(url.toString(), { headers })
    }
}