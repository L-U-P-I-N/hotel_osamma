<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * تنزيل نسخة كاملة من النظام (قاعدة البيانات + صور الهويات اختيارياً)
 * مباشرة من المتصفح — لنقل بيانات النظام الحقيقية لخادم/جهاز آخر (مثلاً
 * من الاستضافة السحابية إلى خادم محلي داخل الفندق) دون الحاجة لصلاحية
 * وصول للخادم (SSH). مقصور على المدير فقط لحساسية البيانات (هويات النزلاء).
 */
class SystemBackupController extends Controller
{
    public function download(Request $request, BackupService $backup)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'هذا الإجراء مقصور على المدير.');
        }

        $includeFiles = $request->boolean('files', true);
        $timestamp    = now()->format('Y-m-d_H-i-s');
        $workDir      = storage_path('app/backup-tmp');
        @mkdir($workDir, 0755, true);

        set_time_limit(600);

        try {
            $dbFile  = $backup->dumpDatabase($workDir, $timestamp);
            $zipPath = $workDir . '/hotel-backup-' . $timestamp . '.zip';
            $backup->buildZip($zipPath, $dbFile, $includeFiles);
            @unlink($dbFile);
        } catch (\Throwable $e) {
            // نظّف أي ملفات جزئية عند الفشل فقط — النجاح يحتاج إبقاء ملف
            // الأرشيف حتى يُرسَل للمتصفح ويُحذَف تلقائياً بعدها (أدناه).
            $backup->cleanupDir($workDir);
            throw $e;
        }

        Log::info('تنزيل نسخة كاملة من النظام من المتصفح', [
            'user_id' => auth()->id(),
            'include_files' => $includeFiles,
        ]);

        return response()->download($zipPath, 'hotel-backup-' . $timestamp . '.zip')
            ->deleteFileAfterSend(true);
    }

    /**
     * نفس تنزيل النسخة الكاملة أعلاه، لكن بدون جلسة تسجيل دخول — مخصّص
     * للاستدعاء الآلي من سكربت التثبيت على جهاز الفندق (لسحب أحدث نسخة
     * وقت التحويل مباشرة بدل الضغط اليدوي على الزر). محمي برمز سري يُقارَن
     * بمتغير بيئة BACKUP_API_TOKEN على الاستضافة فقط — لا تُخزَّن أي قيمة
     * حقيقية لهذا الرمز داخل المستودع (عام على GitHub).
     */
    public function apiDownload(Request $request, BackupService $backup)
    {
        $configuredToken = config('services.backup_api.token');

        // Fail closed: if no token is configured on this deployment, refuse
        // every request rather than silently allowing/matching empty values.
        if (!$configuredToken || !hash_equals($configuredToken, (string) $request->bearerToken())) {
            abort(403, 'رمز الوصول غير صحيح أو غير مُفعَّل.');
        }

        $includeFiles = $request->boolean('files', true);
        $timestamp    = now()->format('Y-m-d_H-i-s');
        $workDir      = storage_path('app/backup-tmp');
        @mkdir($workDir, 0755, true);

        set_time_limit(600);

        try {
            $dbFile  = $backup->dumpDatabase($workDir, $timestamp);
            $zipPath = $workDir . '/hotel-backup-' . $timestamp . '.zip';
            $backup->buildZip($zipPath, $dbFile, $includeFiles);
            @unlink($dbFile);
        } catch (\Throwable $e) {
            $backup->cleanupDir($workDir);
            throw $e;
        }

        Log::info('تنزيل نسخة كاملة من النظام عبر API آلي', [
            'include_files' => $includeFiles,
            'ip' => $request->ip(),
        ]);

        return response()->download($zipPath, 'hotel-backup-' . $timestamp . '.zip')
            ->deleteFileAfterSend(true);
    }
}
