#!/bin/sh
set -e

# تشغيل محلي بقاعدة sqlite: الملف يُنشأ داخل storage/app بدل database/ الافتراضي
# حتى يبقى محفوظاً عبر الـ volume الدائم (storage/) عند إعادة إنشاء الحاوية،
# ولا يتأثر بها مجلد database/migrations (كود التطبيق) الذي يبقى داخل الصورة.
if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/storage/app/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    chown -R www-data:www-data "$(dirname "$DB_FILE")"
fi

php artisan migrate --force
php artisan storage:link --force 2>/dev/null || true

exec supervisord -c /etc/supervisord.conf
