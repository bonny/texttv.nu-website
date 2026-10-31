# Importern (Laravel 8) — utökar serversideup/php med de PHP-extensions
# Laravel/Carbon och dela-bild-generering kräver, och bakar in vendor/ vid
# build så init-servicen kan migrera + importera direkt utan separat
# composer-steg.
#
# PHP 8.4 matchar prod (8.4.27 på Hetzner sedan Debian 13, 2026-10-31, se
# server.md). Lokalt och prod på samma minor: bugg #129 var en 8.2-deprecation
# som aldrig syntes i Valets 8.1 och tog ner schedulern.
#
# Byggkontext = ./importer (se compose.yaml). I dev överlagras koden av en
# bind-mount medan vendor/ behålls via en anonym volym (se compose.override.yaml).
FROM serversideup/php:8.4-fpm-nginx

USER root
# intl: Carbon-lokalisering · bcmath: Laravel-default · gd: dela-bild · exif: bildmetadata
# (pdo_mysql, mbstring, zip, dom, xml, opcache ingår redan i basen)
RUN install-php-extensions intl bcmath gd exif

COPY --chown=www-data:www-data . /var/www/html
# Fräsch vendor/ byggd mot 8.4 (host-vendor exkluderas via .dockerignore).
RUN composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader

USER www-data
