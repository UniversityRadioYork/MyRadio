FROM php:7.2-apache

RUN apt-get update && apt-get install -y libpq-dev libpng-dev libjpeg-dev libldap-dev \
                                         libcurl4-openssl-dev libxslt-dev git libz-dev libmemcached-dev

RUN docker-php-ext-install pgsql pdo_pgsql gd ldap curl xsl zip

RUN pecl install memcached
RUN echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini

# Self-signed certificate
RUN openssl req -nodes -new -subj "/C=GB/ST=North Yorkshire/L=York/O=University Radio York/OU=Localhost/CN=localhost" > myradio.csr && \
    openssl rsa -in privkey.pem -out myradio.key && \
    openssl x509 -in myradio.csr -out myradio.crt -req -signkey myradio.key -days 999 && \
    cp myradio.crt /etc/apache2/myradio.crt && \
    cp myradio.key /etc/apache2/myradio.key

RUN a2enmod rewrite ssl

COPY sample_configs/apache.conf /etc/apache2/sites-available/myradio.conf
RUN a2dissite 000-default && a2ensite myradio \
    && service apache2 restart && apachectl -S

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN mkdir -p /var/www/myradio && chown -R www-data:www-data /var/www/myradio
RUN mkdir -p /var/log/myradio && chown -R www-data:www-data /var/log/myradio
COPY composer.json /var/www/myradio

WORKDIR /var/www/myradio
RUN COMPOSER_VENDOR_DIR=/var/www/myradio/vendor composer install

COPY schema /var/www/schema
COPY src /var/www/myradio

COPY sample_configs/docker-config.php /var/www/myradio/MyRadio_Config.local.php
RUN chown www-data:www-data /var/www/myradio/MyRadio_Config.local.php && chmod 664 /var/www/myradio/MyRadio_Config.local.php