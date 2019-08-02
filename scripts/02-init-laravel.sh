#!/bin/bash

php artisan migrate
php artisan migrate
php artisan passport:key
php artisan passport:clientifnotexist
php artisan db:seed --force
