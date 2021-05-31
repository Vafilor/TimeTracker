import Flashes from "./flashes";
import AutocompleteTaskCreate from "./autocomplete_tasks_create";
import { ApiTask, ApiTaskAssign } from "../core/api/task_api";
import { TimeEntryApi, TimeEntryApiErrorCode } from "../core/api/time_entry_api";
import $ from "jquery";
import { ApiErrorResponse } from "../core/api/api";
import TagList from "./tag_index";
import { ApiTag } from "../core/api/tag_api";
import { TagsAutocompleteV2 } from "../time_entry_index";

export class TimeEntryTaskAssigner {
    private timeEntryId: string;
    private flashes: Flashes;
    private autocomplete: AutocompleteTaskCreate;
    private readonly $container: JQuery;

    static template(taskName?: string): string {
        `<div class="autocomplete js-autocomplete js-autocomplete-task">
            <div class="search">
                <input
                        type="text"
                        class="js-input"
                        placeholder="task name..."
                        name="task">
                <button class="clear js-clear btn btn-sm"><i class="fas fa-times"></i></button>
            </div>
            <div class="search-results js-search-results d-none"></div>
        </div>`

        let inputValue = `
        <div class="border autocomplete js-autocomplete">
            <input
                type="text"
                class="js-input"
                placeholder="task name..."
                aria-label="loading indicator"
                name="task"`

        // if (taskName) {
        //     inputValue += `value = "${taskName}" disabled`;
        // }

        inputValue += `>
            <div class="js-clear clear d-inline-block"><i class="fas fa-times"></i></div>
        </div>`;

        return `
        <div class="autocomplete js-autocomplete js-autocomplete-task">
            <div class="search">
                <input
                        type="text"
                        class="js-input"
                        placeholder="task name..."
                        name="task">
                <button class="clear js-clear btn btn-sm"><i class="fas fa-times"></i></button>
            </div>
            <div class="search-results js-search-results d-none"></div>
        </div>`
    }

    constructor($container: JQuery, timeEntryId: string, assignedToTask = false, flashes: Flashes) {
        this.timeEntryId = timeEntryId;
        this.flashes = flashes;

        this.$container = $container;

        if (assignedToTask) {
            this.onAssignedToTask();
        } else {
            this.onEditTask();
        }

        this.autocomplete = new AutocompleteTaskCreate($container);
        this.autocomplete.valueEmitter.addObserver((apiTask: ApiTask) => {
            TimeEntryApi.assignToTask(timeEntryId, apiTask.name, apiTask.id)
                .then((res) => {
                    this.onAssignedToTask();
                });
        });

        this.autocomplete.enterValueEmitter.addObserver((apiTaskAssign: ApiTaskAssign) => {
            TimeEntryApi.assignToTask(timeEntryId, apiTaskAssign.name, apiTaskAssign.id)
                .then((res) => {
                    if (res.source.status === 201) {
                        if (res.data.url) {
                            this.flashes.appendWithLink('success', `Created new task`, res.data.url, res.data.name);
                        }
                    }
                    this.onAssignedToTask();
                })
        });

        $('.js-clear-task').on('click', (event) => {
            TimeEntryApi.unassignTask(timeEntryId)
                .then(() => {
                    this.autocomplete.clearInput();
                    this.onEditTask();
                    this.flashes.append('success', 'Unassigned from task', true);
                }).catch((errRes: ApiErrorResponse) => {
                if (errRes.hasErrorCode(TimeEntryApiErrorCode.codeNoAssignedTask)) {
                    flashes.append('danger', 'Time entry has no assigned task');
                }
            });
        });

        this.$container.find('.js-edit').on('click', () => {
            this.onEditTask();
        });
    }

    private onAssignedToTask() {
        this.$container.find('.js-edit').removeClass('d-none');
        this.$container.find('.js-set').addClass('d-none');
        this.$container.find('.js-input').attr('disabled', 'true');
    }

    private onEditTask() {
        this.$container.find('.js-edit').addClass('d-none');
        this.$container.find('.js-set').removeClass('d-none');
        this.$container.find('.js-input').removeAttr('disabled');
    }

    getContainer(): JQuery {
        return this.$container;
    }
}