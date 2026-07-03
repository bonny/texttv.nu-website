# Importern (Laravel 8) — utökar serversideup/php med de PHP-extensions
# Laravel/Carbon och dela-bild-generering kräver, och bakar in vendor/ vid
# build så init-servicen kan migrera + importera direkt utan separat
# composer-steg.
#
# PHP 8.2 matchar prod (8.2.31 på Hetzner, se server.md) — samma version som
# tog ner schedulern i bugg #129 (8.2-deprecation som aldrig syntes i Valets 8.1).
#
# Byggkontext = ./importer (se compose.yaml). I dev överlagras koden av en
# bind-mount medan vendor/ behålls via en anonym volym (se compose.override.yaml).
FROM serversideup/php:8.2-fpm-nginx

USER root
# intl: Carbon-lokalisering · bcmath: Laravel-default · gd: dela-bild · exif: bildmetadata
# (pdo_mysql, mbstring, zip, dom, xml, opcache ingår redan i basen)
RUN install-php-extensions intl bcmath gd exif

COPY --chown=www-data:www-data . /var/www/html
# Fräsch vendor/ byggd mot 8.2 (host-vendor exkluderas via .dockerignore).
RUN composer install --no-interaction --no-progress --prefer-dist --optimize-autoloader

USER www-data
