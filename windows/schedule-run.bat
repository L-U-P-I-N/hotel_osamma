@echo off
REM يُستدعى من Windows Task Scheduler كل دقيقة — يشغّل مهام الجدولة المستحقة
REM فقط (النسخ الاحتياطي اليومي/الأسبوعي وغيرها)، بديل schedule:work المستخدم
REM في نسخة Docker. عدّل المسار أدناه إذا نسخت المشروع لمكان غير هذا.
cd /d "C:\laragon\www\hotel_osamma"
php artisan schedule:run >> storage\logs\schedule.log 2>&1
