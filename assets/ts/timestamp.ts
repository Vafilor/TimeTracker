import '../styles/timestamp.scss';

import TagList, { TagListDelegate } from "./components/tag_list";
import Flashes from "./components/flashes";
import { ApiTag } from "./core/api/tag_api";
import { TimestampApi } from "./core/api/timestamp_api";
import $ from "jquery";
import 'jquery-ui/ui/widgets/autocomplete';
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

    const tagList = new TagList('.js-tags', new TimestampApiAdapter(timestampId, flashes));
    const autoComplete = new AutocompleteTags('.js-autocomplete-tags');
    autoComplete.setTags(tagList.getTagNames());

    autoComplete.valueEmitter.addObserver((apiTag: ApiTag) => {
        tagList.add(apiTag);
    })

    tagList.tagsChanged.addObserver(() => {
        autoComplete.setTags(tagList.getTagNames());
    });
});