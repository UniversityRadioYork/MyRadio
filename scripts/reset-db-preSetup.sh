#!/usr/bin/env sh

# Resets the main database - do not run this anywhere important

[[ -z $1 ]] && db="myradio_test" || db="myradio"

dropdb --if-exists $db;
createdb -O myradio $db
