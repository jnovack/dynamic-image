FROM php:7.4-fpm-alpine

# main()
RUN apk add --update --no-cache \
    imagemagick \
    && \
    apk add --update --no-cache --virtual build-dependencies \
    build-base \
    autoconf \
    libtool \
    freetype-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libxml2-dev \
    imagemagick-dev \
    pcre-dev \
    && \
    pecl config-set php_ini /usr/local/etc/php/php.ini && \
    docker-php-ext-configure gd \
    --with-jpeg=/usr/include/ \
    --with-freetype=/usr/include/ \
    && \
    docker-php-ext-install gd && \
    pecl install imagick && \
    docker-php-ext-enable imagick && \
    pecl clear-cache && \
    docker-php-source delete && \
    apk del build-dependencies && \
    rm -rf /tmp/* /var/cache/apk/*

# Set up HEALTHCHECK
RUN apk add --update --no-cache fcgi && \
    echo "ping.path = /php-fpm/ping" > /usr/local/etc/php-fpm.d/healthcheck.conf && \
    echo "pm.status_path = /php-fpm/status" >> /usr/local/etc/php-fpm.d/healthcheck.conf

HEALTHCHECK --interval=10s --timeout=3s --retries=3 \
    CMD \
    SCRIPT_NAME=/php-fpm/ping \
    SCRIPT_FILENAME=/php-fpm/ping \
    REQUEST_METHOD=GET \
    cgi-fcgi -bind -connect 127.0.0.1:9000 || exit 1

# Customization
#   * Remove access logging for php-fpm
#   * Add error logging for php-fpm
#   * Change processes to 'ondemand'
#   * Only listen on IPv4 sockets
#   * Set max_children up a lil' higher
RUN sed -i '/access.log/d' /usr/local/etc/php-fpm.d/docker.conf && \
    sed -i 's/pm = dynamic/pm = ondemand/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/listen = \[::\]:9000/listen=0.0.0.0:9000/' /usr/local/etc/php-fpm.d/zz-docker.conf && \
    sed -i 's/max_children = 5/max_children = 10/' /usr/local/etc/php-fpm.d/www.conf && \
    sed -i 's/;request_terminate_timeout = 0/request_terminate_timeout = 10/' /usr/local/etc/php-fpm.d/www.conf && \
    echo 'access.log = /proc/self/fd/2' >> /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo "access.format = '{ \"remoteip\": \"%{HTTP_X_FORWARDED_FOR}e\", \"method\": \"%m\", \"uri\": \"%r\", \"querystring\": \"%q\", \"datetime\": \"%{%Y-%m-%dT%H:%M:%SZ}t\", \"status\": %s, \"filename\": \"%f\", \"timetaken\": %{mili}d, \"memory\": %{kilo}M, \"cpu\": %C }'" >> /usr/local/etc/php-fpm.d/zz-docker.conf

COPY classes/ /var/www/html/classes/
COPY index.php /var/www/html/index.php