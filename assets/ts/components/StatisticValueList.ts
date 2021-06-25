import $ from 'jquery';
import { ApiError, ApiErrorResponse, JsonResponse } from "../core/api/api";
import { ApiStatisticValue, StatisticValueApi } from "../core/api/statistic_value_api";
import IdGenerator from "./id_generator";
import { ApiStatistic } from "../core/api/statistic_api";
import Flashes from "./flashes";

export interface AddStatisticValue {
    name: string;
    value: number;
    day?: string;
}

export interface StatisticValueListDelegate {
    add(value: AddStatisticValue): Promise<JsonResponse<ApiStatisticValue>>;
    remove(id: string): Promise<JsonResponse<void>>;
}

export default class StatisticValueList {
    private static createItemHtml(value: ApiStatisticValue): string {
        return `
            <div class="statistic-value-row" data-id="${value.id}">
                <div class="content">
                    <span class="statistic-name">${value.name}</span>
                    <span class="statistic-value ml-3">${value.value}</span>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-left-0 js-delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
    }

    private static fakeElement(data: AddStatisticValue): ApiStatisticValue {
        return {
            id: IdGenerator.next(),
            name: data.name,
            value: data.value
        };
    }

    constructor(
        private $container: JQuery,
        private delegate: StatisticValueListDelegate,
        private flashes: Flashes
    ) {
        $container.on(
            'click',
            '.js-delete',
            (event) => {
                const $row = $(event.currentTarget).parent('.statistic-value-row');
                this.deleteStatisticValue($row);
            });
    }

    private disableStatisticValue($container: JQuery) {
        $container.addClass('disabled');
        $container.find('button').attr('disabled', 'true');
    }

    private enableStatisticValue($container: JQuery) {
        $container.removeClass('disabled');
        $container.find('button').removeAttr('disabled');
    }

    // insertNewElement decides where to place the new element, potentially based on filter/order criteria
    private insertNewElement($element: JQuery, data: ApiStatisticValue) {
        this.$container.append($element);
    }

    private deleteStatisticValue($element: JQuery) {
        const id = $element.data('id');
        this.disableStatisticValue($element);

        this.delegate.remove(id)
            .then(res => {
                $element.remove();
            }, () =>{
                this.enableStatisticValue($element);
            })
        ;
    }

    private getElementByName(name: string): JQuery {
        return this.$container.find(`[data-name="${name}"]`);
    }

    private highlightExisting(name: string) {
        const $element = this.getElementByName(name);
        $element.addClass('border-danger');

        setTimeout(() => {
            $element.removeClass('border-danger');
        }, 1000);
    }

    add(value: AddStatisticValue) {
        const $existingElement = this.getElementByName(value.name);
        if ($existingElement.length !== 0) {
            this.highlightExisting(value.name);
            return;
        }

        const fake = StatisticValueList.fakeElement(value);
        const $html = $(StatisticValueList.createItemHtml(fake));

        this.disableStatisticValue($html);

        this.insertNewElement($html, fake);

        this.delegate.add(value)
            .then(res => {
                this.addSuccess($html, res.data);
            }, (err: ApiErrorResponse) => {
                this.addFailure($html);

                if (err.response.status === 409) {
                    this.flashes.append('danger', `Unable to add record, a record with name '${value.name}' already exists`);

                    this.highlightExisting(value.name);
                } else {
                    this.flashes.append('danger', 'Unable to add record');
                }
            })
        ;
    }

    private addSuccess($element: JQuery, value: ApiStatisticValue) {
        $element.data('id', value.id);
        $element.data('name', value.name);

        this.enableStatisticValue($element);
    }

    private addFailure($element: JQuery) {
        $element.remove();
    }
}