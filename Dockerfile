FROM php:7.4-apache

RUN apt-get update && apt-get install -y libpq-dev libpng-dev libjpeg-dev libldap-dev unzip \
    libcurl4-openssl-dev libxslt-dev git libz-dev libzip-dev libmemcached-dev \
    postgresql-client jq msmtp-mta

RUN docker-php-ext-install pgsql pdo_pgsql gd ldap curl xsl zip

RUN pecl install memcached && \
    echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini

RUN a2enmod rewrite

COPY sample_configs/docker-apache.conf /etc/apache2/sites-available/myradio.conf
RUN a2dissite 000-default && a2ensite myradio && \
    service apache2 restart && apachectl -S

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN mkdir -p /var/www/myradio && chown -R www-data:www-data /var/www/myradio && \
    mkdir -p /var/log/myradio && chown -R www-data:www-data /var/log/myradio

WORKDIR /var/www/myradio
COPY composer.* /var/www/myradio/
RUN COMPOSER_VENDOR_DIR=/var/www/myradio/src/vendor composer install

COPY schema schema
COPY src src

COPY src/MyRadio_Config.docker.php /etc/myradio/MyRadio_Config.local.php
RUN chown www-data:www-data /etc/myradio/MyRadio_Config.local.php
ENV MYRADIO_CONFIG_PATH=/etc/myradio/MyRadio_Config.local.php

CMD ["apache2-foreground"]
