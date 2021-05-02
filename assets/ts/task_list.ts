import '../styles/task_list.scss';

import $ from 'jquery';
import Flashes from "./components/flashes";
import { ApiTask, TaskApi } from "./core/api/task_api";
import { JsonResponse } from "./core/api/api";

$(document).ready(() => {
    const dateFormat = $('.js-data').data('date-format');

    $('.js-task-completed').on('change', (event) => {
        const $target = $(event.currentTarget);
        const checked = $target.is(':checked');
        const taskId = $target.data('task-id') as string;

        $target.attr('disabled', 'true');

        TaskApi.check(taskId, checked)
            .then((res: JsonResponse<ApiTask>) => {
                $target.removeAttr('disabled');
                $target.parent().find('.js-completed-at').remove();

                if (res.data.completedAt) {
                    $target.parent().append(`<span class="ml-1 js-completed-at">${res.data.completedAt}</span>`);
                }
            })
            .catch(() => {
                $target.removeAttr('disabled');
            })
    });
});