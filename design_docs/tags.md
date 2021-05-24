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