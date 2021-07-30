import { dateToISOLocal } from "./time";

export class EditDateTime {
    private readonly when?: Date;
    private readonly _$container: JQuery;
    get $container(): JQuery {
        return this._$container;
    }

    public static template(extraClass: string = ''): string {
        return `
        <div class="js-edit-date-time form-inline ${extraClass}">
            <input class="form-control js-datetime" type="datetime-local" />
        </div>`;
    }

    public static templateWithLabel(label: string, extraClass: string = ''): string {
        return `
        <div class="${extraClass}">
            <div>${label}</div>
            <div class="js-edit-date-time d-inline-flex">
                <input class="form-control js-datetime" type="datetime-local" />
            </div>
        </div>`;
    }

    constructor($container: JQuery) {
        this._$container = $container;

        const timestamp = $container.data('timestamp');
        if (timestamp) {
            this.when = new Date(timestamp);
            const isoLocal = dateToISOLocal(this.when);
            $container.find('.js-datetime').val(isoLocal);
        }
    }

    getDate(): Date|undefined {
        const datetime = this.$container.find('.js-datetime').val() as string;

        return new Date(datetime);
    }

    dispose() {
        this._$container.remove();
    }
}