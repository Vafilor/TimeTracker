import { Controller } from '@hotwired/stimulus';
import debounce from "lodash.debounce"

/**
 * This is adapted from https://github.com/afcapel/stimulus-autocomplete
 * The code is mostly the same with a few changes
 * 1. addition of a "loading" target that will be shown right before any network request
 * 2. configurable debounce value
 * 3. optional passValue setting. If true, the hidden element will be updated with the query value as it is modified.
 */
export default class Autocomplete extends Controller {
    static targets = ["input", "hidden", "results", "loading"]
    static values = {
        submitOnEnter: Boolean,
        url: String, // required,
        queryName: String, // optional, defaults to 'q',
        params: Object, // optional, additional query params to set - key/value. Values are url encoded
        minLength: Number, // optional
        debounce: Number, // optional, defaults to 300
        passValue: Boolean, // optional, if true will update the hidden element with the search query as it is typed in
        type: String // optional, emit in the autocomplete.change event to identify what the object is
    }

    connect() {
        this.resultsTarget.hidden = true

        this.inputTarget.setAttribute("autocomplete", "off")
        this.inputTarget.setAttribute("spellcheck", "false")

        this.mouseDown = false

        const debounceTime = this.hasDebounceValue ? this.debounceValue : 300;

        this.onInputChange = debounce(this.onInputChange.bind(this), debounceTime)
        this.onResultsClick = this.onResultsClick.bind(this)
        this.onResultsMouseDown = this.onResultsMouseDown.bind(this)
        this.onInputBlur = this.onInputBlur.bind(this)
        this.onKeydown = this.onKeydown.bind(this)

        this.inputTarget.addEventListener("keydown", this.onKeydown)
        this.inputTarget.addEventListener("blur", this.onInputBlur)
        this.inputTarget.addEventListener("input", this.onInputChange)
        this.resultsTarget.addEventListener("mousedown", this.onResultsMouseDown)
        this.resultsTarget.addEventListener("click", this.onResultsClick)

        if (typeof this.inputTarget.getAttribute("autofocus") === "string") {
            this.inputTarget.focus()
        }

        if (this.queryNameValue === '') {
            this.queryNameValue = 'q';
        }
    }

    disconnect() {
        if (this.hasInputTarget) {
            this.inputTarget.removeEventListener("keydown", this.onKeydown)
            this.inputTarget.removeEventListener("focus", this.onInputFocus)
            this.inputTarget.removeEventListener("blur", this.onInputBlur)
            this.inputTarget.removeEventListener("input", this.onInputChange)
        }
        if (this.hasResultsTarget) {
            this.resultsTarget.removeEventListener(
                "mousedown",
                this.onResultsMouseDown
            )
            this.resultsTarget.removeEventListener("click", this.onResultsClick)
        }
    }

    sibling(next) {
        const options = Array.from(
            this.resultsTarget.querySelectorAll(
                '[role="option"]:not([aria-disabled])'
            )
        )
        const selected = this.resultsTarget.querySelector('[aria-selected="true"]')
        const index = options.indexOf(selected)
        const sibling = next ? options[index + 1] : options[index - 1]
        const def = next ? options[0] : options[options.length - 1]
        return sibling || def
    }

    select(target) {
        for (const el of this.resultsTarget.querySelectorAll(
            '[aria-selected="true"]'
        )) {
            el.removeAttribute("aria-selected")
            el.classList.remove("active")
        }
        target.setAttribute("aria-selected", "true")
        target.classList.add("active")
        this.inputTarget.setAttribute("aria-activedescendant", target.id)
        this.scrollTo(this.resultsTarget, target)
    }

    scrollTo(results, element) {
        let currentElementPosition = element.offsetTop - results.offsetTop;
        let currentScrollPosition = results.scrollTop;

        let currentElementBottom = currentElementPosition + element.offsetHeight;
        let resultsBottom = currentScrollPosition + results.offsetHeight;

        if(currentElementBottom > resultsBottom) {
            // Current element is cut off at bottom
            results.scrollTop = currentScrollPosition + currentElementBottom - resultsBottom
        } else if(currentElementPosition < currentScrollPosition) {
            // Current element is cut off at top
            results.scrollTop = currentElementPosition;
        }
    }

    onEscapeKeyDown(event) {
        if (!this.resultsTarget.hidden) {
            this.hideAndRemoveOptions()
            event.stopPropagation()
            event.preventDefault()
        }
    }

    onArrowDownKeyDown(event) {
        const item = this.sibling(true)
        if (item) {
            this.select(item)
        }
        event.preventDefault()
    }

    onArrowUpKeyDown(event) {
        const item = this.sibling(false)
        if (item) this.select(item)
        event.preventDefault()
    }

    onTabKeyDown(event) {
        const selected = this.resultsTarget.querySelector(
            '[aria-selected="true"]'
        )
        if (selected) {
            this.commit(selected)
        }
    }

    onEnterKeyDown(event) {
        const selected = this.resultsTarget.querySelector(
            '[aria-selected="true"]'
        )
        if (selected && !this.resultsTarget.hidden) {
            this.commit(selected)
            if (!this.hasSubmitOnEnterValue) {
                event.preventDefault()
            }
        }
    }

