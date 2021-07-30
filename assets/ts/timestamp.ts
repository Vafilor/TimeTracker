import '../styles/timestamp.scss';

import $ from "jquery";
import TagList, { TagListDelegate } from "./components/tag_index";
import Flashes from "./components/flashes";
import { ApiTag } from "./core/api/tag_api";
import { TimestampApi } from "./core/api/timestamp_api";
import { TagAssigner } from "./components/tag_assigner";
import StatisticValuePicker, { StatisticValuePickedEvent } from "./components/statistic_value_picker";
import StatisticValueList, { AddStatisticValue, StatisticValueListDelegate } from "./components/statistic_value_list";
import { ApiErrorResponse, JsonResponse } from "./core/api/api";
import { ApiStatisticValue, StatisticValueApi } from "./core/api/statistic_value_api";

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

    update(id: string, value: number): Promise<JsonResponse<ApiStatisticValue>> {
        return StatisticValueApi.update(id, value);
    }

    remove(id: string): Promise<JsonResponse<void>> {
        return TimestampApi.removeStatistic(this.timestampId, id);
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const timestampId = $data.data('timestamp-id');

    const flashes = new Flashes($('#fixed-flash-messages'));

    const tagList = new TagList($('.js-tags'), new TimestampApiAdapter(timestampId, flashes));
    const autocomplete = new TagAssigner($('.js-autocomplete-tags-container'), tagList, flashes);

    const statisticValueList = new StatisticValueList($('.statistic-values'), new TimestampStatisticDelegate(timestampId), flashes);

    const statisticValuePicker = new StatisticValuePicker($('.js-add-statistic'), 'instant');
    statisticValuePicker.valuePicked.addObserver(async (event: StatisticValuePickedEvent) => {
        try {
            await statisticValueList.add({
                name: event.name,
                value: event.value
            });
        } catch (e) {
            if (!(e instanceof ApiErrorResponse)) {
                throw e;
            }

            const err = e as ApiErrorResponse;

            if (err.response.status === 409) {
                flashes.append('danger', `Unable to add record, a record with name '${event.name}' already exists for ${event.day}`);
            } else {
                flashes.append('danger', 'Unable to add record');
            }
        }
    });
});