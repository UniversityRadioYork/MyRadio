#!/usr/bin/env sh

set -eu

# Add a recent Node repo
curl -sL https://deb.nodesource.com/setup_4.x | sudo -E bash -

# Base packages and Apache setup
apt-get update
apt-get install -y apache2 \
	libapache2-mod-php5 \
	php5-common \
	postgresql-9.3 \
	postgresql-client-9.3 \
	memcached \
	php-apc \
	php5-curl \
	php5-geoip \
	php5-gd \
	php5-ldap \
	php5-pgsql \
	php5-dev \
	php5-memcached \
	php5-xdebug \
	openssl \
	libav-tools \
	nodejs
a2enmod ssl
a2enmod rewrite
service apache2 stop

cat <<EOF >> /etc/php5/mods-available/xdebug.ini
xdebug.default_enable=1
xdebug.remote_enable=1
xdebug.remote_autostart=0
xdebug.remote_port=9000
xdebug.remote_log="/var/log/xdebug/xdebug.log"
xdebug.remote_host=10.0.2.2
xdebug.idekey="MyRadio vagrant"
xdebug.remote_handler=dbgp
EOF

# Composer - no package on 14.04 :(
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
cd /vagrant
su vagrant -c 'composer update'

# Tools to run API tests
npm install -g jasmine-node
npm install --no-bin-links frisby

ln -s /vagrant/src /var/www/myradio
ln -s /vagrant/sample_configs/apache.conf /etc/apache2/sites-available/myradio.conf
a2ensite myradio
a2dissite 000-default

# Generate an SSL cert
export PASSPHRASE=$(head -c 500 /dev/urandom | tr -dc a-z0-9A-Z | head -c 128; echo)
subj="
C=UK
ST=Bar
O=MyRadio
localityName=RadioTown
commonName=myradio.local
organizationalUnitName=MyRadio
emailAddress=someone@example.com
"
openssl genrsa -des3 -out /etc/apache2/myradio.key -passout env:PASSPHRASE 2048
openssl req \
	-new \
	-batch \
	-subj "$(echo -n "$subj" | tr "\n" "/")" \
	-key /etc/apache2/myradio.key \
	-out /etc/apache2/myradio.csr \
	-passin env:PASSPHRASE
openssl rsa -in /etc/apache2/myradio.key -out /etc/apache2/myradio.key -passin env:PASSPHRASE
openssl x509 -req -days 3650 -in /etc/apache2/myradio.csr -signkey /etc/apache2/myradio.key -out /etc/apache2/myradio.crt

# Start httpd back up
service apache2 start

# Create DB cluster/database/user
pg_createcluster 9.3 myradio
su - postgres -c "cat /vagrant/sample_configs/postgres.sql | psql"

# Start httpd back up
service apache2 start

# Somewhere to store audio uploads
music_dirs="records membersmusic beds jingles"
for i in ${music_dirs}; do # no spaces
	mkdir -p /music/$i
	chown www-data:www-data /music/$i
done
