FROM php:8.0.7-fpm-alpine3.13

ENV RUN_SCRIPTS 1
ENV PHP_MEM_LIMIT 384
ENV PHP_ERRORS_STDERR 1

ENV php_conf /usr/local/etc/php-fpm.conf
ENV fpm_conf /usr/local/etc/php-fpm.d/www.conf
ENV php_vars /usr/local/etc/php/conf.d/docker-vars.ini

RUN set -ex && \
    echo "Asia/Bangkok" > /etc/TZ && \
    printf "%s%s%s%s\n" "@nginx " "http://nginx.org/packages/alpine/v" `egrep -o '^[0-9]+\.[0-9]+' /etc/alpine-release` "/main" | tee -a /etc/apk/repositories && \
    curl -o /tmp/nginx_signing.rsa.pub https://nginx.org/keys/nginx_signing.rsa.pub && \
    openssl rsa -pubin -in /tmp/nginx_signing.rsa.pub -text -noout && \
    mv /tmp/nginx_signing.rsa.pub /etc/apk/keys/ && \
    apk update && \
    #Install PHP Extension
    apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS openldap-dev postgresql-dev freetype-dev libjpeg-turbo-dev libpng-dev libxpm-dev libxml2-dev cmake gnutls-dev libzip-dev libressl-dev zlib-dev && \
    docker-php-ext-install ldap pdo_mysql pgsql pdo_pgsql mysqli gd exif soap zip opcache bcmath pcntl && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    apk del --purge .phpize-deps && \
    apk add --no-cache \
    #Require by PHP Extension
    libldap postgresql-libs freetype libjpeg-turbo libpng libxpm libzip \
    #Require by Nginx
    openssl curl ca-certificates \
    #Tools
    bash wget supervisor && \
    #Install composer
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --quiet --install-dir=/usr/bin --filename=composer && \
    rm composer-setup.php && \
    #Install Nginx
    apk add nginx@nginx && \
    ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log


# tweak php-fpm config
RUN echo "cgi.fix_pathinfo=0" > ${php_vars} &&\
    echo "upload_max_filesize = 100M"  >> ${php_vars} &&\
    echo "post_max_size = 100M"  >> ${php_vars} &&\
    echo "variables_order = \"EGPCS\""  >> ${php_vars} && \
    echo "memory_limit = 128M"  >> ${php_vars} && \
    echo "max_execution_time = 120"  >> ${php_vars} && \
    sed -i \
        -e "s/;catch_workers_output\s*=\s*yes/catch_workers_output = yes/g" \
        -e "s/pm.max_children = 5/pm.max_children = 4/g" \
        -e "s/pm.start_servers = 2/pm.start_servers = 3/g" \
        -e "s/pm.min_spare_servers = 1/pm.min_spare_servers = 2/g" \
        -e "s/pm.max_spare_servers = 3/pm.max_spare_servers = 4/g" \
        -e "s/;pm.max_requests = 500/pm.max_requests = 200/g" \
        -e "s/user = www-data/user = nginx/g" \
        -e "s/group = www-data/group = nginx/g" \
        -e "s/;listen.mode = 0660/listen.mode = 0666/g" \
        -e "s/;listen.owner = www-data/listen.owner = nginx/g" \
        -e "s/;listen.group = www-data/listen.group = nginx/g" \
        -e "s/listen = 127.0.0.1:9000/listen = \/var\/run\/php-fpm.sock/g" \
        -e "s/^;clear_env = no$/clear_env = no/" \
        -e "s/;decorate_workers_output = no/decorate_workers_output = no/g" \
        ${fpm_conf}

ADD . /var/www/html/
WORKDIR "/var/www/html"

VOLUME [ "/var/www/html/storage" ]

RUN mv -f docker/start.sh /start.sh && chmod 755 /start.sh || true && \
    mv -f docker/supervisord.conf /etc/supervisord.conf || true && \
    mv -f docker/nginx.conf /etc/nginx/nginx.conf || true && \
    mv -f docker/default.conf /etc/nginx/conf.d/default.conf || true && \
    rmdir docker || true && \
    mv .env.example .env || true && \
    { crontab -l ; echo "* * * * * cd /var/www/html && php artisan schedule:run"; } | crontab -u nginx - || true && \
    cp -rf storage storage.default || true && \
    chown -Rf nginx:nginx /var/www/html || true && \
    chown -R nginx:nginx storage.default || true && \
    composer install --working-dir=/var/www/html

EXPOSE 80

CMD ["/start.sh"]
