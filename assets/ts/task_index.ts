import '../styles/task_index.scss';

import $ from 'jquery';
import { ApiTask, TaskApi } from "./core/api/task_api";
import { JsonResponse } from "./core/api/api";

$(document).ready(() => {
    const $data = $('.js-data');
    const dateFormat = $data.data('date-format');
    const showCompleted = $data.data('show-completed');

    $('.js-task-completed').on('change', (event) => {
        const $target = $(event.currentTarget);
        const checked = $target.is(':checked');
        const taskId = $target.data('task-id') as string;

        $target.attr('disabled', 'true');

        TaskApi.check(taskId, checked)
            .then((res: JsonResponse<ApiTask>) => {
                $target.removeAttr('disabled');
                $target.parent().find('.js-completed-at').remove();

                if (res.data.completedAt && showCompleted) {
                    $target.parent().append(`<span class="ml-1 js-completed-at">${res.data.completedAt}</span>`);
                } else {
                    $target.parent().parent().remove();
                }
            })
            .catch(() => {
                $target.removeAttr('disabled');
            })
    });
});