import { Controller } from "stimulus";

export default class extends Controller {
    static targets = ["taskId", "action", "value"];

    onComplete(event) {
        const { taskId, completed } = event.detail;

        this.taskIdTarget.value = taskId;
        this.actionTarget.value = "complete";
        this.valueTarget.value = completed ? "true" : "false";

        this.element.submit();
    }
}