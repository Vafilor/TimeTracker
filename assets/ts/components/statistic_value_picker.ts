import AutocompleteStatistics from "./autocomplete_statistics";
import Observable from "./observable";
import { AutocompleteEnterPressedEvent } from "./autocomplete";
import { ApiStatistic, TimeType } from "../core/api/types";

export interface StatisticValuePickedEvent {
    name: string;
    value: number;
    day?: string;
}

export default class StatisticValuePicker {
    private autocompleteStatistic: AutocompleteStatistics;
    private $statisticInput: JQuery;
    public valuePicked = new Observable<StatisticValuePickedEvent>();

    constructor(private $container: JQuery, timeType: TimeType) {
        this.autocompleteStatistic = new AutocompleteStatistics($container.find('.js-autocomplete-statistic'), timeType);
        this.$statisticInput = this.$container.find('.js-statistic-input');

        this.autocompleteStatistic.enterPressed.addObserver((event: AutocompleteEnterPressedEvent<ApiStatistic>) => {
            if (event.data) {
                this.onAutocompleteValueSelected(event.data.name);
            } else {
                this.onAutocompleteValueSelected(event.query);
            }
        })

        this.autocompleteStatistic.itemSelected.addObserver((event: ApiStatistic) => {
            this.onAutocompleteValueSelected(event.name);
        })

        this.autocompleteStatistic.enterPressed.addObserver((event: AutocompleteEnterPressedEvent<ApiStatistic>) => {
            if (event.data) {
                this.onAutocompleteValueSelected(event.data.name);
            } else {
                event.query;
            }
        })
    }

    private onAutocompleteValueSelected(value: string) {
        this.autocompleteStatistic.setQuery(value);
        this.autocompleteStatistic.clearSearchContent();
        this.autocompleteStatistic.blur();
        this.$statisticInput.trigger('focus');
    }

    onAdd() {
        let statisticName = this.autocompleteStatistic.getQuery();
        const value = this.$statisticInput.val() as string;
        const valueNumber = parseFloat(value);

        const pickedValue = {
            name: statisticName,
            value: valueNumber,
        };

        const day = this.$container.find('.js-day-input').val() as string;
        if (day) {
            pickedValue['day'] = day;
        }

        this.valuePicked.emit(pickedValue);

        this.autocompleteStatistic.clear();
        this.$statisticInput.val('');
        this.autocompleteStatistic.focus();
    }
}
