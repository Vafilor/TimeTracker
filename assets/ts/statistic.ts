import '../styles/statistic.scss';

import $ from "jquery";
import TagList, { TagListDelegate } from "./components/tag_index";
import Flashes from "./components/flashes";
import { ApiTag } from "./core/api/tag_api";
import { TagAssigner } from "./components/tag_assigner";
import { StatisticApi } from "./core/api/statistic_api";

class StatisticApiAdapter implements TagListDelegate {
    constructor(private statisticId: string, private flashes: Flashes) {
    }

    addTag(tag: ApiTag): Promise<ApiTag> {
        return StatisticApi.addTag(this.statisticId, tag.name)
            .then(res => {
                return res.data;
            })
            .catch(res => {
                this.flashes.append('danger', `Unable to add tag '${tag.name}'`)
                throw res;
            });
    }

    removeTag(tagName: string): Promise<void> {
        return StatisticApi.removeTag(this.statisticId, tagName)
            .catch(res => {
                this.flashes.append('danger', `Unable to add remove tag '${tagName}'`)
                throw res;
            });
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const statisticId = $data.data('statistic-id');
    const flashes = new Flashes($('#fixed-flash-messages'));
    const $previewContainer = $('.js-statistic-icon-preview');

    $('.js-color').on('input', (event) => {
        const hexColor = $(event.currentTarget).val() as string;

        $previewContainer.css('color', hexColor);
    })

    let timeout: any = undefined;


    $('.js-icon').on('input', (event) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const val = $(event.currentTarget).val() as string;

            $previewContainer.html(`<i class="${val}"></i>`);
        }, 300);
    })

    const tagList = new TagList($('.js-tags'), new StatisticApiAdapter(statisticId, flashes));
    const autocomplete = new TagAssigner($('.js-autocomplete-tags-container'), tagList, flashes);
});