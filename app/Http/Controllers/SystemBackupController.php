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
}
