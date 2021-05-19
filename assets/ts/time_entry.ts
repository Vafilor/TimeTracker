import '../styles/time_entry.scss';

import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap'; // Adds functions to jQuery

import { TimeEntryApi, TimeEntryApiErrorCode } from "./core/api/time_entry_api";
import { ApiTag } from "./core/api/tag_api";
import Flashes from "./components/flashes";
import TimerView, { DataAttributeTimerView } from "./components/timer";
import AutocompleteTags from "./components/autocomplete_tags";
import AutoMarkdown from "./components/automarkdown";
import AutocompleteTaskCreate from "./components/autocomplete_tasks_create";
import { ApiTask, ApiTaskAssign } from "./core/api/task_api";
import { ApiErrorResponse } from "./core/api/api";
import TagIndex, { TagIndexDelegate } from "./components/tag_index";

class TimeEntryApiAdapter implements TagIndexDelegate {
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

class TimeEntryTaskAssigner {
    private timeEntryId: string;
    private flashes: Flashes;
    private autocomplete: AutocompleteTaskCreate;
    private $container: JQuery;

    constructor(timeEntryId: string, assignedToTask = false, flashes: Flashes) {
        this.timeEntryId = timeEntryId;
        this.flashes = flashes;

        this.$container = $('.js-autocomplete-task-create');

        if (assignedToTask) {
            this.onAssignedToTask();
        } else {
            this.onEditTask();
        }

        this.autocomplete = new AutocompleteTaskCreate('.js-autocomplete-task-create');
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
                        this.flashes.appendWithLink('success', `Created new task`, res.data.url, res.data.name);
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
    const assignedTask = $data.data('assigned-task');

    const flashes = new Flashes($('#flash-messages'));

    const page = new TimeEntryPage(timeEntryId);
    const taskAssigned = new TimeEntryTaskAssigner(timeEntryId, assignedTask, flashes);

    const timerView = new DataAttributeTimerView('.js-timer', durationFormat, (durationString) => {
       document.title = durationString;
    });
    timerView.start();

    const tagIndex = new TagIndex('.js-tags', new TimeEntryApiAdapter(timeEntryId, flashes));
    const autoComplete = new AutocompleteTags('.js-autocomplete-tags');
    autoComplete.setTags(tagIndex.getTagNames());

    autoComplete.valueEmitter.addObserver((apiTag: ApiTag) => {
        tagIndex.add(apiTag);
    })

    tagIndex.tagsChanged.addObserver(() => {
        autoComplete.setTags(tagIndex.getTagNames());
    });
});