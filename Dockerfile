FROM richarvey/nginx-php-fpm:latest

ENV APP_ENV=DEV
ENV DB_CONNECTION mysql
ENV DB_HOST mysql
ENV DB_PORT 3306
ENV DB_DATABASE homestead
ENV DB_USERNAME homestead
ENV DB_PASSWORD secret
ENV RUN_SCRIPTS 1
ENV SKIP_COMPOSER 1

RUN echo "Asia/Bangkok" > /etc/TZ && \
    apk add --no-cache openldap-dev && \
    docker-php-ext-install iconv ldap sockets && \
    sed -i "s/;decorate_workers_output = no/decorate_workers_output = no/g" ${fpm_conf} && \
    composer global require hirak/prestissimo

ADD . /var/www/html/
WORKDIR "/var/www/html"

RUN mv .env.example .env || true && \
    composer install --no-dev --working-dir=/var/www/html
    
EXPOSE 443 80

CMD ["/start.sh"]
