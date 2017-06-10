#!/usr/bin/env sh

# Runs tests, after resetting the database to an initial state

set -eux
export PGUSER=myradio
export PGPASSWORD=myradio
export PGHOST=127.0.0.1

# Reset database
dropdb --if-exists myradio_test;

# (Re)init database
createdb -O myradio myradio_test

psql myradio_test < schema/base.sql > /dev/null
# Disabled until patch files are implemented (there's a 1.sql which renames schema myury to myradio)
#psql myradio_test < schema/patches/*.sql
psql myradio_test < sample_configs/travis-auth.sql

# Run tests
jasmine-node tests/api
