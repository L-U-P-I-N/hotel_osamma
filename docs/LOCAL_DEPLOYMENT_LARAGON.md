# تشغيل النظام محلياً على جهاز ويندوز بدون Docker (عبر Laragon)

بديل عن `docs/LOCAL_DEPLOYMENT.md` (نسخة Docker) لمن يفضّل تثبيت مباشر بدون
أي حاوية. النتيجة نفسها: قاعدة sqlite وصور الهويات على القرص المحلي، بلا أي
اعتماد على الإنترنت للعمل اليومي.

## 1. تثبيت Laragon

نزّل **Laragon Full** من [laragon.org](https://laragon.org/download/) وثبّته
(النسخة "Full" تجمع PHP + Apache + Composer + Node.js + Git في مثبّت واحد).

بعد التثبيت، تأكد من نسخة PHP المستخدمة (يمين الأيقونة في الشريط السفلي أو من
قائمة Laragon > PHP > Version) — يلزم **PHP 8.3** أو أحدث. إذا لم تكن متوفرة،
Laragon > PHP > Get More Versions لتنزيل 8.3.

من ملف `php.ini` (Laragon > PHP > php.ini) تأكد أن هذه الامتدادات مفعّلة (لا
يوجد `;` أمامها): `pdo_sqlite`, `sqlite3`, `gd`, `intl`, `exif`, `bcmath`,
`mbstring`, `zip`, `fileinfo`. ثم أعد تشغيل Laragon.

## 2. نسخ المشروع

انسخ مجلد المشروع إلى:
```
C:\laragon\www\hotel_osamma
```

## 3. التثبيت (من Laragon Terminal)

اضغط على "Terminal" داخل Laragon (يفتح سطر أوامر مهيّأ بمسارات PHP/Composer
تلقائياً)، وتوجّه لمجلد المشروع:

```
cd C:\laragon\www\hotel_osamma
composer install --no-dev --optimize-autoloader
npm install
npm run build
copy .env.example .env
php artisan key:generate
```

## 4. تجهيز `.env`

عدّل داخل `.env`:

- **`APP_URL`**: عنوان IP الجهاز على شبكة الفندق + المنفذ 80، مثال:
  `APP_URL=http://192.168.1.10`
  (لمعرفة IP الجهاز: افتح `cmd` واكتب `ipconfig`، ابحث عن عنوان يبدأ عادة
  بـ `192.168.` أو `10.`)
- **`DB_CONNECTION=sqlite`**
- **لا حاجة لتعديل `DB_DATABASE`** هنا (اتركه كما هو/معلَّقاً) — بخلاف نسخة
  Docker، التثبيت المباشر لا يعيد بناء أي "صورة" تمحو الملفات، فالمسار
  الافتراضي (`database/database.sqlite`) يبقى محفوظاً على القرص دائماً.
- **`FILESYSTEM_DISK=local`** و **`PRIVATE_STORAGE_DISK=private`** (تأكد
  أنها لم تُغيَّر).
- **النسخ الاحتياطي السحابي** (موصى به بشدة): اضبط `BACKUP_DISK` وبيانات
  `AWS_*` حسب مزوّد التخزين السحابي (AWS S3, Backblaze B2, Cloudflare R2...).

## 5. إنشاء قاعدة البيانات والجداول

```
type nul > database\database.sqlite
php artisan migrate --force
php artisan storage:link
```

### تعبئة البيانات الأساسية (مرة واحدة)

```
php artisan db:seed --class=HotelSeeder
php artisan db:seed --class=RolesSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=RoomTypeSeeder
php artisan db:seed --class=RoomSeeder
php artisan db:seed --class=FloorSeeder
```

**لا تشغّل** `TestDataSeeder` أو `HrExpenseSeeder` — هذان لبيانات تجريبية فقط.

هذا ينشئ مستخدم مدير افتراضي: اسم المستخدم `admin`، كلمة المرور
`Admin@1234`. **غيّرها فوراً من داخل النظام بعد أول دخول.**

## 6. ربط الموقع بـ Apache على المنفذ 80

حتى يفتح موظفو الفندق النظام مباشرة بعنوان IP الجهاز بلا اسم نطاق خاص
(`.test`)، اجعل مشروع الفندق هو الموقع الافتراضي لـ Apache:

1. من قائمة Laragon (زر يمين على الأيقونة) → **Apache → httpd.conf**.
2. ابحث عن السطر `DocumentRoot` وغيّره إلى:
   `DocumentRoot "C:/laragon/www/hotel_osamma/public"`
3. وغيّر `<Directory ...>` الموافق له لنفس المسار.
4. احفظ، ثم من Laragon اضغط **Stop All** ثم **Start All** لإعادة تشغيل
   Apache.
5. من **إعدادات جدار حماية ويندوز (Windows Defender Firewall)**: اسمح
   لمنفذ 80 الوارد (Inbound Rule) حتى تقدر بقية الأجهزة بالشبكة الوصول
   للنظام.

## 7. الدخول من أجهزة الموظفين

من أي جهاز/جوال متصل بنفس شبكة الفندق (Wi-Fi):

```
http://<IP الجهاز>
```

مثال: `http://192.168.1.10`

## 8. جدولة النسخ الاحتياطي عبر Windows Task Scheduler

بخلاف Docker (فيه جدولة داخلية جاهزة)، هنا تحتاج تسجّل مهمة في **جدولة
مهام ويندوز (Task Scheduler)** تستدعي كل دقيقة الملف الجاهز
`windows\schedule-run.bat` من هذا المشروع (يشغّل مهام الجدولة المستحقة فقط،
بما فيها `hotel:backup` اليومي/الأسبوعي):

1. افتح **جدولة المهام (Task Scheduler)** من قائمة ابدأ.
2. **إنشاء مهمة أساسية (Create Basic Task)** → اسمها مثلاً "جدولة نظام
   الفندق".
3. المشغّل (Trigger): **يومياً**، ثم كرّرها كل **1 دقيقة** لمدة يوم كامل
   (خيار "Repeat task every" في صفحة الإعدادات المتقدمة بعد الإنشاء).
4. الإجراء (Action): **بدء برنامج** → اختر الملف:
   `C:\laragon\www\hotel_osamma\windows\schedule-run.bat`
5. في خصائص المهمة النهائية، فعّل **"Run whether user is logged on or
   not"** حتى تعمل حتى لو خرج الموظف من حساب ويندوز، وفعّل **"Run task as
   soon as possible after a scheduled start is missed"**.

للتأكد أنها تعمل: بعد دقيقتين تحقق من وجود ملف `storage\logs\schedule.log`
بداخل مجلد المشروع.

## 9. التأكد أن الجهاز جاهز دائماً

- عطّل وضع السكون (Sleep/Hibernate) من إعدادات الطاقة في ويندوز.
- فعّل تشغيل Laragon تلقائياً عند إقلاع ويندوز: Laragon → Preferences →
  Start Laragon on Windows startup (وفعّل Auto Start Apache).

## 10. اختبار النسخة الاحتياطية يدوياً

```
cd C:\laragon\www\hotel_osamma
php artisan hotel:backup --files
```

## 11. التحديثات المستقبلية للنظام

```
cd C:\laragon\www\hotel_osamma
git pull
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
```

بياناتك (`database\database.sqlite` وصور الهويات في `storage\app\private`)
على القرص مباشرة ولا تتأثر بهذه الخطوات إطلاقاً.
