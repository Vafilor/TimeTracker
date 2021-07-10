import TagList from "./tag_index";
import AutocompleteTags from "./autocomplete_tags";
import { ApiTag, ApiTagFromName } from "../core/api/tag_api";
import { AutocompleteEnterPressedEvent } from "./autocomplete";

export class TagFilter {
    private tagList: TagList;
    private autocompleteTags: AutocompleteTags;

    constructor($container: JQuery) {
        this.tagList = new TagList($container.find('.js-tag-list'));
        this.autocompleteTags = new AutocompleteTags($container.find('.js-autocomplete-tags'));

        this.autocompleteTags.itemSelected.addObserver((apiTag: ApiTag) => {
            this.addTag(apiTag);
        })

        this.autocompleteTags.enterPressed.addObserver((event: AutocompleteEnterPressedEvent<ApiTag>) => {
            if (event.data) {
                this.addTag(event.data);
            } else {
                this.addTag(event.query);
            }
        })

        $container.find('.js-autocomplete-tags .js-add').on('click', event => {
            const query = this.autocompleteTags.getQuery();

            this.addTag(query);
        })

        const $realTagInput = $container.find('.js-real-tag-input');
        this.tagList.tagsChanged.addObserver(() => {
            this.autocompleteTags.setTagNames(this.tagList.getTagNames());
            $realTagInput.val(this.tagList.getTagNamesCommaSeparated());
        });
    }

    private addTag(tag: ApiTag|string) {
        if (typeof tag === 'string') {
            tag = ApiTagFromName(tag);
        }

        this.tagList.add(tag);

        this.autocompleteTags.clear();

        setTimeout(() => {
            this.autocompleteTags.positionSearchContent();
        }, 10);
    }
}


