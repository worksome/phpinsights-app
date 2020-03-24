FROM php:7.4-alpine

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY . /action
WORKDIR /action
RUN apk add --no-cache git
RUN composer install
RUN php vendor/bin/box compile --dev


FROM php:7.4-alpine

COPY entrypoint.sh /entrypoint.sh
COPY --from=0 /action/phpinsights-app.phar /action/phpinsights-app.phar
WORKDIR /

ENTRYPOINT ["/entrypoint.sh"]
