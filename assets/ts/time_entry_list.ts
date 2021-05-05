import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap'; // Adds functions to jQuery
import '../styles/time_entry_list.scss';
import { TimeEntryApi } from "./core/api/time_entry_api";
import Flashes from "./components/flashes";
import LoadingButton from "./components/loading_button";
import AutocompleteTags from "./components/autocomplete_tags";
import { ApiTag } from "./core/api/tag_api";
import TagList from "./components/tag_list";
import AutocompleteTasks from "./components/autocomplete_tasks";
import { ApiTask } from "./core/api/task_api";


$(document).ready( () => {
    const dateFormat = $('.js-data').data('date-format');
    const flashes = new Flashes($('#flash-messages'));

    const stopButton = new LoadingButton($('.js-stop'));

    stopButton.$container.on('click', (event) => {
        const $target = $(event.currentTarget);
        const $row = $target.parent().parent();

        const timeEntryId = $target.data('time-entry-id') as string;

        stopButton.stopLoading();

        TimeEntryApi.stop(timeEntryId, dateFormat)
            .then(res => {
                $row.find('.js-ended-at').text(res.data.endedAt);
                $row.find('.js-duration').text(res.data.duration);
                stopButton.stopLoading();
                $target.remove();
            }).catch(res => {
                flashes.append('danger', 'Unable to stop time entry');
                stopButton.stopLoading();
            }
        );
    })

    const createTimeEntryButton = new LoadingButton($('.js-create-time-entry'));

    createTimeEntryButton.$container.on('click', (event) => {
        createTimeEntryButton.startLoading();

        TimeEntryApi.create(dateFormat)
            .then(res => {
                window.location.href = res.data.url;
                createTimeEntryButton.stopLoading();
            }).catch(res => {
                $('.js-stop-running').data('time-entry-id', res.errors[0].data);
                $('#confirm-stop-modal').modal();
                createTimeEntryButton.stopLoading();
            }
        );
    })

    const stopRunningButton = new LoadingButton($('.js-stop-running'));
    stopRunningButton.$container.on('click', (event)=> {
        const $target = $(event.currentTarget);
        const timeEntryId = $target.data('time-entry-id');

        stopRunningButton.startLoading();

        TimeEntryApi.stop(timeEntryId, dateFormat)
            .then(() => {
                TimeEntryApi.create(dateFormat)
                    .then(res => {
                        window.location.href = res.data.url;
                        stopRunningButton.stopLoading();
                    }).catch(res => {
                        $('#confirm-stop-modal').modal('hide');
                        stopRunningButton.stopLoading();
                    }
                );
            })
            .catch(() => {
                flashes.append('danger', 'Unable to stop time entry');
            });
    })

    const tagList = new TagList('.js-tags');
    const $realInput = $('.js-real-input');

    const autoComplete = new AutocompleteTags('.js-autocomplete-tags');
    if (autoComplete.live()) {
        autoComplete.valueEmitter.addObserver((apiTag: ApiTag) => {
            tagList.add(apiTag);
        })
    }

    tagList.tagsChanged.addObserver(() => {
        autoComplete.setTags(tagList.getTagNames());
        $realInput.val(tagList.getTagNamesCommaSeparated());
    });

    const $realTaskInput = $('.js-real-task-input');
    const autoCompleteTask = new AutocompleteTasks('.js-autocomplete-tasks');
    if (autoCompleteTask.live()) {
        autoCompleteTask.valueEmitter.addObserver((task: ApiTask) => {
            $realTaskInput.val(task.id);
        });

        autoCompleteTask.$nameInput.on('input', () => {
            $realTaskInput.val('');
        });
    }
});