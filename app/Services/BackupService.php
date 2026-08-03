<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;

/**
 * منطق بناء أرشيف النسخة الاحتياطية (تفريغ قاعدة البيانات + ضغطها مع ملفات
 * النزلاء اختيارياً) — مشترك بين أمر الرفع السحابي المجدوَل (hotel:backup)
 * وزر التنزيل المباشر من المتصفح (نقل بيانات النظام لخادم آخر).
 */
class BackupService
{
    public function dumpDatabase(string $workDir, string $timestamp): string
    {
        $connection = config('database.default');

        if ($connection === 'sqlite') {
            $source = config('database.connections.sqlite.database');
            // DB_DATABASE may be a relative path (e.g. "database/database.sqlite"),
            // which only resolves correctly against the app base path - not
            // whatever the current working directory happens to be at request time.
            if ($source && !str_starts_with($source, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:[\\\\\/]/', $source)) {
                $source = base_path($source);
            }
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

        $result = Process::timeout(600)->env($env)->run($command);
        if (!$result->successful()) {
            throw new \RuntimeException('فشل mysqldump: ' . $result->errorOutput());
        }

        file_put_contents($dest, $result->output());
        return $dest;
    }

    public function buildZip(string $zipPath, string $dbFile, bool $includeFiles): void
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

    public function cleanupDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') as $f) {
            @unlink($f);
        }
    }
}
