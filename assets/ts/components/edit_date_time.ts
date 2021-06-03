import { DateTimeParts } from "../core/datetime";

export class EditDateTime {
    private readonly when?: Date;
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

    public static dateToParts(when: Date): DateTimeParts {
        let month = `${when.getMonth() + 1}`;
        if (when.getMonth() < 9) {
            month = '0' + month;
        }

        let day = `${when.getDate()}`;
        if (when.getDate() < 10) {
            day = '0' + day;
        }

        const whenDate = `${when.getFullYear()}-${month}-${day}`;
        const whenTime = `${when.getHours()}:${when.getMinutes()}:${when.getSeconds()}`;

        return {
            date: whenDate,
            time: whenTime,
        }
    }

    constructor($container: JQuery) {
        this._$container = $container;

        const timestamp = $container.data('timestamp');
        if (timestamp) {
            this.when = new Date(timestamp);
            const parts = EditDateTime.dateToParts(this.when);

            $container.find('.js-date').val(parts.date);
            $container.find('.js-time').val(parts.time);
        }
    }

    getDate(): Date|undefined {
        const date = this.$container.find('.js-date').val();
        const time = this.$container.find('.js-time').val();

        return new Date(`${date} ${time}`);
    }

    dispose() {
        this._$container.remove();
    }
}