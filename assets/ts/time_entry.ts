import '../styles/time_entry.scss';

import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap'; // Adds functions to jQuery

import { TimeEntryApi } from "./core/api/time_entry_api";
import { ApiTag } from "./core/api/tag_api";
import Flashes from "./components/flashes";
import { DataAttributeTimerView } from "./components/timer";
import AutocompleteTags from "./components/autocomplete_tags";
import AutoMarkdown from "./components/automarkdown";
import TagList from "./components/tag_index";
import { TimeEntryTaskAssigner } from "./components/time_entry_task_assigner";
import { TimeEntryApiAdapter } from "./components/time_entry_api_adapater";

// TODO redo this class, can I just add an event? Why do I need to subclass?
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
    private autocompleteTask: TimeEntryTaskAssigner;
    private readonly flashes: Flashes;

    constructor(private timeEntryId: string, flashes: Flashes) {
        this.flashes = flashes;
        this.autoMarkdown = new TimeEntryAutoMarkdown(
            '.js-description',
            '#preview-content',
            '.markdown-link',
            timeEntryId
        );

        this.autocompleteTask = new TimeEntryTaskAssigner($('.js-autocomplete-task'), this.timeEntryId, this.flashes);
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const timeEntryId = $data.data('time-entry-id');
    const durationFormat = $data.data('duration-format');

    const flashes = new Flashes($('#flash-messages'));

    const page = new TimeEntryPage(timeEntryId, flashes);
    const timerView = new DataAttributeTimerView($('.js-timer'), durationFormat, (durationString) => {
       document.title = durationString;
    });
    timerView.start();

    const tagList = new TagList($('.js-tags'), new TimeEntryApiAdapter(timeEntryId, flashes));
    const autoComplete = new AutocompleteTags($('.js-autocomplete-tags'));
    autoComplete.setTags(tagList.getTagNames());

    autoComplete.valueEmitter.addObserver((apiTag: ApiTag) => {
        tagList.add(apiTag);
    })

    tagList.tagsChanged.addObserver(() => {
        autoComplete.setTags(tagList.getTagNames());
    });
});