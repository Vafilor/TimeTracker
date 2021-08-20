import Autocomplete from "./autocomplete_controller";

export default class AppAutocomplete extends Autocomplete {
    commitFromTextInput() {
        const textValue = this.inputTarget.value;
        const value = textValue;

        if (this.hasHiddenTarget) {
            this.hiddenTarget.value = value
            this.hiddenTarget.dispatchEvent(new Event("input"))
            this.hiddenTarget.dispatchEvent(new Event("change"))
        }

        this.inputTarget.focus()
        this.hideAndRemoveOptions()

        this.element.dispatchEvent(
            new CustomEvent("autocomplete.change", {
                bubbles: true,
                detail: {
                    type: this.typeValue,
                    value: value,
                    textValue: textValue
                }
            })
        )
    }

    onEnterKeyDown(event) {
        if (!this.hasSubmitOnEnterValue) {
            event.preventDefault()
        }

        const selected = this.resultsTarget.querySelector(
            '[aria-selected="true"]'
        )

        // If we selected an item from the list, do the parent logic.
        if (selected && !this.resultsTarget.hidden) {
            this.commit(selected)
            return;
        }

        // Otherwise, we pressed enter and only text was typed into the input, nothing selected
        this.commitFromTextInput();
    }
}