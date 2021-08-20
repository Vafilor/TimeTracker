import AppAutocomplete from "./app-autocomplete_controller";

export default class extends AppAutocomplete {
    static values = {
        excludeTagsCsv: String,
        excludeTags: Array
    };

    connect() {
        super.connect();

        if (this.excludeTagsCsvValue !== '') {
            this.excludeTagsValue = this.excludeTagsCsvValue.split(',');
        }

        this.clearInput = this.clearInput.bind(this);
        this.element.addEventListener('autocomplete.change', this.clearInput);
    }

    disconnect() {
        super.disconnect();

        this.element.removeEventListener('autocomplete.change', this.clearInput);
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