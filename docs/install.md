# Installing Myradio

There are a few different ways to install myradio

## a) Docker Install
If you have Docker on your system, use Docker Compose to set up an environment.
Simply run `docker compose up -d`, and visit "https://localhost:4443/myradio/".

## b) Vagrant Install
MyRadio comes with a Vagrantfile based on Ubuntu 19.10.
If you have [Vagrant](https://www.vagrantup.com) installed and want to get
developing or playing right away, just run `vagrant up` and a few minutes
later [you'll have a working server](https://localhost:4443/myradio/).

When you're done run `vagrant halt` to end the process.

Make sure you have both "vagrant" and "virtualbox" installed and configured.
If this fail, try reinstalling virtualbox and THEN vagrant.

Note: The Vagrant bootstrap script gives the myradio user CREATEDB permissions
so be sure to never run this in a production environment, or remove the
permission before doing so.

## c) Uncontained Install
Install Apache2, PHP, Composer and PostgreSQL on your prefered Unix-based distro.
Or Windows, if you're into that. 
MyRadio has been tested with Ubuntu and FreeBSD.

cd to your MyRadio installation and run `composer install`

Edit your Apache config as follows
(where /usr/local/www/myradio is your checkout of this repository):

```
Alias /myradio /usr/local/www/MyRadio/src/Public

<Directory /usr/local/www/MyRadio/src/Public>
   Require all granted
   AllowOverride None
</Directory>

Alias /api /usr/local/www/MyRadio/src/PublicAPI
<Directory /usr/local/www/MyRadio/src/PublicAPI>
  Require all granted
  AllowOverride None
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteRule ^ /api/index.php [QSA,L]
</Directory>
```

Restart Apache2, go to http://hostname/myradio

To make a new postgresql server, run the following after:
```
pg_createcluster [YOUR_POSTGRES_VERSION] myradio
su postgres
psql
CREATE USER myradio WITH password '[A_STRONG_PASSWORD]';
CREATE DATABASE myradio WITH OWNER=myradio;
```

# Post-Installation

## Myradio Setup
CONNECT:
 - Open up "https://localhost:4443/myradio/" in a browser
 - [Use Chrome as this often fails to run on Firefox]
 - It will say "connection not private" so press "advanced" and then "proceed"

DATABASE:
 - On the intro screen press "Click here to continue"
 - Enter the database details (see Default Credentials) and press Next
 - Press "run task", wait a few seconds and then press "run task" again.
 - [This method is a workaround for a slight bug in how we build the database]
 
USER:
 - [Here you can make config changes but the defaults are autofilled]
 - Press "complete starting set", scroll and press "save and continue"
 - Input any first and last name, an email (NOT an @york.ac.uk email) and a password
 - [If you enter an @york.ac.uk email you will not be able to login at all]
 - Login using the email and password you just enterted

## Default Credentials
Database: (when building the database, these credentials are needed)
 - Hostname: `postgres` if running in Docker, `localhost` otherwise
 - Database: myradio
 - Username: myradio
 - Password: myradio

Vagrant VM: (if you need to ssh into the virtual machine)
 - Username: vagrant
 - Password: vagrant

## Tests
MyRadio uses [Codeception](http://codeception.com/quickstart) for its test suite.

[This was written with a Vagrant install in mind - has not been tested on Docker]

To run the tests, call `src/vendor/bin/codecept run` from the root directory.
By default this assumes that the API is running at http://localhost:7080/api/v2,
as per the Vagrant instance's defaults. It can also use port 80 for the tests,
by running the tests with `--env travis` appended to it.

A script at `./scripts/reset-db.sh` is provided that creates a Config file and
database structure such that the tests can be run (essentially just runs setup
for you). This script operates on the database directly, so needs to be ran on
the Vagrant instance rather than the host, if that's necesssary. The script
blanks the `myradio_test` database each time it is ran, so it can be used to
reset the database and config file, should this prove necessary.

Summary:
* `composer install`
* `vagrant up`
* `vagrant ssh -- /vagrant/scripts/reset-db.sh`
* `src/vendor/bin/codecept run`

The vagrant initialisation script also runs `composer install`, but that is run
on the virtual machine which also installs the PHP extensions required for
Codeception. These extensions may be missing locally, so running composer will
confirm that they are present.

## Next Steps
Once you've got through the setup wizard, the next thing that's most useful to
you is most likely creating a show.

To do this, you first need to:
- Create a Term (Show Scheduler -> Manage Terms)
- Create a Show (List My Shows -> Create a Show)
- Apply for a Season of your new Show (List My Shows -> New Season)
- Schedule the Season (Shows Scheduler)

### A note on Seasons and Terms
MyRadio splits Shows into "Seasons". Any Season is applied to in relation to a
"Term", which is a 10-week space of time. This is because The University of
York has 10 week terms, if you didn't know.

