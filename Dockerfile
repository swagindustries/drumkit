FROM php:8.3-cli-alpine

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions filter hash json openssl pcntl sodium ds

COPY bin/drumkit.phar /drumkit.phar

ENTRYPOINT ["/drumkit.phar"]
