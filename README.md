# فندق أسامة — نظام إدارة الفندق (Hotel PMS)

نظام إدارة فندقي متكامل مبني بـ Laravel 11، يدعم اللغة العربية ويوفر إدارة شاملة للحجوزات والنزلاء والمدفوعات.

## المتطلبات
- PHP 8.3+, MySQL 8.0+, Node.js 18+, Composer 2+

## التثبيت المحلي

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# ضبط DB_* في .env ثم:
php artisan migrate --seed
npm run build
php artisan serve
```

## بيانات الدخول الافتراضية

| المستخدم | كلمة المرور | الدور |
|----------|------------|-------|
| admin | Admin@1234 | مدير النظام |
| receptionist | Admin@1234 | موظف استقبال |
| accountant | Admin@1234 | محاسب |

## الأدوار

| الدور | الصلاحيات |
|-------|-----------|
| admin | كامل الصلاحيات |
| receptionist | تسجيل دخول/خروج، إدارة الحجوزات |
| accountant | المدفوعات، التسوية النقدية، التقارير |
| maintenance | عرض وتغيير حالة الغرف |
| auditor | عرض فقط |

## سير عمل التحويل البنكي

عند اختيار تحويل بنكي: رفع سند التحويل (صورة/PDF) + رقم السند. يُخزَّن بشكل خاص في storage/app/private/bank_receipts/. الوصول محمي بصلاحية payments.bank_receipt ومسجَّل في سجل المراجعة.

## النشر على Railway

أضف متغيرات البيئة في Railway (DB_*, APP_KEY, APP_URL) ثم سيتم تنفيذ الترحيلات والبذور تلقائياً عبر railway.json.
