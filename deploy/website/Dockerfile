# Webben (CodeIgniter) — utökar serversideup/php med mysqli (CI:s dbdriver)
# och injicerar DB-credentials som fastcgi_param så CI:s "live"-gren i
# codeigniter/application/config/database.php aktiveras (den läser
# $_SERVER['DB_USERNAME']).
#
# Varför fastcgi_param och inte vanlig container-env: serversideup kör php med
# variables_order="GPCS" — env-variabler hamnar därför INTE i $_SERVER. CI
# läser bara $_SERVER, så creds måste in via fastcgi_param.
#
# OBS: dev-only fasta värden som matchar compose-defaults (texttv/texttv mot
# mariadb). Ändrar du DB-creds i .env måste website-imagen byggas om
# (`make rebuild`). Stats-DB:n (texttv_stats) har en hårdkodad hostname
# 'localhost' i database.php och nås därför INTE i Docker — bara
# analytics-skrivning (page_actions) påverkas, aldrig sidrendering. Se CLAUDE.md.
#
# Byggkontext = ./deploy/website (ingen kod kopieras in — den bind-mountas i dev).
FROM serversideup/php:8.2-fpm-nginx

USER root
RUN install-php-extensions mysqli

# CI:s php-location includar /etc/nginx/fastcgi_params. Den location:en
# definierar egna fastcgi_param, vilket gör att server-nivå-params INTE ärvs
# dit — därför läggs DB-params i själva fastcgi_params-filen.
RUN cat >> /etc/nginx/fastcgi_params <<'EOF'

# texttv.nu lokal Docker-dev: aktiverar "live"-grenen i CI:s database.php
fastcgi_param  DB_HOSTNAME        mariadb;
fastcgi_param  DB_USERNAME        texttv;
fastcgi_param  DB_PASSWORD        texttv;
fastcgi_param  DB_DATABASE        texttv_nu;
fastcgi_param  DB_DATABASE_STATS  texttv_stats;
EOF

USER www-data
