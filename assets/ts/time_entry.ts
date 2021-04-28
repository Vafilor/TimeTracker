import $ from 'jquery';
import 'jquery-ui/ui/widgets/autocomplete';
import 'bootstrap'; // Adds functions to jQuery
import '../styles/time_entry.scss';

import { TimeEntryApi } from "./core/api/time_entry_api";
import { ApiTag } from "./core/api/tag_api";
import Flashes from "./components/flashes";
import TimerView from "./components/timer";
import TagList, { TagListDelegate } from "./components/tag_list";
import AutocompleteTags from "./components/autocomplete_tags";

class TimeEntryApiAdapter implements TagListDelegate {
    constructor(private timeEntryId: string, private flashes: Flashes) {
    }

    addTag(tag: ApiTag): Promise<ApiTag> {
        return TimeEntryApi.addTag(this.timeEntryId, tag.name)
            .then(res => {
                return res.data;
            })
            .catch(res => {
                this.flashes.append('danger', `Unable to add tag '${tag.name}'`)
                return res;
            });
    }

    removeTag(tagName: string): Promise<void> {
        return TimeEntryApi.removeTag(this.timeEntryId, tagName)
            .catch(res => {
                this.flashes.append('danger', `Unable to add remove tag '${tagName}'`)
                throw res;
            });
    }
}

class TimeEntryPage {
    private $loadingContainer: JQuery;

    constructor(private timeEntryId: string) {
        this.$loadingContainer = $('.markdown-link');
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
            this.$loadingContainer.find('.js-loading').removeClass('d-none');

            textAreaTimeout = setTimeout(() => {
                const text = $descriptionTextArea.val() as string;
                const html = markDownConverter.makeHtml(text);
                $preview.html(html);


                TimeEntryApi.update(this.timeEntryId, {
                    description: text,
                }).then(() => {
                    this.$loadingContainer.find('.js-loading').addClass('d-none');
                })
            }, 500);
        })
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const timeEntryId = $data.data('time-entry-id');
    const durationFormat = $data.data('duration-format');

    const flashes = new Flashes($('#flash-messages'));

    const page = new TimeEntryPage(timeEntryId);

    import('showdown').then(res => {
        page.setMarkDownConverter(new res.Converter())
    });

    const timerView = new TimerView('.js-timer', durationFormat, (durationString) => {
       document.title = durationString;
    });

    const tagList = new TagList('.js-tags', new TimeEntryApiAdapter(timeEntryId, flashes));
    const autoComplete = new AutocompleteTags('.js-autocomplete-tags');

    autoComplete.tagEmitter.addObserver((apiTag: ApiTag) => {
        tagList.add(apiTag);
    })

    tagList.tagsChanged.addObserver(() => {
        autoComplete.setTags(tagList.getTagNames());
    });
});