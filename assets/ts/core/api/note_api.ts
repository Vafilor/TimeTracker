import { AxiosResponse } from "axios";
import { ApiNote, ApiTag } from "./types";

const axios = require('axios').default;

export interface CreateNoteOptions {
    title: string;
    content: string;
}

export interface UpdateNoteOptions {
    content?: string;
}

export class NoteApi {
    public static create(options: CreateNoteOptions): Promise<AxiosResponse<ApiNote>> {
        const url = `/json/note`;

        return axios.post(url, options);
    }

    public static update(noteId: string, update: UpdateNoteOptions): Promise<AxiosResponse<ApiNote>> {
        return axios.put(`/json/note/${noteId}`, update);
    }

    public static addTag(noteId: string, tagName: string): Promise<AxiosResponse<ApiTag>> {
        return axios.post(`/json/note/${noteId}/tag`, {
            name: tagName
        });
    }

    public static getTags(noteId: string): Promise<AxiosResponse<ApiTag[]>> {
        return axios.get(`/json/note/${noteId}/tags`);
    }

    public static removeTag(noteId: string, tagName: string): Promise<AxiosResponse> {
        tagName = encodeURIComponent(tagName);

        return axios.delete(`/json/note/${noteId}/tag/${tagName}`);
    }
}