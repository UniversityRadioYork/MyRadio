FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpq-dev libpng-dev libjpeg-dev libldap-dev unzip \
                                         libcurl4-openssl-dev libxslt-dev git libz-dev libzip-dev libmemcached-dev \
                                         postgresql-client python3-dev jq msmtp-mta ffmpeg

RUN docker-php-ext-install pgsql pdo_pgsql gd ldap curl xsl zip

RUN pecl install memcached && \
    echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini

RUN pecl install xdebug-3.3.1 && docker-php-ext-enable xdebug \
 && echo 'zend_extension="/usr/local/lib/php/extensions/no-debug-non-zts-20220829/xdebug.so"' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo 'xdebug.client_port=9003' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo 'xdebug.mode=develop,debug' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo 'xdebug.start_with_request=yes' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo 'xdebug.client_host=localhost' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN echo 'error_reporting=E_ALL' >> /usr/local/etc/php/conf.d/error-reporting.ini

RUN echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "upload_max_filesize=512M" >> /usr/local/etc/php/conf.d/uploads.ini
RUN echo "post_max_size=512M" >> /usr/local/etc/php/conf.d/uploads.ini

RUN echo sendmail_path = "/usr/bin/msmtp -t --host mail --port 1025 --from myradio@ury.dev" > /usr/local/etc/php/conf.d/sendmail.ini

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
RUN COMPOSER_VENDOR_DIR=/var/www/myradio/src/vendor composer install

COPY schema schema
COPY src src

COPY sample_configs/docker-config.php src/MyRadio_Config.local.php
RUN chown www-data:www-data /var/www/myradio/src/MyRadio_Config.local.php && chmod 664 /var/www/myradio/src/MyRadio_Config.local.php

# Testing requirements
COPY pyproject.toml poetry.lock ./
RUN curl -sSL https://install.python-poetry.org | python3 - && \
  poetry config virtualenvs.in-project true && \
  poetry install

CMD ["apache2-foreground"]
