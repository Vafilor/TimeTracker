# Import/Export Design Document

## Goals

To be able to export all of the data stored and import it into another system.
For example, exporting a local database, in SQLite on your machine into a server in the cloud using MySql, and vice versa.

## Solution

Export the data as JSON. 

For each Entity, create a corresponding class that will be used by a serializer to dump to JSON.
This class will format the data from the raw entity into an easy to read format.

For importing, there needs to be a defined order to import in. 
A file will contain the order as a JSON array that has the path to the import file.

There may be several files per Entity in the export/import. These will have at most 500 items per file.
Each file will start numbering at 1 and increment from there.
For example,
 * tags_1.json
 * tags_2.json
 * tags_3.json

Specifics
* Users will be identified by their username
* tags will be identified by their name, and the username of the creator.

For other entities, they will be identified by their id, which is a UUID. Except for JOIN tables like
TagLink.

The id provides an easy way to identify if the data has already been imported.