import '../styles/timestamp_index.scss';

import $ from 'jquery';
import { CreateTimestampResponse, TimestampApi } from "./core/api/timestamp_api";
import LoadingButton from "./components/loading_button";
import { AxiosResponse } from "axios";

$(document).ready(() => {
    const $timestampList = $('.js-timestamp-list');

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
                $timestampList.prepend(res.data.view);
                loadingButton.stopLoading();
            });
    });
});