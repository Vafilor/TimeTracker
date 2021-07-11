import '../styles/time_entry_index.scss';

import $ from 'jquery';
import 'bootstrap'; // Adds functions to jQuery

import {
    ApiTimeEntry,
    CreateTimeEntryResponse,
    DateFormat,
    TimeEntryApi,
    TimeEntryApiErrorCode
} from "./core/api/time_entry_api";
import Flashes from "./components/flashes";
import LoadingButton from "./components/loading_button";
import { ApiTask } from "./core/api/task_api";
import { ApiErrorResponse, ApiResourceError } from "./core/api/api";
import { ConfirmClickEvent, ConfirmDialog } from "./components/confirm_dialog";
import AutocompleteTask from "./components/autocomplete_task";
import { AutocompleteEnterPressedEvent } from "./components/autocomplete";
import { TagFilter } from "./components/tag_filter";
import { TimeEntryActionDelegate, TimeEntryIndexItem } from "./components/time_entry";

class TimeEntryListFilter {
    private $element: JQuery;
    private flashes: Flashes;
    private tagFilter: TagFilter;

    constructor($element: JQuery, flashes: Flashes) {
        this.$element = $element;
        this.flashes = flashes;

        this.setUpTaskFilter();
        this.tagFilter = new TagFilter($element);
    }

    private setUpTaskFilter() {
        // The actual, hidden, form element
        const $realTaskInput = $('.js-real-task-input');

        const autocompleteTask = new AutocompleteTask($('.js-autocomplete-task'));
        autocompleteTask.itemSelected.addObserver((task: ApiTask) => {
            autocompleteTask.setQuery(task.name);
            autocompleteTask.clearSearchContent();
            $realTaskInput.val(task.id);
        })

        autocompleteTask.enterPressed.addObserver((event: AutocompleteEnterPressedEvent<ApiTask>) => {
            if (event.data) {
                autocompleteTask.setQuery(event.data.name);
                autocompleteTask.clearSearchContent();
                $realTaskInput.val(event.data.id);
            }
        })

        autocompleteTask.inputChange.addObserver(() => {
            $realTaskInput.val('');
        })

        autocompleteTask.inputClear.addObserver(() => {
            $realTaskInput.val('');
        })
    }
}



$(document).ready( () => {
    // const page = new TimeEntryIndexPage();
})