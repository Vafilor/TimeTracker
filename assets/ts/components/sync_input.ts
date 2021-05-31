import $ from "jquery";
import Observable from "./observable";

export abstract class SyncInput {
    private $input: JQuery;
    private $loadingContainer: JQuery;
    private debounceTime = 500;
    private timeout: any;

    /**
     * textChanged is triggered whenever the text has been changed in the input, after the debounce time.
     */
    public readonly textChanged = new Observable<string>();

    /**
     * textUplaoded is triggered whenever the text change has been successfully uploaded the the server.
     */
    public readonly textUploaded = new Observable<string>();

    constructor(
        $inputElement: JQuery,
        $loadingElement: JQuery) {
        this.$input = $inputElement;
        this.$loadingContainer = $loadingElement
    }

    protected abstract update(text: string): Promise<any>;

    private startLoading() {
        this.$loadingContainer.find('.js-loading').removeClass('d-none');
    }

    private stopLoading() {
        this.$loadingContainer.find('.js-loading').addClass('d-none');
    }

    protected onTextChange(text: string): Promise<any> {
        this.textChanged.emit(text);

        return this.update(text)
            .then(() => {
                this.stopLoading();
                this.textUploaded.emit(text);
            });
    }

    start() {
        this.$input.on('input propertychange', () => {
            clearTimeout(this.timeout);
            this.startLoading();

            this.timeout = setTimeout(() => {
                const text = this.$input.val() as string;

                this.onTextChange(text);
            }, this.debounceTime);
        })

    }

    stop() {
        clearTimeout()
        this.$input.off('input propertychange');
    }

    setDebounceTime(value: number) {
        this.debounceTime = value;
    }

    upload() {
        this.startLoading();
        const text = this.$input.val() as string;
        return this.onTextChange(text);
    }

    uploadIfHasText(): Promise<any> {
        const descriptionText = this.$input.val() as string;
        if (descriptionText && descriptionText.length > 0) {
            return this.upload();
        }

        return new Promise<void>(function (resolve, reject) {
            resolve();
        });
    }
}

export interface SyncUploadEvent {
    content: string;
    success: boolean;
    error?: any;
}

export type SyncStatus = 'up-to-date' | 'modified' | 'updating';

export class SyncInputV2 {
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
}