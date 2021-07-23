export class LoadingIndicator {
    private _loading = false;
    public get loading(): boolean {
        return this._loading;
    }
    public set loading(value: boolean) {
        if (value === this._loading) {
            return;
        }

        if (value) {
            this.$container.removeClass('d-none');
        } else {
            this.$container.addClass('d-none');
        }
    }

    constructor(public readonly $container) {
    }
}