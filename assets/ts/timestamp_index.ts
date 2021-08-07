import '../styles/timestamp_index.scss';

import $ from 'jquery';
import { CreateTimestampResponse, TimestampApi } from "./core/api/timestamp_api";
import LoadingButton from "./components/loading_button";
import TimeTrackerRoutes from "./core/routes";
import { AxiosResponse } from "axios";

$(document).ready(() => {
    const $data = $('.js-data');
    const createdAtSort = $data.data('created-at-sort');

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
            .then((res: AxiosResponse<CreateTimestampResponse>) => {
                if (createdAtSort === 'ASC') {
                    $timestampList.append(res.data.view);
                } else {
                    $timestampList.prepend(res.data.view);
                }

                loadingButton.stopLoading();
            });
    });
});