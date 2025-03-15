FROM docker.io/php:8.3-cli-bullseye

RUN set -eux; \
    apt update -y; \
    apt install -y vim libyaml-dev; \
    rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    yes | pecl install pcov yaml; \
    echo "extension=pcov.so" > /usr/local/etc/php/conf.d/pcov.ini; \
    echo "pcov.enabled=1" >> /usr/local/etc/php/conf.d/pcov.ini; \
    echo "extension=yaml.so" > /usr/local/etc/php/conf.d/yaml.ini

RUN set -eux; \
    curl -o /usr/local/bin/composer https://getcomposer.org/download/2.5.7/composer.phar; \
    chmod +x /usr/local/bin/composer

ENV PATH "${PATH}:./vendor/bin"
