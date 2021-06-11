# Import/Export Design Document

## Goals

To be able to export all of the data stored and import it into another system.
Such as exporting a local database on your machine into a server in the cloud, and vice versa.

## Solution

Export the data as JSON. 
For each Entity, create a corresponding class that will be used by a serializer to dump to JSON.
This class will format the data from the raw entity into an easy to read format.

For importing, there needs to be a defined order to import in. 
A file will contain the order as a JSON array that has the relative path to the import file.

There may be several files per Entity in the export/import. These will have at most 500 items per file.