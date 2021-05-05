import $ from "jquery";

export default abstract class AutoMarkdown {
    private $input: JQuery;
    private $markdown: JQuery;
    private $loadingContainer: JQuery;

    constructor(
        inputSelector: string,
        markdownSelector: string,
        loadingSelector: string) {
        this.$input = $(inputSelector);
        this.$markdown = $(markdownSelector);
        this.$loadingContainer = $(loadingSelector);

        import('showdown').then(res => {
            this.setMarkdownConverter(new res.Converter())
        });
    }

    protected abstract update(body: string): Promise<any>;

    private setMarkdownConverter(markdownConverter: any) {
        // Initial rendering
        const text = this.$input.val();
        const html = markdownConverter.makeHtml(text);
        this.$markdown.html(html);

        let textAreaTimeout;
        this.$input.on('input propertychange', () => {
            clearTimeout(textAreaTimeout);
            this.$loadingContainer.find('.js-loading').removeClass('d-none');

            textAreaTimeout = setTimeout(() => {
                const text = this.$input.val() as string;
                const html = markdownConverter.makeHtml(text);
                this.$markdown.html(html);

                this.update(text)
                    .then(() => {
                        this.$loadingContainer.find('.js-loading').addClass('d-none');
                })
            }, 500);
        })
    }
}