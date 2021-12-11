#!/usr/bin/env sh
# Resets the main database - do not run this anywhere important
#  For safety, this defaults to running on the 'myradio_test' database
#  To run on the 'myradio' db, give an arbitrary argument e.g. './###.sh 1'
# This is useful for testing the myradio setup stages

[[ -z $1 ]] && db="myradio_test" || db="myradio"

# Deletes the given database and recreates it with the 'myradio' user
dropdb --if-exists $db;
createdb -O myradio $db
