/**
 * MarkdownView wraps a content editable html element and converts the string input to markdown.
 * It also caches a markdown converter so it isn't reloaded on every class creation.
 */
export default class MarkdownView {
    static markdownConverter?: any;
    static gettingMarkdownConverter = false;

    private readonly $_container;
    get $container(): JQuery {
        return this.$_container;
    }

    constructor($container: JQuery) {
        if (!MarkdownView.markdownConverter && !MarkdownView.gettingMarkdownConverter) {
            MarkdownView.gettingMarkdownConverter = true;
            import('showdown').then(res => {
                MarkdownView.markdownConverter = new res.Converter();
                MarkdownView.gettingMarkdownConverter = false;
            });
        }

        this.$_container = $container;
    }

    get data(): string {
        return this.$container.data('description') as string;
    }

    set data(value: string) {
        this.$container.data('description', value);

        if (MarkdownView.markdownConverter) {
            value = MarkdownView.markdownConverter.makeHtml(value);
        }

        this.$container.html(value);
    }

    hide() {
        this.$container.addClass('d-none');
    }

    show() {
        this.$container.removeClass('d-none');
    }
}