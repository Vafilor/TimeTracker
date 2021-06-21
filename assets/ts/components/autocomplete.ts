import $ from "jquery";
import Observable from "./observable";
import { JsonResponse, PaginatedResponse } from "../core/api/api";
import { createPopper } from "@popperjs/core";

/**
 * Autocomplete provides a basic autocomplete for an input/search results set of elements.
 * It sets up a debounce so queries are not immediately made.
 * It sets up an event listener so the search result are cleared when clicked outside.
 * It sets up a clear button so the search query is cleared.
 *
 * This class must be extended to provide search logic and set up the ui to place inside the results.
 *
 * The expected html is:
 *
 * <div class="autocomplete js-autocomplete">
 *     <div class="search">
 *        <input class="js-input" />
 *        <button class="clear js-clear"><i class="fas fa-times"></i></button>
 *     </div>
 *     <div class="search-results js-search-results d-none"></div>
 * </div>
 */
abstract class Autocomplete {
    /**
     * Used to make sure we have a debounce.
     */
    private timeout: any;

    /**
     * If true, the X or 'clear' button has been pressed.
     * We don't show results of a query if it has and there is one underway.
     */
    protected cancelled = false;

    /**
     * How long we wait until we consider the input to be "ready" to send to an API.
     */
    private _debounceTime = 500;
    public set debounceTime(value: number) {
        this._debounceTime = value;
    }
    public get debounceTime(): number {
        return this._debounceTime;
    }

    /**
     * The input element that has the query.
     */
    protected $input: JQuery;

    /**
     * The element containing the entire search box.
     * Includes the input and clear button, but not search results.
     */
    protected readonly $search: JQuery;

    /**
     * The element containing the search results or other messages.
     * E.g. it contains messages for `loading` and `no search results found`
     */
    protected readonly $searchContent: JQuery;

    /**
     * This is fired whenever the input value changes.
     */
    public readonly inputChange = new Observable<string>();

    /**
     * This is fired whenever the search query is cleared.
     */
    public readonly inputClear = new Observable<void>();

    /**
     * @param $element the element containing the autocomplete content.
     */
    constructor(private $element: JQuery) {
        this.$input = $element.find('.js-input');
        this.$input.on('input', (event) => this.onInput(event));
        this.$search = $element.find('.search');
        this.$searchContent = $element.find('.js-search-results');

        $(document).on('click', () => this.onClickOutside());
        $element.on('click', (event) => {
            // Don't propagate event to document so we don't close search results - which happens when document is clicked.
            event.stopPropagation();
        })

        $element.find('.js-clear').on('click', () => this.clear());
    }

    /**
     * search is called whenever the user enters in a search term and the debounce is over.
     * This should make the API request and handle any results/errors.
     */
    abstract search(query: string);

    /**
     * clearSearchContent is called whenever we need to remove the search content.
     * This happens when we click outside the search.
     */
    public clearSearchContent() {
        this.$searchContent.addClass('d-none');
        this.$searchContent.html('');
    }

    /**
     * onClickOutside is called whenever we click something outside the search element.
     */
    protected onClickOutside() {
        this.clearSearchContent();
    }

    /**
     * onInput is called whenever the input element's content changes.
     */
    protected onInput(event: any) {
        if (this.cancelled) {
            this.cancelled = false;
        }

        const text = $(event.currentTarget).val() as string;
        this.inputChange.emit(text);

        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
            this.search(text);
        }, this.debounceTime);
    }

    /**
     * setMinSearchContentDimensions is called whenever we have search content modifications
     * and we want to make sure the search content has some minimum dimensions.
     *
     * Right now this is used to make sure the search content is as least as wide as the search input.
     */
    protected setMinSearchContentDimensions() {
        const width = this.$search[0].offsetWidth;
        this.$searchContent.css('min-width', width + 'px');
    }

    /**
     * positionSearchResults is called when we're about to display search results
     * and we want to position the search results below the search input.
     *
     * Popper is used to achieve this by default.
     */
    positionSearchContent() {
        createPopper(this.$search[0], this.$searchContent[0], {
            placement: 'bottom-start',
            modifiers: [
                {
                    name: 'offset',
                    options: {
                        offset: [0, 2]
                    }
                }
            ]
        });
    }

    /**
     * setSearchLoadingContent is called whenever we change the query and want to display loading content.
     * @param query
     */
    protected setSearchLoadingContent(query: string) {
        this.$searchContent.removeClass('d-none');

        this.$searchContent.html(`
            <div class="searching">
                <div class="spinner-border spinner-border-sm text-primary mr-1" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                 Searching...
            </div>
        `);
    }

    /**
     * onSearchLoading is called whenever we are ready to send off a query.
     * It sets the minimum content size,
     * sets the loading content,
     * and positions the search content.
     * @param query
     */
    protected onSearchLoading(query: string) {
        this.setMinSearchContentDimensions();
        this.setSearchLoadingContent(query);
        this.positionSearchContent();
    }

    /**
     * setQuery sets the input's value. No search is performed.
     * @param value
     */
    setQuery(value: string) {
        this.$input.val(value);
    }

    /**
     * getQuery gets the input's current value.
     */
    getQuery(): string {
        return this.$input.val() as string;
    }

    /**
     * clear clears the search content and the input. It does not trigger a search.
     */
    clear() {
        this.clearSearchContent();
        this.$input.val('');
        this.cancelled = true;
        this.inputClear.emit();
    }

    /**
     * Triggers blur on the input.
     */
    blur() {
        this.$input.trigger('blur');
    }

    /**
     * Triggers focus on the input.
     */
    focus() {
        this.$input.trigger('focus');
    }
}

