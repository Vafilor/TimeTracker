import '../styles/statistic_index.scss';

import $ from "jquery";
import { ApiStatistic, CreateStatisticOptions, StatisticApi, TimeType } from "./core/api/statistic_api";
import Observable from "./components/observable";
import Flashes from "./components/flashes";
import { ApiErrorResponse } from "./core/api/api";

class CreateStatisticForm {
    private $name: JQuery;
    private $description: JQuery;
    private $timeType: JQuery;
    private $submitButton: JQuery;

    public formSubmitted = new Observable<CreateStatisticOptions>();

    constructor(private $container: JQuery) {
        this.$submitButton = $container.find('button[type=submit]');
        this.$name = $('#statistic-create-name');
        this.$description = $('#statistic-create-description');
        this.$timeType = $('#statistic-create-time-type');

        this.$submitButton.on('click', (event) => this.onFormSubmitted(event));
    }

    onFormSubmitted(event) {
        event.preventDefault();

        this.formSubmitted.emit(this.getData());
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
    public onAddSuccess = new Observable<ApiStatistic>();

    static createItemHtml(statistic: ApiStatistic): string {
        const url = statistic.url ? statistic.url : '#';

        return `
            <tr class="statistic-row">
                <td><a href="${url}" class="js-name">${statistic.name}</a></td>
                <td class="js-created-at">${statistic.createdAt}</td>
                <td>
                    <a href="${url}" class="btn btn-primary js-view">View</a>
                </td>
            </tr>`;
    }

    constructor(private $container: JQuery, private flashes: Flashes) {
    }

    // fakeElement takes data that creates a Statistic and returns a 'fake'
    // version so we can work with it in the list
    private static fakeElement(data: CreateStatisticOptions): ApiStatistic {
        return {
            name: data.name,
            canonicalName: data.name,
            createdAt: '',
            createAtEpoch: 0,
            color: '#000000',
            unit: ''
        };
    }

    // insertNewElement decides where to place the new element, potentially based on filter/order criteria
    private insertNewElement($element: JQuery, data: ApiStatistic) {
        this.$container.prepend($element);
    }

    // take the create object and add pending
    public add(data: CreateStatisticOptions) {
        const fake = StatisticList.fakeElement(data);
        const $html = $(StatisticList.createItemHtml(fake));

        $html.addClass('disabled');

        this.insertNewElement($html, fake);

        StatisticApi.create(data).then(res => {
            this.addSuccess($html, res.data);
        }).catch( (err: ApiErrorResponse) => {
            this.addFailure($html);

            if (err.response.status === 409) {
                this.flashes.append('danger', `Unable to add statistic, a statistic with name '${data.name}' already exists`);
            } else {
                this.flashes.append('danger', 'Unable to add statistic');
            }
        })
    }

    private addSuccess($element: JQuery, data: ApiStatistic) {
        $element.removeClass('disabled');
        $element.removeClass('bg-primary');

        $element.find('.js-created-at').text(data.createdAt);
        if (data.url) {
            $element.find('.js-name').attr('href', data.url);
            $element.find('.js-view').attr('href', data.url);
        }
        $element.data('name', data.name);
        $element.data('createdAt', data.createAtEpoch);

        this.onAddSuccess.emit(data);
    }

    private addFailure($element: JQuery) {
        $element.remove();
    }
}

$(document).ready(() => {
    const flashes = new Flashes($('#fixed-flash-messages'));
    const statisticList = new StatisticList($('.js-statistic-list'), flashes);
    const createForm = new CreateStatisticForm($('form.js-create-statistic'));

    statisticList.onAddSuccess.addObserver(() => {
        createForm.reset();
    })


    // TODO Add pending and then add fully for the list - for now, sort by name.
    createForm.formSubmitted.addObserver((data: CreateStatisticOptions) => {
        statisticList.add(data);
    })
});