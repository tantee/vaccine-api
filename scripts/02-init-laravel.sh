#!/bin/bash

cp -Rfu storage.default/* storage
chown -R nginx:nginx storage &

php artisan migrate
php artisan key:generate