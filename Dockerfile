FROM php:8.3-apache

# Install packages required by Composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        unzip \
        libzip-dev \
    && docker-php-ext-install zip opcache \
    && rm -rf /var/lib/apt/lists/*

# mod_rewrite and mod_headers are off by default in this image, and AllowOverride
# defaults to None, so .htaccess is ignored until both are turned on.
# ServerName silences Apache's "could not reliably determine FQDN" warning.
RUN a2enmod rewrite headers deflate expires \
    && printf 'ServerName localhost\n\n<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/allzerve.conf \
    && a2enconf allzerve

# Compile PHP once and keep it cached; validate_timestamps off means a rebuild
# is required to pick up code changes, which is what we want for a released image.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.memory_consumption=64'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini \
    && mv /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
    && { \
        echo 'expose_php=Off'; \
        echo 'post_max_size=1M'; \
        echo 'upload_max_filesize=1M'; \
    } > /usr/local/etc/php/conf.d/allzerve.ini

WORKDIR /var/www/html

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy Composer files first
COPY composer.json composer.lock ./

# Install production dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# Copy website
COPY . .

# storage/ is gitignored, so it may not exist in the build context
RUN mkdir -p storage/ratelimit \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 750 storage

EXPOSE 80
