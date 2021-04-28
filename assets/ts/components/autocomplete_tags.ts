import $ from "jquery";
import { ApiTag, TagApi } from "../core/api/tag_api";
import Observable from "./observable";

export default class AutocompleteTags {
    private $container: JQuery;
    private $loading: JQuery;
    private readonly $tagNameInput: any;
    private tags = new Array<string>();
    public readonly tagEmitter = new Observable<ApiTag>()

    constructor(selector: string) {
        this.$container = $(selector);
        if (this.$container.length === 0) {
            this.$container = undefined;
            return;
        }

        this.$tagNameInput = this.$container.find('.js-tag-input');
        this.$loading = this.$container.find('.js-load');

        this.$container.find('.js-add-tag').on('click', (event) => {
            this.enterTag(this.getTagInput());
        });

        this.setupAutoComplete();
    }

    private getTagInput(): string {
        return this.$tagNameInput.val() as string;
    }

    private setupAutoComplete() {
        const $tagNameInput = this.$tagNameInput;

        $tagNameInput.on('keypress', (event) => {
            if (event.key === 'Enter') {
                // So form doesn't submit, if there is one.
                event.preventDefault();
                this.enterTag(this.getTagInput());
            }
        });

        $tagNameInput.autocomplete({
            source: (request, response) => {
                this.$loading.removeClass('opacity-invisible');

                TagApi.listTags(request.term, this.tags)
                    .then(res => {
                        this.$loading.addClass('opacity-invisible');
                        response(res.data);
                    })
            },
            focus: function (event, ui) {
                $tagNameInput.val(ui.item.name);
                return false;
            },
            select: (event, ui) => {
                $tagNameInput.val(ui.item.name);
                this.enterTag(ui.item.name);

                return false;
            },
            delay: 300,
        }).autocomplete("instance")._renderItem = function (ul, item) {
            return $("<li>")
                .append("<div>" + item.name + "</div>")
                .appendTo(ul);
        };
    }

    private enterTag(name: string, color: string = '#5d5d5d') {
        if (name === '') {
            return;
        }

        this.tagEmitter.emit({
            name,
            color,
        });

        this.$tagNameInput.val('');
    }

    public setTags(tags: Array<string>) {
        this.tags = tags;
    }
}