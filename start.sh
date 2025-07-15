#!/bin/bash

# Jalankan migrate otomatis
php artisan migrate --force

# Jalankan Laravel
php artisan serve --host=0.0.0.0 --port=8080
