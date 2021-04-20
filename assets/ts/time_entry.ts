import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap'; // Adds functions to jQuery

import { TimeEntryApi } from "./core/api/time_entry_api";
import '../styles/time_entry.scss';
import { TagApi } from "./core/api/tag_api";

$(document).ready(() => {
    const page = new TimeEntryPage();

    import('showdown').then(res => {
        page.setMarkDownConverter(new res.Converter())
    });

    const $timeEntryTags = $('.js-time-entry-tag');

    const timeEntryId = $timeEntryTags.data('time-entry-id');

    $timeEntryTags.find('.js-time-entry-tag-button').on('click', () => {
        const tagName = $('.js-time-entry-tag-input').val();
        page.addTag(tagName);
    });

    $('.js-time-entry-tags-container').on(
        'click',
        '.js-time-entry-remove',
        (event) => {
            const $target = $(event.currentTarget);
            const $parent = $target.parent();
            const tagName = $parent.data('name');

            TimeEntryApi.deleteTag(timeEntryId, tagName)
                .then(() => {
                    page.reloadTags();
                })
        });
});

class TimeEntryPage {
    private currentTags = new Array<string>();
    private timeEntryId = '';
    private markDownConverter: any;

    constructor() {
        this.timeEntryId = $('.js-time-entry-tag').data('time-entry-id');

        $('.time-entry-tag').each((index, element) => {
            this.currentTags.push($(element).text());
        });

        this.setupAutoComplete();
    }

    private setupAutoComplete() {
        const $tagInput = $(".js-time-entry-tag-input");
        $tagInput.on('keypress', (event) => {
            if (event.key === 'Enter') {
                const tagName = $('.js-time-entry-tag-input').val();
                this.addTag(tagName);
            }
        });

        $tagInput.autocomplete({
            source: (request, response) => {
                TagApi.listTags(request.term, this.currentTags)
                    .then(res => {
                        response(res.data);
                    })
            },
            focus: function (event, ui) {
                $tagInput.val(ui.item.name);
                return false;
            },
            select: (event, ui) => {
                $tagInput.val(ui.item.name);
                this.addTag(ui.item.name);

                return false;
            },
            delay: 300,
        }).autocomplete("instance")._renderItem = function (ul, item) {
            return $("<li>")
                .append("<div>" + item.name + "</div>")
                .appendTo(ul);
        };
    }

    setMarkDownConverter(markDownConverter: any) {
        const $preview = $('#preview-content');
        const $descriptionTextArea = $('.js-description');

        // Initial rendering
        const text = $descriptionTextArea.val();
        const html = markDownConverter.makeHtml(text);
        $preview.html(html);

        let textAreaTimeout;
        $descriptionTextArea.on('input propertychange', () => {
            clearTimeout(textAreaTimeout);
            textAreaTimeout = setTimeout(() => {
                const text = $descriptionTextArea.val();
                const html = markDownConverter.makeHtml(text);
                $preview.html(html);

                TimeEntryApi.update(this.timeEntryId, {
                    description: text,
                })
            }, 500);
        })
    }

    reloadTags() {
        TimeEntryApi.getTags(this.timeEntryId)
            .then(res => {
                const $container = $('.js-time-entry-tags-container');
                $container.empty();

                for (const tag of res.data) {
                    $container.append(`<div class="time-entry-tag mr-2" data-name="${tag.name}">${tag.name} <span class="time-entry-remove js-time-entry-remove"><i class="fas fa-times"></i></span></div>`);
                }

                this.currentTags = res.data.map(value => value.name);
            })
    }

    addTag(tagName: string) {
        TimeEntryApi.addTag(this.timeEntryId, tagName)
            .then(() => {
                this.reloadTags();
                $(".js-time-entry-tag-input").val('');
            });
    }
}

