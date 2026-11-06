# THUGSred Dorkify - Work smarter. Dork harder.
#
# Official PHP + Apache image (multi-arch: amd64, arm64, armv7). Apache listens
# on 8080 and runs as www-data, so the container needs no root and no
# capabilities - see docker-compose.yml for the read-only / cap_drop setup.
FROM php:8.4-apache

LABEL org.opencontainers.image.title="THUGSred Dorkify" \
      org.opencontainers.image.description="Self-hosted Google dork help, reference and query builder by the Danish hacking community THUGSred" \
      org.opencontainers.image.source="https://github.com/kawaiipantsu/thugsred-dorkify" \
      org.opencontainers.image.url="https://thugs.red" \
      org.opencontainers.image.version="1.0.0"

ENV APACHE_DOCUMENT_ROOT=/var/www/dorkify/public

RUN set -eux; \
    a2enmod headers; \
    a2disconf other-vhosts-access-log; \
    sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf; \
    sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:8080>/; s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf; \
    sed -ri 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf

COPY docker/apache.conf /etc/apache2/conf-available/dorkify.conf
COPY docker/php.ini     /usr/local/etc/php/conf.d/zz-dorkify.ini
RUN a2enconf dorkify

COPY --chown=root:root src/ /var/www/dorkify/

USER www-data
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
  CMD curl -fsS -o /dev/null http://127.0.0.1:8080/ || exit 1
