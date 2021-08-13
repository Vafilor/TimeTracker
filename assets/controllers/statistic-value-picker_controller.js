import $ from "jquery";
import { Controller } from 'stimulus';
import StatisticValuePicker from "../ts/components/statistic_value_picker";

export default class extends Controller {
    connect() {
        const statisticValuePicker = new StatisticValuePicker($('.js-add-statistic'), 'instant');
    }
}