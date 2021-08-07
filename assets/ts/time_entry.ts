import '../styles/time_entry.scss';

import $ from 'jquery';
import 'bootstrap'; // Adds functions to jQuery

import Flashes from "./components/flashes";
import TagList from "./components/tag_index";
import { TimeEntryTaskAssigner } from "./components/time_entry_task_assigner";
import { TimeEntryApiAdapter } from "./components/time_entry_api_adapater";
import { TagAssigner } from "./components/tag_assigner";
import TimerView from "./components/timer";
import StatisticValuePicker from "./components/statistic_value_picker";

class TimeEntryPage {
    private readonly timeEntryId: string;
    private readonly durationFormat: string;
    private autocompleteTask: TimeEntryTaskAssigner;
    private tagEdit: TagAssigner;
    private readonly flashes: Flashes;
    private timerView?: TimerView;

    constructor() {
        const $data = $('.js-data');
        this.timeEntryId = $data.data('time-entry-id');
        this.durationFormat = $data.data('duration-format');
        this.flashes = new Flashes($('#fixed-flash-messages'));

        this.autocompleteTask = new TimeEntryTaskAssigner($('.js-autocomplete-task'), this.timeEntryId, this.flashes);

        const $tagList = $('.js-tags');
        const tagList = new TagList($tagList, new TimeEntryApiAdapter(this.timeEntryId, this.flashes));
        const $template = $('.js-autocomplete-tags-container');

        this.tagEdit = new TagAssigner($template, tagList, this.flashes);

        const statisticValuePicker = new StatisticValuePicker($('.js-add-statistic'), 'interval');
    }
}

$(document).ready(() => {
    const page = new TimeEntryPage();
});