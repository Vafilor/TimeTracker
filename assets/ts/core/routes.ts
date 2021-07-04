export interface TimeEntryIndexOptions {
    taskId?: string;
}

export default class TimeTrackerRoutes {
    private templates = new Map<string, string>();

    constructor() {
    }

    private getTemplateOrException(name: string): string {
        const result = this.templates.get(name);
        if (!result) {
            throw new Error(`'${name}' is not set in route templates`);
        }

        return result;
    }

    addTemplate(name: string, template: string): TimeTrackerRoutes {
        this.templates.set(name, template);

        return this;
    }

    /**
     * Adds a template from a string containing both name and template, separated by separator.
     * E.g. "time_entry_index;/time-entry"
     * @param combined
     * @param separator
     */
    addTemplateFromJoined(combined: string, separator = ';'): TimeTrackerRoutes {
        const parts = combined.split(separator);
        if (parts.length != 2) {
            throw new Error('path is invalid');
        }

        return this.addTemplate(parts[0], parts[1]);
    }

    timeEntryIndex(options: TimeEntryIndexOptions) {
        let url = this.getTemplateOrException('time_entry_index');
        let params = new URLSearchParams();
        if (options.taskId) {
            params.append('taskId', options.taskId);
        }

        return url + '?' + params.toString();
    }

    taskView(taskId: string) {
        let url = this.getTemplateOrException('task_view');
        url = url.replace('TASK_ID', taskId)

        return url;
    }

    timestampView(id: string): string {
        let url = this.getTemplateOrException('timestamp_view');

        url = url.replace('TIMESTAMP_ID', id);

        return url;
    }
}