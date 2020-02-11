FROM php:7.4-alpine

COPY entrypoint.sh /entrypoint.sh
COPY phpinsights-app.phar /phpinsights-app.phar

ENTRYPOINT ["/entrypoint.sh"]