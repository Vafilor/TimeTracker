import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap'; // Adds functions to jQuery
import '../styles/time_entry.scss';

import { TimeEntryApi } from "./core/api/time_entry_api";
import { ApiTag } from "./core/api/tag_api";
import Flashes from "./components/flashes";
import TimerView from "./components/timer";
import TagList, { TagListDelegate } from "./components/tag_list";
import AutocompleteTags from "./components/autocomplete_tags";
import AutoMarkdown from "./components/automarkdown";

class TimeEntryApiAdapter implements TagListDelegate {
    constructor(private timeEntryId: string, private flashes: Flashes) {
    }

    addTag(tag: ApiTag): Promise<ApiTag> {
        return TimeEntryApi.addTag(this.timeEntryId, tag.name)
            .then(res => {
                return res.data;
            })
            .catch(res => {
                this.flashes.append('danger', `Unable to add tag '${tag.name}'`)
                return res;
            });
    }

    removeTag(tagName: string): Promise<void> {
        return TimeEntryApi.removeTag(this.timeEntryId, tagName)
            .catch(res => {
                this.flashes.append('danger', `Unable to add remove tag '${tagName}'`)
                throw res;
            });
    }
}

class TimeEntryAutoMarkdown extends AutoMarkdown {
    private readonly timeEntryId: string;

    constructor(
        inputSelector: string,
        markdownSelector: string,
        loadingSelector: string,
        timeEntryId: string) {
        super(inputSelector, markdownSelector, loadingSelector);
        this.timeEntryId = timeEntryId;
    }

    protected update(body: string): Promise<any> {
        return TimeEntryApi.update(this.timeEntryId, {
            description: body,
        });
    }
}

class TimeEntryPage {
    private autoMarkdown: TimeEntryAutoMarkdown;

    constructor(private timeEntryId: string) {
        this.autoMarkdown = new TimeEntryAutoMarkdown(
            '.js-description',
            '#preview-content',
            '.markdown-link',
            timeEntryId
        );
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const timeEntryId = $data.data('time-entry-id');
    const durationFormat = $data.data('duration-format');

    const flashes = new Flashes($('#flash-messages'));

    const page = new TimeEntryPage(timeEntryId);

    const timerView = new TimerView('.js-timer', durationFormat, (durationString) => {
       document.title = durationString;
    });

    const tagList = new TagList('.js-tags', new TimeEntryApiAdapter(timeEntryId, flashes));
    const autoComplete = new AutocompleteTags('.js-autocomplete-tags');

    autoComplete.tagEmitter.addObserver((apiTag: ApiTag) => {
        tagList.add(apiTag);
    })

    tagList.tagsChanged.addObserver(() => {
        autoComplete.setTags(tagList.getTagNames());
    });
});