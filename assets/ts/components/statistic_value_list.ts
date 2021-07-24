import $ from 'jquery';
import { ApiErrorResponse, JsonResponse } from "../core/api/api";
import { ApiStatisticValue } from "../core/api/statistic_value_api";
import IdGenerator from "./id_generator";
import Flashes from "./flashes";
import Observable from "./observable";
import { Tooltip } from 'bootstrap';

export interface AddStatisticValue {
    name: string;
    value: number;
    day?: string;
    icon?: string;
}

export interface StatisticValueListDelegate {
    add(value: AddStatisticValue): Promise<JsonResponse<ApiStatisticValue>>;
    update(id: string, value: number): Promise<JsonResponse<ApiStatisticValue>>;
    remove(id: string): Promise<JsonResponse<void>>;
}

export interface StatisticValueItemChangeEvent {
    source: StatisticValueItem;
    data: StatisticValue;
    newValue: number;
}

export interface StatisticValueItemDeleteEvent {
    source: StatisticValueItem;
    data: StatisticValue;
}

export interface StatisticValue {
    id: string;
    value: number;
    name: string;
    color: string;
    unit: string;
    icon?: string;
}

export class StatisticValueItem {
    public static createDisplay(value: StatisticValue): string {
        if (value.icon) {
            return `<i class="${value.icon}"></i>`;
        }

        return value.name;
    }

    public static createItemHtml(value: StatisticValue): string {
        const display = StatisticValueItem.createDisplay(value);

        return `
            <div
                class="statistic-value-row js-statistic-item input-group"
                data-id="${value.id}"
                data-name="${value.name}"
                data-value="${value.value}"
                data-icon="${value.icon}"
                data-unit="${value.unit}">
                <div class="input-group-prepend">
                    <span 
                        class="input-group-text bg-white js-name-icon"
                        style="color: ${value.color}"
                        data-toggle="tooltip" 
                        data-placement="top" 
                        title="${value.name} (${value.unit})">
                        ${display}
                    </span>
                </div>
                <input type="number" class="form-control input-value js-input-value" placeholder="value" value="${value.value}">
                <div class="input-group-append">
                    <div class="upload-status js-upload-status">
                        <div class="spinner-border spinner-border-sm text-primary d-none js-loading" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <div class="js-uploaded">
                            <i class="fas fa-cloud"></i>
                        </div>
                    </div>
                    <button class="btn btn-outline-danger bg-weak-white js-delete" type="button">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>  
        `;
    }

    private timeout: any;
    private $valueInput: JQuery;
    private $deleteButton: JQuery;
    private $uploadStatus: JQuery;

    /**
     * How long we wait until we consider the input to be "ready" to send to an API.
     */
    private _debounceTime = 500;
    public set debounceTime(value: number) {
        this._debounceTime = value;
    }
    public get debounceTime(): number {
        return this._debounceTime;
    }

    private _data: StatisticValue;
    get data(): StatisticValue {
        return this._data;
    }
    set data(value: StatisticValue) {
        this._data = value;

        this.$container.data('id', value.id);
        this.$container.data('name', value.name);
        this.$container.data('value', value.value);
        this.$container.data('icon', value.icon);
        this.$valueInput.val(value.value);

        const display = StatisticValueItem.createDisplay(value);
        const $nameIcon = this.$container.find('.js-name-icon');
        $nameIcon.css('color', value.color);
        $nameIcon.html(display);
        $nameIcon.attr('title', value.name);
        const nameIconToolTip = new Tooltip($nameIcon[0]);
    }

    public delete = new Observable<StatisticValueItemDeleteEvent>();
    public valueChanged = new Observable<StatisticValueItemChangeEvent>()

    constructor(public readonly $container, data: StatisticValue) {
        this._data = data;
        this.$uploadStatus = $container.find('.js-upload-status');
        this.$deleteButton = $container.find('.js-delete');

        this.$valueInput = $container.find('.js-input-value');
        this.$valueInput.on('input', (event) => this.onInput(event));
        $container.find('.js-delete').on('click', (event) => this.onDelete(event));
    }

