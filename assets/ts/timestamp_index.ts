import '../styles/timestamp_index.scss';

import $ from 'jquery';
import { ApiTimestamp, TimestampApi } from "./core/api/timestamp_api";
import { JsonResponse } from "./core/api/api";
import { ApiTag } from "./core/api/tag_api";
import LoadingButton from "./components/loading_button";
import { formatTimeDifference, timeAgo } from "./components/time";
import { createTagsView, createTagView } from "./components/tags";
import TimeTrackerRoutes from "./core/routes";

class TimestampListPage {
    // Get template from twig.
    // Make a macro out of it, one that takes all params and one that takes an API object and use {FIELD_NAME}.
    public static createTableRow(timestamp: ApiTimestamp, dateTimeFormat: string, routes: TimeTrackerRoutes): string {
        const nowMillis = (new Date()).getTime();

        let tagHtml = createTagsView(timestamp.tags);

        const html = `
        <div
            class="card-list-item js-timestamp"
            data-id="${timestamp.id}"
        >
            <div class="tag-list js-tag-list many-rows mt-1">
                <div class="js-tag-list-view">${tagHtml}</div>
            </div>
            <div class="mt-2">
                <div
                    class="time-ago js-timestamp-ago"
                    data-created-at="${timestamp.createdAtEpoch}">
                    ${timeAgo(timestamp.createdAtEpoch * 1000, nowMillis)}
                </div>
                <div class="datetime">${timestamp.createdAt}</div>
            </div>
            <hr/>
            <div class="d-flex justify-content-end js-actions">
                <a href="${routes.timestampView(timestamp.id)}" class="btn btn-primary js-view">View</a>
                <button type="button" class="btn btn-secondary js-timestamp-repeat ml-2">
                    <span class="spinner-border spinner-border-sm d-none js-loading" role="status" aria-hidden="true"></span>
                    Repeat
                </button>
            </div>
        </div>`;

        return html;
    }

    static updateTimeAgo(when: Date) {
        const endMillis = when.getTime();

        $('.js-timestamp-ago').each((index: number, element: HTMLElement) => {
            const $element = $(element);
            const startSeconds = $element.data('created-at');

            const agoString = timeAgo(startSeconds * 1000, endMillis);

            $element.html(agoString);
        });
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const createdAtSort = $data.data('created-at-sort');
    const dateTimeFormat = $data.data('datetime-format');

    const $timestampList = $('.js-timestamp-list');

    const routes = new TimeTrackerRoutes();
    routes.addTemplateFromJoined($data.data('route-timestamp-view'));

    $timestampList
        .on('click',
            '.js-timestamp-repeat',
            (event) => {
        const $currentTarget = $(event.currentTarget);
        const loadingButton = new LoadingButton($currentTarget);
        const timestampId = $currentTarget.closest('.js-timestamp').data('id');

        loadingButton.startLoading();

        TimestampApi.repeat(timestampId)
            .then((res: JsonResponse<ApiTimestamp>) => {
                const newRow = $(TimestampListPage.createTableRow(res.data, dateTimeFormat, routes));

                if (createdAtSort === 'ASC') {
                    $timestampList.append(newRow);
                } else {
                    $timestampList.prepend(newRow);
                }

                loadingButton.stopLoading();
            });
    });

    setInterval(() => {
        const now = new Date();
        TimestampListPage.updateTimeAgo(now);
    }, 1000);
});