# Pre-Myradio Database
## Building
When myradio is installed, a blank postgres database called "myradio" is made by [bootstrap.sh](../scripts/bootstrap.sh); the [setup process](../src/Controllers/Setup) checks this database to see what is required for myradio to run properly.
The first page that runs is [dbserver.php](../src/Controllers/Setup/dbserver.php), which takes a database hostname and credentials and simply tries to connect.
On failure, it just asks again until you give valid inputs but, on success, it moves to [dbschema.php](../src/Controllers/Setup/dbschema.php).

This next page tries to find the current state of the database by checking the value of 'myradio.schema.version' and comparing it  to a hardcoded constant 'MYRADIO_CURRENT_SCHEMA_VERSION'.
This constant declares the number of [patches](../schema/patches) to be added on top of [base.sql](../schema/base.sql), the unpatched schema, while the "version" number in the database is the number of patches that have already been applied.
By simply comparing these numbers and patching as appropriate, the page either builds the database from nothing or upgrades an existing one. 

On success, this moves on to [dbdata.php](../src/Controllers/Setup/dbdata.php), which simply reads from the database to setup the site.
By this stage, the actual database setup is complete.

## Resetting
In the [scripts folder](../scripts) there bash scripts to reset the database, for testing and modifying the database setup.
[reset-db.sh](../scripts/reset-db.sh) is built for the testing system, so has a few lines specific to testing, while [reset-db-v2.sh](../scripts/reset-db-v2.sh) simply drops and recreates the main database.

