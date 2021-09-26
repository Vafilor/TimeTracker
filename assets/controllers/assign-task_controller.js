import { Controller } from 'stimulus';
import { TaskApi } from "../ts/core/api/task_api";
import { ApiError } from "../ts/core/api/errors";
import { useFlash } from "../use-flash/use-flash";
import { TaskApiErrorCode } from "../ts/core/api/types";

export default class extends Controller {
    static targets = ['input'];
    static values = {
        url: String,
        removeUrl: String,
    }

    connect() {
        useFlash(this);
    }

    async assign(event) {
        const { type, value, textValue } = event.detail;
        if (type !== 'task') {
            return;
        }

        const name = textValue;
        const id = value;

        try {
            const response = await TaskApi.assignToResource(this.urlValue, name, id);

            if (response.status === 201) {
                this.flash({
                    type: 'success',
                    message: `Created new task`,
                    url: response.data.url,
                    urlText: response.data.name
                });
            } else {
                this.flash({
                    type: 'success',
                    message: `Assigned to task`,
                    url: response.data.url,
                    urlText: response.data.name
                });
            }
        } catch (err) {
            this.flash({
                type: 'warning',
                message: `Unable to assign task`,
            });
        }
    }

    async remove(event) {
        try {
            await TaskApi.unassignTask(this.urlValue);

            this.flash({
                type: 'success',
                message: 'Removed task'
            });

            this.inputTarget.value = '';
        } catch (err) {
            if (err && err.response.data) {
                const noTaskError = ApiError.findByCode(err.response.data, TaskApiErrorCode.codeNoAssignedTask);
                if (noTaskError) {
                    this.flash({
                        type: 'danger',
                        message: noTaskError.message
                    });
                    this.inputTarget.value = '';
                }
            } else {
                this.flash({
                    type: 'danger',
                    message: 'Unable to remove task'
                });
            }
        }
    }
}