import $ from 'jquery';
import { ApiStatisticValue } from "../core/api/statistic_api";
import { JsonResponse } from "../core/api/api";

export interface AddStatisticValue {
    name: string;
    value: number;
}

export interface StatisticValueListDelegate {
    add(value: AddStatisticValue): Promise<JsonResponse<ApiStatisticValue>>;
    remove(id: string): Promise<JsonResponse<void>>;
}

export default class StatisticValueList {
    private pendingItems = new Map<string, JQuery>();

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

    constructor(
        private $container: JQuery,
        private delegate: StatisticValueListDelegate) {

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

    private addPending(value: AddStatisticValue): string {
        const id = Math.floor(Math.random() * 100000).toString();
        const fakeItem: ApiStatisticValue = {
            id,
            name: value.name,
            value: value.value
        };


        const $html = $(StatisticValueList.createItemHtml(fakeItem));
        this.disableStatisticValue($html);

        this.pendingItems.set(id, $html);

        this.$container.prepend($html);

        return id;
    }

    addRequest(value: AddStatisticValue) {
        const id = this.addPending(value);

        this.delegate.add(value)
            .then(res => {
                this.add(res.data, id);
            }, (err) => {
                this.removePending(id);
            })
        ;
    }

    private add(value: ApiStatisticValue, id: string) {
        const $html = this.pendingItems.get(id);
        if (!$html) {
            return;
        }

        $html.data('id', value.id);

        this.enableStatisticValue($html);
        this.pendingItems.delete(id);
    }

    private removePending(id: string) {
        const $html = this.pendingItems.get(id);
        if (!$html) {
            return;
        }

        $html.remove();

        this.pendingItems.delete(id);
    }
}