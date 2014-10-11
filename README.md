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

Quickstart
----------
Install Apache2, PHP and PostgreSQL on your prefered *nix distro. Or Windows,
if you're into that. MyRadio has been tested with Ubuntu and FreeBSD.

Edit your Apache config as follows (where /usr/local/www/myradio is your
checkout of this repository):

```
Alias /myradio /usr/local/www/myradio/src/Public

<Directory /usr/local/www/myradio/src/Public>
   Require all granted
   AllowOverride None
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

MyRadio uses GitHub Flow as a development workflow:
https://guides.github.com/overviews/flow/
