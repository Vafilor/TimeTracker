# TODO

1. Tab on time entry view - show last 7 (configurable) descriptions and links to the previous entries
2. Add docker commands for generating build files (yarn)
3. Add docker compose for easy local deployment
4. CLI for convenience in terminal
5. Soft Delete tags
6. [Maybe] Icons for tags so you can display small versions?

For related entries, you need to do it via javascript so it doesn't impact the page load time.
So you need to create table rows and return the data via json.


One issue I'm having is sublists.
First question - should I even have them?
Second question - if I do, how do I display that?
Right now I'm thinking making AJAX requests and rendering my own pagination.
But at that point, what do I need twig for the lists for?

Can we use a form and use different labels?

if you do add admin access, should the main pages
show logged in user?
Or should it always be /username/page?

With /admin/time-entry showing all filterable by user?


For the task page - you should be able to see all the description for a task combined.
Also total time taken.


Update all json errors for consistent format - in the typescript

activity log? Andrey did this.... Andrey did that....

// TODO - are tags owned by people? If Aleks and I are on a team, can we access the same tags?
// I guess tags have a couple of layers - private made, and then team made. 
// Can an admin see private tags? Or are those time entries considered private?
// Probably just not see private tags? 
// an admin/team version might be a separate project


// One a form page, when a field is modified, indicate it so update clears it
// Also maybe add a " Are you sure you want to leave this page? You have changes? "

// Mobile friendly

// Registration - optional

// Autocomplete for tags does not move when new tags are added.



Active timer on the bottom?
Tags - up to 5ish
Then started/duration
Actions - stop/resume/continue?

Task needs an Activity history - description using all time entries - or last couple at least.

Markdown -> description?

On Task page, first tab is time entries.
The last 10 or so.
At the bottom, start timer, or something.
No need for continue buttons, since you're on this task.
Really just, start/end, description....?

Do I want a quick action like - repeat last timestamp?
Wait.... isn't that just a dashboard?

// Make sure two different users can have an active time entry

// loading indicator for time entry tasks

// pagination to time entries

// move time entry for tasks out of activity?

// no activity text for tasks if there is no activity

// TODO task list - load more as you complete all

// create tag on index page should be javascript'd

// show tag color in autocomplete - use similar approach as tasks - box with color

// quick action on task - start time entry that appears at the top
// inline edit tags for timestamps

// Create time entry - pre fill tags, task?
For updated UI, you need to consider
Sortable headers
Footers like - total time? It doesn't have to be a footer, mind.
Filter?


active time entry indicator - or else, put it above all of the others somehow? 
Like a placeholder - if active, here it is. If nothing active, this is a placeholder for active.


// redo time entry view

// Resume button
// TODO - do todos
// TODO tags on time entry list - the filter ui
// update sync input in task edit page
// show activity for a specific tag. Maybe a time breakdown report.
// when done editing, transition it out so it's easier to tell - use css classes and set timeout
// tags for tasks so I can search for onepanel tasks
// Task edit page - have a complete button so I don't have to manually type it in.
// I might have to eventually create a JS date formatter.
// the classes know they interact with html, so make use of that.
// other classes might not, so it makes sense to pass data that way
// TODO missing data attribute error 
//         const durationFormat = $container.data('duration-format');
if (!durationFormat) {
throw new Error('No duration format');
}
// test different timezones
// read up about storing date data including timezones. Is an EPOCH timestamp best?
// When you continue a time entry, make sure to scroll up to it when finished.
// Add a "summary" or "small" view for time entries. This would show tags, task, and maybe duration. 1 line. No buttons


// Make the small time entry component be more stimulus like


// Add statistic value
// Remove statistic value
// Get/show statistic values
// Add statistic value for day and show for current day
// Should I store statistic values as strings? 
Maybe just have a column for all supported types? Or just do a double - that works as int. Format it differently for UI though

// Notes resource? Just notes with tags for the day.
// description for timestamps

// A statistics display has
// a list where you can delete one (confirm dialog)
// it shows the icon/name : value
// and at the top of it is a place to add a new one
// autocomplete stat, and then value, add button
// on autocomplete, add the new item to the list.
// if item does not exist in autocomplete, add it.

// Document the idea of an auto generated id that is then replaced by the real one.
// Need an id to uniquely identify the row
// Finally, the actual stat's value page. Add that I drank 2 cups of coffee and hard cider yesterday.

// Add tag, not blank or other errors - display around form
// Allow changing statistic name if there is no conflict
// statistic value details page
// Don't allow same name for a resource - e.g. timestamp or time entry or day. Sum it.

// 
// TODO export use date/datetime formats
// TODO add description to tags


// small pieces to update main with
 * date time updates vs datetimezone 
 * update tasks?

// TODO allow time entries for something like reading, and then a day entry for it too
e.g. I read 10 pages of biology from 5-6 and then I also read 3 pages throughout the day somewhere.
do double null check


// Say I want automatically updating time ago to be a "controller"
// how do I set it up so that it all happens on a global timer? I don't really want to create a new interval for every single item.
// Separate class with static?

// timeago - scss class?
// also timestamps are not having UI added.
// also add sort
// also add generic classes for the type of border thing you're doing
// like having the border, drop shadow, footer, content and header
// and separator
// todo - tag html create