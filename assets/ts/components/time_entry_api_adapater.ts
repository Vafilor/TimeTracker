import Flashes from "./flashes";
import { TagListDelegate } from "./tag_index";
import { TimeEntryApi } from "../core/api/time_entry_api";
import { ApiErrorResponse } from "../core/api/api";
import { ApiTag } from "../core/api/types";

export class TimeEntryApiAdapter implements TagListDelegate {
    constructor(private timeEntryId: string, private flashes: Flashes) {
    }

    addTag(tag: ApiTag): Promise<ApiTag> {
        return TimeEntryApi.addTag(this.timeEntryId, tag.name)
            .then(res => {
                return res.data;
            })
            .catch( (res: ApiErrorResponse) => {
                if (res.errors.length === 1) {
                    const message = res.errors[0].message;
                    this.flashes.append('danger', `Unable to add tag. ${message}`);
                } else {
                    this.flashes.append('danger', `Unable to add tag '${tag.name}'`)
                }

                throw res;
            });
    }

    removeTag(tagName: string): Promise<any> {
        return TimeEntryApi.removeTag(this.timeEntryId, tagName)
            .catch(res => {
                this.flashes.append('danger', `Unable to add remove tag '${tagName}'`)
                throw res;
            });
    }
}