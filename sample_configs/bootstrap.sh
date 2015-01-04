# Base packages and Apache setup
apt-get update
apt-get install -y apache2 libapache2-mod-php5 php5-common postgresql-9.3 postgresql-client-9.3 php-apc php5-curl php5-geoip php5-gd php5-ldap php5-mcrypt php5-pgsql php5-dev php-pear openssl
a2enmod ssl
a2enmod rewrite
service apache2 stop
pear channel-discover pear.twig-project.org
pear install twig/Twig
pear install twig/CTwig
echo "extension=mcrypt.so" > /etc/php5/mods-available/mcrypt.ini
ln -s /etc/php5/mods-available/mcrypt.ini /etc/php5/apache2/conf.d/20-mcrypt.ini
echo "extension=twig.so" > /etc/php5/mods-available/twig.ini
ln -s /etc/php5/mods-available/twig.ini /etc/php5/apache2/conf.d/20-twig.ini

ln -s /vagrant/src /var/www/myradio
ln -s /vagrant/sample_configs/apache.conf /etc/apache2/sites-available/myradio.conf
a2ensite myradio

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

# Create DB cluster/database/user
pg_createcluster 9.3 myradio
su - postgres -c "cat /vagrant/sample_configs/postgres.sql | psql"

# Start httpd back up
service apache2 start