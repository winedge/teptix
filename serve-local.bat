@echo off
cd /d %~dp0
php -d upload_max_filesize=100M -d post_max_size=100M artisan serve --host=127.0.0.1 --port=8010
