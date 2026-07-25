<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

/**
 * نسخ احتياطي لقاعدة البيانات (وملفات النزلاء/السندات اختيارياً) ورفعه إلى
 * تخزين سحابي. مصمَّم للعمل على خادم محلي داخل شبكة الفندق يشتغل بلا إنترنت
 * يومياً — الإنترنت يُستخدم فقط لحظة رفع النسخة، فإن تعذّر الاتصال يُسجَّل
 * الفشل في السجلّ دون أي تأثير على عمل النظام (لا يوقف أي عملية تشغيلية).
 *
 * يدعم قاعدتي sqlite (نسخ الملف مباشرةً) و mysql/mariadb (mysqldump).
 */
class BackupToCloud extends Command
{
    protected $signature = 'hotel:backup
        {--files : تضمين ملفات النزلاء والسندات (هويات، صور أضرار، سندات تحويل) مع النسخة}
        {--disk= : اسم قرص التخزين السحابي (افتراضياً BACKUP_DISK من .env)}';

    protected $description = 'نسخ احتياطي لقاعدة البيانات (وملفات المرفقات اختيارياً) ورفعه إلى تخزين سحابي';

    public function handle(): int
    {
        $disk = $this->option('disk') ?: config('hotel_backup.disk');
        if (!$disk) {
            $this->error('لم يُحدَّد قرص التخزين السحابي — اضبط BACKUP_DISK في .env');
            return self::FAILURE;
        }

        $includeFiles = $this->option('files') || config('hotel_backup.include_files');
        $timestamp    = now()->format('Y-m-d_H-i-s');
        $workDir      = storage_path('app/backup-tmp');
        @mkdir($workDir, 0755, true);

        try {
            $dbFile = $this->dumpDatabase($workDir, $timestamp);

            $zipPath = $workDir . '/hotel-backup-' . $timestamp . '.zip';
            $this->buildZip($zipPath, $dbFile, $includeFiles);

            $remotePath = trim(config('hotel_backup.path', 'backups'), '/') . '/' . basename($zipPath);
            $stream = fopen($zipPath, 'r');
            Storage::disk($disk)->put($remotePath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            $sizeMb = round(filesize($zipPath) / 1024 / 1024, 2);
            $this->info("تم رفع النسخة الاحتياطية بنجاح: {$remotePath} ({$sizeMb} MB)");
            Log::info('تم رفع نسخة احتياطية بنجاح', ['disk' => $disk, 'path' => $remotePath, 'size_mb' => $sizeMb]);

            $this->pruneOldBackups($disk);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            // فشل النسخ الاحتياطي (مثال: لا يوجد إنترنت وقت الرفع) لا يجب أن يوقف
            // أي شيء في النظام — نسجّله فقط وننتظر المحاولة المجدولة التالية.
            $this->error('فشل النسخ الاحتياطي: ' . $e->getMessage());
            Log::error('فشل النسخ الاحتياطي', ['message' => $e->getMessage(), 'file' => $e->getFile() . ':' . $e->getLine()]);
            return self::FAILURE;
        } finally {
            $this->cleanupDir($workDir);
        }
    }

    private function dumpDatabase(string $workDir, string $timestamp): string
    {
        $connection = config('database.default');

        if ($connection === 'sqlite') {
            $source = config('database.connections.sqlite.database');
            if (!$source || !file_exists($source)) {
                throw new \RuntimeException('ملف قاعدة بيانات sqlite غير موجود: ' . $source);
            }
            $dest = $workDir . '/database-' . $timestamp . '.sqlite';
            copy($source, $dest);
            return $dest;
        }

        // mysql / mariadb: mysqldump عبر Process (يجب توفّر mysqldump على الخادم)
        $cfg  = config("database.connections.{$connection}");
        $dest = $workDir . '/database-' . $timestamp . '.sql';

        $command = [
            'mysqldump',
            '-h', $cfg['host'] ?? '127.0.0.1',
            '-P', (string) ($cfg['port'] ?? 3306),
            '-u', $cfg['username'] ?? 'root',
            '--single-transaction',
            '--no-tablespaces',
            $cfg['database'],
        ];

        $env = [];
        if (!empty($cfg['password'])) {
            $env['MYSQL_PWD'] = $cfg['password'];
        }

        $result = Process::env($env)->run($command);
        if (!$result->successful()) {
            throw new \RuntimeException('فشل mysqldump: ' . $result->errorOutput());
        }

        file_put_contents($dest, $result->output());
        return $dest;
    }

    private function buildZip(string $zipPath, string $dbFile, bool $includeFiles): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('تعذّر إنشاء ملف الأرشيف المضغوط');
        }

        $zip->addFile($dbFile, basename($dbFile));

        if ($includeFiles) {
            $filesRoot = storage_path('app/private');
            if (is_dir($filesRoot)) {
                $this->addDirToZip($zip, $filesRoot, 'files');
            }
        }

        $zip->close();
    }

    private function addDirToZip(\ZipArchive $zip, string $dir, string $prefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $localName = $prefix . '/' . substr($file->getPathname(), strlen($dir) + 1);
                $zip->addFile($file->getPathname(), $localName);
            }
        }
    }

    /**
     * حذف النسخ الأقدم على التخزين السحابي بحيث لا يتبقّى أكثر من BACKUP_KEEP
     * نسخة (افتراضياً 30) — يمنع تراكم التخزين السحابي إلى ما لا نهاية.
     */
    private function pruneOldBackups(string $disk): void
    {
        $keep = (int) config('hotel_backup.keep', 30);
        $path = trim(config('hotel_backup.path', 'backups'), '/');

        $files = collect(Storage::disk($disk)->files($path))
            ->filter(fn($f) => str_ends_with($f, '.zip'))
            ->sortByDesc(fn($f) => Storage::disk($disk)->lastModified($f))
            ->values();

        if ($files->count() <= $keep) {
            return;
        }

        $toDelete = $files->slice($keep);
        foreach ($toDelete as $old) {
            Storage::disk($disk)->delete($old);
        }
        Log::info('حذف نسخ احتياطية قديمة', ['count' => $toDelete->count()]);
    }

    private function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') as $f) {
            @unlink($f);
        }
    }
}
