#!/bin/bash

cp -Rfu storage.default/* storage
chown -R 100:101 storage

php artisan migrate
php artisan migrate
php artisan passport:keys
php artisan passport:clientifnotexist
php artisan db:seed --force
