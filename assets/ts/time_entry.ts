import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap'; // Adds functions to jQuery
import '../styles/time_entry.scss';

import { TimeEntryApi } from "./core/api/time_entry_api";
import { TagApi } from "./core/api/tag_api";
import Flashes from "./components/flashes";
import TimerView from "./components/timer";

function createTagView(tagName: string, tagColor: string, extraClasses: string = ''): string {
    return `<div class="tag mr-2 ${extraClasses}" style="background-color: ${tagColor}" data-name="${tagName}">${tagName} <span class="time-entry-remove js-time-entry-remove"><i class="fas fa-times"></i></span></div>`;
}

class TimeEntryPage {
    private currentTags = new Array<string>();
    private readonly timeEntryId = '';

    constructor() {
        this.timeEntryId = $('.js-time-entry-tag').data('time-entry-id');

        $('.time-entry-tag').each((index, element) => {
            this.currentTags.push($(element).text());
        });
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
                const text = $descriptionTextArea.val() as string;
                const html = markDownConverter.makeHtml(text);
                $preview.html(html);

                TimeEntryApi.update(this.timeEntryId, {
                    description: text,
                })
            }, 500);
        })
    }
}



class AutocompleteTags {
    private $tagsContainer: JQuery;
    private pendingTags = new Map<string, boolean>();
    private currentTags = new Array<string>();

    constructor(private timeEntryId: string, private flashes: Flashes) {
        this.$tagsContainer = $('.js-time-entry-tags-container');

        this.$tagsContainer.find('.js-tag').each((index, element) => {
            const tagName = $(element).data('name');
            this.currentTags.push(tagName);
        })

        $('.js-time-entry-tag').find('.js-time-entry-tag-button').on('click', () => {
            const tagName = $('.js-time-entry-tag-input').val() as string;
            this.addTag(tagName);
        });

        this.$tagsContainer.on(
            'click',
            '.js-time-entry-remove',
            (event) => {
                const $target = $(event.currentTarget);
                const $parent = $target.parent();
                const tagName = $parent.data('name');

                this.removeTag(tagName);
            });

        this.setupAutoComplete();
    }

    private setupAutoComplete() {
        const $tagInput = $(".js-time-entry-tag-input") as any;
        $tagInput.on('keypress', (event) => {
            if (event.key === 'Enter') {
                const tagName = $('.js-time-entry-tag-input').val() as string;
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

    private addTag(tagName: string, tagColor: string = '#5d5d5d') {
        this.pendingTags.set(tagName, true);

        const newTagElement = createTagView(tagName, tagColor, 'pending');
        const $newTag = $(newTagElement);
        this.$tagsContainer.append($newTag);

        TimeEntryApi.addTag(this.timeEntryId, tagName)
            .then((newTag) => {
                this.pendingTags.delete(tagName);
                this.currentTags.push(tagName);
                $newTag.removeClass('pending');
                $newTag.css('background-color', newTag.data.color);

                $(".js-time-entry-tag-input").val('');
            }).catch(() => {
                $newTag.remove();
                this.pendingTags.delete(tagName);

                this.flashes.append('danger', `Unable to add tag '${tagName}'`);
            });
    }

    private removeTag(tagName: string) {
        // Don't remove a tag that is pending - being added or removed.
        if (this.pendingTags.has(tagName)) {
            return;
        }

        //if pending do nothing
        this.pendingTags.set(tagName, true);
        const index = this.currentTags.indexOf(tagName);
        if(index < 0) {
            this.flashes.append('danger', `Unable to find tag '${tagName}'`);
            return;
        }

        this.currentTags.splice(index, 1);

        const $element = this.$tagsContainer.find(`[data-name=${tagName}]`);
        $element.addClass('pending');

        TimeEntryApi.deleteTag(this.timeEntryId, tagName)
            .then(() => {
                this.pendingTags.delete(tagName);
                $element.remove();
            }).catch(() => {
                this.pendingTags.delete(tagName);
                this.currentTags.push(tagName);
                $element.removeClass('pending');
                this.flashes.append('danger', `Unable to remove tag '${tagName}'`);
        });
    }
}

$(document).ready(() => {
    const flashes = new Flashes($('#flash-messages'));

    const page = new TimeEntryPage();

    import('showdown').then(res => {
        page.setMarkDownConverter(new res.Converter())
    });

    const $timeEntryTags = $('.js-time-entry-tag');
    const timeEntryId = $timeEntryTags.data('time-entry-id');
    const autoComplete = new AutocompleteTags(timeEntryId, flashes);

    const durationFormat = $('.js-data').data('duration-format');
    const timerView = new TimerView('.js-timer', durationFormat, (durationString) => {
       document.title = durationString;
    });
});