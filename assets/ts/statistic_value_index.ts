import $ from 'jquery';

import '../styles/statistic_value_index.scss';
import Statistic_value_list, { AddStatisticValue, StatisticValueListDelegate } from "./components/statistic_value_list";
import { JsonResponse } from "./core/api/api";
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

    const statisticValueList = new Statistic_value_list($('.statistic-values'), new StatisticValueDayDelegate(), flashes);

    const statisticValuePicker = new StatisticValuePicker($('.js-add-statistic'), 'interval');
    statisticValuePicker.valuePicked.addObserver((event: StatisticValuePickedEvent) => {
        statisticValueList.add({
            name: event.name,
            value: event.value,
            day: event.day,
        });
    });
});