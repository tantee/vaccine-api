#!/bin/bash

php artisan migrate
php artisan passport:key
php artisan passport:personalifnotexist
