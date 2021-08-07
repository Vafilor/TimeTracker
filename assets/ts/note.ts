import '../styles/note.scss';

import $ from "jquery";
import TagList, { TagListDelegate } from "./components/tag_index";
import Flashes from "./components/flashes";
import { TagAssigner } from "./components/tag_assigner";
import { NoteApi } from "./core/api/note_api";
import { ApiTag } from "./core/api/types";

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

    removeTag(tagName: string): Promise<any> {
        return NoteApi.removeTag(this.noteId, tagName)
            .catch(res => {
                this.flashes.append('danger', `Unable to add remove tag '${tagName}'`)
                throw res;
            });
    }
}

$(document).ready(() => {
    const $data = $('.js-data');
    const noteId = $data.data('note-id');
    const flashes = new Flashes($('#flash-messages'));

    const $tagList = $('.js-tags');
    const tagList = new TagList($tagList, new NoteApiAdapter(noteId, flashes));
    const $template = $('.js-autocomplete-tags-container');

    const tagEdit = new TagAssigner($template, tagList, flashes);
});
