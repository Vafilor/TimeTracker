import '../styles/time_entry.scss';

import $ from 'jquery';
import 'bootstrap'; // Adds functions to jQuery

import { TimeEntryApi } from "./core/api/time_entry_api";
import Flashes from "./components/flashes";
import AutoMarkdown from "./components/automarkdown";
import TagList from "./components/tag_index";
import { TimeEntryTaskAssigner } from "./components/time_entry_task_assigner";
import { TimeEntryApiAdapter } from "./components/time_entry_api_adapater";
import { TimeEntryTagAssigner } from "./components/time_entry_tag_assigner";
import TimerView from "./components/timer";

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
    private readonly timeEntryId: string;
    private readonly durationFormat: string;
    private autoMarkdown: TimeEntryAutoMarkdown;
    private autocompleteTask: TimeEntryTaskAssigner;
    private tagEdit: TimeEntryTagAssigner;
    private readonly flashes: Flashes;
    private timerView?: TimerView;

    constructor() {
        const $data = $('.js-data');
        this.timeEntryId = $data.data('time-entry-id');
        this.durationFormat = $data.data('duration-format');
        this.flashes = new Flashes($('#flash-messages'));

        this.autoMarkdown = new TimeEntryAutoMarkdown(
            '.js-description',
            '#preview-content',
            '.markdown-link',
            this.timeEntryId
        );

        this.autocompleteTask = new TimeEntryTaskAssigner($('.js-autocomplete-task'), this.timeEntryId, this.flashes);

        const $tagList = $('.js-tags');
        const tagList = new TagList($tagList, new TimeEntryApiAdapter(this.timeEntryId, this.flashes));
        const $template = $('.js-autocomplete-tags');

        this.tagEdit = new TimeEntryTagAssigner($template, tagList, this.flashes);

        const $timer = $('.js-timer.js-running-timer');
        if ($timer.length !== 0) {
            this.timerView = new TimerView($timer, this.durationFormat, (durationString) => {
                document.title = durationString;
            });

            this.timerView.start($timer.data('start') * 1000);
        }
    }
}

$(document).ready(() => {
    const page = new TimeEntryPage();
});