MyRadio
=======

Hey! You've stumbled upon URY's web management system for all of its operations.
Internally called MyURY, this will be generalised and documented over the
coming months so that it will hopefully be usable for anyone wanting to start
their own community or student radio venture.

MyRadio is part of a suite of upcoming public projects, including:
- iTones, our liquidsoap sustainer system
- loggerng, our python audio logging and retrieving system
- Bootstrapping scripts for setting up and configuring all the dependencies

Super Duper Quickstart
==========

MyRadio is pre-configured for GitHub Codespaces, which let you start a version of
Visual Studio Code running in the cloud, pre-configured for MyRadio.

To do this, go to [Codespaces](https://github.com/UniversityRadioYork/MyRadio/codespaces),
and hit New Codespace. After a minute or so you'll be dropped into a Visual Studio
Code window, running remotely on a GitHub server. Switch to the Ports tab on the bottom,
and click on the Local Address next to port 7080 to open a MyRadio browser tab. Then,
follow the steps below from Database onwards - the database hostname is `postgres` and
the database password is `myradio`.

Super Quickstart
==========
MyRadio comes with a Vagrantfile based on Ubuntu 19.10.
If you have [Vagrant](https://www.vagrantup.com) installed and want to get
developing or playing right away, just run `vagrant up` and a few minutes
later [you'll have a working server](https://localhost:4443/myradio/).

When you're done run `vagrant halt` to end the process.

Steps
-----
Items in [Square Brackets] give notes and advice if things don't work

INSTALL:
 - Make sure you have both "vagrant" and "virtualbox" installed and configured
 - [Try reinstalling vagrant after virtualbox, or downloading an installer]
 - Clone myradio, open the folder in console and run "vagrant up"
 - [This may take a long time and use up all processing power]

CONNECT:
 - Open up "https://localhost:4443/myradio/" in a browser
 - [Use Chrome as this often fails to run on Firefox]
 - It will say "connection not private" so press "advanced" and then "proceed"

DATABASE:
 - On the intro screen press "Click here to continue"
 - Enter the database details (see Database Credentials) and press Next
 - Press "run task", wait a few seconds and then press "run task" again. (And possibly a third time.)
 - [This delay and repetition is a workaround for a slight bug in how we build the database]
 
USER:
 - [Here you can make config changes but the defaults are autofilled]
 - Press "complete starting set", scroll and press "save and continue"
 - Input any first name and last name, an email (NOT a @york.ac.uk email) and a password
 - [If you enter a @york.ac.uk email you will not be able to login at all]
 - Login using the email and password you just enterted

Notes
-----
Database Credentials:
 - Hostname: localhost
 - Database: myradio
 - Username: myradio
 - Password: myradio

Other:
 - To connect to the VM directly, login (with "vagrant", "vagrant") using SSH or VirtualBox
 - Vagrant bootstrap script gives the myradio user CREATEDB permissions so be sure to never run this in a production environment, or remove the permission before doing so.

Docker Quickstart
==========

If you have Docker on your system, you can use Docker Compose to set up an environment.
To do this, simply run `docker compose up -d`, and visit "https://localhost:4443/myradio/".
Then follow the steps above.

Quickstart
==========
Install Apache2, PHP, Composer and PostgreSQL on your prefered Unix-based
distro. Or Windows, if you're into that. MyRadio has been tested with Ubuntu and
FreeBSD.

cd to your MyRadio installation and run `composer install`

Edit your Apache config as follows (where /usr/local/www/myradio is your
checkout of this repository):

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

Restart Apache2, go to http://hostname/myradio and follow the instructions.

For a new postgresql server, run the following after:
```
pg_createcluster [YOUR_POSTGRES_VERSION] myradio
su postgres
psql
CREATE USER myradio WITH password '[A_STRONG_PASSWORD]';
CREATE DATABASE myradio WITH OWNER=myradio;
```

Tests
-----
MyRadio uses [Codeception](http://codeception.com/quickstart) for its test
suite.

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

Next steps
==========
Once you've got through the setup wizard, the next thing that's most useful to
you is most likely creating a show.

To do this, you first need to:
- Create a Term (Show Scheduler -> Manage Terms)
- Create a Show (List My Shows -> Create a Show)
- Apply for a Season of your new Show (List My Shows -> New Season)
- Schedule the Season (Shows Scheduler)

A note on Seasons and Terms
---------------------------
MyRadio splits Shows into "Seasons". Any Season is applied to in relation to a
"Term", which is a 10-week space of time. This is because The University of
York has 10 week terms, if you didn't know.


MyRadio uses GitHub Flow as a development workflow:
https://guides.github.com/overviews/flow/
