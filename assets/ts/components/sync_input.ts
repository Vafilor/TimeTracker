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
        inputSelector: string,
        loadingSelector: string) {
        this.$input = $(inputSelector);
        this.$loadingContainer = $(loadingSelector);
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