import { Controller } from 'stimulus';
import { useDispatch } from "stimulus-use";
import { useFlash } from "../use-flash/use-flash";
import { TagApi } from "../ts/core/api/tag_api";

export default class extends Controller {
    static values = {
        name: String,
        color: String,
        addUrl: String,
        removeUrl: String,
        state: String // 'adding' | 'added' | 'removing', defaults to 'adding'
    };

    connect() {
        useDispatch(this);
        useFlash(this);

        if (!this.hasStateValue) {
            this.stateValue = 'added';
        }

        if (this.stateValue === 'adding') {
            this.add();
        }
    }

    async add() {
        this.element.classList.add('pending');

        if (this.hasAddUrlValue) {
            try {
                await TagApi.addTagToResource(this.addUrlValue, this.nameValue);
            } catch (e) {
                this.flash({
                    type: 'danger',
                    message: 'Unable to add tag'
                });

                this.element.classList.remove('pending');
                return;
            }
        }

        this.element.classList.remove('pending');
        this.dispatch('add', {
            name: this.nameValue,
            color: this.colorValue
        });
    }

    async remove() {
        this.element.classList.add('pending');

        if (this.hasRemoveUrlValue) {
            try {
                await TagApi.removeTagFromResource(this.removeUrlValue, this.nameValue);
            } catch (e) {
                console.log(e);
                this.flash({
                    type: 'danger',
                    message: 'Unable to remove tag'
                });

                this.element.classList.remove('pending');
                return;
            }
        }

        this.dispatch('remove', {
            name: this.nameValue,
        });

        this.element.remove();
    }
}