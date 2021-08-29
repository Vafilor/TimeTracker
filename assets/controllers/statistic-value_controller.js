import { Controller } from 'stimulus';
import { useDebounce, useDispatch } from 'stimulus-use';
import { StatisticValueApi } from "../ts/core/api/statistic_value_api";

export default class extends Controller {
    static debounces = ['updateApi'];
    static targets = ['loading', 'loaded', 'remove', 'valueInput'];
    static values = {
        id: String,
        name: String,
        value: Number,
        updateUrl: String,
        deleteUrl: String
    }

    setLoading(loading) {
        if (loading) {
            this.loadingTargets.forEach((element) => {
                element.classList.remove('d-none');
            });

            this.loadedTarget.classList.add('d-none');
        } else {
            this.loadingTargets.forEach((element) => {
                element.classList.add('d-none');
            });

            this.loadedTarget.classList.remove('d-none');
        }
    }

    connect() {
        useDispatch(this);
        useDebounce(this);
    }

    updateValue(event) {
        this.valueValue = event.currentTarget.value;
        this.updateApi(this.valueValue);
    }

    async updateApi(value) {
        this.setLoading(true);

        try {
            const res = await StatisticValueApi.update(this.updateUrlValue, this.valueValue);
        } catch (e) {
            // TODO
        }

        this.setLoading(false);
    }

    remove() {
        this.removeApi();
    }

    /**
     * Will highlight the element if the event.detail.statisticName is equal to this statistic's name
     *
     * @param event
     */
    highlightOnNameMatch(event) {
        if (event.detail.statisticName === this.nameValue) {
            this.highlight();
        }
    }

    highlight() {
        this.element.classList.add('highlight');
        setTimeout(() => {
            this.element.classList.remove('highlight');
        }, 1000);
    }

    async removeApi() {
        this.setLoading(true);
        this.removeTarget.setAttribute('disabled', true);
        this.element.setAttribute('disabled', true);
        this.valueInputTarget.setAttribute('disabled', true);

        try {
            const response = await StatisticValueApi.remove(this.deleteUrlValue);
        } catch (e) {
            // TODO
        }

        this.dispatch('remove', {
            value: this.valueValue,
            id: this.idValue,
            element: this.element
        });

        this.setLoading(false);
        this.removeTarget.removeAttribute('disabled');
        this.element.removeAttribute('disabled');
        this.valueInputTarget.removeAttribute('disabled');
    }
}