# تشغيل النظام محلياً على جهاز داخل الفندق

هذا الدليل يشغّل النظام كاملاً على جهاز مكتبي داخل شبكة الفندق: قاعدة بيانات
sqlite وصور الهويات على القرص المحلي (بلا أي اعتماد على الإنترنت للعمل
اليومي)، مع نسخة احتياطية سحابية تلقائية (يومية للبيانات + أسبوعية تشمل
الصور) تُرفع كل ما توفّر الإنترنت.

## 1. المتطلبات على الجهاز

- **Windows**: تثبيت [Docker Desktop](https://www.docker.com/products/docker-desktop).
- **Linux**: تثبيت `docker` و `docker compose` (الحزمة `docker-compose-plugin`).
- اتصال الجهاز بشبكة الفندق (Wi-Fi أو كابل LAN) — هذا يكفي، بلا حاجة لإنترنت
  للعمل اليومي.

## 2. نسخ المشروع على الجهاز

انسخ مجلد المشروع كاملاً إلى الجهاز (عبر `git clone` أو نسخ الملفات)، وافتح
سطر أوامر (Terminal / PowerShell) داخل مجلد المشروع.

## 3. تجهيز ملف `.env`

```
copy .env.example .env      (على ويندوز)
cp .env.example .env        (على Linux)
```

ثم عدّل داخل `.env`:

- **`APP_URL`**: عنوان IP الجهاز على الشبكة المحلية + المنفذ، مثال:
  `APP_URL=http://192.168.1.10:8080`
  (لمعرفة IP الجهاز: `ipconfig` على ويندوز أو `ip addr` على Linux — ابحث عن
  عنوان يبدأ عادة بـ `192.168.` أو `10.`)
- **`DB_CONNECTION=sqlite`**
- **فكّ التعليق عن هذا السطر بالضبط** (لازم يكون مفعّلاً، وإلا تُفقد البيانات
  عند أي تحديث مستقبلي للنظام):
  `DB_DATABASE=/var/www/html/storage/app/database/database.sqlite`
- **`FILESYSTEM_DISK=local`** و **`PRIVATE_STORAGE_DISK=private`** (موجودة
  افتراضياً، تأكد فقط أنها لم تُغيَّر).
- **النسخ الاحتياطي السحابي** (اختياري لكن مُوصى به بشدة): اضبط `BACKUP_DISK`
  وبيانات `AWS_*` حسب مزوّد التخزين السحابي الذي تختاره (أي تخزين متوافق مع
  S3: AWS S3, Backblaze B2, Cloudflare R2...).

## 4. التشغيل الأول

```
docker compose up -d --build
```

هذا يبني الصورة، ينشئ ملف قاعدة البيانات، يشغّل الترحيلات (migrations)،
ويشغّل الخادم على المنفذ 8080.

بعدها، ولّد مفتاح التطبيق مرة واحدة فقط:

```
docker compose exec app php artisan key:generate
docker compose restart app
```

### تعبئة البيانات الأساسية (مرة واحدة)

```
docker compose exec app php artisan db:seed --class=HotelSeeder
docker compose exec app php artisan db:seed --class=RolesSeeder
docker compose exec app php artisan db:seed --class=UserSeeder
docker compose exec app php artisan db:seed --class=RoomTypeSeeder
docker compose exec app php artisan db:seed --class=RoomSeeder
docker compose exec app php artisan db:seed --class=FloorSeeder
```

**لا تشغّل** `TestDataSeeder` أو `HrExpenseSeeder` — هذان لبيانات تجريبية فقط.

هذا ينشئ مستخدم مدير افتراضي:
- اسم المستخدم: `admin`
- كلمة المرور: `Admin@1234`

**غيّر كلمة المرور هذه فوراً من داخل النظام بعد أول دخول.**

## 5. الدخول من أجهزة الموظفين

من أي جهاز/جوال متصل بنفس شبكة الفندق (Wi-Fi)، افتح:

```
http://<IP الجهاز>:8080
```

مثال: `http://192.168.1.10:8080`

## 6. التأكد أن الجهاز جاهز دائماً

- **عطّل وضع السكون (Sleep/Hibernate)** من إعدادات الطاقة في ويندوز — الجهاز
  يجب أن يبقى شغّالاً باستمرار.
- **فعّل تشغيل Docker Desktop تلقائياً عند إقلاع ويندوز** (خيار موجود في
  إعدادات Docker Desktop). الحاويات تُقلَع تلقائياً معه بفضل `restart:
  unless-stopped` في `docker-compose.yml`.

## 7. النسخ الاحتياطي السحابي

يعمل تلقائياً بلا تدخّل بالجدول التالي (داخل الحاوية، لا يحتاج Task
Scheduler خارجي):
- يومياً 3:00 صباحاً: نسخة للبيانات (قاعدة البيانات فقط).
- أسبوعياً الجمعة 3:30 صباحاً: نسخة كاملة تشمل صور الهويات.

إذا كان الإنترنت مقطوعاً وقت الرفع، يُسجَّل الفشل في السجلّ فقط ولا يتأثر
عمل النظام إطلاقاً — لا حاجة لأي إجراء يدوي، سيُعاد المحاولة في الموعد
التالي.

لاختبار النسخة الاحتياطية يدوياً في أي وقت:

```
docker compose exec app php artisan hotel:backup --files
```

## 8. التحديثات المستقبلية للنظام

```
git pull
docker compose up -d --build
```

بياناتك (قاعدة البيانات وصور الهويات) محفوظة في volume دائم منفصل عن صورة
التطبيق، فلا تُفقد أبداً عند التحديث أو إعادة البناء.

## 9. نسخة احتياطية إضافية محلية (اختياري، مُوصى به)

بما أن النسخة السحابية تعتمد على توفّر الإنترنت لحظة الرفع، يُفضَّل أيضاً نسخ
مجلد `storage` احتياطياً على قرص USB خارجي بين فترة وأخرى (مثلاً أسبوعياً)،
خصوصاً إذا كان انقطاع الإنترنت المتوقّع طويلاً:

```
docker compose cp app:/var/www/html/storage ./نسخة-احتياطية-محلية
```