    onKeydown(event) {
        switch (event.key) {
            case "Escape":
                this.onEscapeKeyDown(event)
                break
            case "ArrowDown":
                this.onArrowDownKeyDown(event);
                break
            case "ArrowUp":
                this.onArrowUpKeyDown(event);
                break
            case "Tab":
                this.onTabKeyDown(event);
                break
            case "Enter":
                this.onEnterKeyDown(event);
                break
        }
    }

    onInputBlur() {
        if (this.mouseDown) return
        this.resultsTarget.hidden = true
    }

    commit(selected) {
        if (selected.getAttribute("aria-disabled") === "true") {
            return
        }

        if (selected instanceof HTMLAnchorElement) {
            selected.click()
            this.resultsTarget.hidden = true
            return
        }

        const textValue = this.extractTextValue(selected)
        const value = selected.getAttribute("data-autocomplete-value") || textValue
        let object = selected.getAttribute('data-autocomplete-object');
        if (object) {
            object = JSON.parse(object);
        }

        this.inputTarget.value = textValue

        if (this.hasHiddenTarget) {
            this.hiddenTarget.value = value
            this.hiddenTarget.dispatchEvent(new Event("input"))
            this.hiddenTarget.dispatchEvent(new Event("change"))
        } else {
            this.inputTarget.value = value
        }

        this.inputTarget.focus()
        this.hideAndRemoveOptions()

        this.element.dispatchEvent(
            new CustomEvent("autocomplete.change", {
                bubbles: true,
                detail: {
                    type: this.typeValue,
                    value: value,
                    textValue: textValue,
                    object: object,
                }
            })
        )
    }

    onResultsClick(event) {
        if (!(event.target instanceof Element)) {
            return
        }

        const selected = event.target.closest('[role="option"]')

        if (selected) {
            this.commit(selected)
        }
    }

    onResultsMouseDown() {
        this.mouseDown = true
        this.resultsTarget.addEventListener(
            "mouseup",
            () => (this.mouseDown = false),
            { once: true }
        )
    }

    onInputChange() {
        this.element.removeAttribute("value")
        this.fetchResults();

        if (this.hasHiddenTarget && this.passValueValue) {
            this.hiddenTarget.value = this.getQuery();
        }
    }

    identifyOptions() {
        let id = 0
        for (const el of this.resultsTarget.querySelectorAll(
            '[role="option"]:not([id])'
        )) {
            el.id = `${this.resultsTarget.id}-option-${id++}`
        }
    }

    hideAndRemoveOptions() {
        this.resultsTarget.hidden = true
        this.resultsTarget.innerHTML = null
    }

    getQuery() {
        return this.inputTarget.value.trim();
    }

    shouldFetchQuery(query) {
        return query.length > this.minLengthValue;
    }

    showLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.hidden = false;
        }
    }

    hideLoading() {
        if (this.hasLoadingTarget) {
            this.loadingTarget.hidden = true;
        }
    }

    fetchRequest(endpoint, query) {
        const headers = { "X-Requested-With": "XMLHttpRequest" }
        const url = new URL(endpoint, window.location.href)
        const params = new URLSearchParams(url.search.slice(1))
        params.append(this.queryNameValue, query)

        for(const [key, value] of Object.entries(this.paramsValue)) {
            params.append(key, encodeURIComponent(value));
        }

        url.search = params.toString()

        return fetch(url.toString(), { headers })
    }

    fetchResults() {
        const query = this.getQuery();
        if (!this.shouldFetchQuery(query)) {
            this.hideAndRemoveOptions()
            return
        }

        if (!this.hasUrlValue) {
            return
        }

        this.element.dispatchEvent(new CustomEvent("loadstart"))

        this.resultsTarget.hidden = true;
        this.showLoading();

        this.fetchRequest(this.urlValue, query)
            .then(response => response.text())
            .then(html => {
                this.resultsTarget.innerHTML = html
                this.identifyOptions()
                const hasResults = !!this.resultsTarget.querySelector('[role="option"]')
                this.resultsTarget.hidden = !hasResults
                this.element.dispatchEvent(new CustomEvent("load"))
                this.element.dispatchEvent(new CustomEvent("loadend"))
                this.hideLoading();
            })
            .catch(() => {
                this.element.dispatchEvent(new CustomEvent("error"));
                this.element.dispatchEvent(new CustomEvent("loadend"));
                this.hideLoading();
            })
    }

    open() {
        if (!this.resultsTarget.hidden) {
            return
        }

        this.resultsTarget.hidden = false
        this.element.setAttribute("aria-expanded", "true")
        this.element.dispatchEvent(
            new CustomEvent("toggle", {
                detail: { input: this.input, results: this.results }
            })
        )
    }

    close() {
        if (this.resultsTarget.hidden) {
            return
        }

        this.resultsTarget.hidden = true
        this.inputTarget.removeAttribute("aria-activedescendant")
        this.element.setAttribute("aria-expanded", "false")
        this.element.dispatchEvent(
            new CustomEvent("toggle", {
                detail: { input: this.input, results: this.results }
            })
        )
    }

    clearInput() {
        this.inputTarget.value = '';
    }

    extractTextValue = el =>
        el.hasAttribute("data-autocomplete-label")
            ? el.getAttribute("data-autocomplete-label")
            : el.textContent.trim()
}
