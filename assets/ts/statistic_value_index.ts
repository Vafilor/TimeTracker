import $ from 'jquery';

import '../styles/statistic_value_index.scss';
import StatisticValueList, { AddStatisticValue, StatisticValueListDelegate } from "./components/StatisticValueList";
import { JsonResponse } from "./core/api/api";
import { TimestampApi } from "./core/api/timestamp_api";
import Flashes from "./components/flashes";
import StatisticValuePicker, { StatisticValuePickedEvent } from "./components/StatisticValuePicker";
import { ApiStatisticValue, StatisticValueApi } from "./core/api/statistic_value_api";

class StatisticValueDayDelegate implements StatisticValueListDelegate{
    constructor() {
    }

    add(value: AddStatisticValue): Promise<JsonResponse<ApiStatisticValue>> {
        return StatisticValueApi.addForDay(value.name, value.value, value.day);
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
    statisticValuePicker.valuePicked.addObserver((event: StatisticValuePickedEvent) => {
        statisticValueList.add({
            name: event.name,
            value: event.value,
            day: event.day,
        });
    });
});