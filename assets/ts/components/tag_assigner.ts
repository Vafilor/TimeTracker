import Flashes from "./flashes";
import TagList from "./tag_index";
import AutocompleteTags from "./autocomplete_tags";
import { AutocompleteEnterPressedEvent } from "./autocomplete";
import IdGenerator from "./id_generator";
import { ApiTag } from "../core/api/types";

export class TagAssigner {
    private readonly _$container: JQuery;
    get $container(): JQuery {
        return this._$container;
    }

    private readonly flashes: Flashes;
    private readonly tagList: TagList;
    private autocomplete: AutocompleteTags;

    static template(): string {
        const id = IdGenerator.next();

        return `
        <div class="autocomplete js-autocomplete js-autocomplete-tags">
            <div class="autocomplete-search-group">
                <label class="visually-hidden" for="autocomplete-tag-${id}">name</label>
                <input
                        id="autocomplete-tag-${id}"
                        type="search"
                        class="js-input form-control search"
                        placeholder="tag name..."
                        name="tag"
                        autocomplete="off">
                <button type="button" class="btn js-add btn-outline-primary autocomplete-search-group-append">
                    Add
                </button>   
            </div>
            <div class="search-results d-none js-search-results"></div>
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