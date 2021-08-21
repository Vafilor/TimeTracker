import { Controller } from 'stimulus';
import { TimeEntryIndexPage } from "../ts/time_entry_index";

export default class extends Controller {
    connect() {
        const page = new TimeEntryIndexPage();
    }
}