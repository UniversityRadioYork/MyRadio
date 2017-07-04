#!/usr/bin/env sh

# Installs the necessary stuff for running tests. Steals relevant configs from travis and bypasses setup
# Requires elevated permissions

set -eux

if [ $EUID -ne 0 ]; then
    echo "Run with elevated permissions"
	exit 1
fi

# Add a recent Node repo
curl -sL https://deb.nodesource.com/setup_4.x | sudo -E bash -

sudo apt-get install -y nodejs

# Tools to run API tests
npm install -g jasmine-node
npm install --no-bin-links frisby

# Necessary for dropping the database and recreating it each test run
# NEVER RUN IN PRODUCTION
su - postgres -c 'psql -c "ALTER USER myradio CREATEDB;"'
