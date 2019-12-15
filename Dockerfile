FROM docker.canceralliance.co.th/nginx-php-base:latest

ENV APP_ENV=DEV
ENV APP_DEBUG=true
ENV APP_VERSION=1.0.1
ENV DB_CONNECTION mysql
ENV DB_HOST mysql
ENV DB_PORT 3306
ENV DB_DATABASE homestead
ENV DB_USERNAME homestead
ENV DB_PASSWORD secret
ENV DB_EXPORT_CONNECTION mysql
ENV DB_EXPORT_HOST mysql
ENV DB_EXPORT_PORT 3306
ENV DB_EXPORT_DATABASE homestead
ENV DB_EXPORT_USERNAME homestead
ENV DB_EXPORT_PASSWORD secret
ENV RUN_SCRIPTS 1
ENV PHP_MEM_LIMIT 256
ENV PHP_ERRORS_STDERR 1
ENV SKIP_CHOWN 1
ENV SKIP_COMPOSER 1

VOLUME [ "/var/www/html/storage" ]

RUN echo "Asia/Bangkok" > /etc/TZ && \
    docker-php-ext-install sockets && \
    sed -i "s/;decorate_workers_output = no/decorate_workers_output = no/g" ${fpm_conf} 

ADD . /var/www/html/
WORKDIR "/var/www/html"

RUN mv .env.example .env || true && \
    mv conf/supervisor-cron.conf /etc/supervisor/conf.d/supervisor-cron.conf || true && \
    { crontab -l ; echo "* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1"; } | crontab - || true && \
    cp -rf storage storage.default || true && \
    chown -Rf nginx.nginx /var/www/html || true && \
    chown -R 100:101 storage.default || true && \
    composer global require hirak/prestissimo && \
    composer install --working-dir=/var/www/html

EXPOSE 443 80

CMD ["/start.sh"]
