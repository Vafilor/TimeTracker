import { JsonResponse, PaginatedResponse } from "../core/api/api";
import { PaginatedAutocomplete } from "./autocomplete";
import { ApiStatistic, StatisticApi, TimeType } from "../core/api/statistic_api";

export default class AutocompleteStatistics extends PaginatedAutocomplete<ApiStatistic> {
    constructor($element: JQuery, private timeType: TimeType = 'instant') {
        super($element);
    }

    protected template(item: ApiStatistic): string {
        return `<div>${item.name}</div>`;
    }

    protected queryApi(query: string): Promise<JsonResponse<PaginatedResponse<ApiStatistic>>> {
        return StatisticApi.index(query, this.timeType);
    }
}