import $ from "jquery";
import Observable from "./observable";
import { JsonResponse } from "../core/api/api";

export default abstract class Autocomplete<T> {
    protected $container: JQuery;
    protected $loading: JQuery;
    public readonly $nameInput: any;
    public readonly valueEmitter = new Observable<T>()

    constructor(selector: string) {
        this.$container = $(selector);
        if (this.$container.length === 0) {
            this.$container = undefined;
            return;
        }

        this.$nameInput = this.$container.find('.js-input');
        this.$loading = this.$container.find('.js-loading');

        this.setupAutoComplete();
    }

    public live() {
        return this.$container !== undefined;
    }

    protected getInputValue(): string {
        return this.$nameInput.val() as string;
    }

    protected abstract listItems(name: string, request: any): Promise<JsonResponse<any>>;

    protected abstract createItemTemplate(item: T): string;

    protected abstract onItemSelected(item: T);

    private setupAutoComplete() {
        const $nameInput = this.$nameInput;

        $nameInput.autocomplete({
            minLength: 0,
            source: (request, response) => {
                this.$loading.removeClass('opacity-invisible');

                this.listItems(request.term, request)
                    .then(res => {
                        this.$loading.addClass('opacity-invisible');
                        response(res.data.data);

                        // Remove the autocomplete text that shows how many search results were present, etc.
                        $('.ui-helper-hidden-accessible div').remove();
                    }).catch(err => {
                    this.$loading.addClass('opacity-invisible');
                    response([]);
                    // Remove the autocomplete text that shows how many search results were present, etc.
                    $('.ui-helper-hidden-accessible div').remove();
                });
            },
            focus: function (event, ui) {
                $nameInput.val(ui.item.name);
                return false;
            },
            select: (event, ui) => {
                $nameInput.val(ui.item.name);
                this.onItemSelected(ui.item);

                return false;
            },
            delay: 300,
        }).autocomplete("instance")._renderItem = (ul, item) => {
            const template = this.createItemTemplate(item);
            return $("<li>")
                .append(template)
                .append("</li>")
                .appendTo(ul)
            ;
        };
    }

    public clearInput() {
        if (this.live()) {
            this.$nameInput.val('');
        }
    }
}