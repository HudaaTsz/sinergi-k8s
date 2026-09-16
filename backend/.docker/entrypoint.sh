#!/bin/sh

# 1. Jalankan migrasi database ke Supabase
echo "Menjalankan migrasi database..."
php artisan migrate --force

# 2. Jalankan seeder otomatis untuk mengisi data awal
echo "Menjalankan seeder otomatis..."
php artisan db:seed --force

# 3. Jalankan perintah utama Supervisor untuk menyalakan servera
echo "Menyalakan server web..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
