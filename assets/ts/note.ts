import '../styles/note.scss';

import $ from "jquery";
// import AutoMarkdown from "./components/automarkdown";
import { TaskApi } from "./core/api/task_api";
import TaskTimeEntry from "./components/task_time_entry";
import { ApiTimeEntry, TimeEntryApi } from "./core/api/time_entry_api";
import { formatShortTimeDifference, timeAgo } from "./components/time";
import { createTagView } from "./components/tags";
import TagList, { TagListDelegate } from "./components/tag_index";
import Flashes from "./components/flashes";
import { ApiTag } from "./core/api/tag_api";
import { TagAssigner } from "./components/tag_assigner";
import AutoMarkdown from "./components/automarkdown";
import { NoteApi } from "./core/api/note_api";

class NoteApiAdapter implements TagListDelegate {
    constructor(private noteId: string, private flashes: Flashes) {
    }

    addTag(tag: ApiTag): Promise<ApiTag> {
        return NoteApi.addTag(this.noteId, tag.name)
            .then(res => {
                return res.data;
            })
            .catch(res => {
                this.flashes.append('danger', `Unable to add tag '${tag.name}'`)
                throw res;
            });
    }

    removeTag(tagName: string): Promise<void> {
        return NoteApi.removeTag(this.noteId, tagName)
            .catch(res => {
                this.flashes.append('danger', `Unable to add remove tag '${tagName}'`)
                throw res;
            });
    }
}

class NoteAutoMarkdown extends AutoMarkdown {
    private readonly noteId: string;

    constructor(
        inputSelector: string,
        markdownSelector: string,
        loadingSelector: string,
        noteId: string) {
        super(inputSelector, markdownSelector, loadingSelector);
        this.noteId = noteId;
    }

    protected update(body: string): Promise<any> {
        return NoteApi.update(this.noteId, {
            content: body,
        });
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const noteId = $data.data('note-id');
    const flashes = new Flashes($('#flash-messages'));

    const autoMarkdown = new NoteAutoMarkdown(
        '.js-description',
        '#preview-content',
        '.markdown-link',
        noteId
    );

    const $tagList = $('.js-tags');
    const tagList = new TagList($tagList, new NoteApiAdapter(noteId, flashes));
    const $template = $('.js-autocomplete-tags-container');

    const tagEdit = new TagAssigner($template, tagList, flashes);
});
