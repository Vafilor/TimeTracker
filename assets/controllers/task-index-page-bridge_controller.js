import { Controller } from 'stimulus';
import { TaskList } from "../ts/components/task";
import $ from "jquery";
import Flashes from "../ts/components/flashes";

export default class extends Controller {
    #taskTable;

    connect() {
        const $data = $('.js-data');
        const showCompleted = $data.data('show-completed');

        const flashes = new Flashes($('#fixed-flash-messages'));

        this.#taskTable = new TaskList($('.js-task-list'), showCompleted, flashes);
    }
}