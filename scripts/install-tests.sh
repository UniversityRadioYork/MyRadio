#!/usr/bin/env sh

# Installs the necessary stuff for running tests. Steals relevant configs from travis and bypasses setup

set -eux

# Add a recent Node repo
curl -sL https://deb.nodesource.com/setup_4.x | sudo -E bash -

sudo apt-get install -y nodejs

# Tools to run API tests
npm install -g jasmine-node
npm install --no-bin-links frisby

export PGPASSWORD=myradio
psql -U myradio -h 127.0.0.1 myradio < schema/base.sql
# Disabled until patch files are implemented (there's a 1.sql which renames schema myury to myradio)
#psql -U myradio -h 127.0.0.1 myradio < schema/patches/*.sql
psql -U myradio -h 127.0.0.1 myradio < sample_configs/travis-auth.sql
