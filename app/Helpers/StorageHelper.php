<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class StorageHelper
{
    /**
     * اسم الـ disk المستخدم للملفات الخاصة (هويات، سندات...).
     * يُقرأ من .env — افتراضياً local، وعلى Production يُضبط على s3_private.
     */
    public static function privateDisk(): string
    {
        return config('filesystems.private_disk', 'private');
    }

    public static function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(self::privateDisk());
    }

    public static function store(\Illuminate\Http\UploadedFile $file, string $path): string
    {
        $stored = $file->store($path, self::privateDisk());
        if ($stored === false) {
            throw new \RuntimeException('فشل حفظ الملف على القرص: ' . self::privateDisk());
        }
        return $stored;
    }

    public static function exists(string $path): bool
    {
        return self::disk()->exists($path);
    }

    public static function path(string $path): string
    {
        return self::disk()->path($path);
    }

    public static function url(string $path): string
    {
        return self::disk()->url($path);
    }

    public static function response(string $filePath): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        $disk   = self::privateDisk();
        $driver = config("filesystems.disks.{$disk}.driver", 'local');

        if ($driver === 'local') {
            return response()->file(Storage::disk($disk)->path($filePath));
        }

        // S3-compatible: stream مؤقت
        $stream = Storage::disk($disk)->readStream($filePath);
        $mime   = Storage::disk($disk)->mimeType($filePath);
        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type'        => $mime ?? 'application/octet-stream',
            'Content-Disposition' => 'inline',
        ]);
    }
}
