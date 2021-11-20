FROM php:7.4-apache

RUN apt-get update && apt-get install -y libpq-dev libpng-dev libjpeg-dev libldap-dev unzip \
                                         libcurl4-openssl-dev libxslt-dev git libz-dev libzip-dev libmemcached-dev \
                                         postgresql-client jq

RUN docker-php-ext-install pgsql pdo_pgsql gd ldap curl xsl zip

RUN pecl install memcached && \
    echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini

RUN pecl install xdebug-2.9.5 && docker-php-ext-enable xdebug \
 && echo 'zend_extension="/usr/local/lib/php/extensions/no-debug-non-zts-20190902/xdebug.so"' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo 'xdebug.remote_port=9000' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo 'xdebug.remote_enable=1' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo 'xdebug.remote_connect_back=1' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

COPY --from=aheadworks/mhsendmail:latest /usr/bin/mhsendmail /usr/local/bin/mhsendmail
# RUN echo sendmail_path = /usr/local/bin/mhsendmail >> /etc/php/php.ini

# Self-signed certificate
RUN openssl req -nodes -new -subj "/C=GB/ST=North Yorkshire/L=York/O=University Radio York/OU=Localhost/CN=localhost" > myradio.csr && \
    openssl rsa -in privkey.pem -out myradio.key && \
    openssl x509 -in myradio.csr -out myradio.crt -req -signkey myradio.key -days 999 && \
    cp myradio.crt /etc/apache2/myradio.crt && \
    cp myradio.key /etc/apache2/myradio.key

RUN a2enmod rewrite ssl

COPY sample_configs/apache.conf /etc/apache2/sites-available/myradio.conf
RUN a2dissite 000-default && a2ensite myradio && \
    service apache2 restart && apachectl -S

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN mkdir -p /var/www/myradio && chown -R www-data:www-data /var/www/myradio && \
    mkdir -p /var/log/myradio && chown -R www-data:www-data /var/log/myradio

WORKDIR /var/www/myradio
COPY composer.* /var/www/myradio/
RUN COMPOSER_VENDOR_DIR=/var/www/myradio/vendor composer install

COPY schema schema
COPY src src

COPY sample_configs/docker-config.php src/MyRadio_Config.local.php
RUN chown www-data:www-data /var/www/myradio/src/MyRadio_Config.local.php && chmod 664 /var/www/myradio/src/MyRadio_Config.local.php

# COPY scripts/docker-entrypoint.sh /docker-entrypoint.sh
# ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
