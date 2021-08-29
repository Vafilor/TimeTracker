# Manual description of tests until they are coded up

## Today

### Index


## Time-Entry

### Index

### View

## Tasks

### Index

#### Filter

* Make sure checkboxes work after you turn them off, then off, then on again
* Make sure tags work
* Make sure checking an item in the list performs an update
* Make sure create task works

### View

* Make sure update works
* Make sure delete works
* Make sure complete works
* Make sure task for time entries ui works
* Deleting a task's assigned parent - when there is none, should show an alert

## Timestamps

### Index

* Create works
* Repeat works
* Order by newest/oldest works

### View

* Update works
* Delete works
* Adding a statistic works
* Breadcrumbs back to timestamps works

## Tags

### Index

* Filter by name works
* Create works (name required)

### View

* Updating color works
* Repeat works

## Statistics

### Index

* Sort by oldest, newest, name asc/desc works
* Create works (description is optional)

### View

* Update works
* Delete works
* Setting an icon like 'fas fa-coffee' will automatically update the icon next to the header
* Setting a color will automatically change the icon to that color

## Records

### Index

* Pagination works

#### Create

* Unable to create with no statistic
* Create with pre-existing statistic
* Create with non-existing statistic, statistic will be auto created
* Choose different days - make sure record is created for that day

### View

* Update works
* Delete works
* Breadcrumbs navigating to root works

## Notes

### Index

* Pagination works

#### Filter

Make sure you can add tags via pressing enter and add.
On submit, the tags should be in the query parameters of the url and page should only contain
notes that have those tags.

#### Sort

Make sure items are sorted in the correct order based on created timestamp

#### Create

* Create with name and optional content works and refreshes page
* Create with no name fails and shows error under
* After failure, filled out name create works and refreshes page

### View

* Update name
* Update content
* Update with no content
* Add/remove tags
* Breadcrumbs - go back to root works

