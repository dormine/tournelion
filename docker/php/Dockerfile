FROM php:8.4-cli

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql intl zip opcache @composer \
    && apt-get update && apt-get install -y --no-install-recommends git unzip && rm -rf /var/lib/apt/lists/*

RUN echo "memory_limit=512M\nupload_max_filesize=20M\npost_max_size=20M" > /usr/local/etc/php/conf.d/app.ini

WORKDIR /app
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
