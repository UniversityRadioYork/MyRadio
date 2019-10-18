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
	postgresql-11 \
	postgresql-client-11 \
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

su -c "adduser www-data vagrant"

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
openssl req -newkey rsa:2048 -nodes -subj "$(echo -n "$subj" | tr "\n" "/")" -keyout /etc/apache2/myradio.key -x509 -days 365 -out /etc/apache2/myradio.crt \
-addext extendedKeyUsage=serverAuth -addext subjectAltName=DNS:localhost

# Start httpd back up

update-rc.d apache2 defaults
service apache2 start

# Create DB cluster/database/user
pg_dropcluster 11 main --stop || true # Seriously, don't use this anywhere other than vagrant
if ! `pg_lsclusters | grep -q myradio`; then pg_createcluster 11 myradio -p 5432; fi
systemctl start postgresql@11-myradio
su - postgres -c "cat /vagrant/sample_configs/postgres.sql | psql"

rm -f /vagrant/src/MyRadio_Config.local.php # Remove any existing config

# Somewhere to store audio uploads
music_dirs="records membersmusic beds jingles podcasts"
for i in ${music_dirs}; do # no spaces
	mkdir -p /music/$i
	chown www-data:www-data /music/$i
done
# And logs
mkdir -p /var/log/myradio
chown www-data:www-data /var/log/myradio

echo "MyRadio is now installed in your Vagrant VM. Go to https://localhost:4443/myradio/ :)"
