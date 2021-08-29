import { Controller } from 'stimulus';
import { TodayIndexPage } from "../ts/today";

export default class extends Controller {
    connect() {
        const page = new TodayIndexPage();
    }
}