import '../styles/timestamp_list.scss';

import $ from 'jquery';
import { ApiTimestamp, TimestampApi } from "./core/api/timestamp_api";
import { JsonResponse } from "./core/api/api";
import { ApiTag } from "./core/api/tag_api";
import LoadingButton from "./components/loading_button";

class TimestampListPage {
    public static createTag(tag: ApiTag): string {
        return `<div class="tag" style="background-color: ${tag.color};">${tag.name}</div>`;
    }

    // Get template from twig.
    // Make a macro out of it, one that takes all params and one that takes an aPI object and use {FIELD_NAME}.
    public static createTableRow(timestamp: ApiTimestamp, urlTemplate: string): string {
        const editUrl = urlTemplate.replace('TIMESTAMP_ID', timestamp.id);

        let data = `
                <tr data-timestamp-id="${timestamp.id}">
                    <td>`;

        for(const tag of timestamp.tags) {
            data += TimestampListPage.createTag(tag) + ' ';
        }

        data +=    `</td>
                    <td>${timestamp.createdAgo}</td>
                    <td>${timestamp.createdAt}</td>
                    <td>
                        <a href="${editUrl}" class="btn btn-primary">Edit</a>
                        <button type="button" class="btn btn-primary js-timestamp-repeat">
                            <span class="spinner-border spinner-border-sm d-none js-loading" role="status" aria-hidden="true"></span>
                            Mark again
                        </button>
                    </td>
                </tr>
        `;

        return data;
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const editTemplateUrl = $data.data('timestamp-edit-url');
    const createdAtSort = $data.data('created-at-sort');

    const $timestampList = $('.js-timestamp-list .js-timestamp-list-body');

    $('.js-timestamp-list-body')
        .on('click',
            '.js-timestamp-repeat',
            (event) => {
        const $currentTarget = $(event.currentTarget);
        const loadingButton = new LoadingButton($currentTarget);
        const timestampId = $currentTarget.parent().parent().data('timestamp-id');

        loadingButton.startLoading();

        TimestampApi.repeat(timestampId)
            .then((res: JsonResponse<ApiTimestamp>) => {
                const newRow = TimestampListPage.createTableRow(res.data, editTemplateUrl);

                if (createdAtSort === 'ASC') {
                    $timestampList.append(newRow);
                } else {
                    $timestampList.prepend(newRow);
                }

                loadingButton.stopLoading();
            });
    });
});
