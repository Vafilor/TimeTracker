import { Controller } from '@hotwired/stimulus';
import { StatisticValueApi } from "../ts/core/api/statistic_value_api";
import { useDispatch } from "stimulus-use";
import { useFlash } from "../use-flash/use-flash";
import { ApiError } from "../ts/core/api/errors";

export default class extends Controller {
    static targets = ['name', 'value', 'loading'];
    static values = {
        createUrl: String
    };

    connect() {
        useDispatch(this);
        useFlash(this);
    }

    clearInputs() {
        this.nameTarget.value = '';
        this.valueTarget.value = '';
    }

    create(event) {
        event.preventDefault();

        const name = this.nameTarget.value;
        const value = parseFloat(this.valueTarget.value);

        this.createApi(name, value);
    }

    async createApi(statisticName, value) {
        this.loadingTargets.forEach((element) => {
            element.classList.remove('d-none');
        });

        try {
            const response = await StatisticValueApi.addToResource(this.createUrlValue, {
                statisticName,
                value
            });

            this.dispatch('created', {
                statisticValue: response.data.statisticValue,
                view: response.data.view,
            });

            this.clearInputs();
        } catch (e) {
            if (e.response.status === 409) {
                let message = 'Record already exists';

                const err = ApiError.findByCode(e.response.data, 'code_name_taken');
                if (err) {
                    message = err.message;
                }

                this.flash({
                    type: 'danger',
                    message: message
                })

                this.dispatch('create-conflict', {
                    statisticName
                });
            }
            // TODO
            // emit an event, and add a border for a second
            // document on the class all of the events, and the data emitted
        }

        this.loadingTargets.forEach((element) => {
            element.classList.add('d-none');
        });
    }
}