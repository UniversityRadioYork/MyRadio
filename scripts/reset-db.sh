#!/usr/bin/env sh

# Resets the test database to an initial state, for travis and local testing

set -eux
export PGUSER=myradio
export PGPASSWORD=myradio
export PGHOST=127.0.0.1

# Reset database
dropdb --if-exists myradio_test;

# (Re)init database
createdb -O myradio myradio_test

psql myradio_test < `dirname $0`/../schema/base.sql > /dev/null
# Disabled until patch files are implemented (there's a 1.sql which renames schema myury to myradio)
#psql myradio_test < schema/patches/*.sql
psql myradio_test < `dirname $0`/../sample_configs/travis-auth.sql

sed -e '/Config::$db_name/s/myradio/myradio_test/' `dirname $0`/../sample_configs/travis-config.php > `dirname $0`/../src/MyRadio_Config.local.php
