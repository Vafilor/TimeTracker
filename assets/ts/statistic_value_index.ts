import $ from 'jquery';

import '../styles/statistic_value_index.scss';
import StatisticValueList, { AddStatisticValue, StatisticValueListDelegate } from "./components/statistic_value_list";
import { ApiErrorResponse, JsonResponse } from "./core/api/api";
import { TimestampApi } from "./core/api/timestamp_api";
import Flashes from "./components/flashes";
import StatisticValuePicker, { StatisticValuePickedEvent } from "./components/statistic_value_picker";
import { ApiStatisticValue, StatisticValueApi } from "./core/api/statistic_value_api";

class StatisticValueDayDelegate implements StatisticValueListDelegate{
    constructor() {
    }

    add(value: AddStatisticValue): Promise<JsonResponse<ApiStatisticValue>> {
        return StatisticValueApi.addForDay(value.name, value.value, value.day);
    }

    update(id: string, value: number): Promise<JsonResponse<ApiStatisticValue>> {
        return StatisticValueApi.update(id, value);
    }

    remove(id: string): Promise<JsonResponse<void>> {
        // TODO
        return TimestampApi.removeStatistic('this.timestampId', id);
    }
}

$(document).ready(() => {
    const $data = $('.js-data');

    const flashes = new Flashes($('#fixed-flash-messages'));

    const statisticValueList = new StatisticValueList($('.statistic-values'), new StatisticValueDayDelegate(), flashes);

    const statisticValuePicker = new StatisticValuePicker($('.js-add-statistic'), 'interval');
    statisticValuePicker.valuePicked.addObserver( async (event: StatisticValuePickedEvent) => {
        try {
            await statisticValueList.add({
                name: event.name,
                value: event.value,
                day: event.day,
            }, true);

            window.location.reload();
        } catch (e) {
            const err = e as ApiErrorResponse;
            if (!err) {
                return;
            }

            if (err.response.status === 409) {
                if (err.hasErrorCode('code_day_taken')) {
                    const dayTakenError = err.getErrorForCode('code_day_taken');
                    flashes.append('danger', dayTakenError!.message);
                } else {
                    flashes.append('danger', `Unable to add record, a record with name '${event.name}' already exists for ${event.day}`);
                }
            } else {
                flashes.append('danger', 'Unable to add record');
            }
        }
    });
});