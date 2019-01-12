#!/usr/bin/env sh

# Bootstrapping script just for vagrant

set -eux

if [ ! -d /vagrant ]; then
	echo "This script should only ever be run on a vagrant virtual machine"
	echo "Seriously, don't run this anywhere other than vagrant, it will ruin your day";
	exit 1;
fi

# Base packages and Apache setup
apt-get update
apt-get install -y apache2 \
	libapache2-mod-php \
	php-common \
	postgresql-10 \
	postgresql-client-10 \
	memcached \
	php-curl \
	php-geoip \
	php-gd \
	php-ldap \
	php-pgsql \
	php-dev \
	php-memcached \
	php-xdebug \
	php-mbstring \
	php-xsl \
	openssl \
	ffmpeg \
	zip \
	unzip \
	composer
a2enmod ssl
a2enmod rewrite
service apache2 stop

cat <<EOF >> /etc/php/7.2/mods-available/xdebug.ini
xdebug.default_enable=1
xdebug.remote_enable=1
xdebug.remote_autostart=0
xdebug.remote_port=9000
xdebug.remote_log="/var/log/xdebug/xdebug.log"
xdebug.remote_host=10.0.2.2
xdebug.idekey="MyRadio vagrant"
xdebug.remote_handler=dbgp
EOF

# Composer
cd /vagrant
mkdir -p /vagrant/src/vendor
su vagrant -c 'composer --no-progress update'

ln -sf /vagrant/src /var/www/myradio
ln -sf /vagrant/sample_configs/apache.conf /etc/apache2/sites-available/myradio.conf
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

update-rc.d apache2 defaults
service apache2 start

# Create DB cluster/database/user
pg_dropcluster 10 main --stop || true # Seriously, don't use this anywhere other than vagrant
if ! `pg_lsclusters | grep -q myradio`; then pg_createcluster 10 myradio -p 5432; fi
systemctl start postgresql@10-myradio
su - postgres -c "cat /vagrant/sample_configs/postgres.sql | psql"

rm -f /vagrant/src/MyRadio_Config.local.php # Remove any existing config

# Somewhere to store audio uploads
music_dirs="records membersmusic beds jingles"
for i in ${music_dirs}; do # no spaces
	mkdir -p /music/$i
	chown www-data:www-data /music/$i
done
# And logs
mkdir -p /var/log/myradio
chown www-data:www-data /var/log/myradio
