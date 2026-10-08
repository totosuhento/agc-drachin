# AGC Drama YT — image Docker untuk hosting container (Nusapod, dll.)
FROM php:8.3-apache

# Apache: rewrite untuk URL cantik; opcache untuk kecepatan
RUN a2enmod rewrite expires \
 && docker-php-ext-enable opcache \
 && { \
      echo 'opcache.enable=1'; \
      echo 'opcache.memory_consumption=32'; \
      echo 'opcache.validate_timestamps=1'; \
      echo 'opcache.revalidate_freq=60'; \
      echo 'memory_limit=128M'; \
      echo 'expose_php=Off'; \
    } > /usr/local/etc/php/conf.d/agc.ini

COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/tuning.conf /etc/apache2/conf-enabled/agc-tuning.conf
COPY docker/entrypoint.sh /usr/local/bin/agc-entrypoint

WORKDIR /var/www/html
COPY . /var/www/html
COPY docker/config.php /var/www/html/config.php

RUN chmod +x /usr/local/bin/agc-entrypoint \
 && rm -rf /var/www/html/docker /var/www/html/.github \
 && chown -R www-data:www-data /var/www/html/storage

# Database, cache & log disimpan di sini → pasang sebagai persistent volume
VOLUME ["/var/www/html/storage"]
EXPOSE 80

ENTRYPOINT ["agc-entrypoint"]
CMD ["apache2-foreground"]
