import '../styles/statistic_index.scss';

import $ from "jquery";
import {
    ApiStatistic,
    CreateStatisticOptions,
    CreateStatisticResponse,
    StatisticApi,
    TimeType
} from "./core/api/statistic_api";
import Observable from "./components/observable";
import Flashes from "./components/flashes";
import { ApiErrorResponse } from "./core/api/api";
import LoadingButton from "./components/loading_button";

class CreateStatisticForm {
    private $name: JQuery;
    private $description: JQuery;
    private $timeType: JQuery;
    private submitButton: LoadingButton;
    private flashes: Flashes;

    public statisticCreated = new Observable<CreateStatisticResponse>();

    constructor(private $container: JQuery, flashes: Flashes) {
        this.flashes = flashes;
        const $submitButton = $container.find('button[type=submit]');
        this.submitButton = new LoadingButton($submitButton);
        this.$name = $('#statistic-create-name');
        this.$description = $('#statistic-create-description');
        this.$timeType = $('#statistic-create-time-type');

        this.submitButton.$container.on('click', (event) => this.onFormSubmitted(event));
    }

    async onFormSubmitted(event) {
        event.preventDefault();

        this.submitButton.startLoading();

        const data = this.getData();
        try {
            const res = await StatisticApi.create(data);
            this.statisticCreated.emit(res.data);
            this.reset();
        } catch (e) {
            if (e instanceof ApiErrorResponse) {
                if (e.response.status === 409) {
                    this.flashes.append('danger', `Unable to add statistic, a statistic with name '${data.name}' already exists`);
                }
            } else {
                this.flashes.append('danger', 'Unable to add statistic');
            }
        }

        this.submitButton.stopLoading();
    }

    getData(): CreateStatisticOptions {
        const name = this.$name.val() as string;
        const description = this.$description.val() as string;
        const timeType = this.$timeType.val() as TimeType;

        return {
            name,
            description,
            timeType
        };
    }

    clear() {
        this.$name.val('');
        this.$description.val('');
    }

    reset() {
        this.clear();
        this.$name.trigger('focus');
    }
}

class StatisticList {
    constructor(private $container: JQuery) {
    }

    public add(view: string, statistic: ApiStatistic) {
        this.$container.prepend(view);
    }
}

$(document).ready(() => {
    const flashes = new Flashes($('#fixed-flash-messages'));
    const statisticList = new StatisticList($('.js-statistic-list'));
    const createForm = new CreateStatisticForm($('form.js-create-statistic'), flashes);

    createForm.statisticCreated.addObserver((response: CreateStatisticResponse) => {
        if (response.view) {
            statisticList.add(response.view, response.statistic);
        }
    })
});