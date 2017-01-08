MyRadio
=======

Hey! You've stumbled upon URY's web management system for all of its operations.
Internally called MyURY, this will be generalised and documented over the
coming months so that it will hopefully be usable for anyone wanting to start
their own community or student radio venture.

MyRadio is part of a suite of upcoming public projects, including:
- iTones, our liquidsoap sustainer system
- loggerng, our python audio logging and retriving system
- Bootstrapping scripts for setting up and configuring all the dependencies

Super Quickstart
----------------
MyRadio comes with a Vagrantfile based on Ubuntu 14.04.
If you have [Vagrant](https://www.vagrantup.com) installed and want to get
developing or playing right away, just run `vagrant up` and a few minutes
later [you'll have a working server](https://localhost:4443/myradio/).

During setup, you'll be asked for database credentials - you can use:
Hostname: localhost
Database: myradio
Username: myradio
Password: myradio

Quickstart
==========
Install Apache2, PHP, Composer and PostgreSQL on your prefered *nix distro.
Or Windows, if you're into that. MyRadio has been tested with Ubuntu and
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
