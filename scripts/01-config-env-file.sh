#!/bin/bash

sed -i 's/\(^APP_ENV=\).*/\1'$APP_ENV'/' .env
sed -i 's/\(^APP_DEBUG=\).*/\1'$APP_DEBUG'/' .env
sed -i 's/\(^DB_CONNECTION=\).*/\1'$DB_CONNECTION'/' .env
sed -i 's/\(^DB_HOST=\).*/\1'$DB_HOST'/' .env
sed -i 's/\(^DB_PORT=\).*/\1'$DB_PORT'/' .env
sed -i 's/\(^DB_DATABASE=\).*/\1'$DB_DATABASE'/' .env
sed -i 's/\(^DB_USERNAME=\).*/\1'$DB_USERNAME'/' .env
sed -i 's/\(^DB_PASSWORD=\).*/\1'$DB_PASSWORD'/' .env
