import '../styles/timestamp.scss';

import $ from "jquery";
import TagList, { TagListDelegate } from "./components/tag_index";
import Flashes from "./components/flashes";
import { ApiTag } from "./core/api/tag_api";
import { TimestampApi } from "./core/api/timestamp_api";
import { TagAssigner } from "./components/tag_assigner";
import AutocompleteStatistics from "./components/autocomplete_statistics";
import { ApiStatistic, ApiStatisticValue, StatisticApi } from "./core/api/statistic_api";
import StatisticValuePicker, { StatisticValuePickedEvent } from "./components/StatisticValuePicker";
import StatisticValueList, { AddStatisticValue, StatisticValueListDelegate } from "./components/StatisticValueList";
import { JsonResponse } from "./core/api/api";

class TimestampApiAdapter implements TagListDelegate {
    constructor(private timestampId: string, private flashes: Flashes) {
    }

    addTag(tag: ApiTag): Promise<ApiTag> {
        return TimestampApi.addTag(this.timestampId, tag.name)
            .then(res => {
                return res.data;
            })
            .catch(res => {
                this.flashes.append('danger', `Unable to add tag '${tag.name}'`)
                throw res;
            });
    }

    removeTag(tagName: string): Promise<void> {
        return TimestampApi.removeTag(this.timestampId, tagName)
            .catch(res => {
                this.flashes.append('danger', `Unable to add remove tag '${tagName}'`)
                throw res;
            });
    }
}

class TimestampStatisticDelegate implements StatisticValueListDelegate{
    constructor(private timestampId: string) {
    }

    add(value: AddStatisticValue): Promise<JsonResponse<ApiStatisticValue>> {
        return TimestampApi.addStatistic(this.timestampId, {
            statisticName: value.name,
            value: value.value
        });
    }

    remove(id: string): Promise<JsonResponse<void>> {
        return TimestampApi.removeStatistic(this.timestampId, id);
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const timestampId = $data.data('timestamp-id');
    const durationFormat = $data.data('duration-format');

    const flashes = new Flashes($('#flash-messages'));

    const tagList = new TagList($('.js-tags'), new TimestampApiAdapter(timestampId, flashes));
    const autocomplete = new TagAssigner($('.js-autocomplete-tags'), tagList, flashes);

    const statisticValueList = new StatisticValueList($('.statistic-values'), new TimestampStatisticDelegate(timestampId));

    const statisticValuePicker = new StatisticValuePicker($('.js-add-statistic'), 'instant');
    statisticValuePicker.valuePicked.addObserver((event: StatisticValuePickedEvent) => {
        statisticValueList.addRequest({
            name: event.name,
            value: event.value
        });
    });
});