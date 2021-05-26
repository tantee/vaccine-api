#!/bin/bash

cp -Rfu storage.default/* storage
chown -R nginx:nginx storage &

until nc -z -v -w30 $DB_HOST $DB_PORT
do
  echo "Waiting a second until the database is receiving connections..."
  sleep 1
done

php artisan migrate
php artisan key:generate