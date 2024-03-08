import { Controller } from "@hotwired/stimulus"
import debounce from 'debounce'

export default class extends Controller {
    initialize() {
        this.debouncedSubmit = debounce(this.debouncedSubmit.bind(this), 300)
    }
    submit(e) {
        this.element.requestSubmit()
    }
    debouncedSubmit() {
        this.submit()
    }
}