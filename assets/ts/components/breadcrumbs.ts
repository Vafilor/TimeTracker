import { LoadingIndicator } from "./loading_indicator";

export class Breadcrumbs {
    private _loader: LoadingIndicator;
    public set loader(value: boolean) {
        this._loader.loading = value;
    }

    constructor(public readonly $container) {
        this._loader = new LoadingIndicator($container.find('.js-loading'));
    }

    setHtml(content: string) {
        this.$container.html(content);
    }
}