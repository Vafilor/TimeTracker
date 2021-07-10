import '../styles/note_index.scss';

import $ from 'jquery';
import Observable from "./components/observable";
import { ApiNote, CreateNoteOptions, NoteApi } from "./core/api/note_api";
import Flashes from "./components/flashes";
import { ApiErrorResponse } from "./core/api/api";
import { createTagsView } from "./components/tags";
import { TagFilter } from "./components/tag_filter";

class CreateNoteForm {
    private $title: JQuery;
    private $content: JQuery;
    private $submitButton: JQuery;

    public formSubmitted = new Observable<CreateNoteOptions>();

    constructor(private $container: JQuery) {
        this.$submitButton = $container.find('button[type=submit]');
        this.$title = $('#note-create-title');
        this.$content = $('#note-create-content');

        this.$submitButton.on('click', (event) => this.onFormSubmitted(event));
    }

    onFormSubmitted(event) {
        event.preventDefault();

        this.formSubmitted.emit(this.getData());
    }

    getData(): CreateNoteOptions {
        const title = this.$title.val() as string;
        const content = this.$content.val() as string;

        return {
            title,
            content
        };
    }

    clear() {
        this.$title.val('');
        this.$content.val('');
    }

    reset() {
        this.clear();
        this.$title.trigger('focus');
    }
}

class NoteListFilter {
    private $element: JQuery;
    private flashes: Flashes;
    private tagFilter: TagFilter;

    constructor($element: JQuery, flashes: Flashes) {
        this.$element = $element;
        this.flashes = flashes;
        this.tagFilter = new TagFilter($element);
    }
}

class NoteList {
    public onAddSuccess = new Observable<ApiNote>();

    static createItemHtml(note: ApiNote): string {
        const url = note.url ? note.url : '#';
        const tagAdjustmentClass = note.tags.length !== 0 ? 'mt-1' : '';
        const tagsHtml = createTagsView(note.tags);

        return `
        <div
            class="stack-list-item js-note"
            data-id="${note.id}"
        >
            <div class="d-flex justify-content-between">
                <div>
                    ${note.title}
                </div>
                <div>
                    <a href="${url}" class="btn btn-primary js-view ml-2">View</a>
                </div>
            </div>
            <div class="tag-list js-tag-list many-rows ${tagAdjustmentClass}">
                <div class="js-tag-list-view">
                    ${tagsHtml}
                </div>
            </div>
        </div>`;
    }

    constructor(private $container: JQuery, private flashes: Flashes) {
    }

    // fakeElement takes data that creates a Statistic and returns a 'fake'
    // version so we can work with it in the list
    private static fakeElement(data: CreateNoteOptions): ApiNote {
        return {
            title: data.title,
            content: data.content,
            createAtEpoch: 0,
            createdAt: '',
            id: '',
            tags: [],
        };
    }

    // insertNewElement decides where to place the new element, potentially based on filter/order criteria
    private insertNewElement($element: JQuery, data: ApiNote) {
        this.$container.prepend($element);
    }

    // take the create object and add pending
    public add(data: CreateNoteOptions) {
        const fake = NoteList.fakeElement(data);
        const $html = $(NoteList.createItemHtml(fake));

        $html.addClass('disabled');

        this.insertNewElement($html, fake);

        NoteApi.create(data).then(res => {
            this.addSuccess($html, res.data);
        }).catch( (err: ApiErrorResponse) => {
            this.addFailure($html);
            this.flashes.append('danger', 'Unable to add note');
        })
    }

    private addSuccess($element: JQuery, data: ApiNote) {
        $element.removeClass('disabled');

        $element.find('.js-created-at').text(data.createdAt);
        if (data.url) {
            $element.find('.js-title').attr('href', data.url);
            $element.find('.js-view').attr('href', data.url);
        }
        $element.data('title', data.title);
        $element.data('createdAt', data.createAtEpoch);

        this.onAddSuccess.emit(data);
    }

    private addFailure($element: JQuery) {
        $element.remove();
    }
}

$(document).ready(() => {
    const flashes = new Flashes($('#fixed-flash-messages'));
    const noteList = new NoteList($('.js-note-list'), flashes);
    const createForm = new CreateNoteForm($('form.js-create-note'));
    const filter = new NoteListFilter($('.filter'), flashes);

    noteList.onAddSuccess.addObserver(() => {
        createForm.reset();
    })

    // TODO Add pending and then add fully for the list - for now, sort by name.
    createForm.formSubmitted.addObserver((data: CreateNoteOptions) => {
        noteList.add(data);
    })
});