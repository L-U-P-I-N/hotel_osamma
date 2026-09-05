#!/bin/sh
# ═══════════════════════════════════════════════════════════════════
# تهيئة النشر: تُنفَّذ تلقائياً عند كل رفع للاستضافة، فلا يحتاج المالك
# لفتح طرفية وتنفيذ أوامر يدوية.
#
# ملاحظة مهمة: نستخدم ProductionSeeder لا db:seed الكامل. البذرة الكاملة
# كانت تحذف الغرف والطوابق وتعيد إنشاءها وتضيف بيانات نزلاء وهمية في كل
# نشر — أي فقدان حالات الغرف وأسعارها وتلويث الأرقام المالية.
# ═══════════════════════════════════════════════════════════════════
set -e

cd /var/www/html

# قاعدة sqlite (تشغيل محلي/تجريبي): الملف داخل storage ليصمد عبر الـvolume
if [ "$DB_CONNECTION" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/storage/app/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_FILE")"
    touch "$DB_FILE"
    chown -R www-data:www-data "$(dirname "$DB_FILE")"
fi

echo "→ تشغيل ترحيلات قاعدة البيانات"
php artisan migrate --force

echo "→ تحديث البيانات المرجعية (أدوار، حسابات، شجرة حسابات، أنواع غرف)"
php artisan db:seed --class=ProductionSeeder --force

echo "→ ربط مجلد التخزين العام"
php artisan storage:link --force 2>/dev/null || true

# إعادة بناء ذاكرة الإعدادات والمسارات بعد أي تغيير في الكود
echo "→ تحديث ذاكرة الإعدادات والمسارات"
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "✔ التهيئة اكتملت — بدء الخدمات"
exec supervisord -c /etc/supervisord.conf
