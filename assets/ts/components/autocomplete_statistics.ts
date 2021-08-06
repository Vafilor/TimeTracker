import { JsonResponse, PaginatedResponse } from "../core/api/api";
import { PaginatedAutocomplete } from "./autocomplete";
import { StatisticApi } from "../core/api/statistic_api";
import { ApiStatistic, TimeType } from "../core/api/types";

export default class AutocompleteStatistics extends PaginatedAutocomplete<ApiStatistic> {
    constructor($element: JQuery, private timeType: TimeType = 'instant') {
        super($element);
    }

    protected template(item: ApiStatistic): string {
        let icon = `<i class="far fa-square" style="color: ${item.color}"></i>`;
        if (item.icon) {
            icon = `<i class="${item.icon}" style="color: ${item.color}"></i>`
        }

        return `<div><span class="autocomplete-statistic-icon">${icon}</span>${item.name}</div>`;
    }

    // TODO
    protected queryApi(query: string): Promise<JsonResponse<PaginatedResponse<ApiStatistic>>> {
        return StatisticApi.indexV1(query, this.timeType);
    }
}