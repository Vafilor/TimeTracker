import $ from 'jquery';

import { ApiTag } from "../core/api/tag_api";
import { createTagViewRemovable } from "./tags";
import Observable from "./observable";

export interface TagListDelegate {
    addTag(tag: ApiTag): Promise<ApiTag>;
    removeTag(tagName: string): Promise<void>;
}

class DefaultDelegate implements TagListDelegate{
    addTag(tag: ApiTag): Promise<ApiTag> {
        return Promise.resolve(tag);
    }

    removeTag(tagName: string): Promise<void> {
        return Promise.resolve();
    }
}

export default class TagList {
    public static readonly initialDataKey = 'initial-tags-name';

    private delegate: TagListDelegate;
    private tags = new Array<ApiTag>();
    private tagMap = new Map<string, JQuery>();
    private pendingTagMap = new Map<string, boolean>();
    private $container: JQuery;
    public readonly tagsChanged = new Observable<TagList>();

    public constructor($container: JQuery, delegate?: TagListDelegate) {
        this.$container = $container;

        if (delegate) {
            this.delegate = delegate;
        } else {
            this.delegate = new DefaultDelegate();
        }

        const tagsString = this.$container.data(TagList.initialDataKey) as string;
        if (tagsString) {
            for (const tagName of tagsString.split(',')) {
                if (tagName === '') {
                    continue;
                }

                this.addImmediate({
                    name: tagName,
                    color: '#5d5d5d'
                });
            }
        }

        const initialTags = this.$container.data('initial-tags') as Array<ApiTag>;
        if (initialTags) {
            for (const tag of initialTags) {
                this.addImmediate(tag);
            }
        }

        this.$container.on(
            'click',
            '.js-tag-remove',
            (event) => {
                const $target = $(event.currentTarget);
                const $parent = $target.parent();
                const tagName = $parent.data('name');

                this.removeTag(tagName);
            });
    }

    /**
     * handleAddExistingTag is called when trying to add a tag, but that tag is already in the list.
     */
    private handleAddExistingTag(tag: ApiTag) {
        const element = this.tagMap.get(tag.name);
        if (!element) {
            return;
        }

        const originalColor = element.css('background-color');
        element.css('background-color', 'red');
        setTimeout(() => {
            element.css('background-color', originalColor)
        }, 500);
    }

    /**
     * handleAddExistingPending is called when trying to add a tag initially, and we await to see
     * if it's successfully added.
     */
    private handleAddTagPending(tag: ApiTag) {
        const $newTag = $(createTagViewRemovable(tag.name, tag.color, 'pending'));
        this.pendingTagMap.set(tag.name, true);
        this.tagMap.set(tag.name, $newTag);
        this.$container.append($newTag);
    }

    /**
     * handleAddTagSuccess is called when we successfully add a tag.
     */
    private handleAddTagSuccess(tag: ApiTag) {
        const $view = this.tagMap.get(tag.name);
        if (!$view) {
            return;
        }

        this.tags.push(tag);
        this.pendingTagMap.delete(tag.name);

        $view.removeClass('pending');
        $view.css('background-color', tag.color);
        this.tagsChanged.emit(this);
    }

    /**
     * handleAddTagFailure is called when we fail to add a tag.
     */
    private handleAddTagFailure(tag: ApiTag) {
        const $element = this.tagMap.get(tag.name);
        if (!$element) {
            return;
        }

        $element.remove();
        this.pendingTagMap.delete(tag.name);
        this.tagMap.delete(tag.name);
    }

    private addImmediate(tag: ApiTag) {
        const $newTag = $(createTagViewRemovable(tag.name, tag.color));
        this.tags.push(tag);
        this.tagMap.set(tag.name, $newTag);
        this.$container.append($newTag);
    }

    /**
     * add is called when we try to add a tag. It goes through a lifecycle,
     * 1. Check if tag exists, if it does, handleAddExistingTag and return. Otherwise, continue
     * 2. handleAddTagPending
     * 3. call the delegate's addTag method, and either handleAddTagSuccess or handleAddTagFailure
     */
    public add(tag: ApiTag) {
        const exists = this.tagMap.has(tag.name);
        if (exists) {
            this.handleAddExistingTag(tag);
            return;
        }

        this.handleAddTagPending(tag);

        this.delegate.addTag(tag)
            .then((res) => {
                this.handleAddTagSuccess(res);
            })
            .catch((res) => {
                this.handleAddTagFailure(tag);
            })
        ;
    }

    private handleRemoveTagPending(tagName: string) {
        const $tagView = this.tagMap.get(tagName);
        if (!$tagView) {
            return;
        }

        this.pendingTagMap.set(tagName, true);

        $tagView.addClass('pending');
    }

    private handleRemoveTagSuccess(tagName: string) {
        const $element = this.tagMap.get(tagName);
        if (!$element) {
            return;
        }

        $element.remove();
        const tagIndex = this.tags.findIndex(value => value.name === tagName);
        this.tags.splice(tagIndex, 1);
        this.tagMap.delete(tagName);
        this.pendingTagMap.delete(tagName);
        this.tagsChanged.emit(this);
    }

    private handleRemoveTagFailure(tagName: string) {
        this.pendingTagMap.delete(tagName);

        const $element = this.tagMap.get(tagName);
        if ($element) {
            $element.removeClass('pending');
        }

    }

    public removeTag(tagName: string) {
        const exists = this.tagMap.has(tagName);
        if (!exists) {
            return;
        }

        this.handleRemoveTagPending(tagName)

        this.delegate.removeTag(tagName)
            .then(() => {
                this.handleRemoveTagSuccess(tagName);
            })
            .catch(() => {
                this.handleRemoveTagFailure(tagName);
            })
        ;
    }

    public getTagNames(): Array<string> {
        return this.tags.map<string>((value) => value.name);
    }

    public getTagNamesCommaSeparated(): string {
        return this.getTagNames().join(',');
    }

    public getTags(): ApiTag[] {
        return this.tags;
    }
}
