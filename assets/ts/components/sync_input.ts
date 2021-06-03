import $ from "jquery";

export interface SyncUploadEvent {
    content: string;
    success: boolean;
    error?: any;
}

export type SyncStatus = 'up-to-date' | 'modified' | 'updating';

export class SyncInput {
    private readonly $input: JQuery;
    private readonly changed?: () => void;
    private readonly update: (content: string) => void;
    private debounceTime = 500;
    private timeout: any;

    constructor($inputElement: JQuery, value: (content: string) => void, changed?: () => void) {
        this.$input = $inputElement;
        this.update = value;
        this.changed = changed;
    }

    setDebounceTime(value: number) {
        this.debounceTime = value;
    }

    public get content(): string {
        return this.$input.val() as string;
    }

    start() {
        this.$input.on('input', () => {
            clearTimeout(this.timeout);

            if (this.changed) {
                this.changed();
            }

            this.timeout = setTimeout(() => {
                const text = this.$input.val() as string;

                this.update(text);
            }, this.debounceTime);
        })
    }

    stop() {
        clearTimeout()
        this.$input.off('input');
    }

    upload() {
        this.update(this.$input.val() as string);
    }
}