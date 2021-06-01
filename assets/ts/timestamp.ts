import '../styles/timestamp.scss';

import $ from "jquery";
import TagList, { TagListDelegate } from "./components/tag_index";
import Flashes from "./components/flashes";
import { ApiTag } from "./core/api/tag_api";
import { TimestampApi } from "./core/api/timestamp_api";
import AutocompleteTags from "./components/autocomplete_tags";

class TimestampApiAdapter implements TagListDelegate {
    constructor(private timestampId: string, private flashes: Flashes) {
    }

    addTag(tag: ApiTag): Promise<ApiTag> {
        return TimestampApi.addTag(this.timestampId, tag.name)
            .then(res => {
                return res.data;
            })
            .catch(res => {
                this.flashes.append('danger', `Unable to add tag '${tag.name}'`)
                return res;
            });
    }

    removeTag(tagName: string): Promise<void> {
        return TimestampApi.removeTag(this.timestampId, tagName)
            .catch(res => {
                this.flashes.append('danger', `Unable to add remove tag '${tagName}'`)
                throw res;
            });
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const timestampId = $data.data('timestamp-id');
    const durationFormat = $data.data('duration-format');

    const flashes = new Flashes($('#flash-messages'));

    const tagList = new TagList($('.js-tags'), new TimestampApiAdapter(timestampId, flashes));
    const autocomplete = new AutocompleteTags($('.js-autocomplete-tags'));
    autocomplete.setTagNames(tagList.getTagNames());

    autocomplete.itemSelected.addObserver((tag: ApiTag) => {
        tagList.add(tag);
        autocomplete.clear();
    })

    tagList.tagsChanged.addObserver(() => {
        autocomplete.setTagNames(tagList.getTagNames());
    });
});