    protected onInput(event: any) {
        const value = $(event.currentTarget).val() as number;

        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => {
            this.valueChanged.emit({
                source: this,
                data: this.data,
                newValue: value,
            });
        }, this.debounceTime);
    }

    protected onDelete(event: any) {
        this.delete.emit({
            source: this,
            data: this.data,
        });
    }

    highlight() {
        this.$container.addClass('highlighted');

        setTimeout(() => {
            this.$container.removeClass('highlighted');
        }, 1000);
    }

    disable() {
        this.$container.addClass('disabled');
        this.$valueInput.attr('disabled', 'true');
        this.$deleteButton.attr('disabled', 'true');
    }

    enable() {
        this.$valueInput.removeAttr('disabled');
        this.$deleteButton.removeAttr('disabled');
        this.$container.removeClass('disabled');
    }

    markLoading() {
        this.$uploadStatus.find('.js-uploaded').addClass('d-none');
        this.$uploadStatus.find('.js-loading').removeClass('d-none');
    }

    markLoaded() {
        this.$uploadStatus.find('.js-uploaded').removeClass('d-none');
        this.$uploadStatus.find('.js-loading').addClass('d-none');
    }

    updateValue(value: number) {
        this.data.value = value;

        this.$container.data('value', value);
        this.$valueInput.val(value);
    }
}

export default class StatisticValueList {
    private static fakeElement(data: AddStatisticValue): StatisticValue {
        return {
            id: IdGenerator.next(),
            value: data.value,
            name: data.name,
            color: '#000000',
            icon: data.icon,
            unit: '',
        };
    }

    private items = new Array<StatisticValueItem>();

    constructor(
        private $container: JQuery,
        private delegate: StatisticValueListDelegate,
        private flashes: Flashes
    ) {
        $container.find('.statistic-value-row').each((index: number, element) => {
            const $element = $(element);

            const data: StatisticValue = {
                id: $element.data('id'),
                name: $element.data('name'),
                color: $element.data('color'),
                value: $element.data('value'),
                icon: $element.data('icon'),
                unit: $element.data('unit')
            };

            const newItem = this.statisticItemFromData($element, data);

            this.items.push(newItem);
        })
    }

    private statisticItemFromData($element: JQuery, data: StatisticValue): StatisticValueItem {
        const newItem = new StatisticValueItem($element, data);

        newItem.valueChanged.addObserver((event: StatisticValueItemChangeEvent) => {
            this.updateValue(event.source, event.newValue);
        })

        newItem.delete.addObserver((event: StatisticValueItemDeleteEvent) => {
            this.delete(event.source);
        })

        return newItem;
    }

    // insertNewElement decides where to place the new element, potentially based on filter/order criteria
    private insertNewElement(item: StatisticValueItem) {
        this.$container.append(item.$container);
        this.items.push(item);
    }

    private updateValue(item: StatisticValueItem, newValue: number) {
        item.disable();
        item.markLoading();

        this.delegate.update(item.data.id, newValue)
            .then(res => {
                item.updateValue(newValue);
                item.enable();
                item.markLoaded();
            }, (err: ApiErrorResponse) => {
                item.enable();
                item.markLoaded();

                this.flashes.append('danger', 'Unable to update value');
            })
    }

    private delete(item: StatisticValueItem) {
        item.disable();
        item.markLoading();

        this.delegate.remove(item.data.id)
            .then(res => {
                this.removeItem(item);
            }, () =>{
                item.enable();
                item.markLoaded();
            })
        ;
    }

    private getItemByName(name: string): StatisticValueItem|undefined {
        return this.items.find((value => value.data.name === name));
    }

    async add(value: AddStatisticValue, skipExistingCheck = false): Promise<void|JsonResponse<ApiStatisticValue>> {
        if (!skipExistingCheck) {
            const existingElement = this.getItemByName(value.name);
            if (existingElement) {
                existingElement.highlight();
                return Promise.resolve();
            }
        }

        const fake = StatisticValueList.fakeElement(value);
        const $html = $(StatisticValueItem.createItemHtml(fake));

        const newItem = this.statisticItemFromData($html, fake)
        newItem.disable();
        newItem.markLoading();

        this.insertNewElement(newItem);

        try {
            const res = await this.delegate.add(value);
            this.addSuccess(newItem, res.data);
        } catch (e) {
            this.addFailure(newItem);

            throw e;
        }
    }

    private addSuccess(item: StatisticValueItem, value: ApiStatisticValue) {
        item.data = {
            id: value.id,
            name: value.statistic.name,
            value: value.value,
            color: value.statistic.color,
            icon: value.statistic.icon,
            unit: value.statistic.unit
        };

        item.enable();
        item.markLoaded();
    }

    private addFailure(item: StatisticValueItem) {
        this.removeItem(item);
    }

    private removeItem(item: StatisticValueItem) {
        const index = this.items.indexOf(item);
        this.items.splice(index, 1);
        item.$container.remove();
    }
}