import Flashes from "./flashes";
import TagList from "./tag_index";
import { ApiTag } from "../core/api/tag_api";
import AutocompleteTags from "./autocomplete_tags";
import { AutocompleteEnterPressedEvent } from "./autocomplete";

export class TimeEntryTagAssigner {
    private readonly _$container: JQuery;
    get $container(): JQuery {
        return this._$container;
    }

    private readonly flashes: Flashes;
    private readonly tagList: TagList;
    private autocomplete: AutocompleteTags;

    static template(): string {
        return `
        <div class="autocomplete js-autocomplete js-autocomplete-tags">
            <div class="d-flex">
                <div class="search border-right-0 rounded-right-0">
                    <input
                            type="text"
                            class="js-input"
                            placeholder="tag name..."
                            name="tag"
                            autocomplete="off">
                    <button class="clear js-clear btn btn-sm"><i class="fas fa-times"></i></button>
                </div>
                <button type="button" class="btn js-add btn-outline-primary rounded-left-0">
                    Add
                </button>   
            </div>
            <div class="search-results js-search-results d-none"></div>
        </div>`;
    }

    constructor($container: JQuery, tagList: TagList, flashes: Flashes) {
        this._$container = $container;
        this.tagList = tagList;
        this.flashes = flashes;
        this.autocomplete = new AutocompleteTags($container);

        this.autocomplete.itemSelected.addObserver((tag: ApiTag) => this.onTagSelected(tag));
        this.autocomplete.enterPressed.addObserver((event: AutocompleteEnterPressedEvent<ApiTag>) => {
            if (event.data) {
                this.onTagSelected(event.data);
            } else {
                this.onAddTag(event.query);
            }
        });
        $container.find('.js-add').on('click', (event) => {
            this.onAddTag(this.autocomplete.getQuery());
        });

        this.autocomplete.setTagNames(tagList.getTagNames());
        this.tagList.tagsChanged.addObserver(() => {
            this.autocomplete.setTagNames(tagList.getTagNames());
        });
    }

    getTagList(): TagList {
        return this.tagList;
    }

    onTagSelected(tag: ApiTag) {
        this.tagList.add(tag);
        this.autocomplete.clear();
    }

    onAddTag(name: string) {
        this.onTagSelected({
            name,
            color: '#5d5d5d'
        });
    }
}