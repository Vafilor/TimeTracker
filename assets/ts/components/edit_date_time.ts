import { DateTimeParts } from "../core/datetime";

export class EditDateTime {
    private readonly _$container: JQuery;
    get $container(): JQuery {
        return this._$container;
    }

    public static template(extraClass: string = ''): string {
        return `
        <div class="js-edit-date-time form-inline ${extraClass}">
            <input class="form-control js-date" type="date" />
            <input class="form-control js-time" type="time" />
        </div>`;
    }

    public static templateWithLabel(label: string, extraClass: string = ''): string {
        return `
        <div class="${extraClass}">
            <div>${label}</div>
            <div class="js-edit-date-time form-inline">
                <input class="form-control js-date" type="date" />
                <input class="form-control js-time" step="1" type="time" />
            </div>
        </div>`;
    }
    constructor($container: JQuery, timestamp?: string) {
        this._$container = $container;

        if (timestamp) {
            const parts = timestamp.split(' ');

            $container.find('.js-date').val(parts[0]);
            $container.find('.js-time').val(parts[1]);
        }
    }

    getDateTime(): DateTimeParts|undefined {
        const dateValue = this.$container.find('.js-date').val() as string;
        if (!dateValue) {
            return undefined;
        }

        const timeValue = this.$container.find('.js-time').val() as string;
        if (!timeValue) {
            return undefined;
        }

        return {
            date: this.$container.find('.js-date').val() as string,
            time: this.$container.find('.js-time').val() as string
        };
    }
}