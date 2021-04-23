import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap'; // Adds functions to jQuery
import '../styles/time_entry_list.scss';
import { TimeEntryApi } from "./core/api/time_entry_api";
import Flashes from "./components/flashes";

$(document).ready( () => {
    const dateFormat = $('.js-data').data('date-format');
    const flashes = new Flashes($('#flash-messages'));

    $('.js-stop').on('click', (event) => {
        const $target = $(event.currentTarget);
        const $row = $target.parent().parent();

        const timeEntryId = $target.data('time-entry-id') as string;

        $target.attr('disabled', 'true');
        $target.find('.js-loading').toggleClass('d-none');

        TimeEntryApi.stop(timeEntryId, dateFormat)
            .then(res => {
                $row.find('.js-ended-at').text(res.data.endedAt);
                $row.find('.js-duration').text(res.data.duration);
                $target.removeAttr('disabled');
                $target.find('.js-loading').toggleClass('d-none');
                $target.remove();
            }).catch(res => {
                flashes.append('danger', 'Unable to stop time entry');
                $target.removeAttr('disabled');
                $target.find('.js-loading').toggleClass('d-none');
            }
        );
    })
});