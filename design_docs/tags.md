# Tags Design Document

## Goals

* Know who created a tag
* Tags have a color
* Tags have an icon/image
* Have tag access control
  * A team can see all team tags
  * Andrey's private tags can not be seen by team
  * Admin can see all tags
* Assign multiple tags to an entity
* List all tags
* List number of uses of a tag, e.g. work has 50 entities using it.
* Find entities by having
  * no tags
  * tag A or B or C
  * tag A and B and C
* Avoid creating a Join table for tags and each entity
  * That is, if I have entities: tasks, users, there should not be a separate table for each linking to tags.
  
## Solution

The solution taken for now is to have a single `tag_link` table that has the tag_id, 
and a separate entity_id column for each possible link.
This means that we have

tag_link
* tag_id
* time_entry_id
* timestamp_id

**Note:**

With the current implementation, the following are not yet supported:
* Tags have an icon/image (easy)
* Have tag access control
  * A team can see all team tags
  * Andrey's private tags can not be seen by team
  * Admin can see all tags
  

### Rationale 

This solution makes it pretty easy to work with Doctrine. You get to keep all of the nice
relationship annotations and it's pretty much business as usual, no special logic, etc. 
It achieves the goal of a single table, and provides support for all of the other requested functionality.

The nulls are a bit ugly though.