export interface AutocompleteEnterPressedEvent<T> {
    query: string;
    data?: T;
} 

/**
 * PaginatedAutocomplete simplifies creating an autocomplete for responses that are Paginated.
 * A subclass should implement the following methods
 * * template
 * * queryApi
 *
 */
export abstract class PaginatedAutocomplete<T> extends Autocomplete {
    /**
     * itemSelected is fired whenever a search result is clicked on.
     * The pagination response item is the data emitted.
     */
    public itemSelected = new Observable<T>();

    /**
     * enterPressed is fired whenever the enter key is pressed in the search input.
     * The value is the current query along with any currently hovered item from the search results.
     * There may not be any item currently hovered over, so it is optional.
     */
    public enterPressed = new Observable<AutocompleteEnterPressedEvent<T>>();

    private _data: T[];
    set data(value: T[]) {
        this._data = value;
        this.itemIndexFocused = undefined;
    }
    get data(): T[] {
        return this._data;
    }

    private itemFocused?: T;
    private _itemIndexFocused?: number;
    set itemIndexFocused(value: number|undefined) {
        this._itemIndexFocused = value;
        this.$searchContent.find('.paginated-autocomplete-selected')
            .removeClass('paginated-autocomplete-selected')
        ;

        if (value !== undefined) {
            this.itemFocused = this.data[value];

            this.$searchContent.find(`.js-paginated-autocomplete-index-${value}`)
                .addClass('paginated-autocomplete-selected')
            ;
        } else {
            this.itemFocused = undefined;
        }
    }
    get itemIndexFocused(): number|undefined {
        return this._itemIndexFocused;
    }

    constructor($element: JQuery) {
        super($element);

        this.$input.on('keypress', (event) => {
            if (event.key === 'Enter') {
                // Don't form doesn't submit, if there is one.
                event.preventDefault();

                const query = $(event.currentTarget).val() as string;
                this.enterPressed.emit({
                    query,
                    data: this.itemFocused
                });
                this.itemFocused = undefined;
            }
        });

        this.$input.on('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                this.onArrowDown();
            } else if (event.key === 'ArrowUp') {
                this.onArrowUp();
            }
        });
    }

    private onArrowDown() {
        if (this.data.length === 0) {
            return;
        }

        let index = this.itemIndexFocused;
        if (index === undefined) {
            index = -1;
        }

        if (index === (this.data.length - 1)) {
            index = -1;
        }

        this.itemIndexFocused = index + 1;
    }

    private onArrowUp() {
        if (this.data.length === 0) {
            return;
        }

        let index = this.itemIndexFocused;
        if (index === undefined) {
            index = this.data.length - 1;
        }

        if (index === 0) {
            index = this.data.length;
        }

        this.itemIndexFocused = index - 1;
    }

    /**
     * template returns the html template to display in the search results for the input item.
     * @param item
     */
    protected abstract template(item: T): string;

    /**
     * queryApi is the network request made to get a response given the query.
     * @param query
     */
    protected abstract queryApi(query: string): Promise<JsonResponse<PaginatedResponse<T>>>;

    /**
     * noResultsTemplate is the element returned to display that there are no results.
     * It should have a class of "no-more-results" on the root element.
     */
    protected noResultsTemplate(): string {
        return `<div class="no-more-results">No results found</div>`;
    }

    /**
     * moreResultsTemplate is the element returned to display that there are more results for this query.
     * It should have a class of "more-results" on the root element.
     * @param response
     */
    protected moreResultsTemplate(response: PaginatedResponse<T>): string {
        const notDisplayed = response.totalCount - response.count;
        return `<div class="more-results"">${notDisplayed} more results</div>`;
    }

    async search(query: string) {
        this.onSearchLoading(query);

        const results = await this.queryApi(query);

        if (this.cancelled) {
            this.clearSearchContent();
            return;
        }
        if (results.data.count === 0) {
            this.$searchContent.html(this.noResultsTemplate());
            return;
        }

        this.$searchContent.html('');

        this.data = results.data.data;

        let index = 0;
        for(const item of results.data.data) {
            const $template = $(this.template(item));
            $template.addClass('search-result-item');
            $template.addClass(`js-paginated-autocomplete-index-${index}`);
            $template.on('click', () => this.itemSelected.emit(item));
            this.$searchContent.append($template);
            this.$searchContent.append('<hr class="separator"/>');

            index++;
        }

        if(results.data.totalCount > results.data.count) {
            this.$searchContent.append($(this.moreResultsTemplate(results.data)));
        }

        this.$searchContent.append(this.$searchContent);
        this.$searchContent.removeClass('d-none');
    }

    public clear() {
        super.clear();
        this._itemIndexFocused = undefined;
    }
}