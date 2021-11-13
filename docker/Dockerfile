FROM php:latest

RUN docker-php-ext-install sockets && apt update && apt install libssl-dev libcurl4-nss-dev -y && \
    pecl install -D 'enable-sockets="yes" enable-openssl="yes" enable-http2="yes" enable-mysqlnd="no" enable-swoole-json="yes" enable-swoole-curl="yes" enable-cares="yes"' openswoole

RUN echo "extension=openswoole.so" >> /usr/local/etc/php/conf.d/openswoole.ini
