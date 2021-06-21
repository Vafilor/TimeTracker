import AutocompleteStatistics from "./autocomplete_statistics";
import { ApiStatistic, ApiStatisticValue, TimeType } from "../core/api/statistic_api";
import Observable from "./observable";
import { AutocompleteEnterPressedEvent } from "./autocomplete";
import $ from "jquery";

export interface StatisticValuePickedEvent {
    name: string;
    value: number;
}

export default class StatisticValuePicker {
    private autocompleteStatistic: AutocompleteStatistics;
    private $statisticInput: JQuery;
    public valuePicked = new Observable<StatisticValuePickedEvent>();

    constructor(private $container: JQuery, timeType: TimeType) {
        this.autocompleteStatistic = new AutocompleteStatistics($container.find('.js-autocomplete-statistic'), timeType);
        this.$statisticInput = this.$container.find('.js-statistic-input');

        this.autocompleteStatistic.itemSelected.addObserver((val: ApiStatistic) => {
            this.onAutocompleteValueSelected(val.name);
        })

        this.autocompleteStatistic.enterPressed.addObserver((event: AutocompleteEnterPressedEvent<ApiStatistic>) => {
            if (event.data) {
                this.onAutocompleteValueSelected(event.data.name);
            } else {
                event.query;
            }
        })

        this.$statisticInput.on('keypress', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();

               this.onAdd();
            }
        });

        $container.find('.js-add').on('click', (event) => {
            this.onAdd();
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

        this.valuePicked.emit({
            name: statisticName,
            value: valueNumber,
        });

        this.autocompleteStatistic.clear();
        this.$statisticInput.val('');
        this.autocompleteStatistic.focus();
    }
}
