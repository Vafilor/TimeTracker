import { CoreApi } from './api';
import { ApiTag } from "./tag_api";
import { ApiUpdateTask } from "./task_api";

export interface ApiNote {
    id: string;
    title: string;
    content: string;
    createdAt: string;
    createAtEpoch: number;
    tags: ApiTag[];
    url?: string;
}

export interface CreateNoteOptions {
    title: string;
    content: string;
}

export interface UpdateNoteOptions {
    content?: string;
}

export class NoteApi {
    public static create(options: CreateNoteOptions) {
        const url = `/json/note`;

        return CoreApi.post<ApiNote>(url, options);
    }

    public static update(noteId: string, update: UpdateNoteOptions) {
        return CoreApi.put(`/json/note/${noteId}`, update);
    }

    public static addTag(noteId: string, tagName: string) {
        return CoreApi.post<ApiTag>(`/json/note/${noteId}/tag`, {
            name: tagName
        });
    }

    public static getTags(noteId: string) {
        return CoreApi.get<ApiTag[]>(`/json/note/${noteId}/tags`);
    }

    public static removeTag(noteId: string, tagName: string) {
        tagName = encodeURIComponent(tagName);

        return CoreApi.delete(`/json/note/${noteId}/tag/${tagName}`);
    }
}